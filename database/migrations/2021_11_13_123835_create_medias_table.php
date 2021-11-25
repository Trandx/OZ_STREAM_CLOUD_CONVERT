<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->string('media_id')->nullable();
            $table->string('saison_id')->nullable();

            $table->string('bandePath')->nullable();
            $table->string('finalBandeLink')->nullable();

            $table->string('mediaPath')->nullable();
            $table->string('finalMediaLink')->nullable();

            $table->string('coverLink')->nullable();
            $table->string('gifLink')->nullable();
            
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
        Schema::dropIfExists('medias');
    }
}
