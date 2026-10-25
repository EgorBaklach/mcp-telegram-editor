<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('fl_orders', function(Blueprint $table)
        {
            $table->unsignedBigInteger('project_id')->primary();
            $table->string('hash', 32)->unique();
            $table->string('title');
            $table->string('link');
            $table->string('budget')->nullable();
            $table->text('description')->nullable();
            $table->boolean('published')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('fl_orders');
    }
};
