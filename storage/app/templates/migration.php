<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        // Capsule::schema()->create('table_name', function (Blueprint $table)
        // {
        //     $table->increments('id');
        //     $table->timestamps();
        // });
    }

    public function down(): void
    {
        // Capsule::schema()->dropIfExists('table_name');
    }
};
