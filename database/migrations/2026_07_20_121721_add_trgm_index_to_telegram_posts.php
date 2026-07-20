<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::connection()->statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        Capsule::connection()->statement('CREATE INDEX telegram_posts_text_trgm_idx ON telegram_posts USING GIN (text gin_trgm_ops)');
    }

    public function down(): void
    {
        Capsule::connection()->statement('DROP INDEX IF EXISTS telegram_posts_text_trgm_idx');
    }
};
