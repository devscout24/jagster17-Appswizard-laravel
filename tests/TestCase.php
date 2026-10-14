<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Portal routes are guarded by role:customer / role:business on the api guard.
        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            foreach (['customer', 'business'] as $role) {
                Role::findOrCreate($role, 'api');
            }
        }
    }
}
