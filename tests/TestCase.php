<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        if (env('DB_CONNECTION') === 'sqlite') {
            if (!file_exists($path = env('DB_DATABASE'))) {
                touch($path);
            }
        }

        parent::setUp();
    }
}
