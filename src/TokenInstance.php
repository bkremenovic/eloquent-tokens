<?php

namespace Bkremenovic\EloquentTokens;

use Bkremenovic\EloquentTokens\Exceptions\ModelClassUnknownException;
use Bkremenovic\EloquentTokens\Exceptions\TraitMissingException;
use Bkremenovic\EloquentTokens\Traits\HasEloquentTokens;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TokenInstance
{
    /**
     * The name of the token driver that created this token instance.
     *
     * @var string
     */
    protected string $driver;

    /**
     * The unique identifier for the token instance.
     *
     * @var string
     */
    protected string $id;

    /**
     * The class name of the associated model.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * The ID of the associated model instance.
     *
     * @var int
     */
    protected int $modelId;

    /**
     * The type of the token.
     *
     * @var string
     */
    protected string $type;

    /**
     * The creation date and time of the token instance.
     *
     * @var Carbon
     */
    protected Carbon $createdAt;

    /**
     * The expiration date and time of the token instance, if applicable.
     *
     * @var Carbon|null
     */
    protected ?Carbon $expiresAt;

    /**
     * Any additional data associated with the token instance.
     *
     * @var array
     */
    protected array $data;

    /**
     * The actual token string.
     *
     * @var string
     */
    protected string $token;

    /**
     * Constructor for the TokenInstance class.
     *
     * Initializes a new instance of the TokenInstance with specified details.
     *
     * @param string $driverClassName The class name of the driver.
     * @param string $id Unique identifier for the token.
     * @param string $modelClass The class of the associated model.
     * @param int $modelId The ID of the model instance.
     * @param string $type The type of token.
     * @param Carbon $createdAt The creation date and time of the token.
     * @param Carbon|null $expiresAt The expiration date and time of the token, if any.
     * @param array $data Additional data associated with the token.
     * @param string $token The actual token string.
     */
    public function __construct(string $driverClassName, string $id, string $modelClass, int $modelId, string $type, Carbon $createdAt, ?Carbon $expiresAt, array $data, string $token)
    {
        $this->driver = Helpers::getDriverName($driverClassName);
        $this->id = $id;
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->type = $type;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->data = $data;
        $this->token = $token;
    }

    /**
     * Get the driver name for this token instance.
     *
     * @return string The name of the driver.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get the unique token id of the TokenInstance
     *
     * @return string The unique token id of the TokenInstance
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the associated Model object for the TokenInstance
     *
     * @return Model The associated Model object for the TokenInstance
     *
     * @throws ModelClassUnknownException
     * @throws TraitMissingException
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function getModel(): Model
    {
        $modelClass = $this->modelClass;
        $modelId = $this->modelId;

        // Check if the class exists and is an instance of Model
        Helpers::validateModelClass($modelClass);

        // Check if the model uses the HasEloquentTokens trait
        Helpers::validateTraitUse($modelClass);

        // Find the model instance by its class and ID
        /** @var Model|HasEloquentTokens $model */
        $model = $modelClass::findOrFail($modelId);

        // Mark the model as bound from a token
        $model->bindFromToken();

        // Return the model
        return $model;
    }

    /**
     * Get the type of the TokenInstance
     *
     * @return string The type of the TokenInstance
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the creation date of the TokenInstance
     *
     * @return Carbon The creation date of the TokenInstance as a Carbon datetime object
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * Get the expiration date of the TokenInstance, or null if it doesn't expire
     *
     * @return Carbon|null The expiration date of the TokenInstance as a Carbon datetime object, or null if it doesn't expire
     */
    public function getExpiresAt(): ?Carbon
    {
        return $this->expiresAt;
    }

    /**
     * Get the data associated with the TokenInstance
     *
     * @return array The data associated with the TokenInstance as an array
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * Get the token value of the TokenInstance
     *
     * @return string The token value of the TokenInstance
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
