<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('abbreviation', 20);
            $table->string('type', 20)->default('quantity'); // quantity, weight, volume, length, area
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'abbreviation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
