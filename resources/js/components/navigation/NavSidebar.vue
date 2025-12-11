<template>
  <div>
    <!-- Floating Show Button -->
    <button 
      class="sidebar-show-btn"
      :class="{ 'show': !sidebarVisible }"
      @click="showSidebar"
      title="Buka menu"
    >
      <span class="toggle-icon">☰</span>
    </button>

    <!-- Overlay untuk mobile -->
    <div 
      class="sidebar-overlay" 
      :class="{ 'show': sidebarVisible && isMobile }"
      @click="closeSidebar"
    ></div>

    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'hidden': !sidebarVisible, 'show': sidebarVisible && isMobile }">
      <div class="sidebar-header">
        <div class="brand">
          <div class="brand-icon">
            <img src="../../assets/img/Logo-navsidebar.png" alt="Logo" class="brand-logo" />
          </div>
          <div class="brand-text">
            <h3>Fresh Clean</h3>
            <p>Sistem Karyawan</p>
          </div>
        </div>
        
        <button 
          class="sidebar-toggle" 
          @click="hideSidebar"
          title="Tutup menu"
        >
          <span class="toggle-icon">✕</span>
        </button>
      </div>

      <nav class="sidebar-nav">
        <RouterLink to="/DashboardKasir" class="nav-item" active-class="active">
          <img src="../../assets/img/Money-flow.png" alt="Logo" class="money-flow-logo"/> Dashboard
        </RouterLink>
        
        <RouterLink to="/TransaksiOrder" class="nav-item" active-class="active" :class="{ 'has-active-order': orderStore.hasActiveOrder }">
          <span class="nav-content">
            <img src="../../assets/img/Transaction.png" alt="Logo" class="transaction-logo"/>Transaksi/Order
            <div v-if="orderStore.hasActiveOrder" class="order-badge">
              <span class="badge-count">{{ orderStore.totalItems }}</span>
              <span class="badge-text">Aktif</span>
            </div>
          </span>
        </RouterLink>
        
        <RouterLink to="/LaporanHarian" class="nav-item" active-class="active">
          <img src="../../assets/img/Report.png" alt="Logo" class="report-logo"/> Laporan Harian
        </RouterLink>

      </nav>

      <!-- Active Order Summary -->
      <div v-if="orderStore.hasActiveOrder" class="active-order-summary">
        <div class="summary-header">
          <span class="summary-icon">📋</span>
          <h4>Pesanan Aktif</h4>
        </div>
        <div class="summary-content">
          <div class="summary-stats">
            <div class="stat-item">
              <span class="stat-label">Items:</span>
              <span class="stat-value">{{ orderStore.totalItems }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Total:</span>
              <span class="stat-value">Rp {{ formatCurrency(orderStore.grandTotal) }}</span>
            </div>
          </div>
          
          <div class="summary-actions">
            <RouterLink to="/TransaksiOrder" class="continue-btn">
              <span>Lanjutkan</span>
              <span>→</span>
            </RouterLink>
            <button 
              class="clear-btn" 
              @click="clearOrdersQuietly"
              title="Hapus semua pesanan"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>

      <div class="sidebar-footer">
        <!-- Tombol Tutup Kasir -->
        <button 
          class="tutup-kasir-btn" 
          @click="handleTutupKasir"
          :disabled="isProcessing"
        >
          <span v-if="!isProcessing"><img src="../../assets/img/lock.png" alt="logo" class="lock-logo"> Tutup Kasir</span>
          <span v-else>
            <span class="loading-spinner"></span>
            Memproses...
          </span>
        </button>
        
        <button class="logout-btn" @click="performLogout">
          Logout
        </button>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useCustomerStore } from '../../stores/PiniaCustomer.js'
import { useOrderStore } from '../../stores/PiniaOrder.js'
import Swal from 'sweetalert2'
import axios from 'axios'

// Stores
const orderStore = useOrderStore()
const customerStore = useCustomerStore()
const router = useRouter()

// Reactive data
const sidebarVisible = ref(true)
const isMobile = ref(false)
const isProcessing = ref(false)

// Emits
const emit = defineEmits(['logout'])

// Sidebar methods
const showSidebar = () => {
  sidebarVisible.value = true
  localStorage.setItem('sidebarVisible', 'true')
  nextTick(() => updateMainContentMargin())
}

const hideSidebar = () => {
  sidebarVisible.value = false
  localStorage.setItem('sidebarVisible', 'false')
  nextTick(() => updateMainContentMargin())
}

const toggleSidebar = () => {
  sidebarVisible.value = !sidebarVisible.value
  localStorage.setItem('sidebarVisible', sidebarVisible.value.toString())
  nextTick(() => updateMainContentMargin())
}

const closeSidebar = () => {
  if (isMobile.value) {
    sidebarVisible.value = false
    updateMainContentMargin()
  }
}

const updateMainContentMargin = () => {
  const mainContent = document.querySelector('.main-content')
  if (mainContent) {
    if (sidebarVisible.value && !isMobile.value) {
      mainContent.classList.remove('sidebar-hidden')
    } else {
      mainContent.classList.add('sidebar-hidden')
    }
  }
}

const checkScreenSize = () => {
  isMobile.value = window.innerWidth <= 768
  if (isMobile.value && window.innerWidth < 600) {
    sidebarVisible.value = false
  }
  updateMainContentMargin()
}

const handleResize = () => checkScreenSize()

const handleKeyboard = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key === 'b') {
    event.preventDefault()
    toggleSidebar()
  }
  
  if (event.key === 'Escape' && isMobile.value && sidebarVisible.value) {
    closeSidebar()
  }
}

// Utility functions
const formatCurrency = (amount) => {
  return amount ? amount.toLocaleString('id-ID') : '0'
}

const clearOrdersQuietly = () => {
  orderStore.clearAllPesanan()
  orderStore.clearSavedPesanan()
  customerStore.clearCustomerSelection()
}

const performLogout = () => {
  try {
    if (orderStore.hasActiveOrder) {
      orderStore.savePesanan()
    }
    localStorage.setItem('sidebarVisible', sidebarVisible.value.toString())
    emit('logout')
  } catch (error) {
    console.error('Error during logout:', error)
  }
}

// Tutup Kasir Functions - Simplified
const handleTutupKasir = async () => {
  if (isProcessing.value) return

  try {
    const result = await Swal.fire({
      title: '🔒 Tutup Kasir',
      text: 'Anda yakin ingin tutup kasir? Sistem akan mencetak laporan harian.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#f59e0b',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Ya, Tutup Kasir',
      cancelButtonText: 'Batal'
    })

    if (result.isConfirmed) {
      await processTutupKasir()
    }
  } catch (error) {
    console.error('Error handling tutup kasir:', error)
    Swal.fire({
      title: 'Error',
      text: 'Terjadi kesalahan saat memproses tutup kasir',
      icon: 'error'
    })
  }
}

const processTutupKasir = async () => {
  isProcessing.value = true

  try {
    Swal.fire({
      title: 'Memproses Tutup Kasir...',
      text: 'Sedang mengambil data transaksi hari ini',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => Swal.showLoading()
    })

    const today = new Date().toISOString().split('T')[0]
    
    // Fetch data transaksi
    const response = await axios.get('/api/transaksi', {
      params: {
        date_from: today,
        date_to: today
      },
      timeout: 10000
    })
    
    if (response.status !== 200) {
      throw new Error('Gagal mengambil data transaksi')
    }

    const laporanData = calculateSimpleLaporanData(response.data.data || [])
    
    // Print laporan sederhana
    await printSimpleTutupKasir(laporanData, today)
    
    // Show success dengan informasi sederhana
    await Swal.fire({
      title: 'Tutup Kasir Berhasil!',
      html: `
        <div style="text-align: left;">
          <p><strong>📅 Tanggal:</strong> ${formatDate(today)}</p>
          <p><strong>👤 Kasir:</strong> ${getCurrentKasirName()}</p>
          <hr>
          <p><strong>💵 Tunai:</strong> Rp ${formatCurrency(laporanData.tunai)}</p>
          <p><strong>📱 QRIS:</strong> Rp ${formatCurrency(laporanData.qris)}</p>
          <hr>
          <p><strong>💰 TOTAL:</strong> Rp ${formatCurrency(laporanData.total)}</p>
          <p><strong>🖨️ Status:</strong> Laporan telah dicetak</p>
        </div>
      `,
      icon: 'success',
      confirmButtonText: 'OK',
      width: 400
    })

  } catch (error) {
    console.error('Error processing tutup kasir:', error)
    
    let errorMessage = 'Gagal memproses tutup kasir'
    
    if (error.response) {
      const status = error.response.status
      if (status === 401) {
        errorMessage = 'Sesi login berakhir'
        router.push('/login')
        return
      } else if (status === 404) {
        errorMessage = 'Data transaksi tidak ditemukan'
      } else if (status === 500) {
        errorMessage = 'Error server internal'
      } else {
        errorMessage = `HTTP Error ${status}`
      }
    } else if (error.request) {
      errorMessage = 'Tidak dapat terhubung ke server'
    } else if (error.code === 'ECONNABORTED') {
      errorMessage = 'Request timeout'
    }
    
    await Swal.fire({
      title: 'Error',
      text: errorMessage,
      icon: 'error'
    })
  } finally {
    isProcessing.value = false
  }
}

// Simplified calculation - hanya tunai, qris, dan total
const calculateSimpleLaporanData = (transactions) => {
  const laporan = {
    tunai: 0,
    qris: 0,
    total: 0
  }

  transactions.forEach(transaction => {
    if (transaction.status_transaksi === 'sukses') {
      const amount = parseFloat(transaction.total_amount) || 0
      const method = transaction.metode_pembayaran_original || transaction.metode_pembayaran
      
      laporan.total += amount
      
      if (method === 'tunai') {
        laporan.tunai += amount
      } else if (method === 'qris') {
        laporan.qris += amount
      }
    }
  })

  return laporan
}

// Simplified print function menggunakan tutupKasirPrinter.js
const printSimpleTutupKasir = async (laporanData, tanggal) => {
  // Import tutupKasirPrinter yang sudah dibuat khusus
  if (typeof window.tutupKasirPrinter === 'undefined') {
    try {
      const tutupKasirModule = await import('../../utils/TutupKasirPrinter')
      window.tutupKasirPrinter = tutupKasirModule.tutupKasirPrinter || tutupKasirModule.default
    } catch (importError) {
      throw new Error('tutupKasirPrinter tidak dapat diimport: ' + importError.message)
    }
  }

  if (!window.tutupKasirPrinter) {
    throw new Error('tutupKasirPrinter tidak tersedia')
  }

  const kasirName = getCurrentKasirName()
  
  // Konversi data sesuai dengan yang diexpect tutupKasirPrinter
  const printData = {
    pemasukanTunai: laporanData.tunai,
    pemasukanQris: laporanData.qris,
    totalPemasukan: laporanData.total
  }
  
  const printResult = await window.tutupKasirPrinter.printTutupKasir(printData, tanggal, kasirName)
  
  return printResult
}

const getCurrentKasirName = () => {
  try {
    if (window.printHelpers && window.printHelpers.getKasirName) {
      return window.printHelpers.getKasirName()
    }
    
    const userData = JSON.parse(sessionStorage.getItem('user') || localStorage.getItem('user') || '{}')
    return userData.nama || userData.name || userData.username || 'Kasir'
  } catch (error) {
    console.error('Error getting kasir name:', error)
    return 'Kasir'
  }
}

const formatDate = (date) => {
  if (typeof date === 'string') {
    date = new Date(date)
  }
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const initializeComponent = () => {
  const savedSidebarState = localStorage.getItem('sidebarVisible')
  if (savedSidebarState !== null) {
    sidebarVisible.value = savedSidebarState === 'true'
  }
  
  checkScreenSize()
  nextTick(() => updateMainContentMargin())
}

// Lifecycle
onMounted(() => {
  initializeComponent()
  window.addEventListener('resize', handleResize)
  window.addEventListener('keydown', handleKeyboard)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('keydown', handleKeyboard)
})
</script>

<style>
@import '../../assets/css/NavSidebar.css';

.tutup-kasir-btn {
  width: 100%;
  padding: 12px 16px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.tutup-kasir-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #d97706, #b45309);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
}

.tutup-kasir-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.loading-spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid transparent;
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 8px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .tutup-kasir-btn {
    padding: 10px 14px;
    font-size: 13px;
  }
}

.tutup-kasir-btn:focus {
  outline: 2px solid #fbbf24;
  outline-offset: 2px;
}
</style>