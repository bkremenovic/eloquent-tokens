<?php

namespace Bkremenovic\EloquentTokens\Tests;

use Bkremenovic\EloquentTokens\Drivers\DatabaseTokenDriver;
use Bkremenovic\EloquentTokens\Drivers\StatelessTokenDriver;
use Bkremenovic\EloquentTokens\Exceptions\TokenNotFoundException;
use Bkremenovic\EloquentTokens\Exceptions\UnsupportedDriverException;
use Bkremenovic\EloquentTokens\Facades\Token;
use Bkremenovic\EloquentTokens\TokenInstance;

class TokenTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('eloquent-tokens.drivers.supported', [
            'stateless' => StatelessTokenDriver::class,
            'database' => DatabaseTokenDriver::class,
        ]);

        $app['config']->set('eloquent-tokens.drivers.default', 'stateless');

        $app['config']->set('eloquent-tokens.use_all_drivers', true);
    }

    public function test_create_token()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);
    }

    public function test_create_token_with_unsupported_driver()
    {
        $this->expectException(UnsupportedDriverException::class);
        Token::create($this->testCompany, "INVITE_TOKEN", null, [], 'unsupported');
    }

    public function test_find_existing_token()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $found = Token::find($token->getToken());
        $this->assertEquals($token, $found);
    }

    public function test_find_nonexistent_token()
    {
        $found = Token::find('nonexistentToken');
        $this->assertNull($found);
    }

    public function test_find_or_fail_existing_token()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $found = Token::findOrFail($token->getToken());
        $this->assertEquals($token, $found);
    }

    public function test_find_or_fail_nonexistent_token()
    {
        $this->expectException(TokenNotFoundException::class);
        Token::findOrFail('nonexistentToken');
    }

    public function test_find_using_multiple_drivers()
    {
        Token::create($this->testCompany, "INVITE_TOKEN", null, [], 'stateless');
        $token2 = Token::create($this->testCompany, "INVITE_TOKEN", null, [], 'database');

        $found = Token::find($token2->getToken());
        $this->assertEquals($token2, $found);
    }
}
