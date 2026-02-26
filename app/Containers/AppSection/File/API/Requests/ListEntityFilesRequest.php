<?php

namespace App\Containers\AppSection\File\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use App\Containers\AppSection\File\Enums\FileType;

final class ListEntityFilesRequest extends ParentRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'entity_type' => 'required|string|in:' . implode(',', config('appSection-file.allowed_entities')),
            'entity_id' => 'required|string|max:255',
            'type' => 'sometimes|string|in:' . implode(',', array_map(fn (FileType $type) => $type->value, FileType::cases())),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
