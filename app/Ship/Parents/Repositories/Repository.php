<?php

namespace App\Ship\Parents\Repositories;

use Apiato\Core\Repositories\Repository as AbstractRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends AbstractRepository<TModel>
 */
abstract class Repository extends AbstractRepository
{
    /**
     * Extend this method to add custom query logic to the repository,
     * child classes can override this method with their own specific parameter signatures, use scopeQuery to build the query.
     *
     * @return self The repository instance.
     */
    public function loadQuery(): self
    {
        return $this;
    }
}
