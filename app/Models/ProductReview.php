<?php

namespace App\Models;

use App\Models\Back\Catalog\Product\Product as BackProduct;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'product_id',
        'order_id',
        'order_product_id',
        'invitation_id',
        'user_id',
        'reviewer_name',
        'reviewer_email',
        'rating',
        'title',
        'body',
        'locale',
        'status',
        'is_verified_purchase',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'order_id' => 'integer',
        'order_product_id' => 'integer',
        'invitation_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
    ];

    protected $hidden = [
        'reviewer_email',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', static::STATUS_APPROVED);
    }

    public static function statuses(): array
    {
        return [
            static::STATUS_PENDING => 'Na čekanju',
            static::STATUS_APPROVED => 'Odobreno',
            static::STATUS_REJECTED => 'Odbijeno',
        ];
    }

    public function product()
    {
        return $this->belongsTo(BackProduct::class, 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function invitation()
    {
        return $this->belongsTo(ProductReviewInvitation::class, 'invitation_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
