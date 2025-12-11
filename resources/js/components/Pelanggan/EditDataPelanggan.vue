<template>
  <div class="modal-overlay" @click="handleOutsideClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3>Edit Pelanggan</h3>
        <button type="button" class="close-btn" @click="emit('close')">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
              placeholder="Masukkan nama"
              required
            />
            <span v-if="errors.nama_pelanggan" class="error-msg">{{ errors.nama_pelanggan }}</span>
          </div>

          <div class="form-group">
            <label for="telepon">Nomor Telepon</label>
            <input
              id="telepon"
              v-model="form.telepon"
              type="text"
              placeholder="08xx-xxxx-xxxx"
              required
            />
            <span v-if="errors.telepon" class="error-msg">{{ errors.telepon }}</span>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="emit('close')">Batal</button>
            <button type="submit" class="btn-primary" :disabled="isLoading">
              {{ isLoading ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

// === PROPS ===
const props = defineProps({
  pelanggan: {
    type: Object,
    default: () => ({
      id: null,
      nama_pelanggan: '',
      telepon: ''
    })
  }
})

// === EMIT ===
const emit = defineEmits(['close', 'updated'])

// === FORM STATE ===
const form = ref({
  id: null,
  nama_pelanggan: '',
  telepon: ''
})
const errors = ref({})
const isLoading = ref(false)

// === WATCH: Sinkronisasi props ke form ===
watch(
  () => props.pelanggan,
  (newVal) => {
    if (newVal && newVal.id) {
      form.value = { ...newVal }
      console.log('🟢 Form loaded:', form.value)
    }
  },
  { immediate: true }
)

// === VALIDASI ===
const validateForm = () => {
  errors.value = {}

  if (!form.value.nama_pelanggan || form.value.nama_pelanggan.trim().length < 2) {
    errors.value.nama_pelanggan = 'Nama minimal 2 karakter'
  }

  if (!form.value.telepon) {
    errors.value.telepon = 'Nomor telepon wajib diisi'
  } else if (!/^[0-9]+$/.test(form.value.telepon)) {
    errors.value.telepon = 'Nomor telepon hanya angka'
  } else if (!form.value.telepon.startsWith('08')) {
    errors.value.telepon = 'Harus diawali 08'
  }

  return Object.keys(errors.value).length === 0
}

// === SUBMIT ===
const submitForm = async () => {
  if (!validateForm()) return

  if (!form.value.id) {
    Swal.fire('Gagal', 'ID pelanggan tidak valid.', 'error')
    return
  }

  isLoading.value = true

  try {
    await axios.put(`/api/pelanggan/${form.value.id}`, {
      nama_pelanggan: form.value.nama_pelanggan.trim(),
      telepon: form.value.telepon.trim()
    })

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Data pelanggan diperbarui.',
      timer: 1500,
      showConfirmButton: false
    })

    emit('updated')
    emit('close')
  } catch (error) {
    console.error('Gagal update:', error)
    Swal.fire('Error', 'Terjadi kesalahan di server.', 'error')
  } finally {
    isLoading.value = false
  }
}

// === CLOSE JIKA KLIK LUAR ===
const handleOutsideClick = () => {
  emit('close')
}
</script>

<style scoped>
@import '../../assets/css/EditDataPelanggan.css';
</style>
