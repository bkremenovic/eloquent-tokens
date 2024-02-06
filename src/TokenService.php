<?php

namespace Bkremenovic\EloquentTokens;

use ArgumentCountError;
use BadFunctionCallException;
use Bkremenovic\EloquentTokens\Exceptions\ModelClassUnknownException;
use Bkremenovic\EloquentTokens\Exceptions\TraitMissingException;
use Bkremenovic\EloquentTokens\Exceptions\UnsupportedDriverException;
use Bkremenovic\EloquentTokens\Exceptions\UnsupportedTokenTypeException;
use Bkremenovic\EloquentTokens\Traits\HasEloquentTokens;
use Carbon\Carbon;
use Error;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @method TokenQueryBuilder whereModel(Model $model) Specify the model to search for the token.
 * @method TokenQueryBuilder whereModelClass(string $modelClass) Specify the model class to search for the token.
 * @method TokenQueryBuilder whereType(string $type) Specify the type of token to search for.
 * @method TokenQueryBuilder whereData(array $data) Specify additional data to search for the token.
 * @method TokenInstance|null find(string $token) Find a token instance by its token value.
 * @method TokenInstance findOrFail(string $token) Find a token instance by its token value or throw an exception if not found.
 */
class TokenService
{
    /**
     * The TokenConfigManager instance
     */
    protected TokenConfigManager $config;

    /**
     * Constructor for the TokenService
     *
     * @param TokenConfigManager $config Token configuration manager
     */
    public function __construct(TokenConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * Magic method to handle dynamic method calls
     *
     * Handles method calls to the TokenQueryBuilder class
     * using dynamic method names.
     *
     * @param string $methodName The name of the method to call
     * @param array $arguments The arguments to pass to the method
     *
     * @return mixed The result of the method call
     *
     * @throws Error If the method name is not a valid method of TokenQueryBuilder
     */
    public function __call(string $methodName, array $arguments)
    {
        // Retrieve a list of allowed methods for the TokenQueryBuilder class
        $allowedMethods = get_class_methods(TokenQueryBuilder::class);

        // Verify if the called method is allowed, and throw an error if not
        if (!in_array($methodName, $allowedMethods)) {
            throw new Error(sprintf("Call to undefined method %s::%s()", static::class, $methodName));
        }

        // Instantiate a new TokenQueryBuilder and pass the arguments to the method
        $tokenQuery = new TokenQueryBuilder($this->config);
        return $tokenQuery->$methodName(...$arguments);
    }

    /**
     * Create a new token instance
     *
     * @param Model $model The Eloquent model to associate with the token.
     * @param string $type Type of the token being created.
     * @param Carbon|string|null $expires Expiration time of the token, either as a Carbon instance or string.
     * @param array $data Additional data to include with the token.
     * @param string|null $driver Name of the driver for token creation; uses default if null.
     *
     * @return TokenInstance The created token instance
     *
     * @throws UnsupportedDriverException If the specified driver is not supported
     * @throws TraitMissingException If the trait is missing in the model
     * @throws UnsupportedTokenTypeException If the token type is unsupported
     */
    public function create(Model $model, string $type, Carbon|string $expires = null, array $data = [], string $driver = null): TokenInstance
    {
        // Check if the model uses the HasEloquentTokens trait
        Helpers::validateTraitUse(get_class($model));

        // Validate the token type
        Helpers::validateTokenType(get_class($model), $type);

        // Get the default driver instance
        $driverInstance = $this->config->getDefaultDriver();

        // If a specific driver is requested
        if($driver) {
            // Attempt to get the specified driver instance, fallback to null if not found
            $driverInstance = $this->config->getDrivers()[$driver] ?? null;

            // If the specified driver does not exist, throw an exception
            if(!$driverInstance) {
                throw new UnsupportedDriverException($driver);
            }
        }

        // Create and return a new TokenInstance
        return $driverInstance->create($model, $type, $expires, $data);
    }

    /**
     * Delete a token by various criteria
     *
     * @param Model|null $model Model instance
     * @param string|null $modelClass Model class
     * @param string|null $type Token type
     * @param string|null $id Token ID
     * @param array|null $data Additional data
     *
     * @throws ArgumentCountError If no arguments are provided, or only $data is provided without any context.
     * @throws BadFunctionCallException If $data is provided without at least one other argument to provide context for the deletion.
     * @throws InvalidArgumentException If both $model and $modelClass are provided but do not refer to the same model.
     * @throws TraitMissingException If the trait is missing in the model specified by either $model or $modelClass.
     * @throws UnsupportedTokenTypeException If the specified token type is unsupported by the model.
     * @throws ModelClassUnknownException If the specified model class does not exist or is not a valid model class.
     */
    public function deleteBy(Model $model = null, string $modelClass = null, string $type = null, string $id = null, array $data = null): void
    {
        // Validate the provided arguments
        if (is_null($model) && is_null($modelClass) && is_null($type) && is_null($id)) {
            if (is_null($data)) {
                throw new ArgumentCountError(sprintf("Too few arguments to function %s(), 0 passed and at least 1 expected", __METHOD__));
            } else {
                throw new BadFunctionCallException('To use the $data argument, include at least one more argument');
            }
        }

        // Ensure consistency between model and modelClass arguments
        if ($model && $modelClass && get_class($model) !== $modelClass) {
            throw new InvalidArgumentException('If both $model and $modelClass arguments are specified, they should relate to the same model class name');
        }

        if($model || $modelClass) {
            /** @var class-string|HasEloquentTokens $targetModelClass */
            $targetModelClass = $model ? get_class($model) : $modelClass;

            // Check if the class exists and is an instance of Model
            Helpers::validateModelClass($targetModelClass);

            // Check if the model uses the HasEloquentTokens trait
            Helpers::validateTraitUse($targetModelClass);

            // Validate the token type, if present
            if($type) {
                $type && Helpers::validateTokenType($targetModelClass, $type);
            }
        }

        // Execute token deletion logic within a database transaction
        DB::transaction(function () use ($data, $id, $type, $modelClass, $model) {
            foreach ($this->config->getDrivers() as $driver) {
                $driver->deleteBy($model, $modelClass, $type, $id, $data);
            }
        });
    }

    /**
     * Force delete all TokenInstance's
     *
     * This method force deletes (or blacklists) all tokens.
     */
    public function forceDeleteAll(): void
    {
        // Execute force deletion logic within a database transaction
        DB::transaction(function () {
            foreach ($this->config->getDrivers() as $driver) {
                $driver->forceDeleteAll();
            }
        });
    }
}
