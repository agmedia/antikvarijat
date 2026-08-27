<?php

namespace App\Models\Back\Catalog\Concerns;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Normalizer;

trait FindsSemanticallyEquivalentTitles
{
    /**
     * The live author/publisher title columns are VARCHAR(191).
     */
    public static function semanticTitleMaxLength(): int
    {
        return 191;
    }

    /**
     * Clean a title for storage without removing meaningful diacritics.
     */
    public static function cleanSemanticTitle(string $title): string
    {
        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($title, Normalizer::FORM_C);

            if (is_string($normalized)) {
                $title = $normalized;
            }
        }

        $title = preg_replace('/[\p{Z}\s]+/u', ' ', $title) ?? $title;

        return trim($title);
    }

    /**
     * Build a Unicode-aware comparison key while preserving c/č/ć and other
     * semantically meaningful diacritics as different characters.
     */
    public static function normalizeSemanticTitle(string $title): string
    {
        return mb_convert_case(
            static::cleanSemanticTitle($title),
            MB_CASE_FOLD,
            'UTF-8'
        );
    }

    /**
     * Legacy duplicates remain editable when only non-title fields (or title
     * formatting/case) change.
     */
    public function semanticTitleDiffersFromOriginal(string $title): bool
    {
        if (! $this->exists) {
            return true;
        }

        return static::normalizeSemanticTitle($title)
            !== static::normalizeSemanticTitle((string) $this->getRawOriginal('title'));
    }

    /**
     * Return the oldest semantically equivalent record, if one already exists.
     * Existing duplicates are intentionally left untouched.
     */
    public static function findSemanticallyEquivalentTitle(string $title, $ignoredKey = null)
    {
        $normalizedTitle = static::normalizeSemanticTitle($title);

        if ($normalizedTitle === '') {
            return null;
        }

        $model = new static();
        $keyName = $model->getKeyName();
        $matchedId = null;
        $candidates = static::query()
            ->useWritePdo()
            ->select([$keyName, 'title'])
            ->orderBy($keyName)
            ->cursor();

        foreach ($candidates as $candidate) {
            if ($ignoredKey !== null && (string) $candidate->getKey() === (string) $ignoredKey) {
                continue;
            }

            if (static::normalizeSemanticTitle((string) $candidate->title) === $normalizedTitle) {
                $matchedId = $candidate->getKey();

                break;
            }
        }

        // Close the cursor before resolving the full model on the same connection.
        unset($candidates);

        return $matchedId === null
            ? null
            : static::query()->useWritePdo()->find($matchedId);
    }

    /**
     * Laravel validation rule for an explicit create/edit form. Picker and
     * import flows intentionally use findOrCreateBySemanticTitle() instead.
     */
    public static function uniqueSemanticTitleRule($ignoredKey, string $message): Closure
    {
        return function ($attribute, $value, $fail) use ($ignoredKey, $message): void {
            if (! is_string($value) || static::normalizeSemanticTitle($value) === '') {
                return;
            }

            if (static::findSemanticallyEquivalentTitle($value, $ignoredKey)) {
                $fail($message);
            }
        };
    }

    /**
     * Recheck uniqueness while holding the semantic-title lock so an explicit
     * CRUD write cannot race a picker, import, or another CRUD request.
     *
     * @throws ValidationException
     */
    public static function assertSemanticTitleIsAvailable(
        string $title,
        $ignoredKey,
        string $message
    ): void {
        if (static::findSemanticallyEquivalentTitle($title, $ignoredKey)) {
            throw ValidationException::withMessages(['title' => [$message]]);
        }
    }

    /**
     * Run a title-sensitive write under a database-server-wide MySQL advisory
     * lock. SQLite/tests (and other non-MySQL connections) keep the Laravel
     * cache-lock fallback.
     *
     * The 62-byte ASCII name stays below MySQL's 64-byte GET_LOCK limit.
     *
     * @return mixed
     *
     * @throws LockTimeoutException
     */
    public static function withSemanticTitleLock(string $title, callable $callback)
    {
        $model = new static();
        $connection = $model->getConnection();
        $normalizedTitle = static::normalizeSemanticTitle($title);
        $lockName = 'catalog-title:' . substr(
            hash('sha256', $model->getTable() . "\0" . $normalizedTitle),
            0,
            48
        );

        if ($connection->getDriverName() === 'mysql') {
            $acquired = $connection->selectOne(
                'SELECT GET_LOCK(?, ?) AS acquired',
                [$lockName, 10],
                false
            );

            if (! $acquired || (int) $acquired->acquired !== 1) {
                throw new LockTimeoutException('Isteklo je čekanje na zaključavanje naziva kataloga.');
            }

            try {
                return $callback();
            } finally {
                $connection->selectOne(
                    'SELECT RELEASE_LOCK(?) AS released',
                    [$lockName],
                    false
                );
            }
        }

        return Cache::lock($lockName, 30)->block(10, $callback);
    }

    /**
     * Reuse a semantically equal record or create a new one under the shared
     * semantic-title lock. No existing records are updated, merged, or deleted.
     */
    public static function findOrCreateBySemanticTitle(string $title, array $attributes)
    {
        $title = static::cleanSemanticTitle($title);

        if ($title === '') {
            throw new InvalidArgumentException('Naziv ne smije biti prazan.');
        }

        if (mb_strlen($title) > static::semanticTitleMaxLength()) {
            throw new InvalidArgumentException('Naziv ne smije biti dulji od 191 znaka.');
        }

        try {
            return static::withSemanticTitleLock($title, function () use ($title, $attributes) {
                $existing = static::findSemanticallyEquivalentTitle($title);

                if ($existing) {
                    return $existing;
                }

                $record = new static();
                $record->fill(array_merge($attributes, ['title' => $title]));
                $record->save();

                return $record;
            });
        } catch (LockTimeoutException $exception) {
            // A competing request may have completed just as our wait expired.
            $existing = static::findSemanticallyEquivalentTitle($title);

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }
}
