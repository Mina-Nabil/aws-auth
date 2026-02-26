<?php

namespace App\Ship\Criteria;

use App\Ship\Parents\Criteria\Criteria as ParentCriteria;
use Illuminate\Support\Facades\Date;
use Prettus\Repository\Contracts\RepositoryInterface as PrettusRepositoryInterface;

class CreatedTodayCriteria extends ParentCriteria
{
    public function apply($model, PrettusRepositoryInterface $repository)
    {
        return $model->whereBetween(
            'created_at',
            [Date::today()->format('Y-m-d 00:00:00'),
        Date::today()->format('Y-m-d 23:59:59')]
        );
    }
}
