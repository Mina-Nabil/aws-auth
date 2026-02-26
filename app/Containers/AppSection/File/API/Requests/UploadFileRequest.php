<?php

namespace App\Containers\AppSection\File\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Enums\FileType;
use App\Containers\AppSection\File\Enums\FileVisibility;

final class UploadFileRequest extends ParentRequest
{
    /**
     * Prepare the data for validation.
     * Handles Swagger UI limitation where arrays in multipart/form-data are sent as comma-separated strings.
     */
    protected function prepareForValidation(): void
    {
        // Handle tags - convert comma-separated string to array if needed
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_filter(array_map('trim', explode(',', $this->tags)))
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:' . config('appSection-file.max_file_size') . '|mimetypes:' . implode(',', FileType::getAllowedMimeTypes()),
            'description' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'required|string|max:255',
            'visibility' => 'required|in:' . implode(',', array_map(fn (FileVisibility $visibility) => $visibility->value, FileVisibility::cases())),
            'checksum' => 'nullable|string|max:255',
            'uploaded_by' => 'required|uuid|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'uploaded_by.exists' => 'The uploaded by user does not exist',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(FileGate::CREATE_FILE);
    }
}
