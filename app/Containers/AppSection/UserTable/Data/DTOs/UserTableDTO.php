<?php

namespace App\Containers\AppSection\UserTable\Data\DTOs;

use App\Ship\Parents\DTOs\DTO;
use Webmozart\Assert\Assert;

final readonly class UserTableDTO extends DTO
{
    public function __construct(
        public ?int $id = null,
        public ?string $user_id = null,
        public ?string $table_name = null,
        public ?array $columns = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            user_id: $data['user_id'] ?? null,
            table_name: $data['table_name'] ?? null,
            columns: $data['columns'] ?? null,
        );
    }

    public function getCreateArray(): array
    {
        Assert::stringNotEmpty($this->user_id);
        Assert::stringNotEmpty($this->table_name);
        Assert::notNull($this->columns);
        Assert::isArray($this->columns);

        return [
            'user_id' => $this->user_id,
            'table_name' => $this->table_name,
            'columns' => $this->columns,
        ];
    }

    public function getUpdateArray(): array
    {
        $data = [];

        if ($this->table_name !== null) {
            Assert::stringNotEmpty($this->table_name);
            $data['table_name'] = $this->table_name;
        }

        if ($this->columns !== null) {
            Assert::isArray($this->columns);
            $data['columns'] = $this->columns;
        }

        return $data;
    }
}
