<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Always seed baseline data when tests refresh the database.
     */
    protected string $seeder = DatabaseSeeder::class;
}
