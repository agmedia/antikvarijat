<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMailchimpEcommerceTrackingToOrdersTable extends Migration
{
    public function up()
    {
        $missing = array_filter([
            'campaign' => ! Schema::hasColumn('orders', 'mailchimp_campaign_id'),
            'synced_at' => ! Schema::hasColumn('orders', 'mailchimp_ecommerce_synced_at'),
            'financial_status' => ! Schema::hasColumn('orders', 'mailchimp_ecommerce_financial_status'),
            'last_attempt_at' => ! Schema::hasColumn('orders', 'mailchimp_ecommerce_last_attempt_at'),
            'last_error' => ! Schema::hasColumn('orders', 'mailchimp_ecommerce_last_error'),
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($missing) {
            if (isset($missing['campaign'])) {
                $table->string('mailchimp_campaign_id', 100)->nullable()->after('checkout_processed_at');
            }

            if (isset($missing['synced_at'])) {
                $table->timestamp('mailchimp_ecommerce_synced_at')->nullable()->after('mailchimp_campaign_id');
            }

            if (isset($missing['financial_status'])) {
                $table->string('mailchimp_ecommerce_financial_status', 20)->nullable()->after('mailchimp_ecommerce_synced_at');
            }

            if (isset($missing['last_attempt_at'])) {
                $table->timestamp('mailchimp_ecommerce_last_attempt_at')->nullable()->after('mailchimp_ecommerce_financial_status');
            }

            if (isset($missing['last_error'])) {
                $table->text('mailchimp_ecommerce_last_error')->nullable()->after('mailchimp_ecommerce_last_attempt_at');
            }
        });
    }

    public function down()
    {
        $columns = array_values(array_filter([
            'mailchimp_campaign_id',
            'mailchimp_ecommerce_synced_at',
            'mailchimp_ecommerce_financial_status',
            'mailchimp_ecommerce_last_attempt_at',
            'mailchimp_ecommerce_last_error',
        ], static function ($column) {
            return Schema::hasColumn('orders', $column);
        }));

        if ($columns !== []) {
            Schema::table('orders', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}
