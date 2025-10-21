<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 8); // Latitude with high precision
            $table->decimal('longitude', 11, 8); // Longitude with high precision
            $table->string('class_name')->nullable(); // For class-based locations
            $table->string('building_block_name')->nullable(); // For building/block-based locations
            $table->string('location_type')->default('class'); // 'class' or 'building'
            $table->text('description')->nullable(); // Optional description
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Superadmin who created it
            $table->timestamps();
            
            // Ensure at least one of class_name or building_block_name is provided
            $table->index(['latitude', 'longitude']);
            $table->index('location_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
