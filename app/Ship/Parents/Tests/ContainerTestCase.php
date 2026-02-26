<?php

namespace App\Ship\Parents\Tests;

use Apiato\Core\Testing\TestCase as AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Use this class for the average testcase that can be run on a container.
 * This class will use the database transactions to rollback the database after each test.
 * This class expects the database to be empty, seeded with the initial deployment data and synced with the permissions.
 */
abstract class ContainerTestCase extends AbstractTestCase
{
    use DatabaseTransactions;
}
