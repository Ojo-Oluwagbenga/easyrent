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
    public function up()
    {
        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->string('ownerid');
            $table->timestamps();
            $table->string('name');
            $table->text('description');
            $table->mediumText('imagepaths');//url list
            $table->integer('price')->default();
            $table->string('type')->default();
            $table->string('category')->default();
            $table->string('status')->default('0');
            $table->integer('proposedprice')->default('0');            

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bins');
    }
};
