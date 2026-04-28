<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVialibriBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vialibri_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->unique();
            $table->string('translated_title')->nullable();
            $table->longText('translated_description')->nullable();
            $table->string('edition')->nullable();
            $table->text('keywords')->nullable();
            $table->boolean('first_edition')->nullable();
            $table->boolean('signed')->nullable();
            $table->boolean('dust_jacket')->nullable();
            $table->timestamp('translated_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vialibri_books');
    }
}
