<?php

namespace App\Containers\AppSection\File\Data\Factories;

use App\Containers\AppSection\File\Enums\FileVisibility;
use App\Containers\AppSection\File\Models\File;
use App\Containers\AppSection\User\Models\User;
use App\Ship\Parents\Factories\Factory as ParentFactory;

/**
 * @template TModel of File
 *
 * @extends ParentFactory<TModel>
 */
final class FileFactory extends ParentFactory
{
    /** @var class-string<TModel> */
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'original_name' => fake()->word() . '.pdf',
            'storage_disk' => 's3',
            'storage_path' => 'files/' . fake()->uuid() . '.pdf',
            'checksum' => fake()->md5(),
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1000, 1000000),
            'visibility' => FileVisibility::PRIVATE,
            'uploaded_by' => User::factory()->createOne()->id,
            'description' => fake()->sentence(),
            'tags' => [fake()->word(), fake()->word()],
            'is_tmp' => false,
        ];
    }

    public function publicVisibility(): self
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => FileVisibility::PUBLIC,
        ]);
    }

    public function privateVisibility(): self
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => FileVisibility::PRIVATE,
        ]);
    }

    public function shareLinkVisibility(): self
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => FileVisibility::SHARE_LINK,
        ]);
    }
}
