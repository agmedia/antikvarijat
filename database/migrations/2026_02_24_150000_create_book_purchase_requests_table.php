<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookPurchaseRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('book_purchase_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('submission_id')->unique();
            $table->string('full_name');
            $table->string('postal_code', 20);
            $table->string('email');
            $table->string('phone', 50);
            $table->json('photos');
            $table->string('storage_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('full_name');
            $table->index('submitted_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_purchase_requests');
    }
}
