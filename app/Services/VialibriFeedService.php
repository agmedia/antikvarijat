<?php

namespace App\Services;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Marketing\VialibriBook;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class VialibriFeedService
{
    /**
     * Return all selected books that are currently saleable.
     */
    public function getExportableBooks(): Collection
    {
        $books = VialibriBook::query()
            ->with([
                'product.author',
                'product.publisher',
                'product.categories',
                'product.images',
            ])
            ->get()
            ->filter(function (VialibriBook $book) {
                return $book->product && $this->isSaleable($book->product);
            });

        return $books->sort(function (VialibriBook $left, VialibriBook $right) {
            $rightTime = $this->resolveBookUpdatedAt($right)->getTimestamp();
            $leftTime = $this->resolveBookUpdatedAt($left)->getTimestamp();

            if ($rightTime === $leftTime) {
                return $right->product_id <=> $left->product_id;
            }

            return $rightTime <=> $leftTime;
        })->values();
    }

    /**
     * @return array
     */
    public function buildSyncPayload(): array
    {
        $books = $this->getExportableBooks();
        $latest = $books->isNotEmpty()
            ? $books->map(function (VialibriBook $book) {
                return $this->resolveBookUpdatedAt($book);
            })->sortByDesc(function (CarbonInterface $date) {
                return $date->getTimestamp();
            })->first()
            : now();

        return [
            'date_update' => $latest->format('Y-m-d H:i:s'),
            'ids' => $books->pluck('product_id')->map(function ($id) {
                return (string) $id;
            })->all(),
        ];
    }

    /**
     * @return array
     */
    public function buildDataPayload(): array
    {
        return [
            'books' => $this->getExportableBooks()->map(function (VialibriBook $book) {
                return $this->mapBook($book);
            })->all(),
        ];
    }

    /**
     * Build the plain-text source description used both for translation and fallback export.
     */
    public function buildSourceDescription(Product $product): string
    {
        $chunks = [];
        $baseDescription = $this->plainText($product->description);

        if ($baseDescription !== '') {
            $chunks[] = $baseDescription;
        }

        $details = [];

        if ($product->publisher && $product->publisher->title) {
            $details[] = 'Nakladnik: ' . $product->publisher->title;
        }

        if ($product->origin) {
            $details[] = 'Mjesto izdavanja: ' . $product->origin;
        }

        if ($product->year) {
            $details[] = 'Godina izdanja: ' . $product->year;
        }

        if ($product->pages) {
            $details[] = 'Broj stranica: ' . $product->pages;
        }

        if ($product->dimensions) {
            $details[] = 'Dimenzije: ' . $product->dimensions;
        }

        if ($product->binding) {
            $details[] = 'Uvez: ' . $product->binding;
        }

        if ($product->condition) {
            $details[] = 'Stanje: ' . $product->condition;
        }

        if (! empty($product->tags)) {
            $details[] = 'Ključne riječi: ' . implode(', ', (array) $product->tags);
        }

        if (! empty($details)) {
            $chunks[] = implode("\n", $details);
        }

        return trim(implode("\n\n", array_filter($chunks)));
    }

    /**
     * @return CarbonInterface
     */
    public function resolveBookUpdatedAt(VialibriBook $book)
    {
        $imageUpdatedAt = optional(
            collect(optional($book->product)->images)->sortByDesc('updated_at')->first()
        )->updated_at;

        $dates = collect([
            $book->updated_at,
            optional($book->product)->updated_at,
            optional(optional($book->product)->author)->updated_at,
            optional(optional($book->product)->publisher)->updated_at,
            $imageUpdatedAt,
        ])->filter();

        if ($dates->isEmpty()) {
            return now();
        }

        return $dates->map(function ($date) {
            return $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        })->sortByDesc(function (CarbonInterface $date) {
            return $date->getTimestamp();
        })->first();
    }

    /**
     * @return array
     */
    private function mapBook(VialibriBook $book): array
    {
        $product = $book->product;

        return [
            'date_update' => $this->resolveBookUpdatedAt($book)->format('Y-m-d H:i:s'),
            'author' => $this->plainText(optional($product->author)->title),
            'title' => $this->plainText($book->translated_title ?: $product->name),
            'description' => $this->plainText($book->translated_description ?: $this->buildSourceDescription($product)),
            'source_id' => (string) $product->id,
            'sku_dealer_item_id' => $this->plainText($product->sku ?: (string) $product->id),
            'year' => $this->plainText((string) $product->year),
            'edition' => $this->plainText((string) $book->edition),
            'publisher' => $this->plainText(optional($product->publisher)->title),
            'price' => number_format((float) $this->resolvePrice($product), 2, '.', ''),
            'keywords' => $this->resolveKeywords($book, $product),
            'isbn' => $this->plainText((string) ($product->getAttribute('isbn') ?: $product->getAttribute('ean'))),
            'first_edition' => $this->formatNullableBoolean($book->first_edition),
            'signed' => $this->formatNullableBoolean($book->signed),
            'dust_jacket' => $this->formatNullableBoolean($book->dust_jacket),
            'item_url' => url($product->url),
            'image_urls' => $this->resolveImageUrls($product),
        ];
    }

    /**
     * @return bool
     */
    private function isSaleable(Product $product): bool
    {
        return (bool) $product->status && (int) $product->quantity > 0 && (float) $product->price > 0;
    }

    /**
     * @return float
     */
    private function resolvePrice(Product $product)
    {
        $special = $product->special();

        return $special !== false && $special !== null ? $special : $product->price;
    }

    /**
     * @return string
     */
    private function resolveKeywords(VialibriBook $book, Product $product): string
    {
        if ($this->plainText((string) $book->keywords) !== '') {
            return $this->plainText((string) $book->keywords);
        }

        $keywords = collect((array) $product->tags)
            ->merge($product->categories->pluck('title'))
            ->push(optional($product->subcategory())->title)
            ->filter()
            ->map(function ($value) {
                return $this->plainText((string) $value);
            })
            ->filter()
            ->unique()
            ->values();

        return $keywords->implode(', ');
    }

    /**
     * @return array
     */
    private function resolveImageUrls(Product $product): array
    {
        $images = collect([$product->image])
            ->merge($product->images->filter(function ($image) {
                return ! isset($image->published) || (int) $image->published === 1;
            })->pluck('image'))
            ->filter()
            ->map(function ($path) {
                return $this->absoluteUrl((string) $path);
            })
            ->unique()
            ->values();

        return $images->all();
    }

    /**
     * @return string
     */
    private function absoluteUrl(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return rtrim((string) config('settings.images_domain'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return string
     */
    public function plainText(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value);
        $value = preg_replace('/<\/p>/i', "\n\n", $value);
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value);

        return trim((string) $value);
    }

    /**
     * @return string|null
     */
    private function formatNullableBoolean($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value ? 'yes' : 'no';
    }
}
