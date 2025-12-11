<template>
  <div class="pickup-setting-management">
    <!-- Header Section. -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1>⚙️ Pengaturan Pickup</h1>
          <p class="header-subtitle">Kelola rentang jarak, biaya, dan jenis layanan pickup</p>
        </div>
        <button @click="resetForm(); showModal = true" class="btn-add">
          <i class="icon">➕</i>
          Tambah Pickup
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-icon bg-blue">
               <img src="../../assets/img/Service.png" alt="Logo" class="service-logo" />
            </div>
            <div class="stat-info">
              <h3>{{ settings.length }}</h3>
              <p>Total Jenis Layanan</p>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-icon bg-green">
               <img src="../../assets/img/Check.png" alt="Logo" class="check-logo" />
            </div>
            <div class="stat-info">
              <h3>{{ activeSettings }}</h3>
              <p>Pengaturan Aktif</p>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-icon bg-yellow">
              <img src="../../assets/img/KarungDollar.png" alt="Logo" class="karungdollar-logo" />
            </div>
            <div class="stat-info">
              <h3>Rp {{ formatCurrency(averageCost) }}</h3>
              <p>Rata-rata Biaya</p>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-content">
            <div class="stat-icon bg-purple">
              <img src="../../assets/img/Delivery.png" alt="Logo" class="delivery-logo" />
            </div>
            <div class="stat-info">
              <h3>{{ uniqueRangeCount }}</h3>
              <p>Total Layanan</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Filters & Search -->
      <div class="content-header">
        <div class="search-section">
          <div class="search-box">
            <i class="search-icon">🔍</i>
            <input 
              v-model="searchQuery"
              type="text"
              placeholder="Cari pengaturan..."
              class="search-input"
            >
          </div>
          <select v-model="filterStatus" class="filter-select">
            <option value="">Semua Status</option>
            <option value="true">Aktif</option>
            <option value="false">Non-aktif</option>
          </select>
          <select v-model="filterService" class="filter-select">
            <option value="">Semua Layanan</option>
            <option value="pickup_only">Ambil Saja</option>
            <option value="pickup_delivery">Ambil + Antar</option>
            <option value="delivery_only">Antar Saja</option>
          </select>
        </div>
      </div>

      <!-- Settings Table -->
      <div class="table-container">
        <div class="table-wrapper">
          <table class="settings-table">
            <thead>
              <tr>
                <th>Jenis Layanan</th>
                <th>Rentang Jarak</th>
                <th>Biaya</th>
                <th>Status</th>
                <th>Deskripsi</th>
                <th class="actions-col">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredSettings.length === 0">
                <td colspan="6" class="empty-row">
                  <div class="empty-state">
                    <i class="empty-icon">📭</i>
                    <p>Belum ada pengaturan pickup</p>
                    <button @click="resetForm(); showModal = true" class="btn-empty">
                      Tambah Pengaturan Pertama
                    </button>
                  </div>
                </td>
              </tr>
              
              <tr v-for="setting in filteredSettings" :key="setting.id" class="table-row">
                <td>
                  <div class="services-cell">
                    <span class="service-tag" :class="getServiceClass(setting)">
                      {{ getServiceLabel(setting) }}
                    </span>
                  </div>
                </td>
                
                <td>
                  <div class="range-cell">
                    <span class="range-badge">{{ formatDecimal(setting.jarak_min) }} - {{ formatDecimal(setting.jarak_max) }} km</span>
                  </div>
                </td>
                
                <td>
                  <div class="cost-cell">
                    <span class="cost-amount">Rp {{ formatCurrency(setting.biaya) }}</span>
                  </div>
                </td>
                
                <td>
                  <span class="status-badge" :class="{ active: setting.aktif, inactive: !setting.aktif }">
                    {{ setting.aktif ? 'Aktif' : 'Non-aktif' }}
                  </span>
                </td>
                
                <td>
                  <span class="description">{{ setting.deskripsi || '-' }}</span>
                </td>
                
                <td class="actions-cell">
                  <div class="action-buttons">
                    <button @click="editSetting(setting)" class="btn-action edit" title="Edit">
                      <i><img src="../../assets/img/Edit.png" alt="Logo" class="edit-logo" /></i>
                    </button>
                    <button @click="toggleStatus(setting)" class="btn-action toggle" title="Toggle Status">
                      <i>{{ setting.aktif ? '⏸️' : '▶️' }}</i>
                    </button>
                    <button @click="deleteSetting(setting.id)" class="btn-action delete" title="Hapus">
                      <i><img src="../../assets/img/Delete.png" alt="Logo" class="delete-logo" /></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-container">
        <div class="modal-header">
          <h2>{{ editingSetting ? 'Edit Pengaturan' : 'Tambah Pengaturan Baru' }}</h2>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <form @submit.prevent="saveSetting" class="modal-form">
          <div class="form-sections">
            <!-- Jenis Layanan (Primary) -->
            <div class="form-section">
              <h3>🚚 Jenis Layanan</h3>
              <div class="service-selection">
                <label class="service-option" :class="{ selected: form.service_type === 'pickup_only' }">
                  <input type="radio" v-model="form.service_type" value="pickup_only">
                  <div class="option-content">
                    <div class="option-icon">
                      <img src="../../assets/img/KardusPackaging.png" alt="Logo" class="kardus-logo" />
                    </div>
                    <div class="option-text">
                      <span class="option-title">Ambil Saja</span>
                      <span class="option-desc">Hanya mengambil cucian di tempat pelanggan</span>
                    </div>
                  </div>
                </label>
                
                <label class="service-option" :class="{ selected: form.service_type === 'pickup_delivery' }">
                  <input type="radio" v-model="form.service_type" value="pickup_delivery">
                  <div class="option-content">
                    <div class="option-icon">
                      <img src="../../assets/img/Delivery.png" alt="Logo" class="deliver-p-logo" />
                    </div>
                    <div class="option-text">
                      <span class="option-title">Ambil + Antar</span>
                      <span class="option-desc">Mengambil cucian dan mengantar kembali</span>
                    </div>
                  </div>
                </label>

                <label class="service-option" :class="{ selected: form.service_type === 'delivery_only' }">
                  <input type="radio" v-model="form.service_type" value="delivery_only">
                  <div class="option-content">
                    <div class="option-icon">
                      <img src="../../assets/img/House.png" alt="Logo" class="house-logo" />
                    </div>
                    <div class="option-text">
                      <span class="option-title">Antar Saja</span>
                      <span class="option-desc">Hanya mengantar cucian yang sudah selesai</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Rentang Jarak -->
            <div class="form-section">
              <h3><img src="../../assets/img/Distance.png" alt="Logo" class="distance-logo" /> Rentang Jarak</h3>
              <div class="form-row">
                <div class="form-group">
                  <label>Jarak Minimum (km)</label>
                  <input 
                    v-model.number="form.jarak_min"
                    type="number"
                    step="0.1"
                    min="0"
                    placeholder="1.0"
                    class="form-input"
                    required
                  >
                </div>
                <div class="form-group">
                  <label>Jarak Maksimum (km)</label>
                  <input 
                    v-model.number="form.jarak_max"
                    type="number"
                    step="0.1"
                    min="0"
                    placeholder="4.0"
                    class="form-input"
                    required
                  >
                </div>
              </div>
              
              <!-- Conflict Warning -->
              <div v-if="hasRangeConflict" class="conflict-warning">
                <i><img src="../../assets/img/Warning.png" alt="Logo" class="warning-logo" /></i>
                <span>Rentang jarak ini overlap dengan pengaturan <strong>{{ getServiceTypeLabel(form.service_type) }}</strong> yang sudah ada</span>
              </div>
            </div>

            <!-- Biaya -->
            <div class="form-section">
              <h3><img src="../../assets/img/Price.png" alt="Logo" class="price-logo" /> Biaya</h3>
              <div class="form-group">
                <label>Biaya Pickup (Rp)</label>
                <input 
                  v-model.number="form.biaya"
                  type="number"
                  min="0"
                  placeholder="5000"
                  class="form-input"
                  required
                >
                <small class="form-hint">Biaya untuk jenis layanan {{ getServiceTypeLabel(form.service_type) }}</small>
              </div>
            </div>

            <!-- Detail -->
            <div class="form-section">
              <h3><img src="../../assets/img/Detail.png" alt="Logo" class="detail-logo" /> Detail</h3>
              <div class="form-row">
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="form.aktif" class="form-input" required>
                    <option :value="true">Aktif</option>
                    <option :value="false">Non-aktif</option>
                  </select>
                </div>
                <div class="form-group flex-2">
                  <label>Deskripsi</label>
                  <input 
                    v-model="form.deskripsi"
                    type="text"
                    placeholder="Contoh: Jarak dekat dalam kota"
                    class="form-input"
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Preview -->
          <div class="form-preview" v-if="form.service_type && form.jarak_min && form.jarak_max && form.biaya">
            <h4>👁️ Preview</h4>
            <div class="preview-content">
              <div class="preview-item">
                <span class="preview-label">Layanan:</span>
                <span class="preview-value">{{ getServiceTypeLabel(form.service_type) }}</span>
              </div>
              <div class="preview-item">
                <span class="preview-label">Rentang:</span>
                <span class="preview-value">{{ formatDecimal(form.jarak_min) }} - {{ formatDecimal(form.jarak_max) }} km</span>
              </div>
              <div class="preview-item">
                <span class="preview-label">Biaya:</span>
                <span class="preview-value">Rp {{ formatCurrency(form.biaya) }}</span>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn-cancel">
              Batal
            </button>
            <button type="submit" class="btn-save" :disabled="isLoading || hasRangeConflict">
              <span v-if="isLoading" class="loading">⏳</span>
              {{ editingSetting ? 'Update' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'
import axios from 'axios'

// Reactive data
const settings = ref([])
const searchQuery = ref('')
const filterStatus = ref('')
const filterService = ref('')
const isLoading = ref(false)
const editingSetting = ref(null)
const showModal = ref(false)

// Form data
const form = ref({
  service_type: 'pickup_only',
  jarak_min: null,
  jarak_max: null,
  biaya: null,
  deskripsi: '',
  aktif: true
})

// Helper functions for formatting
const formatCurrency = (amount) => {
  if (!amount) return '0'
  return parseInt(amount).toLocaleString('id-ID')
}

const formatDecimal = (number) => {
  if (!number) return '0'
  if (number % 1 === 0) {
    return parseInt(number).toString()
  }
  return parseFloat(number).toFixed(1)
}

// Service type helpers
const getServiceTypeLabel = (serviceType) => {
  switch(serviceType) {
    case 'pickup_only': return '📦 Ambil Saja'
    case 'pickup_delivery': return '🚚 Ambil + Antar'
    case 'delivery_only': return '🏠 Antar Saja'
    default: return 'Tidak Diketahui'
  }
}

const getServiceLabel = (setting) => {
  // For new data structure
  if (setting.service_type) {
    return getServiceTypeLabel(setting.service_type)
  }
  
  // Legacy support for old data structure
  if (setting.pickup_only && !setting.pickup_delivery) {
    return '📦 Ambil Saja'
  } else if (setting.pickup_delivery) {
    return '🚚 Ambil + Antar'
  } else if (setting.delivery_only) {
    return '🏠 Antar Saja'
  }
  return 'Tidak Diketahui'
}

const getServiceClass = (setting) => {
  const serviceType = setting.service_type || 
    (setting.pickup_only && !setting.pickup_delivery ? 'pickup_only' : 
     setting.delivery_only ? 'delivery_only' : 'pickup_delivery')
  
  return {
    'pickup-only': serviceType === 'pickup_only',
    'pickup-delivery': serviceType === 'pickup_delivery',
    'delivery-only': serviceType === 'delivery_only'
  }
}

// Computed properties
const activeSettings = computed(() => {
  return settings.value.filter(setting => setting.aktif).length
})

const uniqueRangeCount = computed(() => {
  const ranges = new Set()
  settings.value.forEach(setting => {
    ranges.add(`${setting.jarak_min}-${setting.jarak_max}`)
  })
  return ranges.size
})

const averageCost = computed(() => {
  if (settings.value.length === 0) return 0
  const total = settings.value.reduce((sum, setting) => sum + parseFloat(setting.biaya), 0)
  return Math.round(total / settings.value.length)
})

// Check for range conflicts when adding/editing
const hasRangeConflict = computed(() => {
  if (!form.value.service_type || !form.value.jarak_min || !form.value.jarak_max) {
    return false
  }

  const formMin = parseFloat(form.value.jarak_min)
  const formMax = parseFloat(form.value.jarak_max)

  return settings.value.some(setting => {
    // Skip current setting when editing
    if (editingSetting.value && setting.id === editingSetting.value.id) {
      return false
    }

    // Get service type dari setting yang ada
    const settingServiceType = setting.service_type || 
      (setting.pickup_only && !setting.pickup_delivery ? 'pickup_only' : 
       setting.delivery_only ? 'delivery_only' : 'pickup_delivery')
    
    // HANYA cek konflik jika jenis layanan SAMA
    if (settingServiceType !== form.value.service_type) {
      return false // Jenis layanan berbeda = TIDAK KONFLIK
    }

    // Jika jenis layanan sama, cek apakah rentang overlap
    const settingMin = parseFloat(setting.jarak_min)
    const settingMax = parseFloat(setting.jarak_max)

    // Deteksi overlap: ada persinggungan antara 2 rentang
    const hasOverlap = (formMin < settingMax && formMax > settingMin)
    
    return hasOverlap
  })
})

const filteredSettings = computed(() => {
  let filtered = settings.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(setting => 
      setting.deskripsi?.toLowerCase().includes(query) ||
      `${setting.jarak_min}-${setting.jarak_max}`.includes(query) ||
      getServiceLabel(setting).toLowerCase().includes(query)
    )
  }

  if (filterStatus.value !== '') {
    const status = filterStatus.value === 'true'
    filtered = filtered.filter(setting => setting.aktif === status)
  }

  if (filterService.value) {
    filtered = filtered.filter(setting => {
      const serviceType = setting.service_type || 
        (setting.pickup_only && !setting.pickup_delivery ? 'pickup_only' : 
         setting.delivery_only ? 'delivery_only' : 'pickup_delivery')
      return serviceType === filterService.value
    })
  }

  // Sort by service type first, then by range
  return filtered.sort((a, b) => {
    const aServiceType = a.service_type || 
      (a.pickup_only && !a.pickup_delivery ? 'pickup_only' : 
       a.delivery_only ? 'delivery_only' : 'pickup_delivery')
    const bServiceType = b.service_type || 
      (b.pickup_only && !b.pickup_delivery ? 'pickup_only' : 
       b.delivery_only ? 'delivery_only' : 'pickup_delivery')
    
    if (aServiceType !== bServiceType) {
      return aServiceType.localeCompare(bServiceType)
    }
    
    return a.jarak_min - b.jarak_min
  })
})

// Methods
const resetForm = () => {
  form.value = {
    service_type: 'pickup_only',
    jarak_min: null,
    jarak_max: null,
    biaya: null,
    deskripsi: '',
    aktif: true
  }
  editingSetting.value = null
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const editSetting = (setting) => {
  editingSetting.value = setting
  
  // Convert old format to new format if needed
  const serviceType = setting.service_type || 
    (setting.pickup_only && !setting.pickup_delivery ? 'pickup_only' : 
     setting.delivery_only ? 'delivery_only' : 'pickup_delivery')
  
  form.value = {
    service_type: serviceType,
    jarak_min: setting.jarak_min,
    jarak_max: setting.jarak_max,
    biaya: setting.biaya,
    deskripsi: setting.deskripsi || '',
    aktif: setting.aktif
  }
  showModal.value = true
}

const saveSetting = async () => {
  isLoading.value = true
  
  try {
    // Validation
    if (form.value.jarak_max <= form.value.jarak_min) {
      throw new Error('Jarak maksimum harus lebih besar dari jarak minimum')
    }

    if (!form.value.service_type) {
      throw new Error('Pilih jenis layanan')
    }

    if (hasRangeConflict.value) {
      throw new Error(`Rentang jarak overlap dengan pengaturan ${getServiceTypeLabel(form.value.service_type)} yang sudah ada`)
    }

    // Convert form data to API format
    const settingData = {
      service_type: form.value.service_type,
      jarak_min: parseFloat(form.value.jarak_min),
      jarak_max: parseFloat(form.value.jarak_max),
      biaya: parseInt(form.value.biaya),
      deskripsi: form.value.deskripsi || '',
      aktif: Boolean(form.value.aktif),
      // For backward compatibility
      pickup_only: form.value.service_type === 'pickup_only',
      pickup_delivery: form.value.service_type === 'pickup_delivery',
      delivery_only: form.value.service_type === 'delivery_only'
    }

    console.log('Sending data to API:', settingData)

    let response
    if (editingSetting.value) {
      console.log('Updating setting with ID:', editingSetting.value.id)
      response = await axios.put(`/api/pickup-settings/${editingSetting.value.id}`, settingData)
      const index = settings.value.findIndex(s => s.id === editingSetting.value.id)
      if (index !== -1) {
        settings.value[index] = response.data.data || response.data
      }
    } else {
      console.log('Creating new setting')
      response = await axios.post('/api/pickup-settings', settingData)
      settings.value.push(response.data.data || response.data)
    }

    console.log('API Response:', response.data)

    Swal.fire({
      title: 'Berhasil!',
      text: `Pengaturan ${editingSetting.value ? 'berhasil diupdate' : 'berhasil ditambahkan'}`,
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    })

    closeModal()
  } catch (error) {
    console.error('Error saving setting:', error)
    
    // Enhanced error handling
    let errorMessage = 'Gagal menyimpan pengaturan'
    
    if (error.response) {
      console.log('Error response:', error.response.data)
      console.log('Error status:', error.response.status)
      
      // Handle specific error formats
      if (error.response.data) {
        if (typeof error.response.data === 'string') {
          errorMessage = error.response.data
        } else if (error.response.data.message) {
          errorMessage = error.response.data.message
        } else if (error.response.data.error) {
          errorMessage = error.response.data.error
        } else if (error.response.data.errors) {
          // Handle Laravel validation errors
          const errors = error.response.data.errors
          const errorFields = Object.keys(errors)
          errorMessage = `Validation Error: ${errorFields.map(field => errors[field][0]).join(', ')}`
        }
      }
      
      if (error.response.status === 422) {
        errorMessage = `Validation Error: ${errorMessage}`
      }
    } else if (error.message) {
      errorMessage = error.message
    }

    Swal.fire({
      title: 'Error!',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'OK'
    })
  } finally {
    isLoading.value = false
  }
}

const deleteSetting = async (settingId) => {
  const result = await Swal.fire({
    title: 'Hapus Pengaturan?',
    text: 'Data akan dihapus permanen',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal'
  })

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/pickup-settings/${settingId}`)
      settings.value = settings.value.filter(setting => setting.id !== settingId)
      
      Swal.fire({
        title: 'Berhasil!',
        text: 'Pengaturan berhasil dihapus',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })
    } catch (error) {
      Swal.fire({
        title: 'Error!',
        text: 'Gagal menghapus pengaturan',
        icon: 'error'
      })
    }
  }
}

const toggleStatus = async (setting) => {
  try {
    await axios.patch(`/api/pickup-settings/${setting.id}/toggle`)
    setting.aktif = !setting.aktif
    
    Swal.fire({
      title: 'Berhasil!',
      text: `Status ${setting.aktif ? 'diaktifkan' : 'dinonaktifkan'}`,
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    Swal.fire({
      title: 'Error!',
      text: error.response?.data?.message || 'Gagal mengubah status',
      icon: 'error'
    })
  }
}

const fetchSettings = async () => {
  try {
    const response = await axios.get('/api/pickup-settings')
    settings.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching settings:', error)
    Swal.fire({
      title: 'Warning',
      text: 'Gagal memuat data pengaturan pickup',
      icon: 'warning'
    })
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
  @import '../../assets/css/PickupManegement.css';

  /* Additional styles for service type selection */
  .service-selection {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .service-option {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fff;
  }

  .service-option:hover {
    border-color: #3b82f6;
    background: #f8fafc;
  }

  .service-option.selected {
    border-color: #3b82f6;
    background: #eff6ff;
  }

  .service-option input[type="radio"] {
    display: none;
  }

  .option-content {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .option-icon {
    font-size: 1.8rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 10px;
    flex-shrink: 0;
  }

  .option-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .option-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
  }

  .option-desc {
    font-size: 0.85rem;
    color: #6b7280;
    line-height: 1.4;
  }

  .conflict-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    color: #92400e;
    margin-top: 8px;
    font-size: 14px;
  }

  .form-hint {
    color: #6b7280;
    font-size: 12px;
    margin-top: 4px;
  }

  /* Filter updates */
  .search-section {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
  }

  .filter-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    font-size: 14px;
    min-width: 140px;
  }

  /* Service tag styles */
  .service-tag {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
  }

  .service-tag.pickup-only {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #3b82f6;
  }

  .service-tag.pickup-delivery {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #10b981;
  }

  .service-tag.delivery-only {
    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    color: #831843;
    border: 1px solid #ec4899;
  }
</style>