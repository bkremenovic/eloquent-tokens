<?php

namespace Bkremenovic\EloquentTokens\Tests;

use Bkremenovic\EloquentTokens\Traits\HasEloquentTokens;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasEloquentTokens;

    protected $table = 'projects';
    protected $fillable = ['name'];
    public $timestamps = false;

    /**
     * List of allowed token types to be used with this model,
     * when querying or creating a token
     *
     * @return string[]
     */
    public static function getAllowedTokenTypes(): array
    {
        return [
            "ACCESS_TOKEN",
        ];
    }
}
