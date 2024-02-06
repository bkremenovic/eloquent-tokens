<?php

namespace Bkremenovic\EloquentTokens\Exceptions;

use Exception;

class UnsupportedDriverException extends Exception
{
    /**
     * The name of the driver that is unsupported.
     *
     * @var string
     */
    protected string $driver;

    /**
     * Constructor for UnsupportedDriverException.
     *
     * @param string $driver The driver name that is unsupported.
     * @param int $code The error code (default is 0).
     * @param Exception|null $previous The previous exception for exception chaining.
     */
    public function __construct(string $driver, int $code = 0, Exception $previous = null) {
        $this->driver = $driver;

        $message = "The driver '$driver' is not supported.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves the name of the driver that caused the exception.
     *
     * @return string The driver name.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }
}
