<?php

namespace App\Containers\AppSection\File\API\Requests;

use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;

final class DetachFileRequest extends ParentRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'file_id' => $this->file_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'file_id' => 'required|uuid|exists:files,id',
            'entity_type' => 'required|string|in:' . implode(',', config('appSection-file.allowed_entities')),
            'entity_id' => 'required|string|max:255',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
