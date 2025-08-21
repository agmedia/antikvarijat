<?php

namespace App\Exports;

use App\Models\Back\Orders\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Request $request;
    protected Order $order;

    public function __construct(Request $request, Order $order)
    {
        $this->request = $request;
        $this->order   = $order;
    }

    public function query()
    {
        // NEMA ->with(['status']) jer status nije relacija.
        // Zadržavamo products radi mapiranja naziva artikala.
        return $this->order
            ->filter($this->request)
            ->with(['products']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Datum',
            'Status',
            'Plaćanje',
            'Kupac',
            'Email',
            'Broj artikala',
            'Artikli (nazivi)',
            'Iznos',
            'Valuta',
        ];
    }

    public function map($order): array
    {
        // koristi postojeću metodu status($id) iz modela
        $status = $order->status($order->order_status_id);
        $statusTitle = $status->title ?? '';

        $currency = $order->id > 4627 ? 'EUR' : 'HRK';
        $productNames = $order->products->pluck('name')->join(', ');

        return [
            $order->id,
            optional($order->created_at)->format('d.m.Y H:i'),
            $statusTitle,
            $order->payment_method,
            trim($order->shipping_fname . ' ' . $order->shipping_lname),
            $order->payment_email,
            $order->products->count(),
            $productNames,
            (float) $order->total,
            $currency,
        ];
    }

}
