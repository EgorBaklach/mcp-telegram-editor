<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        Capsule::schema()->create('test_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('test_records');
    }
};
