<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('gift_vouchers')) {
            Schema::create('gift_vouchers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
                $table->string('cart_item_key', 64)->nullable();
                $table->string('code_hash', 64)->nullable()->unique();
                $table->text('code_ciphertext')->nullable();
                $table->string('code_suffix', 10)->nullable()->index();
                $table->decimal('initial_amount', 15, 4);
                $table->decimal('balance', 15, 4);
                $table->string('currency', 3)->default('EUR');
                $table->string('buyer_name')->nullable();
                $table->string('buyer_email')->nullable();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_email');
                $table->string('sender_name')->nullable();
                $table->text('message')->nullable();
                $table->string('locale', 5)->default('hr');
                $table->string('status', 32)->default('pending')->index();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('email_sent_at')->nullable();
                $table->timestamp('last_email_sent_at')->nullable();
                $table->text('email_error')->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['purchase_order_id', 'cart_item_key'], 'gift_voucher_order_item_unique');
            });
        }

        if (! Schema::hasTable('gift_voucher_redemptions')) {
            Schema::create('gift_voucher_redemptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('gift_voucher_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->decimal('amount', 15, 4);
                $table->string('status', 24)->default('reserved')->index();
                $table->timestamp('reserved_until')->nullable()->index();
                $table->timestamp('redeemed_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->string('release_reason')->nullable();
                $table->timestamps();

                $table->unique(['gift_voucher_id', 'order_id'], 'gift_voucher_order_redemption_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_voucher_redemptions');
        Schema::dropIfExists('gift_vouchers');
    }
};
