<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMailchimpSyncColumnsToNewsletterSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_subscribers', 'mailchimp_synced_at')) {
                $table->timestamp('mailchimp_synced_at')->nullable()->after('subscribed_at');
            }

            if (! Schema::hasColumn('newsletter_subscribers', 'mailchimp_last_error')) {
                $table->text('mailchimp_last_error')->nullable()->after('mailchimp_synced_at');
            }

            $table->index('mailchimp_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (Schema::hasColumn('newsletter_subscribers', 'mailchimp_synced_at')) {
                $table->dropIndex(['mailchimp_synced_at']);
                $table->dropColumn('mailchimp_synced_at');
            }

            if (Schema::hasColumn('newsletter_subscribers', 'mailchimp_last_error')) {
                $table->dropColumn('mailchimp_last_error');
            }
        });
    }
}
