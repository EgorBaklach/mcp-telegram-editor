<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlOrder extends Model
{
    protected $table = 'fl_orders';

    protected $fillable = ['project_id', 'hash', 'title', 'link', 'budget', 'description', 'published'];

    protected $casts = ['project_id' => 'integer', 'published' => 'boolean'];
}
