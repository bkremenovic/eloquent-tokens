<?php

namespace Bkremenovic\EloquentTokens\Facades;

use Bkremenovic\EloquentTokens\TokenInstance;
use Bkremenovic\EloquentTokens\TokenQueryBuilder;
use Bkremenovic\EloquentTokens\TokenService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Token Facade provides a static interface to token management services.
 * Allows creating, querying, finding and deletion of tokens associated with Eloquent models.
 *
 * @method static TokenQueryBuilder whereModel(Model $model) Specify the model to search for the token.
 * @method static TokenQueryBuilder whereModelClass(string $modelClass) Specify the model class to search for the token.
 * @method static TokenQueryBuilder whereType(string $type) Specify the type of token to search for.
 * @method static TokenQueryBuilder whereData(array $data) Specify additional data to search for the token.
 *
 * @method static TokenInstance|null find(string $token) Find a token instance by its token value.
 * @method static TokenInstance findOrFail(string $token) Find a token instance by its token value or throw an exception if not found.
 *
 * @method static TokenInstance create(Model $model, string $type, Carbon|string $expires = null, array $data = [], string $driver = null) Create a new token instance.
 * @method static void deleteBy(Model $model = null, string $modelClass = null, string $type = null, string $id = null, array $data = null) Delete a token by various criteria.
 * @method static void forceDeleteAll() Force delete all tokens
 */
class Token extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return TokenService::class;
    }
}
