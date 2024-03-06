<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportUsersTable extends Migration
{
    public function up()
    {
        Schema::create('import_users', function (Blueprint $table) {
            $table->id();
            $table->string('emso', 13)->unique();
            $table->string('name_surname', 255);
            $table->string('country', 50);
            $table->unsignedTinyInteger('age');
            $table->text('descriptions')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_users');
    }
}