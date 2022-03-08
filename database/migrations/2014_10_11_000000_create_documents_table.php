<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->nullableMorphs('documentable');
            $table->string('group');
            $table->string('media_path');
            $table->unsignedInteger('order')->nullable()->index();
            $table->string('type');
            $table->string('mime');
            $table->string('path');
            $table->string('disk');
            $table->unsignedBigInteger('size')->nullable();

            $table->json('variations')->nullable();

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
        Schema::dropIfExists('dumps');
    }
}