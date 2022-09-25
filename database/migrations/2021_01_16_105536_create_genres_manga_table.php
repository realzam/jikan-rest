<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenresMangaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('genres_manga', function (Blueprint $table) {
            $table->unique(['mal_id' => 1], 'mal_id');
            $table->index('count', 'count');
            $table->index('name', 'name');

            $table->index(
                [
                    'name' => 'text'
                ],
                'search'
            );
        });

        Schema::create('explicit_genres_manga', function (Blueprint $table) {
            $table->unique(['mal_id' => 1], 'mal_id');
            $table->index('count', 'count');
            $table->index('name', 'name');

            $table->index(
                [
                    'name' => 'text'
                ],
                'search'
            );
        });

        Schema::create('demographics_manga', function (Blueprint $table) {
            $table->unique(['mal_id' => 1], 'mal_id');
            $table->index('count', 'count');
            $table->index('name', 'name');

            $table->index(
                [
                    'name' => 'text'
                ],
                'search'
            );
        });

        Schema::create('themes_manga', function (Blueprint $table) {
            $table->unique(['mal_id' => 1], 'mal_id');
            $table->index('count', 'count');
            $table->index('name', 'name');

            $table->index(
                [
                    'name' => 'text'
                ],
                'search'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('genres_manga');
        Schema::dropIfExists('explicit_genres_manga');
        Schema::dropIfExists('demographics_manga');
        Schema::dropIfExists('themes_manga');
    }
}
