<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('open_drivers', function (Blueprint $table) {
            $table->id();
            $table->text('SessionID');
            $table->text("UserName");
            $table->text("UserFirstName");
            $table->text("UserLastName");
            $table->text("AccType");
            $table->text("UserLang");
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
        Schema::dropIfExists('open_drivers');
    }
}
