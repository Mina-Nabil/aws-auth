<?php

use App\Containers\AppSection\File\Enums\FileVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('files', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('original_name');
            $table->string('description')->nullable();
            $table->json('tags')->nullable();

            $table->enum('visibility', FileVisibility::cases());

            $table->string('storage_disk');
            $table->string('storage_path');

            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type');
            $table->string('checksum')->nullable();

            $table->foreignUuid('uploaded_by')->constrained('users');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('file_links', static function (Blueprint $table) {
            $table->foreignUuid('file_id')->constrained('files');
            $table->uuidMorphs('entity');
            $table->primary(['file_id', 'entity_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_links');
        Schema::dropIfExists('files');
    }
};
