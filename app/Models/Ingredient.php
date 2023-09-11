<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read IngredientType type
 */
class Ingredient extends Model
{
    use HasFactory;

    protected $table = 'ingredient';

    public function type(): BelongsTo
    {
        return $this->belongsTo(IngredientType::class);
    }
}
