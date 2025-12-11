<template>
  <div class="pickup-service-wrapper">
    <!-- Pickup Trigger Button (when no active pickup) -->
    <div v-if="!hasActivePickup" class="pickup-trigger">
      <button 
        class="pickup-btn"
        @click="openPickupModal"
        :disabled="!hasAvailableRanges"
      >
        <span class="pickup-icon"><img src="../../assets/img/Delivery.png" alt="Logo" class="delivery-jm-logo"/></span>
        <div class="pickup-content">
          <span class="pickup-title">Layanan Pickup</span>
          <span class="pickup-subtitle">Layanan pickup tersedia</span>
        </div>
      </button>
      
      <div v-if="!hasAvailableRanges" class="no-ranges-info">
        <img src="../../assets/img/Warning.png" alt="logoo" class="warning-warning-logo"> Tidak ada layanan pickup tersedia
      </div>
    </div>

    <!-- Active Pickup Summary (compact display) -->
    <div v-if="hasActivePickup" class="active-pickup-summary">
      <div class="pickup-summary-header">
        <div class="pickup-info">
          <span class="pickup-service">{{ getServiceIcon(activePickup.serviceType) }} {{ activePickup.serviceName }}</span>
          <span class="pickup-cost">Rp {{ activePickup.totalCost.toLocaleString() }}</span>
        </div>
        <div class="pickup-actions">
          <button @click="editPickup" class="btn-edit-mini" title="Edit pickup">✏️</button>
          <button @click="removePickupService" class="btn-remove-mini" title="Hapus pickup">✕</button>
        </div>
      </div>
      
      <div class="pickup-summary-details">
        <span v-if="activePickup.pickupDate && activePickup.pickupTime" class="detail-mini">
           {{ formatPickupDate(activePickup.pickupDate, activePickup.pickupTime) }}
        </span>
        <span class="detail-mini"> {{ activePickup.jarak }} km ({{ activePickup.rentang }})</span>
      </div>
    </div>

    <!-- Pickup Modal -->
    <div v-if="showModal" class="pickup-modal-overlay" @click.self="closeModal">
      <div class="pickup-modal">
        <div class="modal-header">
          <h2>Layanan Pickup</h2>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <div class="modal-body">
          <!-- Step 1: Distance Input -->
          <div class="step-section">
            <h3><img src="../../assets/img/Distance.png" alt="logoo" class="distance-warning-logo">Jarak Tempuh</h3>
            <div class="distance-input-group">
              <input 
                v-model.number="pickupForm.jarak"
                type="number"
                step="0.1"
                min="0"
                max="100"
                class="distance-input-modal"
                placeholder="Masukkan jarak dalam km..."
                @input="onDistanceChange"
              />
              <span class="distance-unit-modal">KM</span>
            </div>
            
            <div v-if="availableRanges.length > 0" class="ranges-info">
              <small>Rentang tersedia: 
                <span v-for="(range, index) in uniqueRanges" :key="range.id" class="range-chip">
                  {{ range.rentang }}{{ index < uniqueRanges.length - 1 ? ', ' : '' }}
                </span>
              </small>
            </div>
          </div>

          <!-- Step 2: Service Selection -->
          <div v-if="availableServices.length > 0" class="step-section">
            <h3>🚚 Pilih Layanan</h3>
            <div class="services-modal-grid">
              <div 
                v-for="(service, index) in availableServices" 
                :key="index"
                class="service-modal-card"
                :class="{ 'selected': selectedService?.type === service.type }"
                @click="selectService(service)"
              >
                <div class="service-modal-header">
                  <span class="service-modal-icon">{{ service.icon }}</span>
                  <div class="service-modal-info">
                    <h4>{{ service.name }}</h4>
                    <p>{{ service.description }}</p>
                  </div>
                </div>
                
                <div class="service-modal-price">
                  <span class="price-total">Rp {{ service.totalCost.toLocaleString() }}</span>
                  <span class="price-breakdown">{{ service.name }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- No Service Available -->
          <div v-else-if="pickupForm.jarak > 0 && availableServices.length === 0" class="no-service-modal">
            <div class="no-service-content">
              <span class="no-service-icon">⚠️</span>
              <div class="no-service-text">
                <span>Tidak ada layanan untuk jarak {{ pickupForm.jarak }} km</span>
                <small v-if="availableRanges.length > 0">
                  Coba jarak dalam rentang yang tersedia di atas
                </small>
              </div>
            </div>
          </div>

          <!-- Step 3: Pickup Details -->
          <div v-if="selectedService" class="step-section">
            <h3> Detail Pickup (Opsional)</h3>
            
            <!-- Date & Time -->
            <div class="form-row-modal">
              <div class="form-group-modal">
                <label>📅 Tanggal <small style="color: #6b7280;">(Opsional)</small></label>
                <input 
                  v-model="pickupForm.pickupDate"
                  type="date"
                  class="input-modal"
                  :min="minDate"
                />
              </div>
              <div class="form-group-modal">
                <label>⏰ Waktu <small style="color: #6b7280;">(Opsional)</small></label>
                <input 
                  v-model="pickupForm.pickupTime"
                  type="time"
                  class="input-modal"
                  placeholder="HH:MM"
                />
              </div>
            </div>

            <!-- Notes -->
            <div class="form-group-modal">
              <label>Catatan Khusus <small style="color: #6b7280;">(Opsional)</small></label>
              <textarea 
                v-model="pickupForm.specialInstructions"
                class="textarea-modal"
                placeholder="Instruksi khusus, patokan lokasi, dll..."
                rows="3"
              ></textarea>
            </div>

            <!-- Cost Summary -->
            <div class="cost-summary-modal">
              <div class="cost-header">💰 Ringkasan Biaya</div>
              <div class="cost-details">
                <div class="cost-line">
                  <span>{{ pickupForm.jarak }} KM ({{ finalCost.rentang }})</span>
                  <span>{{ selectedService.name }}</span>
                </div>
                <div class="cost-total-line">
                  <span>Total Biaya</span>
                  <span class="total-amount">Rp {{ finalCost.totalCost.toLocaleString() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
          <button @click="closeModal" class="btn-modal-cancel">
            ❌ Batal
          </button>
          <button 
            @click="confirmPickup"
            class="btn-modal-confirm"
            :disabled="!isFormValid || isLoading"
          >
            <span v-if="isLoading" class="loading-spinner">⏳</span>
            ✅ Konfirmasi Pickup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'
import axios from 'axios'

const emit = defineEmits(['pickup-added', 'pickup-removed', 'cost-updated'])

// Reactive data
const showModal = ref(false)
const activePickup = ref(null)
const availableRanges = ref([])
const availableServices = ref([])
const selectedService = ref(null)
const finalCost = ref(null)
const isLoading = ref(false)

// Form data
const pickupForm = ref({
  jarak: null,
  pickupDate: '',
  pickupTime: '',
  specialInstructions: ''
})

// Computed properties
const hasActivePickup = computed(() => activePickup.value !== null)

const hasAvailableRanges = computed(() => availableRanges.value.length > 0)

// Get unique ranges (remove duplicates for display)
const uniqueRanges = computed(() => {
  const unique = new Map()
  availableRanges.value.forEach(range => {
    const key = `${range.jarak_min}-${range.jarak_max}`
    if (!unique.has(key)) {
      unique.set(key, range)
    }
  })
  return Array.from(unique.values())
})

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const isFormValid = computed(() => {
  // Only require distance and service selection
  // Date and time are now optional
  return pickupForm.value.jarak > 0 &&
         selectedService.value &&
         finalCost.value !== null
})

// Methods
const openPickupModal = () => {
  if (!hasAvailableRanges.value) {
    Swal.fire({
      title: 'Oops!',
      text: 'Tidak ada layanan pickup yang tersedia saat ini',
      icon: 'warning',
      confirmButtonColor: '#3b82f6'
    })
    return
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  pickupForm.value = {
    jarak: null,
    pickupDate: '',
    pickupTime: '',
    specialInstructions: ''
  }
  selectedService.value = null
  availableServices.value = []
  finalCost.value = null
}

const onDistanceChange = async () => {
  selectedService.value = null
  availableServices.value = []
  finalCost.value = null

  if (!pickupForm.value.jarak || pickupForm.value.jarak <= 0) {
    return
  }

  await loadAvailableServices()
}

const loadAvailableServices = async () => {
  if (!pickupForm.value.jarak) return

  try {
    // Find matching ranges for the given distance
    const matchingRanges = availableRanges.value.filter(range => {
      const distance = parseFloat(pickupForm.value.jarak)
      return distance >= parseFloat(range.jarak_min) && distance <= parseFloat(range.jarak_max)
    })

    if (matchingRanges.length === 0) {
      availableServices.value = []
      return
    }

    console.log('Matching ranges found:', matchingRanges)

    // Convert each matching range directly to a service option
    // Now supporting 3 service types: pickup_only, pickup_delivery, delivery_only
    const services = matchingRanges.map(range => {
      let serviceType, serviceName, serviceIcon, serviceDesc
      
      // NEW: Check for delivery_only first
      if (range.service_type === 'delivery_only' || (range.delivery_only && !range.pickup_only && !range.pickup_delivery)) {
        serviceType = 'delivery_only'
        serviceName = 'Antar Saja'
        serviceIcon = '🏠'
        serviceDesc = 'Hanya mengantar cucian yang sudah selesai'
      }
      // Check for pickup_only
      else if (range.service_type === 'pickup_only' || (range.pickup_only && !range.pickup_delivery && !range.delivery_only)) {
        serviceType = 'pickup_only'
        serviceName = 'Ambil Saja'
        serviceIcon = '📦'
        serviceDesc = 'Hanya mengambil cucian di tempat pelanggan'
      }
      // Check for pickup_delivery
      else if (range.service_type === 'pickup_delivery' || range.pickup_delivery) {
        serviceType = 'pickup_delivery'
        serviceName = 'Ambil + Antar'
        serviceIcon = '🚚'
        serviceDesc = 'Mengambil cucian dan mengantar kembali'
      }
      // Fallback
      else {
        serviceType = 'pickup_only'
        serviceName = 'Ambil Saja'
        serviceIcon = '📦'
        serviceDesc = 'Hanya mengambil cucian di tempat pelanggan'
      }

      return {
        type: serviceType,
        name: serviceName,
        description: serviceDesc,
        icon: serviceIcon,
        baseCost: parseInt(range.biaya),
        totalCost: parseInt(range.biaya), // Use exact cost from management
        rentang: range.rentang || `${range.jarak_min}-${range.jarak_max} km`,
        settingId: range.id
      }
    })

    availableServices.value = services

    console.log('Available services:', availableServices.value)

    // Auto select if only one service available
    if (availableServices.value.length === 1) {
      selectService(availableServices.value[0])
    }

  } catch (error) {
    console.error('Error loading services:', error)
    availableServices.value = []
  }
}

const selectService = (service) => {
  selectedService.value = selectedService.value?.type === service.type ? null : service
  
  if (selectedService.value) {
    finalCost.value = {
      baseCost: service.baseCost,
      totalCost: service.totalCost,
      rentang: service.rentang,
      settingId: service.settingId,
      serviceType: service.type
    }
    
    emit('cost-updated', service.totalCost)
  } else {
    finalCost.value = null
    emit('cost-updated', 0)
  }
}

const confirmPickup = async () => {
  if (!isFormValid.value) {
    Swal.fire({
      title: 'Oops!',
      text: 'Harap lengkapi jarak dan pilih layanan',
      icon: 'warning',
      confirmButtonColor: '#3b82f6'
    })
    return
  }

  isLoading.value = true

  try {
    const pickupData = {
      jarak: pickupForm.value.jarak,
      rentang: finalCost.value.rentang,
      serviceType: selectedService.value.type,
      serviceName: selectedService.value.name,
      pickupDate: pickupForm.value.pickupDate || null,
      pickupTime: pickupForm.value.pickupTime || null,
      specialInstructions: pickupForm.value.specialInstructions.trim() || null,
      totalCost: finalCost.value.totalCost,
      baseCost: finalCost.value.baseCost,
      settingId: finalCost.value.settingId
    }

    activePickup.value = pickupData
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Layanan pickup berhasil ditambahkan',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })

    emit('pickup-added', pickupData)
    closeModal()

  } catch (error) {
    console.error('Save pickup error:', error)
    Swal.fire({
      title: 'Error!',
      text: 'Gagal menyimpan layanan pickup',
      icon: 'error',
      confirmButtonColor: '#ef4444'
    })
  } finally {
    isLoading.value = false
  }
}

const editPickup = () => {
  if (activePickup.value) {
    // Restore form with current pickup data
    pickupForm.value = {
      jarak: activePickup.value.jarak,
      pickupDate: activePickup.value.pickupDate || '',
      pickupTime: activePickup.value.pickupTime || '',
      specialInstructions: activePickup.value.specialInstructions || ''
    }
    
    // Load services for this distance
    onDistanceChange()
    
    // Clear active pickup and open modal
    removePickupService(false) // false = don't show confirmation
    openPickupModal()
  }
}

const removePickupService = (showConfirm = true) => {
  if (showConfirm) {
    Swal.fire({
      title: 'Hapus Pickup?',
      text: 'Layanan pickup akan dihapus dari pesanan',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal'
    }).then(result => {
      if (result.isConfirmed) {
        executeRemovePickup()
      }
    })
  } else {
    executeRemovePickup()
  }
}

const executeRemovePickup = () => {
  const removedPickup = activePickup.value
  activePickup.value = null
  
  emit('pickup-removed', removedPickup)
  
  if (removedPickup) {
    Swal.fire({
      title: 'Berhasil!',
      text: 'Layanan pickup berhasil dihapus',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })
  }
}

const getServiceIcon = (serviceType) => {
  switch(serviceType) {
    case 'pickup_only': return '📦'
    case 'pickup_delivery': return '🚚'
    case 'delivery_only': return '🏠'
    default: return '📦'
  }
}

const formatPickupDate = (date, time) => {
  if (!date) return 'Tanggal belum ditentukan'
  const dateObj = new Date(date)
  const day = dateObj.getDate()
  const month = dateObj.toLocaleDateString('id-ID', { month: 'short' })
  return time ? `${day} ${month}, ${time}` : `${day} ${month}`
}

const fetchAvailableRanges = async () => {
  try {
    // Use the same endpoint as PickupManagement to ensure consistency
    const response = await axios.get('/api/pickup-settings')
    
    // Filter only active settings
    const allSettings = response.data.data || response.data
    const activeSettings = allSettings.filter(setting => setting.aktif === true || setting.aktif === 1)
    
    // Process data to ensure consistency with PickupManagement format
    availableRanges.value = activeSettings.map(setting => ({
      id: setting.id,
      jarak_min: parseFloat(setting.jarak_min),
      jarak_max: parseFloat(setting.jarak_max),
      biaya: parseInt(setting.biaya),
      service_type: setting.service_type, // New format
      pickup_only: Boolean(setting.pickup_only), // Legacy support
      pickup_delivery: Boolean(setting.pickup_delivery), // Legacy support
      delivery_only: Boolean(setting.delivery_only), // NEW: delivery_only support
      deskripsi: setting.deskripsi || '',
      aktif: Boolean(setting.aktif),
      rentang: setting.rentang || `${setting.jarak_min}-${setting.jarak_max} km`
    }))

    console.log('Available ranges loaded:', availableRanges.value)
    
  } catch (error) {
    console.error('Error fetching ranges:', error)
    availableRanges.value = []
    
    // Show user-friendly error
    if (error.response?.status === 404) {
      console.warn('No pickup ranges available')
    } else {
      Swal.fire({
        title: 'Warning',
        text: 'Gagal memuat data rentang pickup',
        icon: 'warning',
        timer: 3000
      })
    }
  }
}

// Expose methods for parent component
defineExpose({
  getActivePickup: () => activePickup.value,
  clearPickup: () => { 
    activePickup.value = null 
    emit('pickup-removed')
  },
  setPickup: (pickupData) => { 
    activePickup.value = pickupData 
    emit('pickup-added', pickupData)
  },
  refreshRanges: fetchAvailableRanges
})

onMounted(() => {
  fetchAvailableRanges()
})
</script>

<style scoped>
@import '../../assets/css/PickupOrder.css';
</style>