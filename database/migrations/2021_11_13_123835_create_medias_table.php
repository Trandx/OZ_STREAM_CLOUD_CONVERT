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

            $table->text('bandePath')->nullable();

            $table->text('mediaPath')->nullable();

           /* $table->string('coverLink')->nullable();
            $table->string('gifLink')->nullable();
            */

            //$table->boolean('isConverted')->nullable();

            $table->boolean('bandeIsOnCloud')->default(0);
            $table->boolean('mediaIsOnCloud')->default(0);
            
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
