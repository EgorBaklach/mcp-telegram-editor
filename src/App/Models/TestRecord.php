<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestRecord extends Model
{
    protected $table = 'test_records';

    protected $fillable = ['message'];
}
