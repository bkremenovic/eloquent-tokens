<?php

namespace Bkremenovic\EloquentTokens\Exceptions;

use Exception;
use Throwable;

class ModelClassUnknownException extends Exception
{
    /**
     * The name of the model class that was attempted to be used.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Constructor for ModelClassUnknownException.
     *
     * @param string $modelClass The model class name that is unknown.
     * @param int $code The error code (default is 0).
     * @param Throwable|null $previous The previous throwable used for exception chaining.
     */
    public function __construct(string $modelClass, int $code = 0, Throwable $previous = null)
    {
        $this->modelClass = $modelClass;

        $message = "Model class $modelClass does not exist.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves the model class name that caused the exception.
     *
     * @return string The model class name.
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}
