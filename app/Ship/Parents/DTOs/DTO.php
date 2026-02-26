<?php

namespace App\Ship\Parents\DTOs;

abstract readonly class DTO
{
    /**
     * The default behavior of the DTO is to return the object properties as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
