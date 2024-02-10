<?php

namespace Bkremenovic\EloquentTokens\Tests;

use Bkremenovic\EloquentTokens\Drivers\StatelessTokenDriver;
use Bkremenovic\EloquentTokens\Facades\Token;
use Bkremenovic\EloquentTokens\TokenInstance;

class StatelessTokenDriverTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('eloquent-tokens.drivers.supported', [
            'stateless' => StatelessTokenDriver::class
        ]);

        $app['config']->set('eloquent-tokens.drivers.default', 'stateless');
    }

    public function test_find_token()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());

        $notFound = Token::find('nonexistentToken');
        $this->assertNull($notFound);
    }

    public function test_find_token_by_type()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::whereType("INVITE_TOKEN")->find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());

        $notFound = Token::whereType("INVALID_TYPE")->find($token->getToken());
        $this->assertNull($notFound);
    }

    public function test_find_token_by_model_class()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::whereModelClass(Company::class)->find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());

        $notFound = Token::whereModelClass(Project::class)->find($token->getToken());
        $this->assertNull($notFound);
    }

    public function test_find_token_by_model()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::whereModel($this->testCompany)->find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());

        $notFound = Token::whereModel($this->testProject1)->find($token->getToken());
        $this->assertNull($notFound);
    }

    public function test_find_token_by_data()
    {
        $tokenData = ['role' => 'admin'];

        $token = Token::create($this->testCompany, "INVITE_TOKEN", null, $tokenData);
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::whereData($tokenData)->find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());

        $invalidTokenData = ['role' => 'user'];

        $notFound = Token::whereData($invalidTokenData)->find($token->getToken());
        $this->assertNull($notFound);
    }

    public function test_find_token_by_multiple_criteria()
    {
        $tokenData = ['role' => 'admin'];

        $token = Token::create($this->testCompany, "INVITE_TOKEN", null, $tokenData);
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::whereModel($this->testCompany)->whereType("INVITE_TOKEN")->whereData($tokenData)->find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());
    }

    public function test_find_expired_token()
    {
        $token = Token::create($this->testCompany, "INVITE_TOKEN", '5 minutes');
        $this->assertNotNull($token);
        $this->assertInstanceOf(TokenInstance::class, $token);

        $found = Token::find($token->getToken());
        $this->assertSame($token->getId(), $found->getId());

        $this->travel(1)->hour();

        $notFound = Token::find($token->getToken());
        $this->assertNull($notFound);
    }

    public function test_delete_all_tokens()
    {
        $token1 = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token1);
        $this->assertInstanceOf(TokenInstance::class, $token1);

        $found = Token::find($token1->getToken());
        $this->assertSame($token1->getId(), $found->getId());

        $token2 = Token::create($this->testCompany, "ACCESS_TOKEN");
        $this->assertNotNull($token2);
        $this->assertInstanceOf(TokenInstance::class, $token2);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());

        $this->assertDatabaseCount($this->tokenBlacklistTable, 0);
        Token::forceDeleteAll();
        $this->assertDatabaseCount($this->tokenBlacklistTable, 1);

        $notFound = Token::find($token1->getToken());
        $this->assertNull($notFound);

        $notFound = Token::find($token2->getToken());
        $this->assertNull($notFound);
    }

    public function test_delete_token_by_model()
    {
        $token1 = Token::create($this->testProject1, "ACCESS_TOKEN");
        $this->assertNotNull($token1);
        $this->assertInstanceOf(TokenInstance::class, $token1);

        $found = Token::find($token1->getToken());
        $this->assertSame($token1->getId(), $found->getId());

        $token2 = Token::create($this->testProject2, "ACCESS_TOKEN");
        $this->assertNotNull($token2);
        $this->assertInstanceOf(TokenInstance::class, $token2);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());

        $this->assertDatabaseCount($this->tokenBlacklistTable, 0);
        Token::deleteBy($this->testProject1);
        $this->assertDatabaseCount($this->tokenBlacklistTable, 1);

        $notFound = Token::find($token1->getToken());
        $this->assertNull($notFound);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());
    }

    public function test_delete_token_by_model_class()
    {
        $token1 = Token::create($this->testCompany, "ACCESS_TOKEN");
        $this->assertNotNull($token1);
        $this->assertInstanceOf(TokenInstance::class, $token1);

        $found = Token::find($token1->getToken());
        $this->assertSame($token1->getId(), $found->getId());

        $token2 = Token::create($this->testProject1, "ACCESS_TOKEN");
        $this->assertNotNull($token2);
        $this->assertInstanceOf(TokenInstance::class, $token2);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());

        $this->assertDatabaseCount($this->tokenBlacklistTable, 0);
        Token::deleteBy(null, Company::class);
        $this->assertDatabaseCount($this->tokenBlacklistTable, 1);

        $notFound = Token::find($token1->getToken());
        $this->assertNull($notFound);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());
    }

    public function test_delete_token_by_type()
    {
        $token1 = Token::create($this->testCompany, "ACCESS_TOKEN");
        $this->assertNotNull($token1);
        $this->assertInstanceOf(TokenInstance::class, $token1);

        $found = Token::find($token1->getToken());
        $this->assertSame($token1->getId(), $found->getId());

        $token2 = Token::create($this->testCompany, "INVITE_TOKEN");
        $this->assertNotNull($token2);
        $this->assertInstanceOf(TokenInstance::class, $token2);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());

        $this->assertDatabaseCount($this->tokenBlacklistTable, 0);
        Token::deleteBy(null, null, "ACCESS_TOKEN");
        $this->assertDatabaseCount($this->tokenBlacklistTable, 1);

        $notFound = Token::find($token1->getToken());
        $this->assertNull($notFound);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());
    }

    public function test_delete_token_by_id()
    {
        $token1 = Token::create($this->testCompany, "ACCESS_TOKEN");
        $this->assertNotNull($token1);
        $this->assertInstanceOf(TokenInstance::class, $token1);

        $found = Token::find($token1->getToken());
        $this->assertSame($token1->getId(), $found->getId());

        $token2 = Token::create($this->testCompany, "ACCESS_TOKEN");
        $this->assertNotNull($token2);
        $this->assertInstanceOf(TokenInstance::class, $token2);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());

        $this->assertDatabaseCount($this->tokenBlacklistTable, 0);
        Token::deleteBy(null, null, null, $token1->getId());
        $this->assertDatabaseCount($this->tokenBlacklistTable, 1);

        $notFound = Token::find($token1->getToken());
        $this->assertNull($notFound);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());
    }

    public function test_delete_token_by_data()
    {
        $token1 = Token::create($this->testCompany, "ACCESS_TOKEN", null, ['role' => 'admin']);
        $this->assertNotNull($token1);
        $this->assertInstanceOf(TokenInstance::class, $token1);

        $found = Token::find($token1->getToken());
        $this->assertSame($token1->getId(), $found->getId());

        $token2 = Token::create($this->testCompany, "ACCESS_TOKEN", null, ['role' => 'user']);
        $this->assertNotNull($token2);
        $this->assertInstanceOf(TokenInstance::class, $token2);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());

        $this->assertDatabaseCount($this->tokenBlacklistTable, 0);
        Token::deleteBy($this->testCompany, null, null, null, ['role' => 'admin']);
        $this->assertDatabaseCount($this->tokenBlacklistTable, 1);

        $notFound = Token::find($token1->getToken());
        $this->assertNull($notFound);

        $found = Token::find($token2->getToken());
        $this->assertSame($token2->getId(), $found->getId());
    }
}
