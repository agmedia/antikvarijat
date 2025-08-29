<?php

namespace App\Exports;

use App\Models\Back\Catalog\Product\Product; // prilagodi ako ti je namespace drukčiji
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsZeroQuantityExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function query()
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'quantity'])
            ->where('quantity', 0); // ako želiš i negativne, zamijeni s ->where('quantity', '<=', 0)
    }

    public function headings(): array
    {
        return ['ID', 'Naziv', 'Šifra', 'Količina'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            '' // prazno umjesto 0
        ];
    }
}
