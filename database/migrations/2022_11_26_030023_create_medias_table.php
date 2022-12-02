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
            $table->uuid('id')->primary();
            $table->json('details');
            $table->string('media_id')->nullable();
            $table->string('saison_id')->nullable();
            $table->boolean('is_film_bande')->default(false);
            $table->string("current_path");
            $table->json("converted_format")->nullable();
            $table->boolean("is_online")->default(false);
            $table->boolean("is_converted")->default(false);
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
