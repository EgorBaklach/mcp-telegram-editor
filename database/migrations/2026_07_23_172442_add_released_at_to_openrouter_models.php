<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->table('openrouter_models', function(Blueprint $table)
        {
            $table->timestamp('released_at')->nullable()->after('published');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('openrouter_models', function(Blueprint $table)
        {
            $table->dropColumn('released_at');
        });
    }
};
