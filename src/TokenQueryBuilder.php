<?php

namespace Bkremenovic\EloquentTokens;

use Bkremenovic\EloquentTokens\Exceptions\TokenNotFoundException;
use Bkremenovic\EloquentTokens\Exceptions\TraitMissingException;
use Bkremenovic\EloquentTokens\Exceptions\UnsupportedTokenTypeException;
use Bkremenovic\EloquentTokens\Traits\HasEloquentTokens;
use Illuminate\Database\Eloquent\Model;

class TokenQueryBuilder
{
    /**
     * The TokenConfigManager instance
     */
    protected TokenConfigManager $config;

    /**
     * @var Model|null The model to search for token on
     */
    protected ?Model $model = null;

    /**
     * @var ?string The name of the model class to search for token on
     */
    protected ?string $modelClass = null;

    /**
     * @var ?string The type of token to search for
     */
    protected ?string $type = null;

    /**
     * @var ?array Additional data to search for token with
     */
    protected ?array $data = null;

    /**
     * Class constructor.
     *
     * @param TokenConfigManager $config The TokenConfigManager instance.
     */
    public function __construct(TokenConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * Set the model to search for token on.
     *
     * @param  Model  $model  The model to search for token on
     *
     * @return self The TokenQuery instance for method chaining
     */
    public function whereModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the model class to search for token on
     *
     * @param  string  $modelClass  The name of the model class to search for token on
     *
     * @return self The TokenQuery instance for method chaining
     */
    public function whereModelClass(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * Set the type of token to search for
     *
     * @param  string  $type  The type of token to search for
     *
     * @return self The TokenQuery instance for method chaining
     */
    public function whereType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set additional data to use when searching for tokens
     *
     * @param  array  $data  Additional data to use when searching for tokens
     *
     * @return self The TokenQuery instance for method chaining
     */
    public function whereData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Find a TokenInstance by its encrypted token
     *
     * @param string $token The token of the TokenInstance to find
     *
     * @return TokenInstance|null The found TokenInstance, or null if not found
     *
     * @throws TraitMissingException If the target model does not use the HasEloquentTokens trait.
     * @throws UnsupportedTokenTypeException If the token type is not supported by the target model.
     */
    public function find(string $token): ?TokenInstance
    {
        $tokenInstance = null;

        if($this->type && ($this->model || $this->modelClass)) {
            /** @var class-string|HasEloquentTokens $targetModelClass */
            $targetModelClass = $this->model ? get_class($this->model) : $this->modelClass;

            // Check if the model uses the HasEloquentTokens trait
            Helpers::validateTraitUse($targetModelClass);

            // Validate the token type
            Helpers::validateTokenType($targetModelClass, $this->type);
        }

        // Iterate through all drivers of tokens
        foreach ($this->config->getDrivers() as $driver) {
            // Try to find the token instance within the current driver
            $tokenInstance = $driver->find($token, $this->type, $this->modelClass, $this->model, $this->data);

            // If a TokenInstance is found, break out of the loop and return it
            if ($tokenInstance) {
                break;
            }

            // If we don't use all drivers, break out of the loop
            if (!$this->config->isUsingAllDrivers()) {
                break;
            }
        }

        // Return the TokenInstance if found, null otherwise
        return $tokenInstance;
    }

    /**
     * Find a TokenInstance by its encrypted token or throw a NotFoundException
     *
     * @param  string  $token  The token of the TokenInstance to find
     *
     * @return TokenInstance The found TokenInstance
     *
     * @throws TokenNotFoundException If no TokenInstance with the given encrypted token is found.
     * @throws TraitMissingException If the target model does not use the HasEloquentTokens trait.
     * @throws UnsupportedTokenTypeException If the token type is not supported by the target model.
     */
    public function findOrFail(string $token): TokenInstance
    {
        // Execute find() method to find a TokenInstance by its encrypted token
        $tokenInstance = $this->find($token);

        // If the find() method returns null, it means that the token was not found, so we throw a TokenNotFoundException
        if (!$tokenInstance) {
            throw new TokenNotFoundException($token);
        }

        // If the find() method returns a valid TokenInstance, return it as the output of the method
        return $tokenInstance;
    }
}
