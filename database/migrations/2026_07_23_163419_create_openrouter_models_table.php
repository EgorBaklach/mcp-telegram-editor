<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('openrouter_models', function(Blueprint $table)
        {
            $table->id();
            $table->string('model_id')->unique();
            $table->string('name');
            $table->enum('accessible', ['free', 'paid'])->default('free');
            $table->integer('context_length')->nullable();
            $table->string('modality')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('openrouter_models');
    }
};
