<template>
  <div class="modal-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <button class="close-btn" @click="closeModal">&times;</button>
        <h2 class="modal-title">Daftarkan Admin Baru</h2>
        <p class="modal-subtitle">
          Isi form di bawah untuk menambahkan admin ke sistem
        </p>
      </div>

      <form class="modal-body" @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input
              v-model="form.nama"
              type="text"
              class="form-input"
              placeholder="👤 Nama lengkap admin"
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

          <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">Password</label>
            <input
              v-model="form.password"
              type="password"
              class="form-input"
              placeholder="🔐 Minimal 6 karakter"
              required
              minlength="6"
            />
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn-primary" :disabled="loading">
            📝 {{ loading ? 'Menyimpan...' : 'Daftarkan Admin' }}
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
import { ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const emit = defineEmits(['close', 'submitted'])
const loading = ref(false)

const form = ref({
  nama: '',
  telepon: '',
  password: ''
})

function closeModal() {
  emit('close')
}

function submit() {
  loading.value = true
  
  axios.post('/api/admin', {
    nama: form.value.nama,
    telepon: form.value.telepon,
    password: form.value.password
  })
    .then(() => {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Admin baru berhasil ditambahkan.',
        confirmButtonText: 'Oke'
      }).then(() => {
        emit('submitted')
        emit('close')
      })
    })
    .catch(error => {
      console.error('DETAIL ERROR:', error.response?.data)
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Gagal mendaftarkan admin. Periksa data input.',
        confirmButtonText: 'Tutup'
      })
    })
    .finally(() => {
      loading.value = false
    })
}
</script>

  <style scoped>
  @import '../../../assets/css/DaftarAdmin.css';
  </style>