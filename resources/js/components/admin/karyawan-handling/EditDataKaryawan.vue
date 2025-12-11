<template>
  <div class="modal-overlay">
    <div class="modal-container">
      <div class="modal-header">
        <button class="close-btn" @click="$emit('close')">&times;</button>
        <h2 class="modal-title">Edit Data Karyawan</h2>
        <p class="modal-subtitle">
          Perbarui informasi karyawan yang sudah terdaftar
        </p>
      </div>

      <form class="modal-body" @submit.prevent="simpanPerubahan">
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

          <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">Jabatan</label>
            <select v-model="form.jabatan" class="form-select" required>
              <option disabled value="">Pilih Jabatan</option>
              <option value="kasir">Kasir</option>
              <option value="operator">Operator</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn-primary">
            💾 Simpan Perubahan
          </button>
          <button type="button" class="btn-secondary" @click="$emit('close')">
            ❌ Batal
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({ karyawan: Object })
const emit = defineEmits(['close', 'updated'])

const form = reactive({
  nama: '',
  telepon: '',
  jabatan: '',
  status: 'aktif'
})

watch(() => props.karyawan, (val) => {
  if (val) {
    form.nama = val.nama || ''
    form.telepon = val.telepon || ''
    form.jabatan = val.jabatan || ''
  }
}, { immediate: true })

function simpanPerubahan() {
  const regex = /^(08|\+62)[0-9]{8,15}$/

  if (!regex.test(form.telepon)) {
    Swal.fire({
      icon: 'warning',
      title: 'Nomor Tidak Valid',
      text: 'Nomor telepon harus dimulai dengan 08 atau +62 dan hanya angka.',
      confirmButtonText: 'OK'
    })
    return
  }

  axios.put(`/api/karyawan/${props.karyawan.id}`, form)
    .then(() => {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data karyawan berhasil diperbarui.',
        timer: 2000,
        showConfirmButton: false
      })
      emit('updated')
      emit('close')
    })
    .catch(() => {
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Terjadi kesalahan saat menyimpan.',
        confirmButtonText: 'OK'
      })
    })
}
</script>

<style scoped>
@import '../../../assets/css/EditDataKaryawan.css';
</style>
