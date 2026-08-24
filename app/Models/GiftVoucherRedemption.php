<?php

namespace App\Models;

use App\Models\Back\Orders\Order;
use Illuminate\Database\Eloquent\Model;

class GiftVoucherRedemption extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_RELEASED = 'released';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'amount' => 'float',
        'reserved_until' => 'datetime',
        'redeemed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function voucher()
    {
        return $this->belongsTo(GiftVoucher::class, 'gift_voucher_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
