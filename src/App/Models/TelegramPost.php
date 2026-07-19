<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPost extends Model
{
    protected $table = 'telegram_posts';

    protected $fillable = [
        'message_id',
        'text'
    ];
}
