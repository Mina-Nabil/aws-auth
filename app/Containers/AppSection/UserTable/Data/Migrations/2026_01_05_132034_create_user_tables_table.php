<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('user_tables', static function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('table_name');
            $table->json('columns');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'table_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tables');
    }
};
