<?php

namespace Bkremenovic\EloquentTokens\Drivers;

use Bkremenovic\EloquentTokens\Interfaces\TokenDriverInterface;
use Bkremenovic\EloquentTokens\TokenConfigManager;
use Bkremenovic\EloquentTokens\TokenInstance;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseTokenDriver implements TokenDriverInterface
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
    public function find(string $token, string $type = null, string $modelClass = null, Model $model = null, array $data = null): ?TokenInstance
    {
        /**
         * Query the 'tokens' table in the database to find the matching token
         *
         * @var object{id: mixed, model_class: class-string, model_id: int, type: string, created_at: DateTime, expires_at: ?DateTime, data: string} $tokenResult
         */
        $tokenResult = $this->tokensQuery()
            ->where('token', $token)
            ->where(function($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->whereNull('deleted_at')
            ->when($model, function ($query) use ($model) {
                $query->where('model_class', get_class($model))->where('model_id', $model->getKey());
            })
            ->when($modelClass && !$model, function ($query) use ($modelClass) {
                $query->where('model_class', $modelClass);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($data, function ($query) use ($data) {
                $query->where('data', json_encode($data));
            })
            ->select([
                'id',
                'model_class',
                'model_id',
                'type',
                'created_at',
                'expires_at',
                'data',
            ])
            ->first();

        // If no token is found, return null
        if (!$tokenResult) {
            return null;
        }

        // Get token data from a found database result
        $tokenId = $tokenResult->id;
        $tokenModelClass = $tokenResult->model_class;
        $tokenModelId = $tokenResult->model_id;
        $tokenType = $tokenResult->type;
        $tokenCreatedAt = Carbon::parse($tokenResult->created_at);
        $tokenExpiresAt = $tokenResult->expires_at ? Carbon::parse($tokenResult->expires_at) : null;
        $tokenData = $tokenResult->data ? json_decode($tokenResult->data, true) : [];

        // Create a new TokenInstance object and return it
        return new TokenInstance(
            static::class,
            $tokenId,
            $tokenModelClass,
            $tokenModelId,
            $tokenType,
            $tokenCreatedAt,
            $tokenExpiresAt,
            $tokenData,
            $token,
        );
    }

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
    public function create(Model $model, string $type, Carbon|string $expires = null, array $data = []): TokenInstance
    {
        // Generates a random string with 128 characters to use as token
        $token = Str::random(128);

        // Get class and id from the $model argument
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        // Set creation timestamp and calculate expiration timestamp
        $createdAt = Carbon::now()->startOfSecond();

        // Convert string expiration time to Carbon
        if($expires && is_string($expires)) {
            $expires = $createdAt->clone()->add($expires);
        }

        // Inserts a new token into the tokens table in the database
        $tokenId = $this->tokensQuery()->insertGetId([
            'token' => $token,
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'type' => $type,
            'data' => $data ? json_encode($data) : null,
            'expires_at' => $expires,
            'created_at' => $createdAt,
        ]);

        // Create a new TokenInstance object and return it
        return new TokenInstance(
            static::class,
            $tokenId,
            $modelClass,
            $modelId,
            $type,
            $createdAt,
            $expires,
            $data,
            $token,
        );
    }

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
    public function deleteBy(Model $model = null, string $modelClass = null, string $type = null, string $id = null, array $data = null): void
    {
        // Get current time
        $currentTime = Carbon::now()->startOfSecond();

        $this->tokensQuery()
            ->where(function($query) use ($currentTime) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $currentTime);
            })
            ->whereNull('deleted_at')
            ->when($model, function ($query) use ($model) {
                $query->where('model_class', get_class($model))->where('model_id', $model->getKey());
            })
            ->when($modelClass && !$model, function ($query) use ($modelClass) {
                $query->where('model_class', $modelClass);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($id, function ($query) use ($id) {
                $query->where('id', $id);
            })
            ->when(!is_null($data), function ($query) use ($data) {
                $query->where('data', json_encode($data));
            })
            ->update([
                'deleted_at' => $currentTime,
            ]);
    }

    /**
     * Force deletes all tokens.
     *
     * @return void
     */
    public function forceDeleteAll(): void
    {
        $this->tokensQuery()
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => Carbon::now()->startOfSecond()
            ]);
    }

    /**
     * Get the query builder for the tokens table.
     *
     * @return Builder The query builder instance.
     */
    protected function tokensQuery(): Builder
    {
        /** @var TokenConfigManager $config */
        $config = app(TokenConfigManager::class);

        return DB::table($config->getTokensTable());
    }
}
