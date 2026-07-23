<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenRouterModel extends Model
{
    protected $table = 'openrouter_models';

    protected $fillable = ['model_id', 'name', 'accessible', 'context_length', 'modality', 'published', 'released_at'];

    protected $casts = ['published' => 'boolean', 'released_at' => 'datetime'];
}
