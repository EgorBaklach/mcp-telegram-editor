<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FLOrder extends Model
{
    public $incrementing = false;

    protected $table = 'fl_orders';

    protected $primaryKey = 'project_id';

    protected $fillable = ['project_id', 'hash', 'title', 'link', 'budget', 'description', 'published'];

    protected $casts = ['project_id' => 'integer', 'published' => 'boolean'];
}
