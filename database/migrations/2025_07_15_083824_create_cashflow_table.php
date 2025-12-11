<?php
// database/migrations/2025_07_15_083644_create_cash_flow_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cash_flow', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaksi_id');
            
            // Cash Flow Information
            $table->date('tanggal');
            $table->enum('jenis_pembayaran', ['tunai', 'qris', 'bayar-nanti']);
            $table->decimal('amount', 15, 2);
            
            // Type of cash flow
            $table->enum('tipe', ['income', 'expense'])->default('income');
            $table->string('kategori', 100)->default('laundry_service');
            
            // Description
            $table->text('deskripsi')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('transaksi_id');
            $table->index('tanggal');
            $table->index('jenis_pembayaran');
            $table->index('tipe');
        });
        
        // Add foreign key constraint after table creation
        Schema::table('cash_flow', function (Blueprint $table) {
            $table->foreign('transaksi_id')
                  ->references('id')
                  ->on('transaksi')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        // Drop foreign key first
        Schema::table('cash_flow', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
        });
        
        Schema::dropIfExists('cash_flow');
    }
};