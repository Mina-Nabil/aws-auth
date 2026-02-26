<?php

namespace App\Containers\AppSection\File\Data\Factories;

use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\File\Models\FileLink;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Factories\Factory as ParentFactory;

/**
 * @template TModel of FileLink
 *
 * @extends ParentFactory<TModel>
 */
final class FileLinkFactory extends ParentFactory
{
    /** @var class-string<TModel> */
    protected $model = FileLink::class;

    public function definition(): array
    {
        return [
            'file_id' => File::factory(),
            'entity_type' => 'user',
            'entity_id' => User::factory(),
        ];
    }
}
