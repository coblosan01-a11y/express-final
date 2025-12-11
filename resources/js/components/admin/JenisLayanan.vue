<template>
  <div class="jenis-layanan-container">
    <section class="card">
      <div class="card-header">
        <div class="card-header-text">
          <h2>Kelola Jenis Layanan</h2>
          <p>Atur jenis layanan & metode perhitungannya</p>
        </div>
        <div class="actions">
          <select v-model="filterMetode" class="form-input">
            <option value="">Semua Metode</option>
            <option
              v-for="unit in metodeUnik"
              :key="unit"
              :value="unit"
            >
              {{ formatMetode(unit) }}
            </option>
          </select>
          <button class="btn-blue" @click="showModal = true">
            + Tambah Layanan
          </button>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Metode</th>
            <th>Harga</th>
            <th>Deskripsi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="5">🔄 Mengambil data...</td>
          </tr>
          <tr v-for="layanan in layananDitampilkan" :key="layanan.id">
            <td>{{ layanan.nama }}</td>
            <td>
              <ul>
                <li v-for="(m, idx) in layanan.metode" :key="idx">{{ formatMetode(m) }}</li>
              </ul>
            </td>
            <td>
              <ul>
                <li v-for="(harga, satuan) in layanan.hargaPerUnit" :key="satuan">
                  Rp {{ harga.toLocaleString() }}/{{ satuan }}
                </li>
              </ul>
            </td>
            <td>{{ layanan.deskripsi }}</td>
            <td>
              <button class="btn-small" @click="editLayanan(layanan)">Edit</button>
              <button class="btn-danger" @click="hapusLayanan(layanan.id)">Hapus</button>
            </td>
          </tr>
          <tr v-if="!isLoading && layananDitampilkan.length === 0">
            <td colspan="5">Data tidak ditemukan</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- Modals -->
    <TambahLayanan v-if="showModal" @close="showModal = false" @submitted="fetchData" />
    <EditJenisLayanan
      v-if="showEditModal"
      :layanan="layananTerpilih"
      @close="showEditModal = false"
      @updated="fetchData"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import TambahLayanan from '../admin/admin-handling/TambahLayanan.vue'
import EditJenisLayanan from '../admin/admin-handling/EditJenisLayanan.vue'

const daftarLayanan = ref([])
const isLoading = ref(false)
const filterMetode = ref('')
const showModal = ref(false)
const showEditModal = ref(false)
const layananTerpilih = ref(null)

// ✅ Buat metode unik otomatis dari data
const metodeUnik = computed(() => {
  const all = daftarLayanan.value.flatMap(l => l.metode)
  const unique = [...new Set(all)]
  return unique
})

const layananDitampilkan = computed(() => {
  if (!filterMetode.value) return daftarLayanan.value
  return daftarLayanan.value.filter(l =>
    l.metode.some(m => m === filterMetode.value)
  )
})

function fetchData() {
  isLoading.value = true
  axios.get('/api/layanan')
    .then(res => { daftarLayanan.value = res.data })
    .catch(err => { console.error(err) })
    .finally(() => { isLoading.value = false })
}

function hapusLayanan(id) {
  Swal.fire({
    title: 'Hapus?',
    text: 'Data akan dihapus permanen',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya',
  }).then(result => {
    if (result.isConfirmed) {
      axios.delete(`/api/layanan/${id}`).then(() => fetchData())
    }
  })
}

function editLayanan(layanan) {
  layananTerpilih.value = layanan
  showEditModal.value = true
}

function formatMetode(m) {
  if (m === 'Kg') return 'Per Kilogram'
  if (m === 'Pcs') return 'Per Quantity'
  return m
}

onMounted(() => fetchData())
</script>

<style>
@import '../../assets/css/JenisLayanan.css';
</style>
