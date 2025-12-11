<template>
  <div class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h2>🔑 Ubah Kata Sandi Admin</h2>
        <button class="close-btn" @click="closeModal">&times;</button>
      </div>

      <p class="modal-subtitle">
        Masukkan password baru untuk {{ nama }}
      </p>

      <label class="form-label">Password Baru 🔒</label>
      <input
        v-model="password"
        type="password"
        placeholder="Masukkan password baru"
        class="form-input"
      />

      <div class="modal-actions">
        <button class="btn btn-primary" @click="submit">Simpan Kata Sandi Baru</button>
        <button class="btn btn-secondary" @click="closeModal">Batal</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
  nama: String,
  id: Number
})

// ✅ PERBAIKAN: Tambahkan emit 'updated' untuk memberitahu parent
const emit = defineEmits(['close', 'updated'])
const password = ref('')

function closeModal() {
  emit('close')
}

function submit() {
  if (!password.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Password Kosong!',
      text: 'Silakan masukkan password baru terlebih dahulu.'
    })
    return
  }

  if (password.value.length < 6) {
    Swal.fire({
      icon: 'warning',
      title: 'Password Terlalu Pendek!',
      text: 'Password minimal 6 karakter.'
    })
    return
  }

  if (password.value.length > 12) {
    Swal.fire({
      icon: 'warning',
      title: 'Password Terlalu Panjang!',
      text: 'Password maksimal 12 karakter.'
    })
    return
  }

  axios.post(`/api/admin/${props.id}/ubah-password`, {
    password: password.value
  })
    .then(() => {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: `Password untuk admin ${props.nama} telah berhasil diubah.`
      }).then(() => {
        // ✅ PERBAIKAN: Emit 'updated' sebelum close untuk refresh data parent
        emit('updated')
        emit('close')
      })
    })
    .catch(error => {
      console.error('Error:', error)
      Swal.fire({
        icon: 'error',
        title: 'Gagal Mengubah Password',
        text: error.response?.data?.message || 'Terjadi kesalahan saat menyimpan password.'
      })
    })
}
</script>
  <style scoped>
  @import '../../../assets/css/UbahPasswordAdmin.css';
  </style>