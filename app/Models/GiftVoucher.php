<?php

namespace App\Models;

use App\Models\Back\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class GiftVoucher extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXHAUSTED = 'exhausted';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['code_hash', 'code_ciphertext'];

    protected $appends = ['code', 'display_status', 'status_color'];

    protected $casts = [
        'initial_amount' => 'float',
        'balance' => 'float',
        'issued_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'disabled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(Order::class, 'purchase_order_id');
    }

    public function redemptions()
    {
        return $this->hasMany(GiftVoucherRedemption::class, 'gift_voucher_id')->latest('id');
    }

    public function getCodeAttribute(): ?string
    {
        if (! $this->code_ciphertext) {
            return null;
        }

        try {
            return Crypt::decryptString($this->code_ciphertext);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function storeCode(string $code): void
    {
        $normalized = strtoupper(trim($code));

        $this->code_hash = hash('sha256', $normalized);
        $this->code_ciphertext = Crypt::encryptString($normalized);
        $this->code_suffix = substr($normalized, -6);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ! $this->isExpired()
            && $this->code_hash
            && (float) $this->balance > 0;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Istekao';
        }

        return [
            self::STATUS_PENDING => 'Čeka plaćanje',
            self::STATUS_ACTIVE => 'Aktivan',
            self::STATUS_EXHAUSTED => 'Iskorišten',
            self::STATUS_DISABLED => 'Onemogućen',
            self::STATUS_CANCELLED => 'Otkazan',
        ][$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->isExpired()) {
            return 'secondary';
        }

        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_EXHAUSTED => 'secondary',
            self::STATUS_DISABLED => 'danger',
            self::STATUS_CANCELLED => 'dark',
        ][$this->status] ?? 'secondary';
    }
}
