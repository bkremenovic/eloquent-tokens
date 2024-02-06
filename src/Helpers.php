<?php

namespace Bkremenovic\EloquentTokens;

use Bkremenovic\EloquentTokens\Exceptions\ModelClassUnknownException;
use Bkremenovic\EloquentTokens\Exceptions\TraitMissingException;
use Bkremenovic\EloquentTokens\Exceptions\UnsupportedDriverException;
use Bkremenovic\EloquentTokens\Exceptions\UnsupportedTokenTypeException;
use Bkremenovic\EloquentTokens\Interfaces\TokenDriverInterface;
use Bkremenovic\EloquentTokens\Traits\HasEloquentTokens;
use Illuminate\Database\Eloquent\Model;

class Helpers
{
    /**
     * Validates if passed class uses the HasEloquentTokens trait.
     * Throws an exception if this is not the case.
     *
     * @param string $modelClass Class name to check for trait usage.
     *
     * @throws TraitMissingException If the trait is not used in model class.
     */
    public static function validateTraitUse(string $modelClass): void
    {
        // If the model class doesn't use the HasEloquentTokens trait, throw an exception
        if (!in_array(HasEloquentTokens::class, class_uses_recursive($modelClass))) {
            throw new TraitMissingException($modelClass, HasEloquentTokens::class);
        }
    }

    /**
     * Validates if passed token type is allowed for the passed class.
     * Throws an exception if this is not the case.
     *
     * @param string $modelClass Class name to check for token type support.
     * @param string $type Token type to validate.
     *
     * @throws UnsupportedTokenTypeException If the token type is not supported in model class.
     */
    public static function validateTokenType(string $modelClass, string $type): void
    {
        // If the passed class doesn't exist or doesn't have 'getAllowedTokenTypes' method, do nothing
        if(!class_exists($modelClass) || !method_exists($modelClass, 'getAllowedTokenTypes')) {
            return;
        }

        // If the given token type is not in the array of allowed types, throw an exception
        if (!in_array($type, $modelClass::getAllowedTokenTypes())) {
            throw new UnsupportedTokenTypeException($type, $modelClass);
        }
    }

    /**
     * Validates if the passed class name is a valid model class.
     * It checks if the class exists and if it is a subclass of the Model class.
     *
     * Throws an exception if these conditions are not met.
     *
     * @param string|null $modelClass Class name to validate as a model class.
     *
     * @throws ModelClassUnknownException If the class does not exist or is not a subclass of Model.
     */
    public static function validateModelClass(string $modelClass = null): void
    {
        // Check if the passed class name exists and is a subclass of the Model class
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw new ModelClassUnknownException($modelClass);
        }
    }

    /**
     * Initializes and returns an instance of the specified token driver class.
     * This method ensures that the driver class exists and implements the TokenDriverInterface.
     *
     * @param string $driverClassName The fully qualified class name of the token driver to initialize.
     *
     * @return TokenDriverInterface An instance of the specified token driver class.
     *
     * @throws UnsupportedDriverException If the driver class does not exist or does not implement the TokenDriverInterface.
     */
    public static function initializeDriver(string $driverClassName): TokenDriverInterface
    {
        // Check if the driver class exists
        if (!class_exists($driverClassName)) {
            throw new UnsupportedDriverException($driverClassName);
        }

        // Create an instance of the driver class
        $driver = new $driverClassName();

        // Ensure the driver implements the required interface
        if (!$driver instanceof TokenDriverInterface) {
            throw new UnsupportedDriverException($driverClassName);
        }

        return $driver;
    }
}
