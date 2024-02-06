<?php

namespace Bkremenovic\EloquentTokens;

use Bkremenovic\EloquentTokens\Exceptions\UnsupportedDriverException;
use Bkremenovic\EloquentTokens\Interfaces\TokenDriverInterface;

class TokenConfigManager
{
    /**
     * The list of supported drivers.
     *
     * @var TokenDriverInterface[]
     */
    protected array $drivers;

    /**
     * The default driver.
     *
     * @var TokenDriverInterface
     */
    protected TokenDriverInterface $defaultDriver;

    /**
     * Whether to use all drivers.
     *
     * @var bool
     */
    protected bool $useAllDrivers;

    /**
     * The token blacklist table.
     *
     * @var string
     */
    protected string $tokenBlacklistTable;

    /**
     * The tokens table.
     *
     * @var string
     */
    protected string $tokensTable;

    /**
     * Class constructor.
     *
     * @param array $supportedDrivers Array of supported driver class names. These drivers must implement TokenDriverInterface.
     * @param string $defaultDriver The default driver class name. This is used if $useAllDrivers is false.
     * @param bool $useAllDrivers Flag to determine if all drivers should be used or just the default one.
     * @param string $tokenBlacklistTable The table name for storing blacklisted tokens.
     * @param string $tokensTable The table name for storing tokens.
     *
     * @throws UnsupportedDriverException if a driver class is not a valid TokenDriver class.
     */
    public function __construct(array $supportedDrivers, string $defaultDriver, bool $useAllDrivers, string $tokenBlacklistTable, string $tokensTable)
    {
        // Initialize configuration parameters
        $this->useAllDrivers = $useAllDrivers;
        $this->tokenBlacklistTable = trim($tokenBlacklistTable);
        $this->tokensTable = trim($tokensTable);

        // Instantiate the driver class
        $this->drivers = [];

        // Process each supported driver
        foreach ($supportedDrivers as $driverName => $driverClassName) {
            // Initialize the driver based on its class name
            $driver = Helpers::initializeDriver($driverClassName);

            if($driverName === $defaultDriver) {
                // Set the default driver and place it at the beginning of the drivers array
                $this->defaultDriver = $driver;

                // Set the default driver as the first one in the drivers array
                $this->drivers = [
                    $driverName => $driver,
                    ...$this->drivers
                ];
            } else {
                // Add the driver to the drivers array
                $this->drivers[$driverName] = $driver;
            }
        }

        // Ensure the default driver is set
        if (!isset($this->defaultDriver)) {
            throw new UnsupportedDriverException($defaultDriver);
        }
    }

    /**
     * Get the list of supported drivers.
     *
     * @return TokenDriverInterface[]
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Get the default driver.
     *
     * @return TokenDriverInterface
     */
    public function getDefaultDriver(): TokenDriverInterface
    {
        return $this->defaultDriver;
    }

    /**
     * Check if all drivers are being used.
     *
     * @return bool
     */
    public function isUsingAllDrivers(): bool
    {
        return $this->useAllDrivers;
    }

    /**
     * @return string
     */
    public function getTokenBlacklistTable(): string
    {
        return $this->tokenBlacklistTable;
    }

    /**
     * @return string
     */
    public function getTokensTable(): string
    {
        return $this->tokensTable;
    }
}
