<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $permissionsTableName = config('permission.table_names')['permissions'];
        Schema::table($permissionsTableName, static function (Blueprint $table) {
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->string('version');
            $table->string('group')->nullable();
            $table->boolean('is_active')->default(false);
        });
    }

    public function down(): void
    {
        $permissionsTableName = config('permission.table_names')['permissions'];
        Schema::table($permissionsTableName, static function (Blueprint $table) {
            $table->dropColumn('display_name');
            $table->dropColumn('description');
            $table->dropColumn('group');
            $table->dropColumn('version');
            $table->dropColumn('is_active');
        });
    }
};
