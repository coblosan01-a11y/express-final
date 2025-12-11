<template>
  <div class="modal-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <h3>Tambah Pelanggan Baru</h3>
        <button type="button" class="close-btn" @click="confirmClose">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="submitForm" class="form-container">
          <div class="form-group">
            <label for="nama">Nama Pelanggan</label>
            <input 
              id="nama"
              v-model="form.nama_pelanggan" 
              type="text" 
              placeholder="Masukkan nama lengkap" 
              required 
            />
          </div>

          <div class="form-group">
            <label for="telepon">Nomor Telepon</label>
            <input 
              id="telepon"
              v-model="form.telepon" 
              type="text" 
              placeholder="08xx-xxxx-xxxx" 
            />
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="confirmClose">
              Batal
            </button>
            <button type="submit" class="btn-primary" :disabled="isSubmitting">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              {{ isSubmitting ? 'Menyimpan...' : 'Simpan Data' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

// Form state using reactive object
const form = reactive({
  nama_pelanggan: '',
  telepon: ''
})

// Loading state
const isSubmitting = ref(false)

// Emit event ke parent
const emit = defineEmits(['close', 'submitted'])

// Fungsi untuk konfirmasi tutup modal
const confirmClose = () => {
  // Jika form sudah diisi, konfirmasi dulu
  if (form.nama_pelanggan.trim() || form.telepon.trim()) {
    Swal.fire({
      title: 'Tutup Modal?',
      text: 'Data yang sudah diisi akan hilang',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Tutup',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        resetForm()
        emit('close')
      }
    })
  } else {
    emit('close')
  }
}

// Fungsi reset form
const resetForm = () => {
  form.nama_pelanggan = ''
  form.telepon = ''
}

// Fungsi submit form
const submitForm = async () => {
  // Validasi form
  if (!form.nama_pelanggan.trim()) {
    Swal.fire('Nama Wajib', 'Nama pelanggan harus diisi.', 'warning')
    return
  }

  if (!form.telepon.trim()) {
    Swal.fire('Telepon Wajib', 'Nomor telepon harus diisi.', 'warning')
    return
  }

  // Cek validasi depan: nomor harus diawali 08 & hanya angka
  const phoneRegex = /^08\d+$/
  if (!phoneRegex.test(form.telepon)) {
    Swal.fire('Nomor Tidak Valid', 'Nomor telepon harus diawali dengan 08 dan hanya angka.', 'warning')
    return
  }

  // Minimum length check
  if (form.telepon.length < 10) {
    Swal.fire('Nomor Terlalu Pendek', 'Nomor telepon minimal 10 digit.', 'warning')
    return
  }

  isSubmitting.value = true

  try {
    const response = await axios.post('/api/pelanggan', {
      nama_pelanggan: form.nama_pelanggan.trim(),
      telepon: form.telepon.trim(),
    })

    Swal.fire('Berhasil!', 'Data pelanggan berhasil disimpan.', 'success')
    
    // Reset form
    resetForm()
    
    // Emit events ke parent
    emit('submitted') // Panggil parent untuk refresh
    emit('close')     // Tutup modal

  } catch (error) {
    console.error('Error submitting form:', error)
    
    if (error.response && error.response.status === 422) {
      // Tampilkan error validasi Laravel
      const errors = error.response.data.errors
      let pesan = ''
      for (const field in errors) {
        pesan += errors[field].join('\n') + '\n'
      }
      Swal.fire('Gagal', pesan.trim(), 'error')
    } else if (error.response && error.response.status === 409) {
      // Conflict - data sudah ada
      Swal.fire('Data Sudah Ada', 'Pelanggan dengan nomor telepon ini sudah terdaftar.', 'warning')
    } else {
      Swal.fire('Error', 'Terjadi kesalahan server. Silakan coba lagi.', 'error')
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
@import '../../assets/css/TambahPelanggan.css';

/* Additional styles for disabled state */
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>