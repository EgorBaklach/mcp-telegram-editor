<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('telegram_posts', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('message_id')->unique();
            $table->text('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('telegram_posts');
    }
};
