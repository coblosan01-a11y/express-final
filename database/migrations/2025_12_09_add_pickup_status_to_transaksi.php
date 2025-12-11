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
        Schema::table('transaksi', function (Blueprint $table) {
            // Add pickup_status column if it doesn't exist
            if (!Schema::hasColumn('transaksi', 'pickup_status')) {
                $table->enum('pickup_status', ['pending', 'dioutlet', 'diantar', 'selesai'])
                      ->default('dioutlet')
                      ->nullable()
                      ->after('status_cucian');
            }
            
            // Add pickup_logs column for tracking
            if (!Schema::hasColumn('transaksi', 'pickup_logs')) {
                $table->json('pickup_logs')
                      ->nullable()
                      ->after('pickup_status');
            }
            
            // Add last_pickup_update column
            if (!Schema::hasColumn('transaksi', 'last_pickup_update')) {
                $table->timestamp('last_pickup_update')
                      ->nullable()
                      ->after('pickup_logs');
            }
            
            // Add index for pickup_status
            $table->index('pickup_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex(['pickup_status']);
            $table->dropColumn(['pickup_status', 'pickup_logs', 'last_pickup_update']);
        });
    }
};
