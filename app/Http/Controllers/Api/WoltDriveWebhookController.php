<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Services\Shipping\OrderTrackingService;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveWebhookException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WoltDriveWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        WoltDriveService $wolt,
        OrderTrackingService $trackingService
    )
    {
        $token = trim((string) $request->input('token'));

        if ($token === '') {
            return response()->json(['message' => 'Missing webhook token.'], 422);
        }

        try {
            $result = $wolt->handleWebhookToken($token);
            $order = $this->resolveOrder($result);

            if (! $order) {
                Log::notice('Wolt Drive webhook did not match a local order.', [
                    'order_id' => $result['order_id'] ?? null,
                    'parcel_id' => $result['parcel_id'] ?? null,
                ]);

                return response()->json(['received' => true, 'updated' => false]);
            }

            $eventId = trim((string) data_get($result, 'payload.id', ''));
            $previousEventId = trim((string) data_get(
                $order->shipping_tracking_payload,
                'last_webhook.id',
                ''
            ));

            if ($eventId !== '' && $previousEventId !== '' && hash_equals($previousEventId, $eventId)) {
                return response()->json(['received' => true, 'updated' => false]);
            }

            // Wolt documents that closely related events can arrive out of
            // order. A later non-terminal event must never downgrade a
            // delivery already confirmed as delivered.
            if ($order->shipped && empty($result['is_delivered'])) {
                return response()->json(['received' => true, 'updated' => false]);
            }

            $existingPayload = is_array($order->shipping_tracking_payload)
                ? $order->shipping_tracking_payload
                : [];
            $result['payload'] = array_merge($existingPayload, [
                'last_webhook' => $result['payload'] ?? [],
            ]);
            $applied = $trackingService->apply($order, $result);

            return response()->json([
                'received' => true,
                'updated' => (bool) ($applied['updated'] ?? false),
            ]);
        } catch (WoltDriveWebhookException $exception) {
            Log::warning('Wolt Drive webhook signature was rejected.', [
                'error_code' => $exception->errorCode(),
                'reason' => $exception->getMessage(),
            ]);

            $status = $exception->httpStatus() ?: 401;

            return response()->json([
                'message' => $status === 503
                    ? 'Wolt webhook is not configured.'
                    : 'Invalid webhook token.',
            ], $status);
        } catch (\Throwable $exception) {
            Log::error('Wolt Drive webhook processing failed.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook could not be processed.'], 503);
        }
    }

    private function resolveOrder(array $tracking): ?Order
    {
        $orderId = (int) ($tracking['order_id'] ?? 0);
        $order = $orderId > 0 ? Order::query()->find($orderId) : null;
        $parcelId = trim((string) ($tracking['parcel_id'] ?? ''));
        $trackingCode = trim((string) ($tracking['tracking_code'] ?? ''));

        if (! $order && $parcelId !== '') {
            $order = Order::query()->where('shipping_parcel_id', $parcelId)->first();
        }

        if (! $order && $trackingCode !== '') {
            $order = Order::query()->where('tracking_code', $trackingCode)->first();
        }

        if (! $order) {
            return null;
        }

        $shipping = Str::lower(
            (string) $order->shipping_carrier . ' '
            . (string) $order->shipping_code . ' '
            . (string) $order->shipping_method
        );

        if (! Str::contains($shipping, ['wolt_drive', 'wolt drive', 'wolt'])) {
            return null;
        }

        if (filled($order->shipping_parcel_id) && $parcelId !== ''
            && ! hash_equals((string) $order->shipping_parcel_id, $parcelId)) {
            Log::warning('Wolt webhook reference did not match the local order.', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        return $order;
    }
}
