<template>
  <!-- Modal Backdrop -->
  <div class="backdrop" @click="closeModal" @keydown.esc="closeModal">
    <div class="popup" @click.stop>
      
      <!-- Header -->
      <div class="header">
        <div>
          <h3>Pilih Pelanggan</h3>
          <p>{{ filteredPelanggan.length }} dari {{ pelangganList.length }} pelanggan</p>
        </div>
        <button class="close" @click="closeModal" title="Tutup (Esc)">✕</button>
      </div>

      <!-- Search -->
      <div class="search-area">
        <div class="search-box">
          <span class="search-icon"><img src="../../assets/img/Maknifier.png" alt="Logo" class="Maknifier-logo"/></span>
          <input 
            ref="searchInput"
            type="text" 
            v-model="searchQuery"
            placeholder="Cari nama atau nomor telepon..."
            class="search-field"
            @keydown.enter="selectFirstResult"
            @keydown.down="navigateDown"
            @keydown.up="navigateUp"
          />
          <button v-if="searchQuery" @click="clearSearch" class="clear">×</button>
        </div>
      </div>

      <!-- Content -->
      <div class="content" @keydown="handleKeydown">
        
        <!-- Loading -->
        <div v-if="isLoading" class="loading">
          <div class="loading-spinner"></div>
          <p>Memuat data pelanggan...</p>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredPelanggan.length === 0" class="empty">
          <div class="empty-icon">👥</div>
          <p v-if="searchQuery">Tidak ditemukan pelanggan dengan kata kunci "{{ searchQuery }}"</p>
          <p v-else>Belum ada pelanggan yang terdaftar</p>
        </div>

        <!-- List -->
        <div v-else class="people-list">
          <div 
            v-for="(pelanggan, index) in paginatedPelanggan" 
            :key="pelanggan.id"
            :class="['person', { 'selected': selectedIndex === index }]"
            @click="selectCustomer(pelanggan)"
            @keydown.enter="selectCustomer(pelanggan)"
            tabindex="0"
          >
            <div class="person-main">
              <div class="person-info">
                <div class="name" v-html="highlightText(pelanggan.nama_pelanggan || pelanggan.nama || 'Nama tidak tersedia')"></div>
                <div class="phone">
                  <img src="../../assets/img/Customer.png" alt="Logo" class="customer-logo"/> <span v-html="highlightText(formatPhone(pelanggan.telepon || pelanggan.phone || ''))"></span>
                </div>
                <div class="meta">
                  <span class="customer-id">#{{ pelanggan.id }}</span>
                  <span class="separator">•</span>
                  <span class="join-date">{{ getJoinDate(pelanggan.created_at) }}</span>
                </div>
              </div>
            </div>
            
            <div class="actions">
              <button 
                class="secondary-btn edit-btn" 
                @click.stop="editPelanggan(pelanggan)"
                title="Edit data"
              >
                <img src="../../assets/img/Edit.png" alt="Logo" class="Edit-logo"/>
              </button>
              <button 
                class="secondary-btn delete-btn" 
                @click.stop="hapusPelanggan(pelanggan.id)"
                title="Hapus pelanggan"
              >
                <img src="../../assets/img/Delete.png" alt="Logo" class="delete-logo"/>
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination in content -->
        <div v-if="totalPages > 1" class="pagination">
          <button 
            class="page-btn"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
            title="Halaman sebelumnya (←)"
          >
            ← Sebelumnya
          </button>
          
          <div class="page-numbers">
            <button 
              v-for="page in visiblePages" 
              :key="page"
              :class="['page-num', { active: page === currentPage }]"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            class="page-btn"
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
            title="Halaman selanjutnya (→)"
          >
            Selanjutnya →
          </button>
        </div>
      </div>

      <!-- Edit Modal -->
      <EditPelanggan
        v-if="showEditModal"
        :pelanggan="selectedPelanggan"
        @close="closeEditModal"
        @updated="onPelangganUpdated"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import EditPelanggan from './EditDataPelanggan.vue'

// Define emits
const emit = defineEmits(['close', 'customer-selected'])

// Reactive data
const pelangganList = ref([])
const isLoading = ref(false)
const showEditModal = ref(false)
const selectedPelanggan = ref(null)
const searchInput = ref(null)
const selectedIndex = ref(-1)

// Search and Pagination
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(18) // Increased since items are very compact now

// Helper functions
const getInitial = (name) => {
  if (!name || typeof name !== 'string') return '?'
  return name.charAt(0).toUpperCase()
}

const formatPhone = (phone) => {
  if (!phone) return 'Tidak tersedia'
  // Format: 0812-3456-7890
  const cleaned = phone.replace(/\D/g, '')
  if (cleaned.length >= 10) {
    return cleaned.replace(/(\d{4})(\d{4})(\d{4})/, '$1-$2-$3')
  }
  return phone
}

const getJoinDate = (date) => {
  if (!date) return 'Pelanggan lama'
  const d = new Date(date)
  return `Bergabung ${d.getFullYear()}`
}

const highlightText = (text) => {
  if (!searchQuery.value || !text) return text
  const regex = new RegExp(`(${searchQuery.value})`, 'gi')
  return text.replace(regex, '<mark>$1</mark>')
}

const clearSearch = () => {
  searchQuery.value = ''
  selectedIndex.value = -1
  nextTick(() => {
    searchInput.value?.focus()
  })
}

// Computed properties
const filteredPelanggan = computed(() => {
  if (!searchQuery.value) return pelangganList.value
  
  const query = searchQuery.value.toLowerCase()
  return pelangganList.value.filter(pelanggan => {
    const nama = (pelanggan.nama_pelanggan || pelanggan.nama || '').toLowerCase()
    const telepon = (pelanggan.telepon || pelanggan.phone || '').toLowerCase()
    return nama.includes(query) || telepon.includes(query)
  })
})

const totalPages = computed(() => {
  return Math.ceil(filteredPelanggan.value.length / itemsPerPage.value)
})

const paginatedPelanggan = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredPelanggan.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - 2)
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Keyboard navigation
const navigateDown = () => {
  if (selectedIndex.value < paginatedPelanggan.value.length - 1) {
    selectedIndex.value++
  }
}

const navigateUp = () => {
  if (selectedIndex.value > 0) {
    selectedIndex.value--
  } else {
    selectedIndex.value = -1
  }
}

const selectFirstResult = () => {
  if (paginatedPelanggan.value.length > 0) {
    selectCustomer(paginatedPelanggan.value[0])
  }
}

const handleKeydown = (e) => {
  if (e.key === 'ArrowLeft' && currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  } else if (e.key === 'ArrowRight' && currentPage.value < totalPages.value) {
    goToPage(currentPage.value + 1)
  }
}

// Watch for search changes
watch(searchQuery, () => {
  currentPage.value = 1
  selectedIndex.value = -1
})

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    selectedIndex.value = -1
  }
}

// Main functions
const fetchPelanggan = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/pelanggan')
    
    if (Array.isArray(response.data)) {
      pelangganList.value = response.data
    } else if (response.data && Array.isArray(response.data.data)) {
      pelangganList.value = response.data.data
    } else {
      pelangganList.value = []
    }
    
  } catch (error) {
    console.error('Error fetching pelanggan:', error)
    await Swal.fire('Gagal', 'Tidak bisa memuat data pelanggan.', 'error')
    pelangganList.value = []
  } finally {
    isLoading.value = false
  }
}

const selectCustomer = (pelanggan) => {
  emit('customer-selected', pelanggan)
  closeModal()
}

const editPelanggan = (pelanggan) => {
  selectedPelanggan.value = pelanggan
  showEditModal.value = true
}

const hapusPelanggan = async (id) => {
  try {
    const result = await Swal.fire({
      title: 'Hapus Pelanggan?',
      text: 'Data akan dihapus permanen',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e74c3c',
      cancelButtonColor: '#95a5a6',
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal'
    })

    if (result.isConfirmed) {
      await axios.delete(`/api/pelanggan/${id}`)
      await fetchPelanggan()
      await Swal.fire('Berhasil', 'Pelanggan berhasil dihapus.', 'success')
    }
  } catch (error) {
    console.error('Error deleting pelanggan:', error)
    await Swal.fire('Gagal', 'Gagal menghapus pelanggan.', 'error')
  }
}

const closeModal = () => {
  emit('close')
}

const closeEditModal = () => {
  showEditModal.value = false
  selectedPelanggan.value = null
}

const onPelangganUpdated = async () => {
  await fetchPelanggan()
  closeEditModal()
}

onMounted(() => {
  fetchPelanggan()
  nextTick(() => {
    searchInput.value?.focus()
  })
})
</script>

<style scoped>
/* Reset & Base */
@import '../../assets/css/DataPelanggan.css';
</style>