<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old table if exists
        Schema::dropIfExists('pickup_zones');
        
        // Create new pickup settings table
        Schema::create('pickup_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('jarak_min', 8, 2); // Jarak minimum (contoh: 1.00)
            $table->decimal('jarak_max', 8, 2); // Jarak maksimum (contoh: 4.00)
            $table->decimal('biaya', 10, 2);    // Biaya untuk rentang ini (contoh: 5000.00)
            
            // NEW: Service type dengan 3 pilihan
            $table->enum('service_type', ['pickup_only', 'pickup_delivery', 'delivery_only'])
                  ->default('pickup_only')
                  ->comment('Jenis layanan: pickup_only (ambil saja), pickup_delivery (ambil+antar), delivery_only (antar saja)');
            
            // Backward compatibility columns (deprecated but kept for old data)
            $table->boolean('pickup_only')->default(true)->comment('DEPRECATED: Use service_type instead');
            $table->boolean('pickup_delivery')->default(false)->comment('DEPRECATED: Use service_type instead');
            $table->boolean('delivery_only')->default(false)->comment('NEW: Antar saja (DEPRECATED: Use service_type instead)');
            
            $table->string('deskripsi')->nullable(); // Deskripsi rentang
            $table->boolean('aktif')->default(true); // Status aktif/nonaktif
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index(['jarak_min', 'jarak_max', 'aktif'], 'idx_range_active');
            $table->index(['service_type', 'aktif'], 'idx_service_active');
            
            // Unique constraint: tidak boleh ada 2 setting dengan service_type sama dan rentang yang overlap
            // Note: Ini akan di-handle di application level karena overlap detection complex
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_settings');
    }
};