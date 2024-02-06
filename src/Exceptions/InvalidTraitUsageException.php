<?php

namespace Bkremenovic\EloquentTokens\Exceptions;

use Exception;
use Throwable;

class InvalidTraitUsageException extends Exception {
    /**
     * The name of the trait class that was attempted to be used.
     *
     * @var string
     */
    protected string $traitClass;

    /**
     * The name of the target class where the trait was incorrectly used.
     *
     * @var string
     */
    protected string $targetClass;

    /**
     * Constructor for InvalidTraitUsageException.
     *
     * @param string $traitClass The trait class name that is invalidly used.
     * @param string $targetClass The target class name where the trait is incorrectly used.
     * @param int $code The error code (default is 0).
     * @param Throwable|null $previous The previous throwable used for exception chaining.
     */
    public function __construct(string $traitClass, string $targetClass, int $code = 0, Throwable $previous = null)
    {
        $this->traitClass = $traitClass;
        $this->targetClass = $targetClass;

        $message = "The trait $traitClass can only be applied to a $targetClass class instance.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves the trait class name that caused the exception.
     *
     * @return string The trait class name.
     */
    public function getTraitClass(): string
    {
        return $this->traitClass;
    }

    /**
     * Retrieves the target class name where the trait was incorrectly used.
     *
     * @return string The target class name.
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }
}
