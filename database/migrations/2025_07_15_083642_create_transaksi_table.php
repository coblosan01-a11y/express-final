<?php
// database/migrations/2025_07_15_083642_create_transaksi_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 50)->unique();
            $table->datetime('tanggal_transaksi');
            
            // Customer Information
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name', 255);
            $table->string('customer_phone', 20);
            
            // Payment Information
            $table->enum('metode_pembayaran', ['tunai', 'qris', 'bayar-nanti']);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->decimal('kembalian', 15, 2)->default(0);
            
            // 🚚 PICKUP SERVICE FIELDS
            $table->boolean('has_pickup_service')->default(false);
            $table->unsignedBigInteger('pickup_setting_id')->nullable();
            $table->string('pickup_service_name', 100)->nullable();
            $table->enum('pickup_service_type', ['pickup_only', 'pickup_delivery', 'delivery_only'])->nullable(); //service type dengan 3 pilihan
            $table->decimal('pickup_jarak', 5, 1)->nullable();
            $table->string('pickup_rentang', 50)->nullable();
            $table->date('pickup_date')->nullable();
            $table->time('pickup_time')->nullable();
            $table->text('pickup_special_instructions')->nullable();
            $table->decimal('pickup_base_cost', 12, 2)->nullable();
            $table->decimal('pickup_total_cost', 12, 2)->nullable();
            
            // 💰 PAYMENT BREAKDOWN
            $table->decimal('subtotal_layanan', 15, 2)->default(0);
            $table->decimal('biaya_pickup', 15, 2)->default(0);
            // total_amount = subtotal_layanan + biaya_pickup
            
            // Transaction Status
            $table->enum('status_transaksi', ['pending', 'sukses', 'cancelled'])->default('pending');
            $table->enum('status_cucian', ['pending', 'processing', 'completed', 'delivered'])->default('pending');
            
            // Additional Information
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('tanggal_transaksi');
            $table->index('metode_pembayaran');
            $table->index('status_transaksi');
            $table->index('customer_phone');
            $table->index('customer_id');
            $table->index('created_by');
            
            // 🚚 PICKUP INDEXES
            $table->index('has_pickup_service');
            $table->index('pickup_date');
            $table->index('pickup_setting_id');
        });
        
        // Foreign key constraints
        if (Schema::hasTable('pelanggan')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('pelanggan')
                      ->onDelete('set null');
            });
        }
        
        if (Schema::hasTable('users')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->foreign('created_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        // Drop foreign keys first
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropForeign(['created_by']);
            });
        }
        
        Schema::dropIfExists('transaksi');
    }
};