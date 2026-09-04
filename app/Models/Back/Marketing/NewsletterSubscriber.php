<?php

namespace App\Models\Back\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NewsletterSubscriber extends Model
{
    /**
     * @var string
     */
    protected $table = 'newsletter_subscribers';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'mailchimp_synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function subscribe(array $data)
    {
        $email = strtolower(trim($data['email'] ?? ''));

        $subscriber = static::firstOrNew(['email' => $email]);

        if (! empty($data['first_name'])) {
            $subscriber->first_name = $data['first_name'];
        }

        if (! empty($data['last_name'])) {
            $subscriber->last_name = $data['last_name'];
        }

        if (! empty($data['user_id']) && (int) $data['user_id'] > 0) {
            $subscriber->user_id = (int) $data['user_id'];
        }

        if (! empty($data['order_id']) && (int) $data['order_id'] > 0) {
            $subscriber->order_id = (int) $data['order_id'];
        }

        $subscriber->source = $data['source'] ?? 'unknown';
        $subscriber->gdpr = isset($data['gdpr']) ? (int) ((bool) $data['gdpr']) : 1;
        $subscriber->status = 1;
        $subscriber->subscribed_at = now();

        $subscriber->save();

        return $subscriber;
    }

    /**
     * Store a footer signup without modifying an existing subscriber. A
     * repeated public request must never reactivate an unsubscribed address
     * or overwrite customer/order attribution.
     */
    public static function subscribeFromFooter(string $email, int $userId = 0): void
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return;
        }

        $now = now();

        DB::table((new static())->getTable())->insertOrIgnore([
            [
                'email' => $email,
                'user_id' => max(0, $userId),
                'order_id' => 0,
                'source' => 'footer',
                'gdpr' => 1,
                'subscribed_at' => $now,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Attach order id to existing subscriber by email.
     *
     * @param string $email
     * @param int $orderId
     *
     * @return void
     */
    public static function attachOrderToEmail(string $email, int $orderId): void
    {
        $email = strtolower(trim($email));

        if ($email === '' || $orderId <= 0) {
            return;
        }

        static::query()
            ->where('email', $email)
            ->where(function ($q) {
                $q->whereNull('order_id')->orWhere('order_id', 0);
            })
            ->update([
                'order_id' => $orderId,
                'updated_at' => now(),
            ]);
    }
}
