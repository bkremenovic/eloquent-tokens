<?php

namespace Bkremenovic\EloquentTokens\Exceptions;

use Exception;

class TraitMissingException extends Exception
{
    /**
     * The class name of the model missing the trait.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * The class name of the missing trait.
     *
     * @var string
     */
    protected string $traitClass;

    /**
     * Constructor for TraitMissingException.
     *
     * @param string $modelClass The model class that is missing the trait.
     * @param string $traitClass The class name of the missing trait.
     * @param int $code The error code (default is 0).
     * @param Exception|null $previous The previous exception used for exception chaining, if any.
     */
    public function __construct(string $modelClass, string $traitClass, int $code = 0, Exception $previous = null)
    {
        $this->modelClass = $modelClass;
        $this->traitClass = $traitClass;

        $message = "The model '$modelClass' is missing '$traitClass' trait.";

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves the model class name missing the trait.
     *
     * @return string The name of the model class missing the trait.
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Retrieves the class name of the missing trait.
     *
     * @return string The class name of the missing trait.
     */
    public function getTraitClass(): string
    {
        return $this->traitClass;
    }
}
