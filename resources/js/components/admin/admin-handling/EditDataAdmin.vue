<template>
  <div class="modal-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <button class="close-btn" @click="closeModal">&times;</button>
        <h2 class="modal-title">Edit Data Admin</h2>
        <p class="modal-subtitle">
          Update informasi admin yang sudah terdaftar
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
            <label class="form-label">Jabatan</label>
            <input
              v-model="form.jabatan"
              type="text"
              class="form-input disabled-input"
              readonly
              disabled
            />
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn-primary" :disabled="loading">
            ✏️ {{ loading ? 'Menyimpan...' : 'Update Data' }}
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
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
  admin: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])
const loading = ref(false)

const form = ref({
  nama: '',
  telepon: '',
  jabatan: ''
})

function closeModal() {
  emit('close')
}

function submit() {
  loading.value = true
  
  axios.put(`/api/admin/${props.admin.id}`, {
    nama: form.value.nama,
    telepon: form.value.telepon
  })
    .then(() => {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data admin berhasil diupdate.',
        confirmButtonText: 'Oke'
      }).then(() => {
        emit('updated')
        emit('close')
      })
    })
    .catch(error => {
      console.error('DETAIL ERROR:', error.response?.data)
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Gagal mengupdate data admin. Periksa data input.',
        confirmButtonText: 'Tutup'
      })
    })
    .finally(() => {
      loading.value = false
    })
}

// Load data admin ke form saat component dimount
onMounted(() => {
  form.value = {
    nama: props.admin.nama,
    telepon: props.admin.telepon,
    jabatan: props.admin.jabatan
  }
})
</script>


  <style scoped>
  @import '../../../assets/css/EditDataAdmin.css';
  </style>