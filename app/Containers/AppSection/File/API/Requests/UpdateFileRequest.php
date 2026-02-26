<?php

namespace App\Containers\AppSection\File\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use App\Containers\AppSection\File\Enums\FileVisibility;

final class UpdateFileRequest extends ParentRequest
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
            'description' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'required|string|max:255',
            'visibility' => 'required|in:' . implode(',', array_map(fn (FileVisibility $visibility) => $visibility->value, FileVisibility::cases())),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
