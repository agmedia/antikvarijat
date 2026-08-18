<?php

namespace App\Services\Shipping;

use App\Models\Back\Orders\Order;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BoxNowService
{
    public const CARRIER = 'boxnow';

    /** @var string|null */
    private $token;

    /** @var BoxNowSettingsService */
    private $settings;

    public function __construct(BoxNowSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function createDeliveryRequest(Order $order): array
    {
        $response = $this->authorizedRequest()
            ->asJson()
            ->post($this->url('/delivery-requests'), $this->deliveryPayload($order));

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Box Now pošiljka nije kreirana.'));
        }

        $payload = $response->json() ?: [];
        $parcelId = trim((string) data_get($payload, 'parcels.0.id', ''));

        if ($parcelId === '') {
            throw new RuntimeException('Box Now nije vratio ID pošiljke.');
        }

        return [
            'carrier' => self::CARRIER,
            'parcel_id' => $parcelId,
            'tracking_code' => $parcelId,
            'tracking_url' => $this->trackingUrl($parcelId),
            'status_code' => 'new',
            'status' => $this->statusLabel('new'),
            'tracked_at' => now(),
            'payload' => $payload,
        ];
    }

    public function track(Order $order): array
    {
        $parcelId = trim((string) ($order->shipping_parcel_id ?: $order->tracking_code));
        $query = ['limit' => 1];

        if ($parcelId !== '') {
            $query['parcelId'] = $parcelId;
        } else {
            $query['orderNumber'] = (string) $order->id;
        }

        $response = $this->authorizedRequest()
            ->acceptJson()
            ->get($this->url('/parcels'), $query);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Box Now status nije dohvaćen.'));
        }

        $payload = $response->json() ?: [];
        $parcel = $this->firstParcel($payload);
        $parcelId = trim((string) (data_get($parcel, 'id') ?: data_get($parcel, 'parcelId') ?: $parcelId));
        $state = $this->latestState($parcel);

        if ($parcelId === '') {
            throw new RuntimeException('Box Now nije pronašao pošiljku za ovu narudžbu.');
        }

        return [
            'carrier' => self::CARRIER,
            'parcel_id' => $parcelId,
            'tracking_code' => $parcelId,
            'tracking_url' => $this->trackingUrl($parcelId),
            'status_code' => $state !== '' ? $state : null,
            'status' => $state !== '' ? $this->statusLabel($state) : 'Box Now status nije dostupan.',
            'tracked_at' => now(),
            'payload' => $payload,
            'is_delivered' => $state === 'delivered',
        ];
    }

    public function trackingUrl(string $parcelId): ?string
    {
        $parcelId = trim($parcelId);
        $baseUrl = trim((string) config('services.boxnow.tracking_url'));

        if ($parcelId === '' || $baseUrl === '') {
            return null;
        }

        if (Str::contains($baseUrl, '{parcel}')) {
            return str_replace('{parcel}', urlencode($parcelId), $baseUrl);
        }

        if (Str::contains($baseUrl, 'track.boxnow.hr')) {
            $baseUrl = preg_replace('#/track/?$#', '', rtrim($baseUrl, '/')) ?: $baseUrl;

            if (preg_match('/([?&]track=)([^&]*)/', $baseUrl)) {
                return preg_replace('/([?&]track=)([^&]*)/', '$1' . urlencode($parcelId), $baseUrl);
            }

            return $baseUrl . (Str::contains($baseUrl, '?') ? '&' : '?') . 'track=' . urlencode($parcelId);
        }

        return rtrim($baseUrl, '/') . '/' . urlencode($parcelId);
    }

    public function statusLabel(?string $state): string
    {
        $state = trim((string) $state);

        return [
            'new' => 'Čeka se preuzimanje iz trgovine.',
            'in-depot' => 'Pošiljka se dostavlja.',
            'in-transit' => 'Pošiljka se dostavlja.',
            'final-destination' => 'Pošiljka se nalazi u odabranom paketomatu.',
            'in-final-destination' => 'Pošiljka se nalazi u odabranom paketomatu.',
            'delivered' => 'Pošiljka je preuzeta.',
            'returned' => 'Pošiljka je vraćena pošiljatelju.',
            'expired' => 'Isteklo je vrijeme preuzimanja i pošiljka se vraća pošiljatelju.',
            'expired-return' => 'Isteklo je vrijeme preuzimanja i pošiljka se vraća pošiljatelju.',
            'canceled' => 'Pošiljka je otkazana.',
            'cancelled' => 'Pošiljka je otkazana.',
            'lost' => 'Pošiljka se pronalazi.',
            'missing' => 'Pošiljka se pronalazi.',
            'accepted-to-locker' => 'Pošiljka je u procesu dostave.',
            'accepted-for-return' => 'Pošiljka je u procesu povrata.',
            'wait-for-load' => 'Pošiljka čeka preuzimanje iz paketomata.',
        ][$state] ?? ($state !== '' ? 'Box Now status: ' . $state : 'Box Now status nije dostupan.');
    }

    public static function terminalStatusCodes(): array
    {
        return ['delivered', 'returned', 'expired', 'expired-return', 'canceled', 'cancelled', 'lost'];
    }

    private function authorizedRequest(): PendingRequest
    {
        $request = Http::withToken($this->accessToken())
            ->timeout(25);
        $partnerId = $this->settings->get()['api_partner_id'];

        if ($partnerId !== '') {
            $request->withHeaders(['X-PartnerID' => $partnerId]);
        }

        return $request;
    }

    private function accessToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $this->assertConfigured();

        $response = Http::asJson()
            ->timeout(20)
            ->post($this->url('/auth-sessions'), [
                'grant_type' => 'client_credentials',
                'client_id' => $this->settings->get()['client_id'],
                'client_secret' => $this->settings->get()['client_secret'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response->json(), 'Box Now autorizacija nije uspjela.'));
        }

        $this->token = trim((string) data_get($response->json(), 'access_token', ''));

        if ($this->token === '') {
            throw new RuntimeException('Box Now nije vratio pristupni token.');
        }

        return $this->token;
    }

    private function assertConfigured(): void
    {
        $settings = $this->settings->get();
        $missing = collect([
            'Client ID' => $settings['client_id'],
            'Client Secret' => $settings['client_secret'],
            'ID polaznog skladišta' => $settings['warehouse_location_id'],
            'e-mail pošiljatelja' => $settings['origin_email'],
            'telefon pošiljatelja' => $settings['origin_phone'],
        ])->filter(function ($value) {
            return trim((string) $value) === '';
        })->keys();

        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Box Now nije konfiguriran u adminu. Nedostaje: ' . $missing->implode(', ') . '.');
        }
    }

    private function deliveryPayload(Order $order): array
    {
        $settings = $this->settings->get();
        $lockerId = $this->lockerId($order);

        if ($lockerId === '') {
            throw new RuntimeException('Box Now paketomat nije upisan na narudžbi.');
        }

        $paymentMode = strtolower((string) $order->payment_code) === 'cod' ? 'cod' : 'prepaid';

        return [
            'orderNumber' => (string) $order->id,
            'invoiceValue' => $this->money($order->total),
            'paymentMode' => $paymentMode,
            'amountToBeCollected' => $paymentMode === 'cod' ? $this->money($order->total) : '0.00',
            'allowReturn' => $settings['allow_return'],
            'origin' => [
                'contactNumber' => $this->normalizePhone($settings['origin_phone']),
                'contactEmail' => $settings['origin_email'],
                'contactName' => $settings['origin_name'],
                'locationId' => $settings['warehouse_location_id'],
            ],
            'destination' => [
                'contactNumber' => $this->normalizePhone($order->shipping_phone ?: $order->payment_phone),
                'contactEmail' => (string) ($order->shipping_email ?: $order->payment_email),
                'contactName' => trim((string) ($order->shipping_fname ?: $order->payment_fname) . ' ' . (string) ($order->shipping_lname ?: $order->payment_lname)),
                'locationId' => $lockerId,
            ],
            'items' => [$this->itemPayload($order)],
        ];
    }

    private function itemPayload(Order $order): array
    {
        $products = $order->products;
        $first = $products->first();
        $value = (float) $products->sum('total');

        return [
            'id' => (string) ($first->product_id ?? $order->id),
            'name' => (string) ($first->name ?? ('Narudžba #' . $order->id)),
            'value' => $this->money($value > 0 ? $value : $order->total),
            'weight' => 0,
            'compartmentSize' => 1,
        ];
    }

    private function lockerId(Order $order): string
    {
        $comment = trim((string) $order->comment);
        $position = strrpos($comment, '_');

        return trim($position === false ? $comment : substr($comment, $position + 1));
    }

    private function normalizePhone($phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) $phone) ?: '';

        if (Str::startsWith($phone, '00385')) {
            return '+385' . substr($phone, 5);
        }

        if (Str::startsWith($phone, '385')) {
            return '+' . $phone;
        }

        if (Str::startsWith($phone, '0')) {
            return '+385' . substr($phone, 1);
        }

        return $phone;
    }

    private function latestState(array $parcel): string
    {
        $direct = trim((string) (data_get($parcel, 'event') ?: data_get($parcel, 'state') ?: data_get($parcel, 'parcelState')));

        if ($direct !== '') {
            return $direct;
        }

        $events = data_get($parcel, 'events', []);

        if (! is_array($events) || empty($events)) {
            return '';
        }

        usort($events, function ($left, $right) {
            return $this->timestamp(data_get($left, 'createTime', data_get($left, 'time')))
                <=> $this->timestamp(data_get($right, 'createTime', data_get($right, 'time')));
        });

        $latest = end($events) ?: [];

        return trim((string) (data_get($latest, 'event') ?: data_get($latest, 'type') ?: data_get($latest, 'state')));
    }

    private function timestamp($value): int
    {
        try {
            return $value ? Carbon::parse($value)->timestamp : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function firstParcel(array $payload): array
    {
        $data = data_get($payload, 'data', data_get($payload, 'parcels', $payload));

        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        return is_array($data) ? $data : [];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.boxnow.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function errorMessage($payload, string $fallback): string
    {
        $message = data_get($payload, 'message') ?: data_get($payload, 'error');

        if (is_array($message)) {
            $message = data_get($message, 'message') ?: json_encode($message);
        }

        return trim((string) $message) ?: $fallback;
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
