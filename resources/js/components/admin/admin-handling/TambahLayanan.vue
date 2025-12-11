<template>
  <div class="modal-overlay">
    <transition name="modal">
      <div class="modal-content">
        <!-- HEADER -->
        <div class="modal-header">
          <h2>Tambah Jenis Layanan</h2>
          <button class="close-btn" @click="closeModal">×</button>
        </div>
        
        <!-- SUBTITLE -->
        <p class="modal-subtitle">
          Lengkapi form untuk menambahkan layanan baru
        </p>

        <!-- FORM -->
        <form @submit.prevent="submit" class="modal-form">
          <!-- Nama Layanan -->
          <div class="form-group">
            <label>Nama Layanan</label>
            <input
              v-model="form.nama"
              type="text"
              placeholder="Contoh: Cuci Kering"
              class="form-input"
              required
            />
          </div>

          <!-- Metode Perhitungan -->
          <div class="form-group">
            <label>Metode Perhitungan</label>
            <div class="multiselect-group">
              <VueMultiselect
                v-model="form.metode"
                :options="availableUnits"
                :multiple="true"
                placeholder="Pilih satu atau lebih"
                close-on-select="false"
                clear-on-select="false"
                class="custom-multiselect"
              />
              <button type="button" class="btn-add" @click="tambahSatuan" title="Tambah Metode Baru">+</button>
            </div>
          </div>

          <!-- Harga per Unit -->
          <div
            class="form-group"
            v-for="unit in form.metode"
            :key="unit"
          >
            <label>Harga per {{ unit }} (Rp)</label>
            <input
              v-model.number="form.hargaPerUnit[unit]"
              type="number"
              placeholder="Masukkan harga"
              class="form-input"
              required
            />
          </div>

          <!-- Deskripsi -->
          <div class="form-group">
            <label>Deskripsi (Opsional)</label>
            <textarea
              v-model="form.deskripsi"
              placeholder="Contoh: Layanan ini hanya untuk pakaian ringan..."
              class="form-input"
              rows="2"
            ></textarea>
          </div>

          <!-- ACTIONS -->
          <div class="modal-actions">
            <button type="submit" class="btn btn-primary">Tambah</button>
            <button type="button" class="btn btn-secondary" @click="closeModal">Batal</button>
          </div>
        </form>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import VueMultiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.min.css'

const emit = defineEmits(['close', 'submitted'])

const availableUnits = ref(['Kg', 'Pcs'])

const form = reactive({
  nama: '',
  metode: [],
  hargaPerUnit: {},
  deskripsi: '',
})

function tambahSatuan() {
  Swal.fire({
    title: 'Tambah Metode Baru',
    input: 'text',
    inputPlaceholder: 'Contoh: Liter',
    showCancelButton: true,
    confirmButtonText: 'Simpan',
  }).then(result => {
    if (result.isConfirmed && result.value.trim()) {
      const newUnit = result.value.trim()
      if (!availableUnits.value.includes(newUnit)) {
        availableUnits.value.push(newUnit)
      }
    }
  })
}

function submit() {
  if (!form.nama || form.metode.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Form belum lengkap',
      text: 'Nama layanan & metode harus diisi.',
    })
    return
  }

  for (const unit of form.metode) {
    if (!form.hargaPerUnit[unit]) {
      Swal.fire({
        icon: 'warning',
        title: 'Form belum lengkap',
        text: `Harga untuk ${unit} belum diisi.`,
      })
      return
    }
  }

  axios.post('/api/layanan', { ...form })
    .then(() => {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Layanan berhasil ditambahkan.',
        timer: 1500,
        showConfirmButton: false
      })
      emit('submitted')
      closeModal()
    })
    .catch(err => {
      console.error(err)
      Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan.', 'error')
    })
}

function closeModal() {
  emit('close')
}
</script>

<style scoped>
@import '../../../assets/css/TambahLayanan.css';
</style>
