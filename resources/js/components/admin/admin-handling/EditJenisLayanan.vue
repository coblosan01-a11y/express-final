<template>
  <div class="modal-overlay">
    <div class="modal-box">
      <!-- HEADER -->
      <div class="modal-header">
        <h2>Edit Jenis Layanan</h2>
          <button class="close-btn" @click="closeModal"> × </button>
      </div>
      <p class="modal-subtitle">
        Perbarui detail layanan laundry di bawah ini.
      </p>

      <!-- FORM -->
      <form @submit.prevent="submit" class="modal-form">
        <!-- Nama Layanan -->
        <div class="form-group">
          <label>Nama Layanan</label>
          <input
            v-model.trim="form.nama"
            type="text"
            placeholder="Contoh: Cuci Kering"
            class="form-input"
            required
          />
        </div>

        <!-- Metode Perhitungan -->
        <!-- Metode Perhitungan -->
<div class="form-group">
  <label>Metode Perhitungan</label>
  <div class="multiselect-row">
    <VueMultiselect
      v-model="form.metode"
      :options="availableUnits"
      :multiple="true"
      placeholder="Pilih satu atau lebih"
      close-on-select="false"
      clear-on-select="false"
      class="custom-multiselect"
    />
    <button
      type="button"
      class="btn-add-blue"
      @click="tambahSatuan"
      title="Tambah Satuan Baru"
    >
      +
    </button>
  </div>
</div>


        <!-- Harga per satuan -->
        <div
          class="form-group"
          v-for="unit in form.metode"
          :key="unit"
        >
          <label>Harga per {{ unit }} (Rp)</label>
          <input
            v-model.number="form.hargaPerUnit[unit]"
            type="number"
            min="0"
            class="form-input"
            :placeholder="`Masukkan harga per ${unit}`"
            required
          />
        </div>

        <!-- Deskripsi -->
        <div class="form-group">
          <label>Deskripsi (Opsional)</label>
          <textarea
            v-model.trim="form.deskripsi"
            class="form-input"
            placeholder="Keterangan tambahan layanan"
            rows="2"
          ></textarea>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="modal-actions">
          <button type="submit" class="btn btn-primary">
            Simpan Perubahan
          </button>
          <button type="button" class="btn btn-secondary" @click="closeModal">
            Batal
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import VueMultiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.min.css'

const props = defineProps({
  layanan: { type: Object, required: true }
})

const emit = defineEmits(['close', 'updated'])

// Sama: default + satuan tambahan
const defaultUnits = ['Kg', 'Pcs']
const extraUnits = ref([])

const availableUnits = computed(() => [...defaultUnits, ...extraUnits.value])

// Form reactive
const form = reactive({
  nama: '',
  metode: [],
  hargaPerUnit: {},
  deskripsi: '',
})

// Saat layanan berubah → reset form + extraUnits
watch(
  () => props.layanan,
  (val) => {
    if (val) {
      form.nama = val.nama || ''
      form.metode = Array.isArray(val.metode) ? [...val.metode] : []
      form.hargaPerUnit = val.hargaPerUnit ? { ...val.hargaPerUnit } : {}
      form.deskripsi = val.deskripsi || ''
    }
    extraUnits.value = []
  },
  { immediate: true }
)

// Tambah satuan baru
function tambahSatuan() {
  Swal.fire({
    title: 'Tambah Satuan Baru',
    input: 'text',
    inputPlaceholder: 'Contoh: Liter',
    showCancelButton: true,
    confirmButtonText: 'Simpan',
  }).then(result => {
    const newUnit = result.value?.trim()
    if (result.isConfirmed && newUnit) {
      if (![...defaultUnits, ...extraUnits.value].includes(newUnit)) {
        extraUnits.value.push(newUnit)
      } else {
        Swal.fire('Info', 'Satuan sudah ada.', 'info')
      }
    }
  })
}

// Submit
function submit() {
  if (!form.nama.trim() || form.metode.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Form belum lengkap',
      text: 'Nama layanan & metode wajib diisi.',
    })
    return
  }

  for (const unit of form.metode) {
    if (!form.hargaPerUnit[unit] || form.hargaPerUnit[unit] <= 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Form belum lengkap',
        text: `Harga untuk ${unit} harus diisi & lebih dari 0.`,
      })
      return
    }
  }

  const payload = {
    nama: form.nama,
    metode: form.metode,
    hargaPerUnit: form.hargaPerUnit,
    deskripsi: form.deskripsi,
  }

  axios
    .put(`/api/layanan/${props.layanan.id}`, payload)
    .then(() => {
      Swal.fire('Berhasil!', 'Layanan berhasil diperbarui.', 'success')
      emit('updated')
      closeModal()
    })
    .catch(err => {
      console.error(err)
      if (err.response?.status === 422) {
        const errors = err.response.data.errors
        Swal.fire('Gagal!', Object.values(errors).join(', '), 'error')
      } else {
        Swal.fire('Gagal!', 'Terjadi kesalahan server.', 'error')
      }
    })
}

function closeModal() {
  emit('close')
}
</script>

<style scoped>
@import '../../../assets/css/EditJenisLayanan.css';
</style>
