<?php

namespace Bkremenovic\EloquentTokens\Drivers;

use Bkremenovic\EloquentTokens\Exceptions\ModelClassUnknownException;
use Bkremenovic\EloquentTokens\Exceptions\TraitMissingException;
use Bkremenovic\EloquentTokens\Interfaces\TokenDriverInterface;
use Bkremenovic\EloquentTokens\TokenConfigManager;
use Bkremenovic\EloquentTokens\TokenInstance;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatelessTokenDriver implements TokenDriverInterface
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
     * @throws ModelClassUnknownException If the specified model class does not exist or is not a valid model class.
     * @throws TraitMissingException If the trait is not used in model class.
     */
    public function find(string $token, string $type = null, string $modelClass = null, Model $model = null, array $data = null): ?TokenInstance
    {
        try {
            // Attempt to decrypt the token data for its details
            $plainData = $this->decryptData($token);
        } catch (DecryptException) {
            // When a DecryptException occurs because of an invalid token, return null
            return null;
        }

        // Destructure the $plainData array
        $tokenUuid = $plainData[0];
        $tokenModelClass = $plainData[1];
        $tokenModelId = $plainData[2];
        $tokenType = $plainData[3];
        $tokenCreatedAt = Carbon::parse($plainData[4]);
        $tokenExpiresAt = $plainData[5] ? Carbon::parse($plainData[5]) : null;
        $tokenData = $plainData[6] ?: [];

        // Check if the token has expired
        if ($tokenExpiresAt && Carbon::now()->startOfSecond()->isAfter($tokenExpiresAt)) {
            return null;
        }

        // Check if the token type matches the given type
        if ($type && $tokenType !== $type) {
            return null;
        }

        // Check if the token model class matches the given model class
        if ($modelClass && $tokenModelClass !== $modelClass) {
            return null;
        }

        // Check if the token model matches the given model instance
        if ($model && ($tokenModelClass !== get_class($model) || $tokenModelId !== $model->getKey())) {
            return null;
        }

        // Check if the token data matches the given data
        if ($data && $tokenData !== $data) {
            return null;
        }

        // Create a new TokenInstance
        $tokenInstance = new TokenInstance(
            static::class,
            $tokenUuid,
            $tokenModelClass,
            $tokenModelId,
            $tokenType,
            $tokenCreatedAt,
            $tokenExpiresAt,
            $tokenData,
            $token,
        );

        // Check if the TokenInstance is blacklisted
        if ($this->isTokenBlacklisted($tokenInstance)) {
            return null;
        }

        // Return the TokenInstance object
        return $tokenInstance;
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
        // Get class and id from the $model argument
        $tokenModelClass = get_class($model);
        $tokenModelId = $model->getKey();

        // Set creation timestamp and calculate expiration timestamp
        $createdAt = Carbon::now()->startOfSecond();

        // Convert string expiration time to Carbon
        if($expires && is_string($expires)) {
            $expires = $createdAt->clone()->add($expires);
        }

        // Generate a unique token ID
        $uuid = (string) Str::orderedUuid();

        // Prepare the token data
        $plainData = [
            $uuid,
            $tokenModelClass,
            $tokenModelId,
            $type,
            $createdAt->timestamp,
            $expires?->timestamp,
            $data ?: null,
        ];

        // Encrypt the data array to create the token string
        $token = $this->encryptData($plainData);

        // Create a new TokenInstance object and return it
        return new TokenInstance(
            static::class,
            $uuid,
            $tokenModelClass,
            $tokenModelId,
            $type,
            $createdAt,
            $expires,
            $data,
            $token
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
        // Get the model class and id from the $model argument, or use the $modelClass argument if $model is null
        $modelClass = ($model ? get_class($model) : null) ?? $modelClass ?? null;
        $modelId = $model?->getKey();

        // Create a new TokenBlacklist with the specified arguments and variables
        $this->blacklistQuery()->insert([
            'uuid' => $id,
            'model_class' => $modelClass,
            'model_id' => $modelId,
            'type' => $type,
            'data' => $data ? json_encode($data) : null,
        ]);
    }

    /**
     * Force deletes all tokens.
     *
     * @return void
     */
    public function forceDeleteAll(): void
    {
        $this->blacklistQuery()->insert([
            // Since Laravel does not allow calling insert() with an empty array,
            // This is a workaround to insert a blank row
            "id" => null
        ]);
    }

    /**
     * JSON-encode, GZ compress and encrypt an array of data
     *
     * @param array $data The data to encrypt
     *
     * @return string The encrypted data as a string
     */
    protected function encryptData(array $data): string
    {
        return encrypt(gzdeflate(json_encode($data)), false);
    }

    /**
     * Decrypt, GZ decode and JSON-decode an encrypted data string and return an array
     *
     * @param string $token The encrypted data string
     *
     * @return array The decrypted data as an array
     */
    protected function decryptData(string $token): array
    {
        return json_decode(gzinflate(decrypt($token, false)), true);
    }

    /**
     * Check whether a given token instance is blacklisted or not.
     *
     * @param TokenInstance $tokenInstance The token instance to check
     *
     * @return bool Returns true if the given token instance is blacklisted, false otherwise
     * @throws ModelClassUnknownException If the specified model class does not exist or is not a valid model class.
     * @throws TraitMissingException If the trait is not used in model class.
     */
    protected function isTokenBlacklisted(TokenInstance $tokenInstance): bool
    {
        $query = $this->blacklistQuery()
            ->where('blacklisted_at', '>=', $tokenInstance->getCreatedAt()) // Apply blacklist only to tokens created before the blacklist date
            ->where(function ($query) use ($tokenInstance) {
                // Match with the given model class, if present in the blacklist
                $query->where('model_class', $tokenInstance->getModelClass())->orWhereNull('model_class');
            })
            ->where(function ($query) use ($tokenInstance) {
                // Match with the given model class and model ID, if present in the blacklist
                $query->where(function ($query) use ($tokenInstance) {
                    $query->where('model_class', $tokenInstance->getModelClass())->where('model_id', $tokenInstance->getModelId());
                })->orWhereNull('model_id');
            })
            ->where(function ($query) use ($tokenInstance) {
                // Match with the given type, if present in the blacklist
                $query->where('type', $tokenInstance->getType())->orWhereNull('type');
            })
            ->where(function ($query) use ($tokenInstance) {
                // Match with the given uuid, if present in the blacklist
                $query->where('uuid', $tokenInstance->getId())->orWhereNull('uuid');
            })
            ->where(function ($query) use ($tokenInstance) {
                // Match with the given data, if present in the blacklist
                $query->where('data', json_encode($tokenInstance->getData()))->orWhereNull('data');
            })
            ->select('id');

        return $query->exists();
    }

    /**
     * Get the query builder for the token blacklist table.
     *
     * @return Builder The query builder instance.
     */
    protected function blacklistQuery(): Builder
    {
        /** @var TokenConfigManager $config */
        $config = app(TokenConfigManager::class);

        return DB::table($config->getTokenBlacklistTable());
    }
}
