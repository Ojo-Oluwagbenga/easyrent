<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('creator_code');
            $table->string('product_code');
            $table->string('apartment');
            $table->text('images');
            $table->integer('amount');
            $table->text('location');
            $table->string('about');
            $table->string('features');
            $table->string('main_features');
            $table->boolean('has_water'); 
            $table->boolean('has_fence'); 
            $table->boolean('has_electricity'); 
            $table->string('status')->default('0'); //0-queuing, 1 accepted, 2 rejected
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
