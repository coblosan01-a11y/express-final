<template>
  <CardLayout>
    <div class="pengaturan-container">
      <div class="card">
        <h2>Pengaturan Sistem</h2>
        <p class="desc">Kelola pengaturan umum sistem laundry</p>

        <div class="setting-box">
          <div class="setting-info">
            <h3>🔒 Pengaturan Keamanan</h3>
            <p>Kelola kebijakan password dan keamanan akun</p>
          </div>
          <button class="action-btn">Ubah Pengaturan</button>
        </div>

        <div class="setting-box">
          <div class="setting-info">
            <h3>🗂️ Backup Data</h3>
            <p>Cadangkan data karyawan dan transaksi</p>
          </div>
          <button class="action-btn">Backup Sekarang</button>
        </div>
      </div>
    </div>
  </CardLayout>
</template>

<script setup>
// kosong
</script>

<style >
  @import '../../assets/css/Pengaturan.css';
</style>
