<?php

namespace Bkremenovic\EloquentTokens\Traits;

use Bkremenovic\EloquentTokens\Exceptions\InvalidTraitUsageException;
use Bkremenovic\EloquentTokens\Facades\Token;
use Bkremenovic\EloquentTokens\TokenInstance;
use Illuminate\Database\Eloquent\Model;

trait HasEloquentTokens
{
    /**
     * @var bool $_isBoundFromToken Indicates if the model was bound from a token.
     */
    protected bool $_isBoundFromToken = false;

    /**
     * Create a new TokenInstance
     *
     * @param string $type The type of the TokenInstance
     * @param string|null $expiresIn Optional. The expiration time for the TokenInstance, as a string compatible with strtotime()
     * @param array $data Optional. Additional data to associate with the TokenInstance
     * @param string|null $driver Driver name
     *
     * @return TokenInstance The newly created TokenInstance.
     *
     * @throws InvalidTraitUsageException
     */
    public function createToken(string $type, string $expiresIn = null, array $data = [], string $driver = null): TokenInstance
    {
        // Check if the trait is applied to Model class
        if (!$this instanceof Model) {
            throw new InvalidTraitUsageException(static::class, Model::class);
        }

        // Create a TokenInstance and return it
        return Token::create($this, $type, $expiresIn, $data, $driver);
    }

    /**
     * Delete (or blacklist) all TokenInstance's of the model by the given input
     *
     * @param string|null $type The type of the TokenInstance
     * @param string|null $id The unique id of the TokenInstance
     * @param array|null $data Optional. Additional data to associate with the TokenInstance
     *
     * @return void
     *
     * @throws InvalidTraitUsageException
     */
    public function deleteTokens(string $type = null, string $id = null, array $data = null): void
    {
        // Check if the trait is applied to Model class
        if (!$this instanceof Model) {
            throw new InvalidTraitUsageException(static::class, Model::class);
        }

        // Delete all TokenInstance's by the given input
        Token::deleteBy($this, null, $type, $id, $data);
    }

    /**
     * List of allowed token types to be used with this model,
     * when querying or creating a token
     *
     * @return string[]
     */
    abstract public static function getAllowedTokenTypes(): array;

    /**
     * Mark the model as bound from a token.
     *
     * @internal
     *
     * @return void
     */
    public function bindFromToken(): void
    {
        $this->_isBoundFromToken = true;
    }

    /**
     * Check if the model is bound from a token.
     *
     * @return bool True if the model is bound from a token, false otherwise.
     */
    public function isBoundFromToken(): bool
    {
        return $this->_isBoundFromToken;
    }
}
