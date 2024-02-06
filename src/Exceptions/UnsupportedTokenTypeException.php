<?php

namespace Bkremenovic\EloquentTokens\Exceptions;

use Exception;
use Throwable;

class UnsupportedTokenTypeException extends Exception {
    /**
     * The token type that is not supported.
     *
     * @var string
     */
    protected string $tokenType;

    /**
     * The model class where the token type is unsupported.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Constructor for UnsupportedTokenTypeException.
     *
     * @param string $tokenType The token type that is not supported.
     * @param string $modelClass The model class where the token type is unsupported.
     * @param int $code The error code (default is 0).
     * @param Throwable|null $previous The previous throwable used for exception chaining.
     */
    public function __construct(string $tokenType, $modelClass, $code = 0, Throwable $previous = null)
    {
        $this->tokenType = $tokenType;
        $this->modelClass = $modelClass;

        $message = "Token type $tokenType is not supported in $modelClass";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves the token type that caused the exception.
     *
     * @return string The token type.
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Retrieves the model class where the token type is unsupported.
     *
     * @return string The model class.
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}
