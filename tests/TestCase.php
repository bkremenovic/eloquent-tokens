<?php

namespace Bkremenovic\EloquentTokens\Tests;

use Bkremenovic\EloquentTokens\EloquentTokensServiceProvider;
use Bkremenovic\EloquentTokens\TokenConfigManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected Company $testCompany;
    protected Project $testProject1;
    protected Project $testProject2;

    protected string $tokenBlacklistTable;

    /**
     * Retrieves package providers.
     *
     * @param Application $app - Laravel application instance.
     *
     * @return array - Returns an array of service providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            EloquentTokensServiceProvider::class,
        ];
    }

    /**
     * Sets up the tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        // Calling parent setup method
        parent::setUp();

        // Setting up the database
        $this->setUpDatabase($this->app);
        $this->setUpMigrations();

        // Setting up the token blacklist table
        $this->tokenBlacklistTable = $this->app->make(TokenConfigManager::class)->getTokenBlacklistTable();
    }

    /**
     * Sets up the database for tests.
     *
     * @param Application $app - Laravel application instance.
     * @return void
     */
    protected function setUpDatabase(Application $app): void
    {
        // Getting the database schema builder
        $schema = $app['db']->connection()->getSchemaBuilder();

        // Creating 'companies' table
        $schema->create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        // Creating 'projects' table
        $schema->create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        // Creating test data for the tests
        $this->testCompany = Company::create(["name" => "Boris's awesome company"]);
        $this->testProject1 = Project::create(["name" => "A token project"]);
        $this->testProject2 = Project::create(["name" => "Another token project"]);
    }

    /**
     * Sets up the migrations.
     *
     * @return void
     */
    protected function setUpMigrations(): void
    {
        // Running 'create_token_blacklists_table' migrations
        $migration = require __DIR__.'/../database/migrations/create_token_blacklists_table.php.stub';
        $migration->up();

        // Running 'create_tokens_table' migrations
        $migration = require __DIR__.'/../database/migrations/create_tokens_table.php.stub';
        $migration->up();
    }
}
