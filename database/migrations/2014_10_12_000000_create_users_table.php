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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default("");
            $table->string('code');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('temp_email_code');// This code will be sent to the user mail for confirm
            $table->integer('status')->default(0); // 0 for email unverified and 1 for verified
            $table->string('gender')->default("-");
            $table->string('role')->default('-');
            $table->string('account_details')->default('{}');
            $table->integer('cashbalance')->default(0);
            $table->mediumText('likedproducts'); // ['stringlist of product id']
            $table->mediumText('profile_picture');
            //medium Text dont have default values;
            //Seems this guy is damn important too lol
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
        Schema::dropIfExists('users');
    }
};
