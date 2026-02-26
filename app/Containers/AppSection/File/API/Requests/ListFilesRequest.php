<?php

namespace App\Containers\AppSection\File\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;
use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Enums\FileType;

final class ListFilesRequest extends ParentRequest
{
    public function rules(): array
    {
        return [
            'q' => 'sometimes|string|max:255',
            'tags' => 'sometimes|string|max:255',
            'file_type' => 'sometimes|string|in:' . implode(',', array_map(fn (FileType $type) => $type->value, FileType::cases())),
            'uploader_id' => 'sometimes|string|max:255',
            'entity_type' => 'sometimes|string|max:255',
            'entity_id' => 'sometimes|string|max:255',
            'from' => 'sometimes|date',
            'to' => 'sometimes|date',
        ];
    }

    public function authorize(): bool
    {
        return Gate::allows(FileGate::VIEW_ANY_FILE);
    }
}
