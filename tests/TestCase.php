<?php

namespace Tests;

use App\Models\ClinicSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.iprogsms.api_token' => null,
        ]);

        // The in-memory settings cache is static; reset it so DB rollbacks between tests never leak stale values.
        ClinicSetting::clearCache();
    }
}
