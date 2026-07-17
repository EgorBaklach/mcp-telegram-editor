<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $migration
 * @property int $batch
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Migration extends Model
{
    protected $table = 'migrations';

    protected $fillable = [
        'migration',
        'batch',
    ];
}
