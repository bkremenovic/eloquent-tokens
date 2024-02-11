<?php

namespace Bkremenovic\EloquentTokens\Interfaces;

use Bkremenovic\EloquentTokens\TokenInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

interface TokenDriverInterface
{
    /**
     * Finds a token based on various parameters.
     *
     * @param string $token The token to find.
     * @param string|null $type Optional token type.
     * @param string|null $modelClass Optional model class associated with the token.
     * @param Model|null $model Optional model instance associated with the token.
     * @param array|null $data Optional additional data associated with the token.
     *
     * @return TokenInstance|null Token instance if found, otherwise null.
     */
    public function find(string $token, string $type = null, string $modelClass = null, Model $model = null, array $data = null): ?TokenInstance;

    /**
     * Creates a new token instance.
     *
     * @param Model $model The Eloquent model to associate with the token.
     * @param string $type Type of the token being created.
     * @param Carbon|string|null $expires Expiration time of the token, either as a Carbon instance or string.
     * @param array $data Additional data to include with the token.
     *
     * @return TokenInstance The newly created token instance.
     */
    public function create(Model $model, string $type, Carbon|string $expires = null, array $data = []): TokenInstance;

    /**
     * Deletes a token based on various parameters.
     *
     * @param Model|null $model Optional model instance associated with the token to be deleted.
     * @param string|null $modelClass Optional model class associated with the token.
     * @param string|null $type Optional token type.
     * @param string|null $id Optional token ID.
     * @param array|null $data Optional data associated with the token.
     *
     * @return void
     */
    public function deleteBy(Model $model = null, string $modelClass = null, string $type = null, string $id = null, array $data = null): void;

    /**
     * Force deletes all tokens.
     *
     * @return void
     */
    public function forceDeleteAll(): void;
}
