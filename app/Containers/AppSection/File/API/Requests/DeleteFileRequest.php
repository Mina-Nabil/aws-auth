<?php

namespace App\Containers\AppSection\File\API\Requests;

use App\Containers\AppSection\File\Enums\FileGate;
use App\Containers\AppSection\File\Models\File;
use App\Ship\Parents\Requests\Request as ParentRequest;
use Illuminate\Support\Facades\Gate;

final class DeleteFileRequest extends ParentRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}
