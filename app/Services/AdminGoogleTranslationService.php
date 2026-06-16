<?php

namespace App\Services;

use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Publisher;
use App\Models\Back\Marketing\Blog;
use App\Models\Back\Settings\Faq;
use App\Models\Back\Settings\Page;
use App\Models\Back\Settings\Settings;
use App\Models\Back\Widget\Widget;
use App\Models\Back\Widget\WidgetGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AdminGoogleTranslationService
{
    private const CACHE_PREFIX = 'admin_google_translation_job:';
    private const CACHE_TTL_MINUTES = 720;
    private const DEFAULT_BATCH_SIZE = 5;
    private const MAX_BATCH_SIZE = 25;
    private const MAX_MESSAGES = 20;

    public function targetsForView(): array
    {
        $targets = [];

        foreach ($this->definitions() as $key => $definition) {
            $fields = [];

            foreach ($definition['fields'] as $fieldKey => $field) {
                $fields[$fieldKey] = [
                    'key' => $key . '.' . $fieldKey,
                    'label' => $field['label'],
                    'default' => in_array($fieldKey, $definition['default'] ?? [], true),
                    'available' => $this->fieldIsAvailable($definition, $field),
                ];
            }

            $targets[$key] = [
                'label' => $definition['label'],
                'description' => $definition['description'] ?? null,
                'fields' => $fields,
            ];
        }

        return $targets;
    }

    public function start(array $selected, bool $overwrite = false, int $batchSize = self::DEFAULT_BATCH_SIZE, int $limit = 0): array
    {
        $selections = $this->normalizeSelections($selected);

        if (empty($selections)) {
            return [
                'ok' => false,
                'message' => 'Odaberite barem jedno polje za prijevod.',
            ];
        }

        $batchSize = max(1, min($batchSize, self::MAX_BATCH_SIZE));
        $limit = max(0, $limit);
        $targets = [];
        $total = 0;

        foreach ($selections as $targetKey => $fieldKeys) {
            $definition = $this->definitions()[$targetKey];
            $targetTotal = $this->countTarget($targetKey, $fieldKeys, $overwrite);

            if ($limit > 0) {
                $targetTotal = min($targetTotal, max(0, $limit - $total));
            }

            $targets[] = [
                'key' => $targetKey,
                'label' => $definition['label'],
                'fields' => array_values($fieldKeys),
                'total' => $targetTotal,
                'processed' => 0,
                'translated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'last_id' => 0,
                'processed_refs' => [],
                'done' => $targetTotal === 0,
            ];

            $total += $targetTotal;

            if ($limit > 0 && $total >= $limit) {
                break;
            }
        }

        $job = [
            'id' => (string) Str::uuid(),
            'status' => $total > 0 ? 'running' : 'completed',
            'source' => 'hr',
            'target' => 'en',
            'overwrite' => $overwrite,
            'batch_size' => $batchSize,
            'total' => $total,
            'processed' => 0,
            'translated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'current_index' => 0,
            'messages' => [],
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'finished_at' => $total > 0 ? null : now()->toDateTimeString(),
            'targets' => $targets,
        ];

        $this->store($job);

        return [
            'ok' => true,
            'job' => $this->summarize($job),
        ];
    }

    public function status(string $jobId): ?array
    {
        $job = $this->get($jobId);

        return $job ? $this->summarize($job) : null;
    }

    public function cancel(string $jobId): ?array
    {
        $job = $this->get($jobId);

        if (! $job) {
            return null;
        }

        if ($job['status'] === 'running') {
            $job['status'] = 'cancelled';
            $job['finished_at'] = now()->toDateTimeString();
            $job['updated_at'] = now()->toDateTimeString();
            $this->store($job);
        }

        return $this->summarize($job);
    }

    public function process(string $jobId, GoogleTranslateService $translate): ?array
    {
        $job = $this->get($jobId);

        if (! $job) {
            return null;
        }

        if ($job['status'] !== 'running') {
            return $this->summarize($job);
        }

        $targetIndex = $this->nextTargetIndex($job);

        if ($targetIndex === null) {
            $job = $this->complete($job);
            $this->store($job);

            return $this->summarize($job);
        }

        $target = $job['targets'][$targetIndex];
        $definition = $this->definitions()[$target['key']];
        $remaining = max(0, (int) $target['total'] - (int) $target['processed']);
        $limit = min((int) $job['batch_size'], $remaining);

        if ($limit <= 0) {
            $job['targets'][$targetIndex]['done'] = true;
            $this->store($job);

            return $this->summarize($job);
        }

        if ($definition['type'] === 'model') {
            $job = $this->processModelTarget($job, $targetIndex, $translate, $limit);
        } else {
            $job = $this->processSettingsTarget($job, $targetIndex, $translate, $limit);
        }

        $job['updated_at'] = now()->toDateTimeString();

        if ($this->nextTargetIndex($job) === null) {
            $job = $this->complete($job);
        }

        $this->store($job);

        return $this->summarize($job);
    }

    private function processModelTarget(array $job, int $targetIndex, GoogleTranslateService $translate, int $limit): array
    {
        $target = $job['targets'][$targetIndex];
        $definition = $this->definitions()[$target['key']];
        $query = $this->buildModelNeedsQuery($target['key'], $target['fields'], (bool) $job['overwrite'])
            ->where($definition['id_column'] ?? 'id', '>', (int) $target['last_id'])
            ->orderBy($definition['id_column'] ?? 'id')
            ->limit($limit);

        $records = $query->get();

        if ($records->isEmpty()) {
            $job['targets'][$targetIndex]['done'] = true;

            return $job;
        }

        foreach ($records as $record) {
            $job['targets'][$targetIndex]['last_id'] = max((int) $job['targets'][$targetIndex]['last_id'], (int) $record->getKey());
            $result = $this->translateModelRecord($record, $definition, $target['fields'], (bool) $job['overwrite'], $translate);
            $job = $this->applyItemResult($job, $targetIndex, $result);
        }

        return $job;
    }

    private function processSettingsTarget(array $job, int $targetIndex, GoogleTranslateService $translate, int $limit): array
    {
        $target = $job['targets'][$targetIndex];
        $processedRefs = $target['processed_refs'] ?? [];
        $definition = $this->definitions()[$target['key']];
        $processed = 0;

        foreach ($this->settingsRows($definition) as $setting) {
            if ($processed >= $limit) {
                break;
            }

            $items = $this->decodeSettingItems($setting);
            $changed = false;

            foreach ($items as $index => $item) {
                if ($processed >= $limit) {
                    break;
                }

                $ref = $setting->id . ':' . $index;

                if (in_array($ref, $processedRefs, true)) {
                    continue;
                }

                if (! $this->settingItemNeedsTranslation($item, $definition, $target['fields'], (bool) $job['overwrite'])) {
                    $processedRefs[] = $ref;
                    $job = $this->applyItemResult($job, $targetIndex, [
                        'translated' => 0,
                        'skipped' => 1,
                        'errors' => 0,
                        'messages' => [],
                    ]);
                    $processed++;
                    continue;
                }

                $result = $this->translateSettingItem($items[$index], $definition, $target['fields'], (bool) $job['overwrite'], $translate);
                $processedRefs[] = $ref;
                $processed++;

                if (($result['changed'] ?? false) === true) {
                    $changed = true;
                }

                unset($result['changed']);
                $job = $this->applyItemResult($job, $targetIndex, $result);
            }

            if ($changed) {
                Settings::edit($setting->id, $setting->code, $setting->key, json_encode($items, JSON_UNESCAPED_UNICODE), true);
            }
        }

        $job['targets'][$targetIndex]['processed_refs'] = array_values(array_unique($processedRefs));

        if ($processed === 0) {
            $job['targets'][$targetIndex]['done'] = true;
        }

        return $job;
    }

    private function translateModelRecord(Model $record, array $definition, array $fieldKeys, bool $overwrite, GoogleTranslateService $translate): array
    {
        $updates = [];
        $result = [
            'translated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        foreach ($fieldKeys as $fieldKey) {
            $field = $definition['fields'][$fieldKey] ?? null;

            if (! $field || ! $this->fieldIsAvailable($definition, $field)) {
                $result['skipped']++;
                continue;
            }

            if (! $overwrite && $this->filled($record->getAttribute($field['target']))) {
                $result['skipped']++;
                continue;
            }

            $source = $this->modelSourceValue($record, $field);

            if ($source === '') {
                $result['skipped']++;
                continue;
            }

            $response = $translate->translateText($source, 'hr', 'en', $field['format'] ?? 'text');

            if (! $response['ok']) {
                $result['errors']++;
                $result['messages'][] = $definition['label'] . ' #' . $record->getKey() . ' / ' . $field['label'] . ': ' . $response['error'];
                continue;
            }

            $updates[$field['target']] = $this->limitValue((string) $response['text'], $field);
            $result['translated']++;
        }

        if (! empty($updates)) {
            if (Schema::hasColumn($definition['table'], 'updated_at')) {
                $updates['updated_at'] = now();
            }

            $record->newQuery()->whereKey($record->getKey())->update($updates);
        }

        if ($result['translated'] === 0 && $result['errors'] === 0) {
            $result['skipped'] = max(1, $result['skipped']);
        }

        return $result;
    }

    private function translateSettingItem(array &$item, array $definition, array $fieldKeys, bool $overwrite, GoogleTranslateService $translate): array
    {
        $result = [
            'translated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'messages' => [],
            'changed' => false,
        ];

        foreach ($fieldKeys as $fieldKey) {
            $field = $definition['fields'][$fieldKey] ?? null;

            if (! $field) {
                $result['skipped']++;
                continue;
            }

            if (! $overwrite && $this->filled(data_get($item, $field['target_path']))) {
                $result['skipped']++;
                continue;
            }

            $source = $this->settingSourceValue($item, $field);

            if ($source === '') {
                $result['skipped']++;
                continue;
            }

            $response = $translate->translateText($source, 'hr', 'en', $field['format'] ?? 'text');

            if (! $response['ok']) {
                $result['errors']++;
                $result['messages'][] = $definition['label'] . ' / ' . $field['label'] . ': ' . $response['error'];
                continue;
            }

            data_set($item, $field['target_path'], $this->limitValue((string) $response['text'], $field));
            $result['translated']++;
            $result['changed'] = true;
        }

        if ($result['translated'] === 0 && $result['errors'] === 0) {
            $result['skipped'] = max(1, $result['skipped']);
        }

        return $result;
    }

    private function countTarget(string $targetKey, array $fieldKeys, bool $overwrite): int
    {
        $definition = $this->definitions()[$targetKey];

        if ($definition['type'] === 'model') {
            return $this->buildModelNeedsQuery($targetKey, $fieldKeys, $overwrite)->count();
        }

        $count = 0;

        foreach ($this->settingsRows($definition) as $setting) {
            foreach ($this->decodeSettingItems($setting) as $item) {
                if ($this->settingItemNeedsTranslation($item, $definition, $fieldKeys, $overwrite)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function buildModelNeedsQuery(string $targetKey, array $fieldKeys, bool $overwrite): Builder
    {
        $definition = $this->definitions()[$targetKey];
        /** @var Model $model */
        $model = new $definition['model']();
        $query = $model->newQuery();
        $this->applyTargetFilter($query, $targetKey);

        return $query->where(function (Builder $outer) use ($definition, $fieldKeys, $overwrite) {
            foreach ($fieldKeys as $fieldKey) {
                $field = $definition['fields'][$fieldKey] ?? null;

                if (! $field || ! $this->fieldIsAvailable($definition, $field)) {
                    continue;
                }

                $outer->orWhere(function (Builder $inner) use ($field, $overwrite) {
                    $inner->where(function (Builder $sourceQuery) use ($field) {
                        foreach ($field['source'] as $sourceColumn) {
                            $sourceQuery->orWhere(function (Builder $sourceFilled) use ($sourceColumn) {
                                $sourceFilled->whereNotNull($sourceColumn)->where($sourceColumn, '!=', '');
                            });
                        }
                    });

                    if (! $overwrite) {
                        $inner->where(function (Builder $targetQuery) use ($field) {
                            $targetQuery->whereNull($field['target'])->orWhere($field['target'], '');
                        });
                    }
                });
            }
        });
    }

    private function settingItemNeedsTranslation(array $item, array $definition, array $fieldKeys, bool $overwrite): bool
    {
        foreach ($fieldKeys as $fieldKey) {
            $field = $definition['fields'][$fieldKey] ?? null;

            if (! $field) {
                continue;
            }

            if (! $overwrite && $this->filled(data_get($item, $field['target_path']))) {
                continue;
            }

            if ($this->settingSourceValue($item, $field) !== '') {
                return true;
            }
        }

        return false;
    }

    private function modelSourceValue(Model $record, array $field): string
    {
        foreach ($field['source'] as $sourceColumn) {
            $value = (string) ($record->getAttribute($sourceColumn) ?? '');
            $value = $this->normalizeSource($value, (bool) ($field['strip_source_html'] ?? false));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function settingSourceValue(array $item, array $field): string
    {
        foreach ($field['source_path'] as $sourcePath) {
            $value = (string) (data_get($item, $sourcePath) ?? '');
            $value = $this->normalizeSource($value, (bool) ($field['strip_source_html'] ?? false));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function normalizeSource(string $value, bool $stripHtml): string
    {
        $value = trim($value);

        if ($stripHtml) {
            $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = trim((string) preg_replace('/\s+/', ' ', $value));
        }

        return $value;
    }

    private function limitValue(string $value, array $field): string
    {
        $value = trim($value);
        $max = (int) ($field['max'] ?? 0);

        if ($max > 0 && mb_strlen($value) > $max) {
            return mb_substr($value, 0, $max);
        }

        return $value;
    }

    private function applyItemResult(array $job, int $targetIndex, array $result): array
    {
        $job['targets'][$targetIndex]['processed']++;
        $job['targets'][$targetIndex]['translated'] += (int) ($result['translated'] ?? 0);
        $job['targets'][$targetIndex]['skipped'] += (int) ($result['skipped'] ?? 0);
        $job['targets'][$targetIndex]['errors'] += (int) ($result['errors'] ?? 0);

        $job['processed']++;
        $job['translated'] += (int) ($result['translated'] ?? 0);
        $job['skipped'] += (int) ($result['skipped'] ?? 0);
        $job['errors'] += (int) ($result['errors'] ?? 0);

        foreach (($result['messages'] ?? []) as $message) {
            $job['messages'][] = $message;
        }

        if (count($job['messages']) > self::MAX_MESSAGES) {
            $job['messages'] = array_slice($job['messages'], -self::MAX_MESSAGES);
        }

        if ($job['targets'][$targetIndex]['processed'] >= $job['targets'][$targetIndex]['total']) {
            $job['targets'][$targetIndex]['done'] = true;
        }

        return $job;
    }

    private function nextTargetIndex(array $job): ?int
    {
        foreach ($job['targets'] as $index => $target) {
            if (! ($target['done'] ?? false)) {
                return $index;
            }
        }

        return null;
    }

    private function complete(array $job): array
    {
        $job['status'] = $job['errors'] > 0 ? 'completed_with_errors' : 'completed';
        $job['finished_at'] = now()->toDateTimeString();
        $job['updated_at'] = now()->toDateTimeString();

        return $job;
    }

    private function summarize(array $job): array
    {
        $percent = $job['total'] > 0 ? round(($job['processed'] / $job['total']) * 100, 1) : 100;

        return [
            'id' => $job['id'],
            'status' => $job['status'],
            'source' => $job['source'],
            'target' => $job['target'],
            'overwrite' => $job['overwrite'],
            'total' => $job['total'],
            'processed' => $job['processed'],
            'translated' => $job['translated'],
            'skipped' => $job['skipped'],
            'errors' => $job['errors'],
            'percent' => $percent,
            'messages' => $job['messages'],
            'created_at' => $job['created_at'],
            'updated_at' => $job['updated_at'],
            'finished_at' => $job['finished_at'],
            'targets' => array_map(static function (array $target) {
                $percent = $target['total'] > 0 ? round(($target['processed'] / $target['total']) * 100, 1) : 100;

                return [
                    'key' => $target['key'],
                    'label' => $target['label'],
                    'total' => $target['total'],
                    'processed' => $target['processed'],
                    'translated' => $target['translated'],
                    'skipped' => $target['skipped'],
                    'errors' => $target['errors'],
                    'done' => $target['done'],
                    'percent' => $percent,
                ];
            }, $job['targets']),
        ];
    }

    private function normalizeSelections(array $selected): array
    {
        $definitions = $this->definitions();
        $selections = [];

        foreach ($selected as $value) {
            $value = trim((string) $value);

            if (! str_contains($value, '.')) {
                continue;
            }

            [$targetKey, $fieldKey] = explode('.', $value, 2);

            if (! isset($definitions[$targetKey]['fields'][$fieldKey])) {
                continue;
            }

            $field = $definitions[$targetKey]['fields'][$fieldKey];

            if (! $this->fieldIsAvailable($definitions[$targetKey], $field)) {
                continue;
            }

            $selections[$targetKey][] = $fieldKey;
        }

        foreach ($selections as $targetKey => $fieldKeys) {
            $selections[$targetKey] = array_values(array_unique($fieldKeys));
        }

        return $selections;
    }

    private function fieldIsAvailable(array $definition, array $field): bool
    {
        if ($definition['type'] !== 'model') {
            return true;
        }

        if (! Schema::hasColumn($definition['table'], $field['target'])) {
            return false;
        }

        foreach ($field['source'] as $sourceColumn) {
            if (Schema::hasColumn($definition['table'], $sourceColumn)) {
                return true;
            }
        }

        return false;
    }

    private function applyTargetFilter(Builder $query, string $targetKey): void
    {
        if ($targetKey === 'pages') {
            $query->where('group', 'page');
        }

        if ($targetKey === 'blogs') {
            $query->where('group', 'blog');
        }
    }

    private function settingsRows(array $definition)
    {
        return Settings::query()
            ->where('code', $definition['code'])
            ->where('key', 'like', $definition['key'])
            ->orderBy('id')
            ->get();
    }

    private function decodeSettingItems(Settings $setting): array
    {
        try {
            $items = json_decode((string) $setting->value, true);

            if (! is_array($items)) {
                return [];
            }

            return array_values($items);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function filled($value): bool
    {
        return trim((string) $value) !== '';
    }

    private function get(string $jobId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $jobId);
    }

    private function store(array $job): void
    {
        Cache::put(self::CACHE_PREFIX . $job['id'], $job, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    private function definitions(): array
    {
        return [
            'products' => [
                'type' => 'model',
                'label' => 'Artikli',
                'description' => 'Naziv, opis i SEO polja artikala.',
                'model' => Product::class,
                'table' => 'products',
                'default' => ['description_en'],
                'fields' => [
                    'name_en' => $this->modelField('Naziv', ['name'], 'name_en', 'text', 191),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'meta_title_en' => $this->modelField('SEO title', ['meta_title', 'name'], 'meta_title_en', 'text', 70, true),
                    'meta_description_en' => $this->modelField('SEO opis', ['meta_description', 'description'], 'meta_description_en', 'text', 160, true),
                ],
            ],
            'categories' => [
                'type' => 'model',
                'label' => 'Kategorije',
                'description' => 'Naziv, opis i SEO polja kategorija.',
                'model' => Category::class,
                'table' => 'categories',
                'default' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naziv', ['title'], 'title_en', 'text', 191),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'meta_title_en' => $this->modelField('SEO title', ['meta_title', 'title'], 'meta_title_en', 'text', 70, true),
                    'meta_description_en' => $this->modelField('SEO opis', ['meta_description', 'description'], 'meta_description_en', 'text', 160, true),
                ],
            ],
            'authors' => [
                'type' => 'model',
                'label' => 'Autori',
                'description' => 'Naziv, opis i SEO polja autora.',
                'model' => Author::class,
                'table' => 'authors',
                'default' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naziv', ['title'], 'title_en', 'text', 191),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'meta_title_en' => $this->modelField('SEO title', ['meta_title', 'title'], 'meta_title_en', 'text', 70, true),
                    'meta_description_en' => $this->modelField('SEO opis', ['meta_description', 'description'], 'meta_description_en', 'text', 160, true),
                ],
            ],
            'publishers' => [
                'type' => 'model',
                'label' => 'Nakladnici',
                'description' => 'Naziv, opis i SEO polja nakladnika.',
                'model' => Publisher::class,
                'table' => 'publishers',
                'default' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naziv', ['title'], 'title_en', 'text', 191),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'meta_title_en' => $this->modelField('SEO title', ['meta_title', 'title'], 'meta_title_en', 'text', 70, true),
                    'meta_description_en' => $this->modelField('SEO opis', ['meta_description', 'description'], 'meta_description_en', 'text', 160, true),
                ],
            ],
            'pages' => [
                'type' => 'model',
                'label' => 'Info stranice',
                'description' => 'Naslov, opis i SEO polja info stranica.',
                'model' => Page::class,
                'table' => 'pages',
                'default' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naslov', ['title'], 'title_en', 'text', 191),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'meta_title_en' => $this->modelField('SEO title', ['meta_title', 'title'], 'meta_title_en', 'text', 70, true),
                    'meta_description_en' => $this->modelField('SEO opis', ['meta_description', 'description'], 'meta_description_en', 'text', 160, true),
                ],
            ],
            'blogs' => [
                'type' => 'model',
                'label' => 'Blog',
                'description' => 'Naslov, kratki opis, opis i SEO polja bloga.',
                'model' => Blog::class,
                'table' => 'pages',
                'default' => ['title_en', 'short_description_en', 'description_en', 'meta_title_en', 'meta_description_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naslov', ['title'], 'title_en', 'text', 191),
                    'short_description_en' => $this->modelField('Kratki opis', ['short_description'], 'short_description_en', 'text', 500, true),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'meta_title_en' => $this->modelField('SEO title', ['meta_title', 'title'], 'meta_title_en', 'text', 70, true),
                    'meta_description_en' => $this->modelField('SEO opis', ['meta_description', 'short_description', 'description'], 'meta_description_en', 'text', 160, true),
                ],
            ],
            'faq' => [
                'type' => 'model',
                'label' => 'FAQ',
                'description' => 'Pitanja i odgovori.',
                'model' => Faq::class,
                'table' => 'faq',
                'default' => ['title_en', 'description_en'],
                'fields' => [
                    'title_en' => $this->modelField('Pitanje', ['title'], 'title_en', 'text', 191),
                    'description_en' => $this->modelField('Odgovor', ['description'], 'description_en', 'html'),
                ],
            ],
            'widgets' => [
                'type' => 'model',
                'label' => 'Widgeti',
                'description' => 'Naslovi, podnaslovi, opisi i badge tekstovi widgeta.',
                'model' => Widget::class,
                'table' => 'widgets',
                'default' => ['title_en', 'subtitle_en', 'description_en', 'badge_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naslov', ['title'], 'title_en', 'text', 191),
                    'subtitle_en' => $this->modelField('Podnaslov', ['subtitle'], 'subtitle_en', 'text', 500, true),
                    'description_en' => $this->modelField('Opis', ['description'], 'description_en', 'html'),
                    'badge_en' => $this->modelField('Badge', ['badge'], 'badge_en', 'text', 191),
                ],
            ],
            'widget_groups' => [
                'type' => 'model',
                'label' => 'Widget grupe',
                'description' => 'Nazivi grupa widgeta.',
                'model' => WidgetGroup::class,
                'table' => 'widget_groups',
                'default' => ['title_en'],
                'fields' => [
                    'title_en' => $this->modelField('Naziv', ['title'], 'title_en', 'text', 191),
                ],
            ],
            'payments' => [
                'type' => 'settings',
                'label' => 'Načini plaćanja',
                'description' => 'Nazivi i opisi načina plaćanja.',
                'code' => 'payment',
                'key' => 'list.%',
                'default' => ['title_en', 'short_description_en', 'description_en'],
                'fields' => [
                    'title_en' => $this->settingField('Naziv', ['title'], 'title_en', 'text', 191),
                    'short_description_en' => $this->settingField('Kratki opis', ['data.short_description'], 'data.short_description_en', 'text', 500, true),
                    'description_en' => $this->settingField('Opis', ['data.description'], 'data.description_en', 'html'),
                ],
            ],
            'shippings' => [
                'type' => 'settings',
                'label' => 'Načini dostave',
                'description' => 'Nazivi, rokovi i opisi načina dostave.',
                'code' => 'shipping',
                'key' => 'list.%',
                'default' => ['title_en', 'time_en', 'short_description_en', 'description_en'],
                'fields' => [
                    'title_en' => $this->settingField('Naziv', ['title'], 'title_en', 'text', 191),
                    'time_en' => $this->settingField('Trajanje isporuke', ['data.time'], 'data.time_en', 'text', 191),
                    'short_description_en' => $this->settingField('Kratki opis', ['data.short_description'], 'data.short_description_en', 'text', 500, true),
                    'description_en' => $this->settingField('Opis', ['data.description'], 'data.description_en', 'html'),
                ],
            ],
            'order_statuses' => [
                'type' => 'settings',
                'label' => 'Statusi narudžbi',
                'description' => 'Nazivi statusa narudžbi.',
                'code' => 'order',
                'key' => 'statuses',
                'default' => ['title_en'],
                'fields' => [
                    'title_en' => $this->settingField('Naziv', ['title'], 'title_en', 'text', 191),
                ],
            ],
        ];
    }

    private function modelField(string $label, array $source, string $target, string $format = 'text', int $max = 0, bool $stripSourceHtml = false): array
    {
        return compact('label', 'source', 'target', 'format', 'max') + [
            'strip_source_html' => $stripSourceHtml,
        ];
    }

    private function settingField(string $label, array $sourcePath, string $targetPath, string $format = 'text', int $max = 0, bool $stripSourceHtml = false): array
    {
        return compact('label', 'sourcePath', 'targetPath', 'format', 'max') + [
            'source_path' => $sourcePath,
            'target_path' => $targetPath,
            'strip_source_html' => $stripSourceHtml,
        ];
    }
}
