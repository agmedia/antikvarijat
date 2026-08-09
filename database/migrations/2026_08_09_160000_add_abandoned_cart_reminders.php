<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAbandonedCartReminders extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'locale')) {
                $table->string('locale', 5)->nullable()->after('payment_email');
            }

            if (! Schema::hasColumn('orders', 'unfinished_at')) {
                $table->timestamp('unfinished_at')->nullable()->after('checkout_processed_at');
            }
        });

        if (! Schema::hasTable('abandoned_cart_reminders')) {
            Schema::create('abandoned_cart_reminders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedTinyInteger('sequence');
                $table->timestamp('scheduled_for');
                $table->timestamp('sent_at')->nullable();
                $table->string('source', 20);
                $table->string('recipient_email', 191);
                $table->string('locale', 5)->default('hr');
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->timestamps();

                $table->unique(['order_id', 'sequence'], 'abandoned_cart_order_sequence_unique');
                $table->index(['sent_at', 'scheduled_for'], 'abandoned_cart_due_index');
            });
        }

        DB::table('orders')
            ->where('order_status_id', (int) config('settings.order.status.unfinished', 8))
            ->where('created_at', '>=', config('abandoned_cart.starts_at'))
            ->whereNull('unfinished_at')
            ->update(['unfinished_at' => DB::raw('created_at')]);
    }

    public function down()
    {
        Schema::dropIfExists('abandoned_cart_reminders');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'unfinished_at')) {
                $table->dropColumn('unfinished_at');
            }

            if (Schema::hasColumn('orders', 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
}
