<template>
  <div class="page-container">
    <!-- NavSidebar Component -->
    <NavSidebar @logout="handleLogout" />
    
    <!-- Main Content -->
    <div class="main-content">
      <!-- Main POS Content -->
      <div class="pos-content">
        <!-- Left Panel - Service Selection -->
        <div class="left-panel">
          <!-- Customer Selection Section -->
          <div class="section">
            <h3><img src="../assets/img/Customer.png" alt="Logo" class="Customersatu-logo"/>Customer</h3>
            <div class="customer-section">
              <!-- Customer Phone Input -->
              <div class="input-group">
                <label><img src="../assets/img/Phone-call.png" alt="Logo" class="phone-call-logo"/> Nomor Telepon Customer</label>
                <div class="customer-input-wrapper">
                  <input 
                    :value="customerStore.customerPhone"
                    @input="onPhoneInput"
                    @blur="searchCustomer"
                    class="input-field phone-field"
                    placeholder="Masukkan nomor telepon customer..."
                  />
                  <button 
                    class="view-customer-btn" 
                    @click="openCustomerPopup"
                    title="Lihat daftar pelanggan"
                  >
                    Lihat Pelanggan
                  </button>
                  <button 
                    class="add-customer-btn" 
                    @click="openAddCustomer"
                    title="Tambah pelanggan baru"
                  >
                    ➕ Tambah Baru
                  </button>
                </div>
                
                <!-- Customer Search Results -->
                <div v-if="customerStore.isSearching" class="search-loading">
                  <div class="loading-spinner"></div>
                  <span>Mencari pelanggan...</span>
                </div>
                
                <!-- Customer Info Display -->
                <div v-if="customerStore.customerInfo && !customerStore.isSearching" class="customer-info">
                  <div class="customer-details">
                    <div class="customer-avatar">{{ customerStore.customerInitials }}</div>
                    <div class="customer-data">
                      <span class="customer-name">{{ customerStore.customerDisplayName }}</span>
                      <span class="customer-phone">{{ customerStore.customerPhone }}</span>
                    </div>
                  </div>
                  <span class="customer-found">✅ Pelanggan ditemukan</span>
                </div>
                
                <!-- Customer Not Found -->
                <div v-else-if="customerStore.customerPhone && !customerStore.customerInfo && customerStore.searchAttempted && !customerStore.isSearching" class="customer-not-found">
                  <div class="not-found-content">
                    <span class="not-found-icon">⚠️</span>
                    <div class="not-found-text">
                      <span>Pelanggan tidak ditemukan</span>
                      <small>Nomor {{ customerStore.customerPhone }} belum terdaftar</small>
                    </div>
                  </div>
                  <button class="register-customer-btn" @click="registerNewCustomer">
                    ➕ Daftar Sekarang
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Bagian Pickup Service -->
          <div class="section pickup-section">
            <h3><img src="../assets/img/Delivery.png" alt="Logo" class="delivery-satu-logo"/> Layanan Pickup</h3>
            <PickupService 
              ref="pickupServiceRef"
              @pickup-added="onPickupAdded"
              @pickup-removed="onPickupRemoved"
              @pickup-error="onPickupError"
            />
          </div>

          <!-- Service Selection -->
          <div class="section">
            <div class="section-header">
              <h3>Pilih Layanan</h3>
              <span v-if="!enablePickupService" class="optional-badge secondary">Wajib</span>
              <span v-else class="optional-badge">Opsional</span>
            </div>
            
            <div class="services-grid">
              <!-- Loading State -->
              <div v-if="orderStore.isLoadingLayanan" class="loading">
                <div class="spinner"></div>
                <span>Loading layanan...</span>
              </div>
              
              <!-- Empty State -->
              <div v-else-if="!orderStore.isLoadingLayanan && orderStore.daftarLayanan.length === 0" class="empty">
                <span><img src="../assets/img/Detail.png" alt="Logo" class="detail-satu-logo"/></span>
                <p>Belum ada layanan tersedia</p>
                <button class="btn-reload" @click="fetchLayanan">
                   Muat Ulang
                </button>
              </div>
              
              <!-- Service Cards -->
              <div 
                v-else
                v-for="layanan in orderStore.daftarLayanan" 
                :key="layanan.id"
                class="service-card"
                :class="{ active: orderStore.layananTerpilih?.id === layanan.id }"
                @click="pilihLayanan(layanan)"
              >
                <div class="service-icon"></div>
                <h4>{{ layanan.nama }}</h4>
                <div class="prices">
                  <span 
                    v-for="(harga, satuan) in layanan.hargaPerUnit" 
                    :key="satuan"
                    class="price"
                  >
                    Rp {{ harga.toLocaleString() }}/{{ satuan }}
                  </span>
                </div>
                <div v-if="layanan.deskripsi" class="service-description">
                  {{ layanan.deskripsi }}
                </div>
              </div>
            </div>
          </div>

          <!-- Quantity Input - MODIFIED -->
          <div v-if="orderStore.layananTerpilih" class="section">
            <h3><img src="../assets/img/Detergent.png" alt="Logo" class="detergent-satu-logo"/> Kuantitas</h3>
            <div class="quantity-inputs">
              <div 
                v-for="(harga, satuan) in orderStore.layananTerpilih.hargaPerUnit" 
                :key="satuan"
                class="qty-group"
              >
                <label>{{ formatSatuan(satuan) }}</label>
                <div class="qty-controls">
                  <button 
                    class="qty-btn" 
                    @click="orderStore.kurangiKuantitas(satuan)"
                    :disabled="orderStore.kuantitas[satuan] <= 0"
                  >
                    -
                  </button>
                  <input 
                    type="number" 
                    :value="orderStore.kuantitas[satuan]" 
                    :min="0"
                    :step="isPiecesUnit(satuan) ? '1' : '0.1'"
                    class="qty-input"
                    @input="updateKuantitas(satuan, $event.target.value)"
                    @keypress="handleKeyPress($event, satuan)"
                  >
                  <button 
                    class="qty-btn" 
                    @click="orderStore.tambahKuantitas(satuan)"
                  >
                    +
                  </button>
                </div>
                <span class="unit-price">Rp {{ harga.toLocaleString() }}</span>
              </div>
            </div>
            
            <div class="total">
              <span>Subtotal Layanan: </span>
              <span class="total-amount">Rp {{ orderStore.totalHarga.toLocaleString() }}</span>
            </div>
          </div>

          <!-- Notes and Add Button -->
          <div class="section">
            <textarea 
              :value="orderStore.catatan"
              @input="orderStore.setCatatan($event.target.value)"
              placeholder="Catatan khusus untuk layanan laundry..."
              class="notes"
              rows="2"
            ></textarea>
            
            <button 
              class="btn-add"
              @click="tambahPesanan"
              :disabled="!orderStore.canAddOrder"
            >
              ➕ Tambah Pesanan
            </button>
          </div>
        </div>

        <!-- Right Panel - Order List -->
        <div class="right-panel">
          <div class="section">
            <h3><img src="../assets/img/shopping-basket.png" alt="Logo" class="shopping-basket-logo"/> Ringkasan Pesanan</h3>
            
            <!-- Pickup Service Summary (COMPACT VERSION) -->
            <div v-if="activePickupService" class="pickup-summary-compact">
              <div class="pickup-compact-header">
                <span class="pickup-compact-icon">🚚</span>
                <div class="pickup-compact-info">
                  <span class="pickup-compact-title">{{ activePickupService.serviceName }}</span>
                  <span class="pickup-compact-cost">Rp {{ activePickupService.totalCost.toLocaleString() }}</span>
                </div>
                <button 
                  class="pickup-remove-btn" 
                  @click="removePickupService"
                  title="Hapus layanan pickup"
                >
                  ✕
                </button>
              </div>
              <div class="pickup-compact-details">
                <span class="pickup-compact-detail">📅 {{ formatPickupSchedule(activePickupService.pickupDate, activePickupService.pickupTime) }}</span>
                <span class="pickup-compact-detail">📏 {{ activePickupService.jarak }} km</span>
                <span v-if="activePickupService.specialInstructions" class="pickup-compact-detail">
                  📝 {{ activePickupService.specialInstructions }}
                </span>
              </div>
            </div>
            
            <!-- Order Items -->
            <div v-if="orderStore.daftarPesanan.length > 0" class="order-list">
              <div 
                v-for="(pesanan, index) in orderStore.daftarPesanan" 
                :key="pesanan.id"
                class="order-item"
              >
                <div class="order-info">
                  <h4>{{ pesanan.layanan.nama }}</h4>
                  <p class="order-qty">
                    <span v-for="(qty, satuan) in pesanan.kuantitas" :key="satuan">
                      {{ qty }} {{ satuan }}{{ qty > 1 && satuan !== 'Kg' ? 's' : '' }}
                      <span v-if="pesanan.harga_satuan && pesanan.harga_satuan[satuan]">
                        @ Rp {{ pesanan.harga_satuan[satuan].toLocaleString() }}
                      </span>
                    </span>
                  </p>
                  <p v-if="pesanan.catatan" class="order-notes">
                    📝 {{ pesanan.catatan }}
                  </p>
                  <span class="order-time">🕒 {{ formatTime(pesanan.waktu) }}</span>
                </div>
                <div class="order-actions">
                  <span class="order-price">Rp {{ pesanan.total.toLocaleString() }}</span>
                  <button 
                    class="btn-remove"
                    @click="hapusPesanan(index)"
                    title="Hapus pesanan"
                  >
                    🗑️
                  </button>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!activePickupService" class="empty-orders">
              <span>🛒</span>
              <p>Belum ada pesanan</p>
              <small>Pilih layanan laundry atau aktifkan pickup service</small>
            </div>
            
            <!-- Pickup Only State -->
            <div v-else class="pickup-only-state">
              <span>🚚</span>
              <p>Hanya layanan pickup</p>
              <small>Tidak ada layanan laundry ditambahkan</small>
            </div>
          </div>

          <!-- Validation Messages -->
          <div v-if="validationMessage" class="validation-message" :class="validationMessage.type">
            <span class="validation-icon">{{ validationMessage.type === 'error' ? '⚠️' : 'ℹ️' }}</span>
            <span>{{ validationMessage.text }}</span>
          </div>

          <!-- Grand Total and Payment -->
          <div v-if="hasAnyService" class="payment-section">
            <div class="total-breakdown">
              <div v-if="orderStore.daftarPesanan.length > 0" class="total-row">
                <span>Subtotal Layanan ({{ orderStore.daftarPesanan.length }} item{{ orderStore.daftarPesanan.length !== 1 ? 's' : '' }}):</span>
                <span>Rp {{ orderStore.grandTotal.toLocaleString() }}</span>
              </div>
              <div v-if="activePickupService" class="total-row">
                <span>Biaya Pickup:</span>
                <span>Rp {{ activePickupService.totalCost.toLocaleString() }}</span>
              </div>
              <div class="grand-total">
                <span>Total Keseluruhan:</span>
                <span>Rp {{ finalGrandTotal.toLocaleString() }}</span>
              </div>
            </div>
            <button 
              class="btn-payment"
              @click="showPaymentModal"
              :disabled="!canProceedToPayment"
            >
              💳 Bayar ({{ totalItemCount }} item{{ totalItemCount !== 1 ? 's' : '' }})
            </button>
          </div>
          
          <!-- No Service State -->
          <div v-else class="no-service-state">
            <div class="no-service-content">
              <span class="no-service-icon">📝</span>
              <p>Pilih minimal satu layanan</p>
              <small>Layanan laundry atau pickup service</small>
            </div>
          </div>
        </div>

        <!-- Payment Modal dengan Print Integration -->
        <Pembayaran 
          v-if="showPayment"
          :pickup-service="validatedPickupService"
          @order-completed="handleOrderCompleted"
          @close-modal="closePaymentModal"
        />

        <!-- Modal Tambah Pelanggan -->
        <TambahPelanggan
          v-if="showAddCustomerModal"
          @close="closeAddCustomerModal"
          @submitted="onCustomerAdded"
          :initial-phone="customerStore.customerPhone"
        />

        <!-- Pelanggan Popup Modal -->
        <PelangganPopup
          v-if="showPelangganPopup"
          @close="closePelangganPopup"
          @customer-selected="onCustomerSelected"
        />

        <!-- Loading Overlay -->
        <div v-if="isProcessing" class="loading-overlay">
          <div class="loading-content">
            <div class="loading-spinner large"></div>
            <p>{{ processingMessage }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'

// Import components
import NavSidebar from '../components/navigation/NavSidebar.vue'
import Pembayaran from '../components/transaksi/Pembayaran.vue'
import TambahPelanggan from '../components/Pelanggan/TambahPelanggan.vue'
import PelangganPopup from '../components/Pelanggan/DataPelanggan.vue'
import PickupService from '../components/transaksi/PickupOrder.vue'

// Import stores
import { useCustomerStore } from '../stores/PiniaCustomer.js'
import { useOrderStore } from '../stores/PiniaOrder.js'

// Router & Stores
const router = useRouter()
const orderStore = useOrderStore()
const customerStore = useCustomerStore()

// Reactive Data
const showPayment = ref(false)
const showAddCustomerModal = ref(false)
const showPelangganPopup = ref(false)
const pickupServiceRef = ref(null)
const activePickupService = ref(null)
const enablePickupService = ref(false)
const isProcessing = ref(false)
const processingMessage = ref('')
const validationMessage = ref(null)

let searchTimeout = null

// 🆕 HELPER FUNCTION - Cek apakah satuan adalah Pcs
const isPiecesUnit = (satuan) => {
  return satuan === 'Pcs' || satuan === 'pcs' || satuan === 'PCS'
}

// 🆕 HANDLER - Validasi input keyboard untuk Pcs
const handleKeyPress = (event, satuan) => {
  // Jika bukan Pcs, allow semua input (termasuk desimal)
  if (!isPiecesUnit(satuan)) {
    return true
  }
  
  // Untuk Pcs, block karakter koma, titik, dan minus
  const invalidChars = ['.', ',', '-', '+', 'e', 'E']
  if (invalidChars.includes(event.key)) {
    event.preventDefault()
    return false
  }
}

// Computed Properties
const finalGrandTotal = computed(() => {
  let total = orderStore.grandTotal || 0
  if (activePickupService.value) {
    total += activePickupService.value.totalCost || 0
  }
  return total
})

const validatedPickupService = computed(() => {
  if (!activePickupService.value) {
    console.log('🔍 validatedPickupService: No active pickup service')
    return null
  }
  
  const pickup = activePickupService.value
  
  const isValid = pickup.serviceName && 
    pickup.serviceName.trim() !== '' &&
    pickup.jarak && 
    pickup.jarak > 0 &&
    pickup.totalCost && 
    pickup.totalCost > 0 &&
    pickup.pickupDate &&
    pickup.pickupTime && 
    pickup.pickupTime.trim() !== ''
  
  console.log('🔍 validatedPickupService validation:')
  console.log('- serviceName:', pickup.serviceName)
  console.log('- jarak:', pickup.jarak)
  console.log('- totalCost:', pickup.totalCost)
  console.log('- pickupDate:', pickup.pickupDate)
  console.log('- pickupTime:', pickup.pickupTime)
  console.log('- isValid:', isValid)
  
  if (!isValid) {
    console.log('🚨 validatedPickupService: Invalid pickup data, returning null')
    return null
  }
  
  console.log('✅ validatedPickupService: Valid pickup data')
  return pickup
})

const hasAnyService = computed(() => {
  return orderStore.daftarPesanan.length > 0 || activePickupService.value
})

const totalItemCount = computed(() => {
  return orderStore.daftarPesanan.length + (activePickupService.value ? 1 : 0)
})

const canProceedToPayment = computed(() => {
  const hasCustomer = customerStore.customerPhone.trim()
  const hasService = hasAnyService.value
  const hasMinimumOrder = enablePickupService.value ? hasService : orderStore.daftarPesanan.length > 0
  
  return hasCustomer && hasMinimumOrder
})

// Watchers
watch(
  () => orderStore.daftarPesanan,
  () => {
    orderStore.savePesanan()
    updateValidationMessage()
  },
  { deep: true }
)

watch(
  () => [orderStore.layananTerpilih, orderStore.kuantitas, orderStore.catatan, orderStore.totalHarga],
  () => {
    orderStore.savePesanan()
  },
  { deep: true }
)

watch(
  () => activePickupService.value,
  (newPickup) => {
    if (newPickup) {
      localStorage.setItem('activePickupService', JSON.stringify(newPickup))
    } else {
      localStorage.removeItem('activePickupService')
    }
    updateValidationMessage()
  },
  { deep: true }
)

watch(
  () => enablePickupService.value,
  (isEnabled) => {
    localStorage.setItem('enablePickupService', JSON.stringify(isEnabled))
    if (!isEnabled && activePickupService.value) {
      removePickupService()
    }
    updateValidationMessage()
  }
)

watch(
  () => customerStore.customerPhone,
  () => {
    updateValidationMessage()
  }
)

// Validation Message Update
const updateValidationMessage = () => {
  validationMessage.value = null
  
  if (!customerStore.customerPhone.trim()) {
    validationMessage.value = {
      type: 'error',
      text: 'Pilih customer terlebih dahulu'
    }
    return
  }
  
  if (!hasAnyService.value) {
    validationMessage.value = {
      type: 'error',
      text: enablePickupService.value ? 
        'Pilih minimal satu layanan (laundry atau pickup)' : 
        'Tambahkan minimal satu layanan laundry'
    }
    return
  }
  
  if (enablePickupService.value && !orderStore.daftarPesanan.length && !activePickupService.value) {
    validationMessage.value = {
      type: 'info',
      text: 'Mode pickup aktif - tambahkan layanan pickup atau laundry'
    }
    return
  }
}

// API Functions
async function fetchLayanan() {
  orderStore.setLoadingLayanan(true)
  
  try {
    const response = await axios.get('/api/layanan')
    orderStore.setDaftarLayanan(response.data)
    console.log('✅ Layanan loaded:', response.data)
  } catch (error) {
    console.error('❌ Error fetching layanan:', error)
    
    Swal.fire({
      title: 'Peringatan',
      text: 'Tidak dapat memuat data layanan dari server',
      icon: 'warning',
      confirmButtonColor: '#3b82f6',
      showCancelButton: true,
      cancelButtonText: 'Batal',
      confirmButtonText: 'Coba Lagi'
    }).then((result) => {
      if (result.isConfirmed) {
        fetchLayanan()
      }
    })
    
    orderStore.setDaftarLayanan([])
  } finally {
    orderStore.setLoadingLayanan(false)
  }
}

// Customer Functions
const onPhoneInput = (event) => {
  const phone = event.target.value
  customerStore.setCustomerPhone(phone)
  
  if (phone !== customerStore.customerInfo?.telepon) {
    customerStore.clearCustomerInfo()
  }
  
  if (customerStore.cleanPhone.length >= 8) {
    searchCustomerDebounced()
  }
}

const searchCustomerDebounced = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    searchCustomer()
  }, 500)
}

const searchCustomer = async () => {
  if (!customerStore.customerPhone.trim()) return
  
  try {
    await customerStore.searchCustomer()
  } catch (error) {
    console.error('❌ Error searching customer:', error)
    Swal.fire({
      title: 'Error',
      text: 'Gagal mencari customer. Silakan coba lagi.',
      icon: 'error',
      timer: 3000
    })
  }
}

const openCustomerPopup = () => {
  showPelangganPopup.value = true
}

const closePelangganPopup = () => {
  showPelangganPopup.value = false
}

const onCustomerSelected = (customer) => {
  customerStore.setSelectedCustomer(customer)
  closePelangganPopup()

  Swal.fire({
    title: 'Berhasil!',
    text: `Pelanggan ${customer.nama_pelanggan || customer.nama} berhasil dipilih`,
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
}

const openAddCustomer = () => {
  showAddCustomerModal.value = true
}

const registerNewCustomer = () => {
  showAddCustomerModal.value = true
}

const closeAddCustomerModal = () => {
  showAddCustomerModal.value = false
}

const onCustomerAdded = async (newCustomer) => {
  customerStore.addCustomer(newCustomer)
  closeAddCustomerModal()

  Swal.fire({
    title: 'Berhasil!',
    text: 'Pelanggan baru berhasil ditambahkan dan otomatis dipilih',
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
}

// Pickup Service Functions
const togglePickupService = (event) => {
  enablePickupService.value = event.target.checked
  
  if (!enablePickupService.value) {
    Swal.fire({
      title: 'Konfirmasi',
      text: 'Menonaktifkan pickup service akan menghapus layanan pickup yang sudah dipilih. Lanjutkan?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Nonaktifkan',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (!result.isConfirmed) {
        enablePickupService.value = true
      }
    })
  }
}

const onPickupAdded = (pickupData) => {
  console.log('🚚 onPickupAdded called with data:', pickupData)
  
  if (pickupData && pickupData.serviceName && pickupData.totalCost > 0) {
    activePickupService.value = { ...pickupData }
    console.log('✅ Valid pickup data set:', activePickupService.value)
  } else {
    console.log('🚨 Invalid pickup data received, not setting:', pickupData)
    activePickupService.value = null
  }
  
  Swal.fire({
    title: 'Pickup Ditambahkan!',
    text: `Layanan ${pickupData?.serviceName || 'pickup'} berhasil ditambahkan`,
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
  
  nextTick(() => {
    const orderSection = document.querySelector('.right-panel')
    if (orderSection) {
      orderSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  })
}

const onPickupRemoved = () => {
  console.log('🗑️ onPickupRemoved called')
  console.log('- Before removal:', activePickupService.value)
  
  activePickupService.value = null
  
  console.log('- After removal:', activePickupService.value)
  console.log('✅ Pickup service removed')
}

const onPickupError = (errorMessage) => {
  Swal.fire({
    title: 'Error Pickup Service',
    text: errorMessage || 'Terjadi kesalahan pada layanan pickup',
    icon: 'error',
    confirmButtonColor: '#ef4444'
  })
}

const removePickupService = () => {
  Swal.fire({
    title: 'Hapus Pickup Service?',
    text: 'Layanan pickup akan dihapus dari pesanan',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      console.log('🚨 FORCE REMOVING pickup service')
      console.log('- Before removal:', activePickupService.value)
      
      activePickupService.value = null
      
      if (pickupServiceRef.value) {
        console.log('🚨 Clearing pickup from component')
        pickupServiceRef.value.clearPickup()
      }
      
      localStorage.removeItem('activePickupService')
      
      console.log('- After removal:', activePickupService.value)
      console.log('- validatedPickupService after removal:', validatedPickupService.value)
      
      Swal.fire({
        title: 'Berhasil!',
        text: 'Layanan pickup berhasil dihapus',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    }
  })
}

// Formatting Functions
const formatPickupSchedule = (date, time) => {
  if (!date || !time) return 'Belum dijadwalkan'
  
  try {
    const dateObj = new Date(date)
    const options = { 
      weekday: 'short', 
      month: 'short', 
      day: 'numeric' 
    }
    return `${dateObj.toLocaleDateString('id-ID', options)}, ${time}`
  } catch (error) {
    return `${date}, ${time}`
  }
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  try {
    const date = new Date(timestamp)
    return date.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  } catch (error) {
    return ''
  }
}

const formatSatuan = (satuan) => {
  const satuanMap = {
    'Kg': 'Kilogram',
    'Pcs': 'Pieces',
    'Set': 'Set',
    'Pasang': 'Pasang'
  }
  return satuanMap[satuan] || satuan
}

// Order Functions
const pilihLayanan = (layanan) => {
  try {
    orderStore.pilihLayanan(layanan)
    console.log('✅ Service selected:', layanan.nama)
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Gagal memilih layanan: ' + error.message,
      icon: 'error'
    })
  }
}

// 🆕 MODIFIED - Update kuantitas dengan validasi Pcs ONLY
const updateKuantitas = (satuan, nilai) => {
  try {
    let numValue = parseFloat(nilai) || 0
    
    // ✅ Jika satuan adalah Pcs, bulatkan ke bilangan bulat
    if (isPiecesUnit(satuan)) {
      numValue = Math.floor(numValue) // Bulatkan ke bawah
      
      // Jika ada desimal yang diinput, kasih notifikasi
      if (nilai.includes('.') || nilai.includes(',')) {
        Swal.fire({
          title: 'Perhatian!',
          text: 'Pieces hanya menerima bilangan bulat. Desimal diabaikan.',
          icon: 'info',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        })
      }
    }
    // ✅ Untuk Kg, Set, Pasang, dll -> TETAP ALLOW DESIMAL (tidak ada perubahan)
    
    orderStore.setKuantitas(satuan, numValue)
  } catch (error) {
    console.error('Error updating quantity:', error)
  }
}

const tambahPesanan = async () => {
  try {
    const pesanan = orderStore.tambahPesanan()
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Pesanan berhasil ditambahkan',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })
    
    nextTick(() => {
      const orderSection = document.querySelector('.order-list')
      if (orderSection) {
        orderSection.scrollTop = orderSection.scrollHeight
      }
    })
    
    console.log('✅ Order added:', pesanan)
  } catch (error) {
    Swal.fire({
      title: 'Oops!',
      text: error.message,
      icon: 'warning',
      confirmButtonColor: '#3b82f6'
    })
  }
}

const hapusPesanan = (index) => {
  const pesanan = orderStore.daftarPesanan[index]
  
  Swal.fire({
    title: 'Hapus Pesanan?',
    html: `
      <div style="text-align: left;">
        <p><strong>Layanan:</strong> ${pesanan.layanan.nama}</p>
        <p><strong>Total:</strong> Rp ${pesanan.total.toLocaleString()}</p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal'
  }).then(result => {
    if (result.isConfirmed) {
      orderStore.hapusPesanan(index)
      
      Swal.fire({
        title: 'Berhasil!',
        text: 'Pesanan berhasil dihapus',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    }
  })
}

// Payment Functions
const showPaymentModal = () => {
  console.log('🚨 showPaymentModal called')
  console.log('- activePickupService.value:', activePickupService.value)
  console.log('- validatedPickupService.value:', validatedPickupService.value)
  console.log('- canProceedToPayment.value:', canProceedToPayment.value)
  console.log('- orderStore.daftarPesanan.length:', orderStore.daftarPesanan.length)
  
  if (!canProceedToPayment.value) {
    let message = 'Tidak dapat melanjutkan pembayaran:'
    
    if (!customerStore.customerPhone.trim()) {
      message += '\n• Pilih customer terlebih dahulu'
    }
    
    if (!hasAnyService.value) {
      message += '\n• Tambahkan minimal satu layanan'
    }
    
    Swal.fire({
      title: 'Oops!',
      text: message,
      icon: 'warning',
      confirmButtonColor: '#3b82f6'
    })
    return
  }

  if (activePickupService.value && orderStore.daftarPesanan.length === 0) {
    console.log('🔍 Pickup-only transaction detected')
    console.log('- activePickupService valid?', !!validatedPickupService.value)
    
    if (!validatedPickupService.value) {
      Swal.fire({
        title: 'Error!',
        text: 'Data pickup service tidak valid. Silakan setup ulang layanan pickup.',
        icon: 'error',
        confirmButtonColor: '#ef4444'
      })
      return
    }
    
    Swal.fire({
      title: 'Konfirmasi Pickup Only',
      text: 'Anda hanya memesan layanan pickup tanpa layanan laundry. Lanjutkan?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3b82f6',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Lanjutkan',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        console.log('✅ Opening payment modal for pickup-only transaction')
        showPayment.value = true
      }
    })
    return
  }

  console.log('✅ Opening payment modal for regular transaction')
  showPayment.value = true
}

const closePaymentModal = () => {
  showPayment.value = false
}

const handleOrderCompleted = async (transaksiData) => {
  isProcessing.value = true
  processingMessage.value = 'Menyelesaikan transaksi...'
  
  try {
    orderStore.clearAllPesanan()
    orderStore.clearSavedPesanan()
    customerStore.clearCustomerSelection()
    
    activePickupService.value = null
    enablePickupService.value = false
    if (pickupServiceRef.value) {
      pickupServiceRef.value.clearPickup()
    }
    
    localStorage.removeItem('activePickupService')
    localStorage.removeItem('enablePickupService')
    
    showPayment.value = false

    const hasLaundry = transaksiData.items_count > 0
    const hasPickup = transaksiData.pickup_service
    
    let serviceText = ''
    if (hasLaundry && hasPickup) {
      serviceText = `${transaksiData.items_count} layanan laundry + pickup service`
    } else if (hasLaundry) {
      serviceText = `${transaksiData.items_count} layanan laundry`
    } else if (hasPickup) {
      serviceText = 'pickup service'
    }

    await Swal.fire({
      title: '✅ Transaksi Berhasil!',
      html: `
        <div style="text-align: left; font-family: Arial, sans-serif; margin: 20px 0;">
          <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin: 5px 0;"><strong>🎫 Kode:</strong> ${transaksiData.kode_transaksi}</p>
            <p style="margin: 5px 0;"><strong>👤 Customer:</strong> ${transaksiData.customer_name}</p>
            <p style="margin: 5px 0;"><strong>📱 Telepon:</strong> ${transaksiData.customer_phone}</p>
          </div>
          
          <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin: 5px 0;"><strong>🛍️ Layanan:</strong> ${serviceText}</p>
            ${hasLaundry ? `<p style="margin: 5px 0;"><strong>🧺 Subtotal Laundry:</strong> Rp ${transaksiData.subtotal_layanan?.toLocaleString() || '0'}</p>` : ''}
            ${hasPickup ? `<p style="margin: 5px 0;"><strong>🚚 Biaya Pickup:</strong> Rp ${transaksiData.biaya_pickup?.toLocaleString() || '0'}</p>` : ''}
            <p style="margin: 5px 0; font-size: 16px;"><strong>💰 Total:</strong> Rp ${transaksiData.total_amount?.toLocaleString() || '0'}</p>
          </div>
          
          <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
            <p style="margin: 5px 0;"><strong>💳 Pembayaran:</strong> ${transaksiData.metode_pembayaran}</p>
            <p style="margin: 5px 0;"><strong>📊 Status:</strong> ${transaksiData.status_transaksi}</p>
            ${transaksiData.kembalian > 0 ? `<p style="margin: 5px 0;"><strong>💸 Kembalian:</strong> Rp ${transaksiData.kembalian.toLocaleString()}</p>` : ''}
          </div>
        </div>
      `,
      icon: 'success',
      showConfirmButton: true,
      confirmButtonText: 'OK 👍',
      allowOutsideClick: false
    })

    console.log('✅ Transaction completed successfully:', transaksiData)
    
  } catch (error) {
    console.error('❌ Error completing transaction:', error)
    Swal.fire({
      title: 'Error',
      text: 'Terjadi kesalahan saat menyelesaikan transaksi',
      icon: 'error'
    })
  } finally {
    isProcessing.value = false
    processingMessage.value = ''
  }
}

// System Functions
const handleLogout = () => {
  Swal.fire({
    title: 'Konfirmasi Logout',
    text: 'Apakah Anda yakin ingin keluar? Data yang belum disimpan akan hilang.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Logout',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      orderStore.clearAllPesanan()
      orderStore.clearSavedPesanan()
      customerStore.clearCustomerSelection()
      
      activePickupService.value = null
      enablePickupService.value = false
      
      localStorage.removeItem('activePickupService')
      localStorage.removeItem('enablePickupService')
      localStorage.removeItem('token')
      sessionStorage.removeItem('user')
      
      router.push('/AuthKaryawan')
      
      Swal.fire('Berhasil', 'Anda telah logout', 'success')
    }
  })
}

const checkForSelectedCustomer = () => {
  try {
    customerStore.restoreCustomerFromSession()
  } catch (error) {
    console.warn('Error restoring customer session:', error)
  }
}

const restorePickupService = () => {
  try {
    const savedPickup = localStorage.getItem('activePickupService')
    const savedEnabled = localStorage.getItem('enablePickupService')
    
    if (savedEnabled) {
      enablePickupService.value = JSON.parse(savedEnabled)
    }
    
    if (savedPickup && enablePickupService.value) {
      activePickupService.value = JSON.parse(savedPickup)
      
      if (pickupServiceRef.value) {
        nextTick(() => {
          pickupServiceRef.value.setPickup(activePickupService.value)
        })
      }
    }
  } catch (error) {
    console.error('Error restoring pickup service:', error)
    localStorage.removeItem('activePickupService')
    localStorage.removeItem('enablePickupService')
  }
}

const handleBeforeUnload = (event) => {
  if (orderStore.hasActiveOrder || activePickupService.value) {
    orderStore.savePesanan()
    const message = 'Anda memiliki pesanan yang belum selesai. Data akan disimpan otomatis.'
    event.returnValue = message
    return message
  }
}

const handleVisibilityChange = () => {
  if (document.hidden) {
    orderStore.savePesanan()
    if (activePickupService.value) {
      localStorage.setItem('activePickupService', JSON.stringify(activePickupService.value))
    }
    localStorage.setItem('enablePickupService', JSON.stringify(enablePickupService.value))
  } else {
    checkForSelectedCustomer()
    updateValidationMessage()
  }
}

const handleKeyboardShortcuts = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key === 's') {
    event.preventDefault()
    orderStore.savePesanan()
    
    const toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 1000
    })
    toast.fire({
      icon: 'success',
      title: 'Data tersimpan'
    })
  }
  
  if (event.key === 'F2') {
    event.preventDefault()
    const customerInput = document.querySelector('.phone-field')
    if (customerInput) {
      customerInput.focus()
    }
  }
  
  if (event.key === 'F3') {
    event.preventDefault()
    if (canProceedToPayment.value) {
      showPaymentModal()
    }
  }
}

// Lifecycle Hooks
onMounted(async () => {
  console.log('🚀 TransaksiOrder component mounted')
  
  try {
    orderStore.restorePesanan()
    restorePickupService()
    
    await fetchLayanan()
    
    try {
      await customerStore.loadAllCustomers()
      console.log('✅ Customers loaded')
    } catch (error) {
      console.warn('⚠️ Could not load customers:', error)
    }

    checkForSelectedCustomer()
    updateValidationMessage()
    
    window.addEventListener('beforeunload', handleBeforeUnload)
    document.addEventListener('visibilitychange', handleVisibilityChange)
    document.addEventListener('keydown', handleKeyboardShortcuts)
    
    console.log('✅ TransaksiOrder ready')
    console.log(`📊 Orders: ${orderStore.daftarPesanan.length}`)
    console.log(`🚚 Pickup: ${activePickupService.value ? 'Active' : 'Inactive'}`)
    
  } catch (error) {
    console.error('❌ Error during component initialization:', error)
    Swal.fire({
      title: 'Error Initialization',
      text: 'Terjadi kesalahan saat memuat aplikasi. Silakan refresh halaman.',
      icon: 'error',
      showCancelButton: true,
      confirmButtonText: 'Refresh',
      cancelButtonText: 'Lanjutkan'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.reload()
      }
    })
  }
})

onUnmounted(() => {
  console.log('📋 TransaksiOrder component unmounted')
  
  try {
    orderStore.savePesanan()
    if (activePickupService.value) {
      localStorage.setItem('activePickupService', JSON.stringify(activePickupService.value))
    }
    localStorage.setItem('enablePickupService', JSON.stringify(enablePickupService.value))
  } catch (error) {
    console.error('Error saving data on unmount:', error)
  }
  
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  window.removeEventListener('beforeunload', handleBeforeUnload)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  document.removeEventListener('keydown', handleKeyboardShortcuts)
})
</script>

<style scoped>
@import '../assets/css/TransaksiOrder.css';

/* Enhanced styles untuk pieces validation */
.qty-input[data-pieces="true"] {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border: 2px solid #3b82f6;
}

.qty-input[data-pieces="true"]:focus {
  outline: 3px solid rgba(59, 130, 246, 0.3);
  outline-offset: 2px;
}

/* Semua style sebelumnya tetap sama */
.pickup-section {
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  position: relative;
}

.pickup-section.active {
  border-color: #3b82f6;
  background: #f8faff;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.optional-badge {
  background: #10b981;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.optional-badge.secondary {
  background: #f59e0b;
}

.pickup-toggle {
  margin-bottom: 16px;
}

.toggle-switch {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}

.toggle-switch input[type="checkbox"] {
  appearance: none;
  width: 44px;
  height: 24px;
  background: #e5e7eb;
  border-radius: 12px;
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.toggle-switch input[type="checkbox"]:checked {
  background: #3b82f6;
}

.toggle-switch input[type="checkbox"]::before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.toggle-switch input[type="checkbox"]:checked::before {
  transform: translateX(20px);
}

.toggle-label {
  font-weight: 500;
  color: #374151;
}

.pickup-disabled-info {
  background: #f9fafb;
  border: 1px dashed #d1d5db;
  border-radius: 8px;
  padding: 16px;
}

.info-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-icon {
  font-size: 20px;
}

.info-text p {
  margin: 0;
  font-weight: 500;
  color: #6b7280;
}

.info-text small {
  color: #9ca3af;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e5e7eb;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-spinner.large {
  width: 40px;
  height: 40px;
  border-width: 3px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.search-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  font-size: 14px;
  color: #6b7280;
  background: #f8fafc;
  border-radius: 6px;
}

.customer-info,
.customer-not-found {
  margin-top: 8px;
  padding: 12px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  animation: fadeIn 0.3s ease;
}

.customer-info {
  background: #f0fdf4;
  border-color: #22c55e;
}

.customer-not-found {
  background: #fef2f2;
  border-color: #ef4444;
}

.customer-details {
  display: flex;
  align-items: center;
  gap: 12px;
}

.customer-avatar {
  width: 36px;
  height: 36px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.customer-data {
  display: flex;
  flex-direction: column;
}

.customer-name {
  font-weight: 600;
  color: #111827;
}

.customer-phone {
  font-size: 13px;
  color: #6b7280;
}

.validation-message {
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  animation: slideDown 0.3s ease;
}

.validation-message.error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
}

.validation-message.info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
}

.validation-icon {
  font-size: 16px;
}

.pickup-summary-compact {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #0ea5e9;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  animation: slideIn 0.3s ease;
  position: relative;
}

.pickup-compact-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.pickup-compact-icon {
  font-size: 20px;
}

.pickup-compact-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.pickup-compact-title {
  font-weight: 600;
  font-size: 15px;
  color: #0c4a6e;
}

.pickup-compact-cost {
  font-weight: 700;
  color: #0ea5e9;
  font-size: 16px;
}

.pickup-remove-btn {
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s ease;
}

.pickup-remove-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.pickup-compact-details {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pickup-compact-detail {
  font-size: 13px;
  color: #475569;
  background: rgba(255, 255, 255, 0.5);
  padding: 4px 8px;
  border-radius: 6px;
}

.empty-orders,
.pickup-only-state,
.no-service-state {
  text-align: center;
  padding: 32px 16px;
  color: #6b7280;
}

.empty-orders span,
.pickup-only-state span,
.no-service-state .no-service-icon {
  font-size: 48px;
  display: block;
  margin-bottom: 12px;
  opacity: 0.5;
}

.empty-orders p,
.pickup-only-state p,
.no-service-state p {
  font-weight: 500;
  margin: 0 0 4px 0;
  color: #374151;
}

.empty-orders small,
.pickup-only-state small,
.no-service-state small {
  color: #9ca3af;
}

.no-service-content {
  background: #f9fafb;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  padding: 24px;
}

.payment-section {
  background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
  border: 1px solid #f59e0b;
  border-radius: 12px;
  padding: 20px;
  margin-top: 20px;
}

.total-breakdown {
  margin-bottom: 16px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
  color: #374151;
}

.grand-total {
  display: flex;
  justify-content: space-between;
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  padding-top: 12px;
  border-top: 2px solid #f59e0b;
  margin-top: 12px;
}

.btn-payment {
  width: 100%;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  border: none;
  padding: 14px 20px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-payment:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-payment:disabled {
  background: #d1d5db;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.loading-content {
  background: white;
  padding: 32px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.loading-content p {
  margin: 16px 0 0 0;
  font-size: 16px;
  color: #374151;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { 
    opacity: 0;
    transform: translateY(-10px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideDown {
  from { 
    opacity: 0;
    transform: translateY(-10px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .customer-input-wrapper {
    flex-direction: column;
    gap: 8px;
  }
  
  .view-customer-btn,
  .add-customer-btn {
    width: 100%;
  }
  
  .pickup-compact-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .pickup-remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
  }
}

.btn-reload {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  margin-top: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-reload:hover {
  background: #2563eb;
}

.qty-input {
  text-align: center;
  font-weight: 600;
}

.qty-input:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.order-item {
  transition: all 0.2s ease;
}

.order-item:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.order-time {
  font-size: 11px;
  color: #9ca3af;
  font-style: italic;
}
</style>