<template>
  <div class="modal-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <button class="close-btn" @click="closeModal">&times;</button>
        <h2 class="modal-title">Daftarkan Karyawan Baru</h2>
        <p class="modal-subtitle">
          Isi form di bawah untuk menambahkan karyawan ke sistem
        </p>
      </div>

      <form class="modal-body" @submit.prevent="submitForm">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input
              v-model="form.nama"
              type="text"
              class="form-input"
              placeholder="👤 Nama lengkap karyawan"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Nomor Telepon</label>
            <input
              v-model="form.telepon"
              type="tel"
              class="form-input"
              placeholder="📞 08xxxxxxxxxx"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Jabatan</label>
            <select v-model="form.jabatan" class="form-select" required>
              <option value="">Pilih Jabatan</option>
              <option value="admin">👨‍💼 Admin (High Access)</option>
              <option value="kasir">🧾 Kasir (Medium Access)</option>
              <option value="kurir">🚚 Kurir (Low Access)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Password Sementara</label>
            <input
              v-model="form.password"
              type="password"
              class="form-input"
              placeholder="🔐 Password yang akan diberikan ke karyawan"
              required
              minlength="6"
            />
            <small class="form-hint">Minimal 6 karakter</small>
          </div>
        </div>

        <!-- Role Info -->
        <div class="role-info" v-if="form.jabatan">
          <div class="role-card" :class="form.jabatan">
            <h4>{{ getRoleTitle(form.jabatan) }}</h4>
            <p>{{ getRoleDescription(form.jabatan) }}</p>
            <ul>
              <li v-for="permission in getRolePermissions(form.jabatan)" :key="permission">
                ✅ {{ permission }}
              </li>
            </ul>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn-primary" :disabled="isLoading">
            {{ isLoading ? '⏳ Mendaftarkan...' : '📝 Daftarkan Karyawan' }}
          </button>
          <button type="button" class="btn-secondary" @click="closeModal">
            ❌ Batal
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const emit = defineEmits(['close', 'submitted'])

const form = reactive({
  nama: '',
  telepon: '',
  jabatan: '',
  password: ''
})

const isLoading = ref(false)

function getRoleTitle(jabatan) {
  const titles = {
    admin: 'Admin Karyawan',
    kasir: 'Kasir',
    kurir: 'Kurir'
  }
  return titles[jabatan] || ''
}

function getRoleDescription(jabatan) {
  const descriptions = {
    admin: 'Karyawan kepercayaan dengan akses luas untuk mengelola sistem',
    kasir: 'Mengelola transaksi dan melihat laporan harian',
    kurir: 'Mengelola pengantaran dan update status orderan'
  }
  return descriptions[jabatan] || ''
}

function getRolePermissions(jabatan) {
  const permissions = {
    admin: [
      'Mengelola karyawan lain',
      'Mengelola layanan',
      'Melihat laporan lengkap',
      'Mengelola transaksi',
      'Mengelola orderan'
    ],
    kasir: [
      'Mengelola transaksi',
      'Melihat laporan harian sendiri',
      'Input data pelanggan'
    ],
    kurir: [
      'Melihat daftar orderan',
      'Update status pengantaran',
      'Konfirmasi pickup/delivery'
    ]
  }
  return permissions[jabatan] || []
}

function submitForm() {
  isLoading.value = true
  
  axios.post('/api/karyawan', form)
    .then(() => {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: `Karyawan ${form.nama} berhasil didaftarkan sebagai ${form.jabatan}.`,
        confirmButtonText: 'Oke'
      }).then(() => {
        resetForm()
        emit('submitted')
        emit('close')
      })
    })
    .catch(error => {
      console.error('DETAIL ERROR:', error.response?.data)
      
      let errorMessage = 'Gagal mendaftarkan karyawan. Periksa data input.'
      
      if (error.response?.data?.message) {
        errorMessage = error.response.data.message
      } else if (error.response?.data?.error) {
        errorMessage = error.response.data.error
      }
      
      Swal.fire({
        icon: 'error',
        title: 'Gagal Mendaftar',
        text: errorMessage,
        confirmButtonText: 'Tutup'
      })
    })
    .finally(() => {
      isLoading.value = false
    })
}

function resetForm() {
  form.nama = ''
  form.telepon = ''
  form.jabatan = ''
  form.password = ''
}

function closeModal() {
  emit('close')
}
</script>

<style scoped>
@import '../../../assets/css/DaftarKaryawan.css';

.form-hint {
  display: block;
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

.role-info {
  margin: 20px 0;
}

.role-card {
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid;
}

.role-card.admin {
  background: #e3f2fd;
  border-left-color: #1976d2;
}

.role-card.kasir {
  background: #f3e5f5;
  border-left-color: #7b1fa2;
}

.role-card.kurir {
  background: #e8f5e8;
  border-left-color: #2e7d32;
}

.role-card h4 {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 16px;
}

.role-card p {
  margin: 0 0 12px 0;
  color: #666;
  font-size: 14px;
}

.role-card ul {
  margin: 0;
  padding: 0;
  list-style: none;
}

.role-card li {
  margin: 4px 0;
  font-size: 13px;
  color: #555;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>