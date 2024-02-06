<?php

namespace Bkremenovic\EloquentTokens\Exceptions;

use Exception;
use Throwable;

class TokenNotFoundException extends Exception
{
    /**
     * The token string that was not found.
     *
     * @var string
     */
    protected string $token;

    /**
     * Constructor for TokenNotFoundException.
     *
     * @param string $token The token string that is not found.
     * @param int $code The error code (default is 0).
     * @param Throwable|null $previous The previous throwable used for exception chaining.
     */
    public function __construct(string $token, int $code = 0, Throwable $previous = null)
    {
        $this->token = $token;

        $message = "Token $token not found.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves the token string that caused the exception.
     *
     * @return string The token string.
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
