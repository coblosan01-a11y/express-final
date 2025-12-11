<?php
// database/migrations/2025_07_15_083643_create_transaksi_detail_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaksi_id');
            
            // Service Information
            $table->unsignedBigInteger('layanan_id')->nullable();
            $table->string('layanan_nama', 255);
            
            // Quantity and Pricing - MENGGUNAKAN TEXT UNTUK COMPATIBILITY
            $table->text('kuantitas'); // JSON: {"Kg": 2, "Pcs": 5}
            $table->text('harga_satuan'); // JSON: {"Kg": 5000, "Pcs": 2000}
            $table->decimal('subtotal', 15, 2);
            
            // Additional Information
            $table->text('catatan')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('transaksi_id');
            $table->index('layanan_id');
        });
        
        // Add foreign key constraints after table creation
        Schema::table('transaksi_detail', function (Blueprint $table) {
            $table->foreign('transaksi_id')
                  ->references('id')
                  ->on('transaksi')
                  ->onDelete('cascade');
        });
        
        // Foreign key untuk layanan table (jika ada)
        if (Schema::hasTable('layanan')) {
            Schema::table('transaksi_detail', function (Blueprint $table) {
                $table->foreign('layanan_id')
                      ->references('id')
                      ->on('layanan')
                      ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        // Drop foreign keys first
        Schema::table('transaksi_detail', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            if (Schema::hasTable('layanan')) {
                $table->dropForeign(['layanan_id']);
            }
        });
        
        Schema::dropIfExists('transaksi_detail');
    }
};