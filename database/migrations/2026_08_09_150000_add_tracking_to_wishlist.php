<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrackingToWishlist extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('wishlist')) {
            return;
        }

        $addClickedAt = ! Schema::hasColumn('wishlist', 'clicked_at');
        $addClickCount = ! Schema::hasColumn('wishlist', 'click_count');

        Schema::table('wishlist', function (Blueprint $table) use ($addClickedAt, $addClickCount) {
            if ($addClickedAt) {
                $table->timestamp('clicked_at')->nullable()->after('sent_at');
                $table->index('clicked_at', 'wishlist_clicked_at_index');
            }

            if ($addClickCount) {
                $table->unsignedInteger('click_count')->default(0)->after('clicked_at');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('wishlist')) {
            return;
        }

        $dropClickedAt = Schema::hasColumn('wishlist', 'clicked_at');
        $dropClickCount = Schema::hasColumn('wishlist', 'click_count');

        Schema::table('wishlist', function (Blueprint $table) use ($dropClickedAt, $dropClickCount) {
            if ($dropClickedAt) {
                $table->dropIndex('wishlist_clicked_at_index');
                $table->dropColumn('clicked_at');
            }

            if ($dropClickCount) {
                $table->dropColumn('click_count');
            }
        });
    }
}
