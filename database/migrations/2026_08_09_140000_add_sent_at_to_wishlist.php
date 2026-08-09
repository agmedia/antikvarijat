<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSentAtToWishlist extends Migration
{
    public function up()
    {
        if (Schema::hasTable('wishlist') && ! Schema::hasColumn('wishlist', 'sent_at')) {
            Schema::table('wishlist', function (Blueprint $table) {
                $table->timestamp('sent_at')->nullable()->after('sent')->index();
                $table->index(['sent', 'status'], 'wishlist_sent_status_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('wishlist') && Schema::hasColumn('wishlist', 'sent_at')) {
            Schema::table('wishlist', function (Blueprint $table) {
                $table->dropIndex('wishlist_sent_status_index');
                $table->dropIndex(['sent_at']);
                $table->dropColumn('sent_at');
            });
        }
    }
}
