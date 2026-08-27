<?php

namespace App\Models\Back\Catalog;

use App\Models\Back\Catalog\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Normalizer;

class Translator extends Model
{
    use HasFactory;

    protected $table = 'translators';

    protected $guarded = ['id', 'normalized_title', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::saving(function (Translator $translator): void {
            $translator->title = static::cleanTitle((string) $translator->title);

            if ($translator->title === '') {
                throw new InvalidArgumentException('Naziv prevoditelja ne smije biti prazan.');
            }

            if (mb_strlen($translator->title) > 191) {
                throw new InvalidArgumentException('Naziv prevoditelja ne smije biti dulji od 191 znaka.');
            }

            $normalizedTitle = static::normalizeTitle($translator->title);

            if (mb_strlen($normalizedTitle) > 191) {
                throw new InvalidArgumentException('Naziv prevoditelja ne smije biti dulji od 191 znaka.');
            }

            $translator->normalized_title = $normalizedTitle;
        });

        static::deleting(function (Translator $translator): void {
            $translator->products()->detach();
        });
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_translator',
            'translator_id',
            'product_id'
        )->withPivot('sort_order')->withTimestamps();
    }

    public static function findOrCreateByTitle(string $title): self
    {
        $title = static::cleanTitle($title);

        if ($title === '') {
            throw new InvalidArgumentException('Naziv prevoditelja ne smije biti prazan.');
        }

        if (mb_strlen($title) > 191) {
            throw new InvalidArgumentException('Naziv prevoditelja ne smije biti dulji od 191 znaka.');
        }

        $normalizedTitle = static::normalizeTitle($title);

        if (mb_strlen($normalizedTitle) > 191) {
            throw new InvalidArgumentException('Naziv prevoditelja ne smije biti dulji od 191 znaka.');
        }

        try {
            return static::query()->useWritePdo()->firstOrCreate(
                ['normalized_title' => $normalizedTitle],
                ['title' => $title]
            );
        } catch (QueryException $exception) {
            // A concurrent request may have inserted the same normalized title.
            $translator = static::query()
                ->useWritePdo()
                ->where('normalized_title', $normalizedTitle)
                ->first();

            if ($translator) {
                return $translator;
            }

            throw $exception;
        }
    }

    public static function normalizeTitle(string $title): string
    {
        return mb_convert_case(static::cleanTitle($title), MB_CASE_FOLD, 'UTF-8');
    }

    private static function cleanTitle(string $title): string
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
}
