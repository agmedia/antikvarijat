<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNormalizedEmailIndexToProductReviewInvitations extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_review_invitations')) {
            return;
        }

        if (! Schema::hasColumn('product_review_invitations', 'recipient_email_normalized')) {
            Schema::table('product_review_invitations', function (Blueprint $table) {
                $table->string('recipient_email_normalized', 191)
                    ->storedAs('LOWER(TRIM(recipient_email))')
                    ->after('recipient_email');
                $table->index(
                    ['recipient_email_normalized', 'sent_at'],
                    'review_invitations_email_sent_index'
                );
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('product_review_invitations')
            || ! Schema::hasColumn('product_review_invitations', 'recipient_email_normalized')) {
            return;
        }

        Schema::table('product_review_invitations', function (Blueprint $table) {
            $table->dropIndex('review_invitations_email_sent_index');
            $table->dropColumn('recipient_email_normalized');
        });
    }
}
