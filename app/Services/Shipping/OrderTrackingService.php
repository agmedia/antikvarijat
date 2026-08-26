<?php

namespace App\Services\Shipping;

use App\Mail\ShippingTrackingAvailable;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class OrderTrackingService
{
    public const TRACKING_EMAIL_HISTORY_COMMENT = 'Kupcu poslan email s podacima za praćenje pošiljke.';

    /** @var GlsTrackingService */
    private $gls;

    /** @var BoxNowService */
    private $boxNow;

    public function __construct(GlsTrackingService $gls, BoxNowService $boxNow)
    {
        $this->gls = $gls;
        $this->boxNow = $boxNow;
    }

    public function refresh(Order $order): array
    {
        $carrier = $this->resolveCarrier($order);

        if ($carrier === GlsTrackingService::CARRIER) {
            return $this->apply($order, $this->gls->trackOrder($order));
        }

        if ($carrier === BoxNowService::CARRIER) {
            return $this->apply($order, $this->boxNow->track($order));
        }

        if ($carrier === WoltDriveService::CARRIER) {
            return [
                'updated' => false,
                'message' => 'Wolt Drive status prima se automatski putem webhooka.',
                'tracking' => [
                    'carrier' => WoltDriveService::CARRIER,
                    'parcel_id' => $order->shipping_parcel_id,
                    'tracking_code' => $order->tracking_code,
                    'tracking_url' => $order->shipping_tracking_url,
                    'status_code' => $order->shipping_tracking_status_code,
                    'status' => $order->shipping_tracking_status,
                    'tracked_at' => $order->shipping_tracking_updated_at,
                ],
            ];
        }

        throw new RuntimeException('Praćenje nije podržano za ovaj način dostave.');
    }

    public function apply(Order $order, array $tracking, bool $writeHistory = true): array
    {
        $trackedAt = $this->trackedAt($tracking['tracked_at'] ?? null);
        $currentTrackedAt = $order->shipping_tracking_updated_at
            ? Carbon::make($order->shipping_tracking_updated_at)
            : null;
        $hadCustomerTrackingIdentifier = $this->hasCustomerTrackingIdentifier($order);

        if ($currentTrackedAt && $trackedAt->lt($currentTrackedAt)) {
            return [
                'updated' => false,
                'message' => 'Preskočen je stariji tracking update.',
                'tracking' => $tracking,
            ];
        }

        $previousStatusCode = (string) ($order->shipping_tracking_status_code ?? '');
        $newStatusCode = (string) ($tracking['status_code'] ?? '');
        $previousCustomerTrackingCode = $this->customerTrackingIdentifier($order);

        $order->forceFill([
            'shipping_carrier' => $tracking['carrier'] ?? $this->resolveCarrier($order),
            'shipping_parcel_id' => $tracking['parcel_id'] ?? $order->shipping_parcel_id,
            'tracking_code' => $tracking['tracking_code'] ?? $order->tracking_code,
            'shipping_tracking_url' => $tracking['tracking_url'] ?? $order->shipping_tracking_url,
            'shipping_tracking_status_code' => $newStatusCode ?: null,
            'shipping_tracking_status' => $tracking['status'] ?? null,
            'shipping_tracking_updated_at' => $trackedAt,
            'shipping_tracking_payload' => $tracking['payload'] ?? [],
        ])->save();

        if (! empty($tracking['is_delivered'])) {
            $order->forceFill(['shipped' => true])->save();
        }

        $customerTrackingCodeFirstAppeared = $previousCustomerTrackingCode === ''
            && $this->customerTrackingIdentifier($order) !== '';

        if ($writeHistory && (
            ($newStatusCode !== '' && $newStatusCode !== $previousStatusCode)
            || $customerTrackingCodeFirstAppeared
        )) {
            $this->storeHistory($order, $tracking);
        }

        $this->sendTrackingAvailableMail($order, $hadCustomerTrackingIdentifier);

        return [
            'updated' => true,
            'message' => 'Tracking je osvježen: ' . ($tracking['status'] ?? 'status nije dostupan'),
            'tracking' => $tracking,
        ];
    }

    public function resolveCarrier(Order $order): ?string
    {
        if (filled($order->shipping_carrier)) {
            $carrier = strtolower((string) $order->shipping_carrier);

            return in_array($carrier, [GlsTrackingService::CARRIER, BoxNowService::CARRIER, WoltDriveService::CARRIER], true)
                ? $carrier
                : null;
        }

        $shipping = Str::lower((string) $order->shipping_method . ' ' . (string) $order->shipping_code);

        if (Str::contains($shipping, ['boxnow', 'box now'])) {
            return BoxNowService::CARRIER;
        }

        if (Str::contains($shipping, ['wolt_drive', 'wolt drive', 'wolt'])) {
            return WoltDriveService::CARRIER;
        }

        return Str::contains($shipping, 'gls') ? GlsTrackingService::CARRIER : null;
    }

    public function carrierLabel(?string $carrier): string
    {
        return [
            GlsTrackingService::CARRIER => 'GLS',
            BoxNowService::CARRIER => 'Box Now',
            WoltDriveService::CARRIER => 'Wolt Drive',
        ][$carrier] ?? 'Dostava';
    }

    public function trackingUrlForOrder(Order $order): ?string
    {
        if (filled($order->shipping_tracking_url)) {
            return (string) $order->shipping_tracking_url;
        }

        if ($this->resolveCarrier($order) === BoxNowService::CARRIER) {
            $parcelId = trim((string) ($order->tracking_code ?: $order->shipping_parcel_id));

            return $parcelId !== '' ? $this->boxNow->trackingUrl($parcelId) : null;
        }

        if ($this->resolveCarrier($order) === GlsTrackingService::CARRIER && filled($order->tracking_code)) {
            return $this->gls->trackingUrl((string) $order->tracking_code);
        }

        return null;
    }

    public function trackingEmailSentAt(Order $order): ?Carbon
    {
        if (Schema::hasColumn('orders', 'shipping_tracking_email_sent_at') && $order->shipping_tracking_email_sent_at) {
            return Carbon::make($order->shipping_tracking_email_sent_at);
        }

        $historyCreatedAt = OrderHistory::query()
            ->where('order_id', $order->id)
            ->where('comment', 'like', self::TRACKING_EMAIL_HISTORY_COMMENT . '%')
            ->latest('created_at')
            ->value('created_at');

        return $historyCreatedAt ? Carbon::make($historyCreatedAt) : null;
    }

    public function sendTrackingAvailableMailManually(Order $order): array
    {
        if (! $this->hasCustomerTrackingIdentifier($order)) {
            return [
                'sent' => false,
                'error' => 'Tracking broj nije upisan.',
            ];
        }

        if (! filled($order->payment_email)) {
            return [
                'sent' => false,
                'error' => 'Narudžba nema e-mail adresu kupca.',
            ];
        }

        $sentAt = $this->trackingEmailSentAt($order);

        if ($sentAt) {
            return [
                'sent' => false,
                'message' => 'Tracking email je već poslan kupcu ' . $sentAt->format('d.m.Y H:i') . '.',
            ];
        }

        $this->sendTrackingAvailableMail($order, false, true);

        return [
            'sent' => true,
            'message' => 'Tracking email je poslan kupcu.',
        ];
    }

    private function trackedAt($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return $value ? Carbon::parse($value) : now();
        } catch (\Throwable $e) {
            return now();
        }
    }

    private function storeHistory(Order $order, array $tracking): void
    {
        $carrier = $this->carrierLabel($tracking['carrier'] ?? null);
        $status = $tracking['status'] ?? 'status nije dostupan';
        $trackingCode = trim((string) ($tracking['tracking_code'] ?? ''));
        $trackingInfo = $trackingCode !== '' ? ' Broj pošiljke: ' . $trackingCode . '.' : '';

        OrderHistory::store($order->id, new Request([
            'status' => 0,
            'comment' => 'Tracking update (' . $carrier . '): ' . $status . $trackingInfo,
        ]));
    }

    private function sendTrackingAvailableMail(Order $order, bool $hadCustomerTrackingIdentifier, bool $throwOnFailure = false): bool
    {
        if ($hadCustomerTrackingIdentifier || ! $this->hasCustomerTrackingIdentifier($order)) {
            return false;
        }

        if (! filled($order->payment_email) || $this->trackingEmailSentAt($order)) {
            return false;
        }

        try {
            Mail::to($order->payment_email)->send(new ShippingTrackingAvailable(
                $order->fresh(['products', 'totals']) ?: $order
            ));

            if (Schema::hasColumn('orders', 'shipping_tracking_email_sent_at')) {
                $order->forceFill([
                    'shipping_tracking_email_sent_at' => now(),
                ])->save();
            }

            OrderHistory::store($order->id, new Request([
                'status' => 0,
                'comment' => self::TRACKING_EMAIL_HISTORY_COMMENT
                    . ' Broj pošiljke: ' . $this->customerTrackingIdentifier($order) . '.',
            ]));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Shipment tracking email failed.', [
                'order_id' => $order->id,
                'email' => $order->payment_email,
                'error' => $e->getMessage(),
            ]);

            if ($throwOnFailure) {
                throw $e;
            }
        }

        return false;
    }

    private function hasCustomerTrackingIdentifier(Order $order): bool
    {
        return $this->customerTrackingIdentifier($order) !== '';
    }

    private function customerTrackingIdentifier(Order $order): string
    {
        return trim((string) $order->tracking_code);
    }
}
