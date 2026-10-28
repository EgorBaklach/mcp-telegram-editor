<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::connection()->statement('DROP TYPE IF EXISTS hh_schedule');
        Capsule::connection()->statement('CREATE TYPE hh_schedule AS ENUM (\'ON_SITE\', \'REMOTE\', \'HYBRID\', \'FIELD_WORK\', \'fullDay\', \'remote\', \'flexible\', \'shift\', \'flyInFlyOut\')');

        Capsule::schema()->create('hh_vacancies', function(Blueprint $table)
        {
            $table->unsignedBigInteger('vacancy_id')->primary();
            $table->string('hash', 32)->unique();
            $table->string('title');
            $table->string('company')->nullable();
            $table->string('address')->nullable();
            $table->rawColumn('schedule', 'hh_schedule[]')->nullable();
            $table->string('link');
            $table->string('salary')->nullable();
            $table->text('description')->nullable();
            $table->boolean('published')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('hh_vacancies');
        Capsule::connection()->statement('DROP TYPE IF EXISTS hh_schedule');
    }
};
