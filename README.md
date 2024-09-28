# Flexible Token Management for Laravel Applications

This package was developed to address a longstanding need for a flexible and efficient token management system in Laravel. It supports multiple drivers, is space-efficient, and offers a variety of useful functionalities for token handling.

After four months of dedicated development, I am announcing the launch of this package as a special thanks to the awesome Laravel community, on my birthday. 🎉

## Table of Contents

- [Installation Guide](#installation-guide)
    - [Quick Installation](#quick-installation)
    - [Configuration](#configuration)
- [Token Drivers](#token-drivers)
    - [Stateless Token Driver](#stateless-token-driver)
    - [Database Token Driver](#database-token-driver)
    - [Decision Guide: Choosing the Right Driver](#decision-guide-choosing-the-right-driver)
- [Usage Instructions](#usage-instructions)
    - [Implementing Traits](#implementing-traits)
    - [Defining Token Types](#defining-token-types)
- [Token Management](#token-management)
    - [Creating Tokens](#creating-tokens)
    - [Querying Tokens](#querying-tokens)
    - [Token Deletion](#token-deletion)
    - [Force Deleting All Tokens](#force-deleting-all-tokens)
- [Advanced Token Creation](#advanced-token-creation)
    - [Specifying Token Expiration Date](#specifying-token-expiration-date)
    - [Including Additional Data](#including-additional-data)
    - [Specifying a Driver](#specifying-a-driver)
- [Advanced Token Querying](#advanced-token-querying)
- [Understanding TokenInstance](#understanding-tokeninstance)
    - [Available Methods](#available-methods)
- [Testing](#testing)
- [License](#license)

## Installation Guide

### Quick Installation

Install the package via Composer:
```bash
composer require bkremenovic/eloquent-tokens
```

Laravel's auto-discovery mechanism will automatically detect the Service Provider.

### Configuration

1. **Publish and Run Migrations:**
   ```bash
   php artisan vendor:publish --provider="Bkremenovic\EloquentTokens\EloquentTokensServiceProvider" --tag="migrations"
   php artisan migrate
   ```

2. **Publish Config File:**
   ```bash
   php artisan vendor:publish --provider="Bkremenovic\EloquentTokens\EloquentTokensServiceProvider" --tag="config"
   ```

## Token Drivers

The Laravel Eloquent Tokens package provides two versatile drivers for token management, each with unique characteristics catering to different requirements and scenarios. 

Understanding the pros and cons of these two drivers is crucial for making an informed decision and selecting the appropriate driver is essential for ensuring the effectiveness and efficiency of your token management strategy.

### Stateless Token Driver

This driver leverages Laravel's `encrypt()` and `decrypt()` functions and optimizes space by compressing data before encryption.

#### Advantages

- **Space Efficiency:** Does not consume disk space, ideal for applications with high token generation.
- **Bulk Blacklisting:** Simplifies the process of invalidating old tokens.
- **Enhanced Security:** Reduces risks in case of data breaches, as tokens are not stored.

#### Limitations

- **Dependency on APP_KEY:** Tightly bound to Laravel's APP_KEY; any change to this affects already issued tokens.
- **No Token Tracking:** Inability to track or audit previously issued tokens.
- **Token Length Concerns:** Tokens with extensive data can become excessively long, potentially causing issues if used within URLs.

### Database Token Driver

This driver utilizes database storage for token management.

#### Advantages

- **Token Auditability:** Facilitates easy tracking and listing of all issued tokens.

#### Limitations

- **Space Consumption:** Higher database space usage.
- **Potential Security Risks:** Visible token list in the database could be a security concern.

### Decision Guide: Choosing the Right Driver

Selecting an appropriate driver depends on various factors such as token lifespan, usage patterns, and management preferences.

- **For Long-Lived, High-Volume Use:** Opt for `StatelessTokenDriver` to save on database space.
- **For Detailed Token Management:** Choose `DatabaseTokenDriver` if comprehensive control over tokens is required.

## Usage Instructions

### Implementing Traits

Add the `HasEloquentTokens` trait to your Eloquent models for enabling token functionalities:
```php
use Bkremenovic\EloquentTokens\Traits\HasEloquentTokens;

class Project extends Model
{
    use HasEloquentTokens;
    
    // ... model properties
}
```

### Defining Token Types

Define allowable token types in the `getAllowedTokenTypes` method:
```php
public static function getAllowedTokenTypes(): array
{
    return [
        "INVITE_TOKEN",
        "ACCESS_TOKEN", 
        // ... other types as per your needs
    ];
}
```

## Token Management

### Creating Tokens

1. **Model Method:**
   ```php
   $token = $model->createToken("INVITE_TOKEN")->getToken();
   ```

2. **Token Facade:**
   ```php
   $token = Token::create($model, "INVITE_TOKEN")->getToken();
   ```

   *Options to set token expiry time, additional data and specific driver are available, [see below](#advanced-token-creation).*

### Querying Tokens

Similar to Eloquent’s functions, this dependency provides two functions for finding/resolving tokens:

1. **Standard Find:** Returns `TokenInstance` or `null`.
    ```php
    $tokenInstance = Token::find("eyJpdiI6Ik9FdFFqRmpxbXhh.....");
    ```
2. **Force Find:** Returns `TokenInstance`. Throws `TokenNotFoundException` if not found.
    ```php
    $tokenInstance = Token::findOrFail("eyJpdiI6Ik9FdFFqRmpxbXhh.....");
    ```

### Token Deletion

There are two ways to delete an Eloquent model tokens:

- By calling a `deleteTokens()` function on a model instance
    ```php
    // This would delete/blacklist all tokens related to the $model (targeting both model class and model id)
    $model->deleteTokens();
    ```

- By calling a `deleteBy` function on Token facade:
    ```php
    // This would delete/blacklist all tokens related to the $model (targeting both model class and model id)
    Token::deleteBy($model);
    
    // This would delete/blacklist all tokens related to the Project model (targeting model class)
    Token::deleteBy(null, Project::class);
    ```

The signature of `deleteBy` function within a Token facade is following:
```php
function deleteBy(
    Model $model = null,
    string $modelClass = null,
    string $type = null,
    string $id = null,
    array $data = null
): void {
    // ...
}
```
Since PHP 8.0 you may specify function arguments in any order by using their named arguments.

#### Example 1
```php
// Delete all tokens related to Project model, where role is 'project-administrator'
Token::deleteBy(modelClass: Project::class, ['role' => 'project-administrator']);
```

#### Example 2
```php
// Delete a single token by its unique ID
Token::deleteBy(id: "9ad5a1f5-6207-4727-b1ee-e9eddbf752a1");
```

The signature of `deleteTokens` function within an Eloquent model class is following:
```php
function deleteTokens(
    string $type = null,
    string $id = null,
    array $data = null
): void {
    // ...
}
```

#### Example 1
```php
// Delete all tokens related to the Project model instance, while targeting a specific token type
$project->deleteTokens(type: "ACCESS_TOKEN");
```

#### Example 2
```php
// Delete a single token by its unique ID, also targeting the specific Project model instance
$project->deleteTokens(id: "9ad5a1f5-6207-4727-b1ee-e9eddbf752a1");
```

### Force Deleting All Tokens

To completely remove or blacklist all tokens that have been issued, you can use the forceDeleteAll method.

This action is irreversible, and <u>***should be used with caution***</u>, as it will permanently delete all tokens from your system, making them unusable for any further operations or validations.
```php
Token::forceDeleteAll();
```
Its use in production environments should be carefully considered due to its impactful nature on token validity and system security.

## Advanced Token Creation

### Specifying Token Expiration Date

When specifying the expiration date for a token, you have the flexibility to use either a Carbon instance or a string compatible with the strtotime() function.
```php
// Use Carbon instance
$expiresIn = now()->addDays(15);
$token = $model->createToken("INVITE_TOKEN", $expiresIn)->getToken();

// Use `strtotime()` compatible string
$token = $model->createToken("INVITE_TOKEN", "15 days")->getToken();

// Using Token facade
$token = Token::create($model, "ACCESS_TOKEN", "15 minutes")->getToken();
```

### Including Additional Data
The data parameter allows you to include an associative array as meta-data with the token. This feature is ideal for adding extra details that are pertinent to the token's function, including roles, permissions, or any type of additional information.
```php
$data = ['role' => 'admin', 'projectId' => 123];
$token = $model->createToken('ACCESS_TOKEN', '+1 hour', $data)->getToken();

// Using Token facade
$token = Token::create($model, "ACCESS_TOKEN", "15 minutes", $data)->getToken();
```

### Specifying a Driver
The driver parameter allows you to explicitly specify which token driver to use when creating the token. If you have configured multiple token drivers in your application and want to use a specific one for certain tokens, you can specify the driver's name. 

If no driver is specified, it will automatically fallback to the default driver that has been previously configured in the config file.
```php
// Utilizes the `database` driver during token creation
$token = $model->createToken('INVITE_TOKEN', $expiresIn, $data, 'database')->getToken();

// Using Token facade
$token = Token::create($model, 'INVITE_TOKEN', $expiresIn, $data, 'database')->getToken();
```

## Advanced Token Querying

You may use chained methods to find the token based on several criteria:
- whereModel(Model $model)
- whereModelClass(string $modelClass)
- whereType(string $type)
- whereData(array $data)

#### Example 1
```php
$model = Token::whereModelClass(Project::class)
    ->whereType("INVITE_TOKEN")
    ->findOrFail("eyJpdiI6Ik9FdFFqRmpxbXhh.....")
    ->getModel();
```

#### Example 2
```php
$model = Token::whereModelClass(Project::class)
    ->whereData(['role' => 'project-administrator'])
    ->whereType("INVITE_TOKEN")
    ->findOrFail("eyJpdiI6Ik9FdFFqRmpxbXhh.....")
    ->getModel();
```

#### Example 3
```php
$project = Project::find(12345);

$isTokenFound = (bool) Token::whereModel($project)
    ->whereType("ACCESS_TOKEN")
    ->find("eyJpdiI6Ik9FdFFqRmpxbXhh.....");
```

## Understanding `TokenInstance`
The TokenInstance class is a crucial part of this token management system. It holds all relevant information for a specific token, such as its identifier, associated model, type, creation time, expiration time and additional data. 

### Available Methods
- `getDriver()` Returns the name of the driver that was used to create the token instance.
- `getId()` Returns the token's unique identifier.
- `getModel()` Fetches the associated Eloquent model for the token.
- `getModelClass()` Returns the class name of the associated model.
- `getModelId()` Returns the ID/UUID of the associated model.
- `getType()` Returns the token's type (e.g., "ACCESS_TOKEN").
- `getCreatedAt()` Returns the creation timestamp as a Carbon instance.
- `getExpiresAt()` Returns the expiration date and time of the token, if it is set to expire.
- `getData()` Returns any additional data associated with the token.
- `getToken()` Returns the actual string value of the token, used when finding a token.

## Testing
To ensure the reliability and functionality of Laravel Eloquent Tokens, you can run tests using the Composer command:
```bash
composer test
```

## Security
If you identify any security-related problems, please contact me via email at [boris@incollab.io](mailto:boris@incollab.io) or, if you prefer, you can report the issue using the GitHub issue tracker.

## License
This package is licensed under the MIT License. For detailed information, please refer to the [License File](LICENSE.md).
