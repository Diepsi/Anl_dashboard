<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        // The reporting queries are written against MySQL, but tests run on in-memory
        // SQLite. DATEDIFF is the only function SQLite lacks, so emulate it to keep the
        // real queries under test instead of rewriting them per database driver.
        $connection->getPdo()->sqliteCreateFunction('DATEDIFF', function ($left, $right) {
            if (empty($left) || empty($right)) {
                return null;
            }

            $leftTime = strtotime((string) $left);
            $rightTime = strtotime((string) $right);

            if ($leftTime === false || $rightTime === false) {
                return null;
            }

            // MySQL compares calendar dates only, so drop the time component.
            $leftDay = strtotime(date('Y-m-d', $leftTime));
            $rightDay = strtotime(date('Y-m-d', $rightTime));

            return (int) round(($leftDay - $rightDay) / 86400);
        }, 2);
    }
}
