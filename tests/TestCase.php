<?php

namespace Mrclln\MassMailer\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Mrclln\MassMailer\Providers\MassMailerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MassMailerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup mail configuration
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', [
            'transport' => 'array',
        ]);

        // Setup queue configuration
        $app['config']->set('queue.default', 'sync');
    }
}
