<?php

namespace App\Containers\AppSection\File\Data\DTOs;

use App\Containers\AppSection\File\Enums\FileType;
use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Ship\Parents\DTOs\DTO;
use Illuminate\Http\UploadedFile;
use Webmozart\Assert\Assert;

final readonly class FileDTO extends DTO
{
    public function __construct(
        public ?UploadedFile $file = null,
        public ?string $fileId = null,
        public ?string $originalName = null,
        public ?string $description = null,
        public ?array $tags = null,
        public ?FileVisibility $visibility = null,
        public ?string $storageDisk = null,
        public ?string $initialPath = null,
        public ?int $sizeBytes = null,
        public ?string $mimeType = null,
        public ?string $checksum = null,
        public ?string $uploadedBy = null,
        public ?bool $isTmp = null,
    ) {
        if ($this->file) {
            Assert::isInstanceOf($this->file, UploadedFile::class);
        }
        if ($this->mimeType) {
            Assert::inArray($this->mimeType, FileType::getAllowedMimeTypes());
        }
    }

    /**
     * Creates a new FileDTO from request or data arrays.
     * @param array $data The data for the FileDTO
     * @return FileDTO The FileDTO
     */
    public static function fromArray(array $data): self
    {
        $originalName = null;
        $sizeInBytes = null;
        $mimeType = null;
        if (array_key_exists('file', $data)) {
            $sizeInBytes = $data['file']->getSize();
            $mimeType = $data['file']->getMimeType();
            $originalName = $data['file']->getClientOriginalName();
        }

        $storageDisk = config('filesystems.default');
        $isTmp = array_key_exists('is_tmp', $data) ? (bool)$data['is_tmp'] : null;
        if ($isTmp === true) {
            $initialPath = config('appSection-file.tmp_directory', 'tmp/');
        } else {
            $initialPath = config('appSection-file.default_directory') . '/';
        }

        if (array_key_exists('visibility', $data)) {
            if (is_string($data['visibility'])) {
                $visibility = FileVisibility::tryFrom($data['visibility']);
            } else {
                $visibility = $data['visibility'];
            }
        } else {
            $visibility = null;
        }

        return new self(
            file: $data['file'] ?? null,
            fileId: $data['file_id'] ?? null,
            originalName: $originalName ?? null,
            description: $data['description'] ?? null,
            tags: $data['tags'] ?? null,
            visibility: $visibility,
            storageDisk: $storageDisk,
            initialPath: $initialPath,
            checksum: $data['checksum'] ?? null,
            uploadedBy: $data['uploaded_by'] ?? null,
            sizeBytes: $sizeInBytes,
            mimeType: $mimeType,
            isTmp: $isTmp,
        );
    }

    public function getFileUploadArray(): array
    {
        Assert::notNull($this->file, 'File is required to create a file');
        Assert::isInstanceOf($this->file, UploadedFile::class, 'Invalid file class type');

        return [
            'file' => $this->file,
            'storage_disk' => $this->storageDisk,
            'initial_path' => $this->initialPath,
        ];
    }

    /**
     * Used for create action, best practice to use this function to get the data for the create action. Runs Assertions on the data.
     * @return array The data for the create action
     */
    public function getCreateRecordArray(): array
    {
        Assert::notNull($this->file, 'File is required to create a file');
        Assert::isInstanceOf($this->file, UploadedFile::class, 'Invalid file class type');
        Assert::stringNotEmpty($this->originalName, 'Original name is required to create a file');
        Assert::stringNotEmpty($this->storageDisk, 'Storage disk is required to create a file');
        Assert::integer($this->sizeBytes, 'Size bytes is required to create a file');
        Assert::stringNotEmpty($this->mimeType, 'Mime type is required to create a file');
        Assert::notNull($this->visibility, 'Visibility is required to create a file');
        Assert::stringNotEmpty($this->uploadedBy, 'Uploaded by is required to create a file');

        $createArray = [
            'original_name' => $this->originalName,
            'storage_disk' => $this->storageDisk,
            'size_bytes' => $this->sizeBytes,
            'mime_type' => $this->mimeType,
            'visibility' => $this->visibility->value,
            'uploaded_by' => $this->uploadedBy,
            'description' => $this->description,
            'checksum' => $this->checksum,
            'tags' => $this->tags,
        ];

        // Add is_tmp if it's set (internal use only)
        if ($this->isTmp !== null) {
            $createArray['is_tmp'] = $this->isTmp;
        } else {
            $createArray['is_tmp'] = false;
        }

        return $createArray;
    }

    /**
     * Used for all update actions, best practice to use this function to get the data for the update action. Runs Assertions on the data.
     * @return array The data for the update action
     */
    public function getUpdateArray(): array
    {
        Assert::stringNotEmpty($this->fileId, 'File ID is required to update a file');
        $retArray = [];
        if ($this->description !== null) {
            $retArray['description'] = $this->description;
        }
        if ($this->tags !== null) {
            $retArray['tags'] = $this->tags;
        }
        if ($this->visibility !== null) {
            $retArray['visibility'] = $this->visibility->value;
        }

        return $retArray;
    }
}
