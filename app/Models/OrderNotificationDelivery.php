<?php

namespace App\Models;

use App\Models\Back\Orders\Order;
use Illuminate\Database\Eloquent\Model;

class OrderNotificationDelivery extends Model
{
    public const KIND_ADMIN = 'admin';
    public const KIND_CUSTOMER = 'customer';

    protected $table = 'order_notification_deliveries';

    protected $fillable = [
        'order_id',
        'kind',
        'recipient_email',
        'locale',
        'attempts',
        'available_at',
        'claimed_at',
        'last_attempt_at',
        'sent_at',
        'failed_at',
        'last_error',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'available_at' => 'datetime',
        'claimed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
