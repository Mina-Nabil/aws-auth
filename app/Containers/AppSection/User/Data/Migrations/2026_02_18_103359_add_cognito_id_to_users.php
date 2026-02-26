<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->uuid('cognito_id')->nullable();
            $table->boolean('is_cognito_user_confirmed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('cognito_id');
            $table->dropColumn('is_cognito_user_confirmed');
        });
    }
};
