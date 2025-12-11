<template>
  
  <div class="kurir-container">
    <!-- Header -->
    <header class="kurir-header">
      <div class="header-content">
        <div class="header-left">
          <h1>Dashboard Kurir</h1>
          <p>{{ currentDate }}</p>
        </div>
        <div class="header-right">
          <div class="user-info">
            <div class="user-details">
              <span class="user-name">{{ userInfo.nama }}</span>
              <span class="user-role">Kurir Laundry</span>
            </div>
            <button class="logout-btn" @click="handleLogout">
              <i class="fas fa-sign-out-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Quick Stats -->
    <section class="quick-stats">
      <div class="stat-card pickup">
        <div class="stat-icon">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
          <h3>{{ pendingPickups.length }}</h3>
          <p>Pickup Pending</p>
        </div>
      </div>
      <div class="stat-card delivery">
        <div class="stat-icon">
          <i class="fas fa-truck"></i>
        </div>
        <div class="stat-info">
          <h3>{{ pendingDeliveries.length }}</h3>
          <p>Delivery Pending</p>
        </div>
      </div>
      <div class="stat-card completed">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <h3>{{ completedToday.length }}</h3>
          <p>Selesai Hari Ini</p>
        </div>
      </div>
      <div class="stat-card earnings">
        <div class="stat-icon">
          <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-info">
          <h3>Rp {{ formatPrice(todayEarnings) }}</h3>
          <p>{{ earningsFilterLabel }}</p>
        </div>
      </div>
    </section>

    <!-- Earnings Filter Section -->
    <section class="earnings-filter-section">
      <div class="filter-container">
        <h3>Filter Pendapatan</h3>
        <div class="filter-buttons">
          <button 
            @click="earningsFilter = 'today'"
            :class="['filter-btn', { active: earningsFilter === 'today' }]"
          >
            <i class="fas fa-calendar-day"></i>
            Hari Ini
          </button>
          <button 
            @click="earningsFilter = 'week'"
            :class="['filter-btn', { active: earningsFilter === 'week' }]"
          >
            <i class="fas fa-calendar-week"></i>
            Minggu Ini
          </button>
          <button 
            @click="earningsFilter = 'custom'"
            :class="['filter-btn', { active: earningsFilter === 'custom' }]"
          >
            <i class="fas fa-calendar-alt"></i>
            Custom
          </button>
          <button 
            @click="printEarnings"
            class="filter-btn print-btn"
            title="Print laporan pendapatan"
          >
            <i class="fas fa-print"></i>
            Print
          </button>
        </div>
        
        <!-- Custom Date Range Picker -->
        <div v-if="earningsFilter === 'custom'" class="custom-date-range">
          <div class="date-input-group">
            <label>Dari Tanggal:</label>
            <input v-model="customStartDate" type="date" class="date-input">
          </div>
          <div class="date-input-group">
            <label>Sampai Tanggal:</label>
            <input v-model="customEndDate" type="date" class="date-input">
          </div>
        </div>

        <!-- Earnings Summary -->
        <div class="earnings-summary">
          <div class="summary-item">
            <span>Total Pesanan Selesai:</span>
            <strong>{{ todayEarningsCount }} order</strong>
          </div>
          <div class="summary-item">
            <span>Total Pendapatan:</span>
            <strong style="color: #10b981;">Rp {{ formatPrice(todayEarnings) }}</strong>
          </div>
        </div>
      </div>
    </section>

    <!-- Service Tabs -->
    <section class="service-tabs">
      <button 
        v-for="tab in serviceTabs" 
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="['tab-btn', { active: activeTab === tab.key }]"
      >
        <i :class="tab.icon"></i>
        {{ tab.label }}
        <span class="badge" v-if="getTasksByType(tab.type).length > 0">
          {{ getTasksByType(tab.type).length }}
        </span>
      </button>
    </section>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-section">
      <div class="spinner"></div>
      <span>Memuat data tugas kurir...</span>
    </div>

    <!-- Tasks List -->
    <section v-else class="tasks-section">
      <div v-if="filteredTasks.length === 0" class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Tidak ada tugas {{ getCurrentTabLabel() }}</h3>
        <p>Tugas akan muncul di sini sesuai kategori yang dipilih</p>
        <button class="refresh-btn" @click="fetchTransactions">
          <i class="fas fa-sync-alt"></i>
          Refresh Data
        </button>
      </div>

      <div v-else class="tasks-list">
        <div 
          v-for="task in filteredTasks" 
          :key="task.id"
          :class="['task-card', getTaskStatusClass(task.pickup_status), getTaskTypeClass(task.type)]"
        >
          <!-- Task Header -->
          <div class="task-header">
            <div class="task-type-badge" :class="task.type">
              <i :class="task.type === 'pickup' ? 'fas fa-box' : 'fas fa-truck'"></i>
              {{ task.type === 'pickup' ? 'PICKUP' : 'DELIVERY' }}
            </div>
            <div class="task-priority" :class="getTaskPriority(task)">
              <i class="fas fa-flag"></i>
              {{ getTaskPriority(task).toUpperCase() }}
            </div>
          </div>

          <!-- Customer Info -->
          <div class="customer-info">
            <h4>{{ task.customer_name }}</h4>
            <div class="contact-info">
              <span class="phone">
                <i class="fas fa-phone"></i>
                {{ task.customer_phone }}
              </span>
              <button class="call-btn" @click="callCustomer(task.customer_phone)">
                <i class="fas fa-phone-alt"></i>
              </button>
            </div>
          </div>

          <!-- Task Details -->
          <div class="task-details">
            <div class="detail-row">
              <span>Order ID:</span>
              <strong>{{ task.kode_transaksi }}</strong>
            </div>
            <div class="detail-row">
              <span>Items:</span>
              <span>{{ getServicesText(task.items) }}</span>
            </div>
            <div class="detail-row">
              <span>Total:</span>
              <span>Rp {{ formatPrice(task.total_amount) }}</span>
            </div>
            <div class="detail-row">
              <span>Tanggal Order:</span>
              <span>{{ formatDate(task.tanggal_transaksi) }}</span>
            </div>
            <div class="detail-row" v-if="task.catatan">
              <span>Catatan:</span>
              <span>{{ task.catatan }}</span>
            </div>
            <div class="detail-row">
              <span>Status Bayar:</span>
              <span :class="task.status_transaksi === 'sukses' ? 'paid' : 'cod'">
                {{ task.status_transaksi === 'sukses' ? 'LUNAS' : 'COD' }}
              </span>
            </div>
            <div class="detail-row">
              <span>Biaya Pickup:</span>
              <span>Rp {{ formatPrice(task.biaya_pickup) }}</span>
            </div>
            <div class="detail-row" v-if="task.service_type">
              <span>Layanan:</span>
              <span class="service-badge">
                <i :class="getServiceTypeIcon(task.service_type)"></i>
                {{ getServiceTypeLabel(task.service_type) }}
              </span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="task-actions">
            <template v-if="task.pickup_status === 'dioutlet'">
              <button class="btn-action start" @click="startTask(task)" :disabled="isUpdatingStatus">
                <i class="fas fa-play"></i>
                Mulai {{ task.type === 'pickup' ? 'Pickup' : 'Antar' }}
              </button>
            </template>

            <template v-if="task.pickup_status === 'diantar'">
              <!-- KASUS 1: delivery_only atau pickup_delivery + bayar-nanti + belum lunas -->
              <!-- Kurir HARUS terima pembayaran di sini -->
              <template v-if="shouldPayAtDashboardKurir(task) && task.status_transaksi === 'pending'">
                <button class="btn-action pay-cash" @click="completeDeliveryWithPayment(task, 'tunai')" :disabled="isUpdatingStatus">
                  <i class="fas fa-money-bill"></i>
                  Bayar Tunai
                </button>
                <button class="btn-action pay-qris" @click="completeDeliveryWithPayment(task, 'qris')" :disabled="isUpdatingStatus">
                  <i class="fas fa-qrcode"></i>
                  Bayar QRIS
                </button>
              </template>
              <!-- KASUS 2: Sudah dibayar di kasir atau sudah lunas -->
              <!-- Kurir hanya perlu konfirmasi pengantaran selesai -->
              <template v-else>
                <button class="btn-action complete" @click="completeDelivery(task)" :disabled="isUpdatingStatus">
                  <i class="fas fa-check-circle"></i>
                  Selesai Antar
                </button>
              </template>
            </template>

            <!-- Always available actions -->
            <button class="btn-action info" @click="viewTaskDetails(task)">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>

          <!-- Status Timeline -->
          <div class="status-timeline">
            <div class="timeline-item" :class="{ active: ['dioutlet', 'diantar', 'selesai'].includes(task.pickup_status) }">
              <i class="fas fa-box"></i>
              <span>Di Outlet</span>
            </div>
            <div class="timeline-item" :class="{ active: ['diantar', 'selesai'].includes(task.pickup_status) }">
              <i class="fas fa-truck"></i>
              <span>Dalam Perjalanan</span>
            </div>
            <div class="timeline-item" :class="{ active: task.pickup_status === 'selesai' }">
              <i class="fas fa-check-circle"></i>
              <span>Selesai</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Task Detail Modal -->
    <div v-if="showDetailModal" class="modal-overlay" @click="closeDetailModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-truck"></i>
            Detail Delivery Order
          </h3>
          <button class="modal-close" @click="closeDetailModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="selectedTask">
          <div class="detail-section">
            <h4>Informasi Customer</h4>
            <div class="info-grid">
              <div class="info-item">
                <span>Nama:</span>
                <strong>{{ selectedTask.customer_name }}</strong>
              </div>
              <div class="info-item">
                <span>Telepon:</span>
                <strong>{{ selectedTask.customer_phone }}</strong>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h4>Detail Order</h4>
            <div class="info-grid">
              <div class="info-item">
                <span>Order ID:</span>
                <strong>{{ selectedTask.kode_transaksi }}</strong>
              </div>
              <div class="info-item">
                <span>Tanggal:</span>
                <strong>{{ formatDateTime(selectedTask.tanggal_transaksi) }}</strong>
              </div>
              <div class="info-item">
                <span>Items:</span>
                <span>{{ getServicesText(selectedTask.items) }}</span>
              </div>
              <div class="info-item">
                <span>Total:</span>
                <strong class="price">Rp {{ formatPrice(selectedTask.total_amount) }}</strong>
              </div>
              <div class="info-item">
                <span>Biaya Pickup:</span>
                <span>Rp {{ formatPrice(selectedTask.biaya_pickup) }}</span>
              </div>
              <div class="info-item">
                <span>Status Bayar:</span>
                <span :class="selectedTask.status_transaksi === 'sukses' ? 'paid' : 'cod'">
                  {{ selectedTask.status_transaksi === 'sukses' ? 'LUNAS' : 'COD' }}
                </span>
              </div>
            </div>
          </div>

          <div class="detail-section" v-if="selectedTask.catatan">
            <h4>Catatan</h4>
            <p class="notes">{{ selectedTask.catatan }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- COD Payment Modal -->
    <div v-if="showCODModal" class="modal-overlay">
      <div class="modal-content cod-modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-credit-card"></i>
            Pembayaran COD
          </h3>
          <button class="modal-close" @click="closeCODModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="codTask">
          <div class="cod-info">
            <p><strong>Order:</strong> {{ codTask.kode_transaksi }}</p>
            <p><strong>Customer:</strong> {{ codTask.customer_name }}</p>
            <p><strong>Total:</strong> Rp {{ formatPrice(codTask.total_amount) }}</p>
            <p v-if="codTask.service_type"><strong>Layanan:</strong> {{ getServiceTypeLabel(codTask.service_type) }}</p>
            <p><strong>Metode:</strong> {{ selectedCODMethod === 'tunai' ? 'Tunai' : 'QRIS' }}</p>
          </div>

          <!-- Cash Input for COD -->
          <div v-if="selectedCODMethod === 'tunai'" class="cod-cash-section">
            <h4>Input Pembayaran Tunai:</h4>
            <div class="cash-input-group">
              <label>Uang yang diterima dari customer:</label>
              <div class="cash-input-wrapper">
                <span class="currency-prefix">Rp</span>
                <input 
                  type="number" 
                  v-model="codCashAmount"
                  :min="codTask.total_amount"
                  step="1000"
                  placeholder="Masukkan jumlah uang"
                  class="cash-input"
                >
              </div>
              
              <!-- Quick Amounts -->
              <div class="quick-amounts">
                <button 
                  v-for="amount in getQuickAmounts(codTask.total_amount)"
                  :key="amount"
                  @click="codCashAmount = amount"
                  class="quick-amount-btn"
                  :class="{ active: codCashAmount == amount }"
                >
                  Rp {{ formatPrice(amount) }}
                </button>
              </div>

              <!-- Change Calculation -->
              <div v-if="codCashAmount >= codTask.total_amount" class="change-info success">
                <i class="fas fa-check-circle"></i>
                <span>Kembalian: <strong>Rp {{ formatPrice(codCashAmount - codTask.total_amount) }}</strong></span>
              </div>
              <div v-else-if="codCashAmount > 0" class="change-info error">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Uang kurang Rp {{ formatPrice(codTask.total_amount - codCashAmount) }}</span>
              </div>
            </div>
          </div>

          <!-- QRIS Info -->
          <div v-if="selectedCODMethod === 'qris'" class="qris-section">
            <div class="qris-info">
              <i class="fas fa-qrcode"></i>
              <p>Customer telah membayar via QRIS</p>
              <p class="qris-note">Pastikan pembayaran sudah diterima</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeCODModal" class="btn-cancel" :disabled="isUpdatingStatus">
            Batal
          </button>
          <button 
            @click="processCODPayment" 
            class="btn-confirm"
            :disabled="!canProcessCOD || isUpdatingStatus"
          >
            <span v-if="isUpdatingStatus" class="btn-spinner"></span>
            <span v-else>Konfirmasi Pembayaran & Selesai</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { printHelpers } from '../utils/PrintHelpers'

const router = useRouter()

// Reactive data
const currentDate = ref('')
const activeTab = ref('delivery')
const showDetailModal = ref(false)
const showCODModal = ref(false)
const selectedTask = ref(null)
const codTask = ref(null)
const selectedCODMethod = ref('')
const codCashAmount = ref(0)
const isLoading = ref(false)
const isUpdatingStatus = ref(false)
const earningsFilter = ref('today') // 'today', 'week', 'custom'
const customStartDate = ref(null)
const customEndDate = ref(null)

// User info
const userInfo = ref({
  nama: 'Kurir Laundry',
  id: 1,
  jabatan: 'kurir'
})

// Tasks data from API
const transactions = ref([])

// Service tabs - Updated to only show delivery and completed
const serviceTabs = [
  { key: 'delivery', label: 'Delivery', icon: 'fas fa-truck', type: 'delivery' },
  { key: 'completed', label: 'Selesai', icon: 'fas fa-check-circle', type: 'completed' }
]

// Computed properties
const filteredTasks = computed(() => {
  // Filter hanya transaksi yang memiliki pickup service
  const pickupOrders = transactions.value.filter(tx => tx.has_pickup_service)
  
  if (activeTab.value === 'completed') {
    return pickupOrders.filter(tx => tx.pickup_status === 'selesai')
  } else {
    // delivery tab - show orders ready for delivery
    return pickupOrders.filter(tx => 
      tx.pickup_status !== 'selesai' && 
      tx.status_cucian === 'completed' // Only show completed orders
    )
  }
})

const pendingPickups = computed(() => {
  return transactions.value.filter(tx => 
    tx.has_pickup_service && 
    tx.pickup_status === 'dioutlet' &&
    tx.status_cucian !== 'completed'
  )
})

const pendingDeliveries = computed(() => {
  return transactions.value.filter(tx => 
    tx.has_pickup_service && 
    tx.pickup_status === 'diantar' &&
    tx.status_cucian === 'completed'
  )
})

const completedToday = computed(() => {
  const today = new Date().toDateString()
  return transactions.value.filter(tx => 
    tx.has_pickup_service &&
    tx.pickup_status === 'selesai' && 
    new Date(tx.updated_at).toDateString() === today
  )
})

// Filtered completed transactions based on earnings filter
const filteredCompletedTransactions = computed(() => {
  const now = new Date()
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const startOfWeek = new Date(startOfToday)
  startOfWeek.setDate(startOfToday.getDate() - startOfToday.getDay())
  
  return transactions.value.filter(tx => {
    if (!tx.has_pickup_service || tx.pickup_status !== 'selesai') return false
    
    const txDate = new Date(tx.updated_at)
    
    if (earningsFilter.value === 'today') {
      return txDate >= startOfToday
    } else if (earningsFilter.value === 'week') {
      return txDate >= startOfWeek
    } else if (earningsFilter.value === 'custom') {
      if (!customStartDate.value || !customEndDate.value) return false
      const start = new Date(customStartDate.value)
      const end = new Date(customEndDate.value)
      end.setHours(23, 59, 59, 999)
      return txDate >= start && txDate <= end
    }
    return false
  })
})

const todayEarnings = computed(() => {
  return filteredCompletedTransactions.value.reduce((total, tx) => {
    return total + (tx.biaya_pickup || 0)
  }, 0)
})

const todayEarningsCount = computed(() => {
  return filteredCompletedTransactions.value.length
})

const earningsFilterLabel = computed(() => {
  if (earningsFilter.value === 'today') return 'Pendapatan Hari Ini'
  if (earningsFilter.value === 'week') return 'Pendapatan Minggu Ini'
  if (earningsFilter.value === 'custom') {
    if (customStartDate.value && customEndDate.value) {
      return `Pendapatan ${customStartDate.value} - ${customEndDate.value}`
    }
    return 'Pilih Tanggal'
  }
  return 'Pendapatan'
})

const canProcessCOD = computed(() => {
  if (!selectedCODMethod.value) return false
  if (selectedCODMethod.value === 'tunai') {
    return codCashAmount.value >= codTask.value?.total_amount
  }
  return true
})

// Functions
const fetchTransactions = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/transaksi')
    if (response.data.success) {
      transactions.value = response.data.data.map(tx => ({
        ...tx,
        type: 'delivery', // All pickup service orders are delivery tasks for kurir
        pickup_status: tx.pickup_status || 'dioutlet'
      }))
      console.log('Transactions loaded for kurir:', transactions.value.length)
    }
  } catch (error) {
    console.error('Error fetching transactions:', error)
    showErrorAlert('Gagal memuat data transaksi')
  } finally {
    isLoading.value = false
  }
}

const getTasksByType = (type) => {
  const pickupOrders = transactions.value.filter(tx => tx.has_pickup_service)
  
  if (type === 'completed') {
    return pickupOrders.filter(tx => tx.pickup_status === 'selesai')
  } else {
    // delivery type - show orders that are ready for pickup/delivery
    return pickupOrders.filter(tx => 
      tx.pickup_status === 'dioutlet' || tx.pickup_status === 'diantar'
    )
  }
}

const getCurrentTabLabel = () => {
  const tab = serviceTabs.find(t => t.key === activeTab.value)
  return tab ? tab.label.toLowerCase() : ''
}

const getTaskStatusClass = (status) => {
  const statusClasses = {
    dioutlet: 'status-ready',
    diantar: 'status-progress',
    selesai: 'status-completed'
  }
  return statusClasses[status] || ''
}

const getTaskTypeClass = (type) => {
  return `type-${type}`
}

const getTaskPriority = (task) => {
  // Determine priority based on order date and payment status
  const orderDate = new Date(task.tanggal_transaksi)
  const daysDiff = Math.floor((new Date() - orderDate) / (1000 * 60 * 60 * 24))
  
  if (task.status_transaksi === 'pending') return 'high' // COD orders are priority
  if (daysDiff > 2) return 'high'
  if (daysDiff > 1) return 'normal'
  return 'low'
}

const getServicesText = (items) => {
  if (!items || items.length === 0) return 'Tidak ada layanan'
  return items.map(item => item.layanan_nama || item.nama_layanan || 'Unknown Service').join(', ')
}

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('id-ID').format(price)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// ========================================
// SERVICE TYPE HELPER FUNCTIONS
// ========================================

// Delivery Only (Antar Saja): Pembayaran bisa di DashboardKasir atau DashboardKurir
const isDeliveryOnlyWithBayarNanti = (task) => {
  return (
    task.has_pickup_service === true &&
    task.service_type === 'delivery_only' &&
    task.metode_pembayaran === 'bayar-nanti' &&
    task.status_transaksi === 'pending'
  )
}

// Pickup and Delivery (Ambil + Antar): Pembayaran HARUS di DashboardKurir
const isPickupDeliveryOrder = (task) => {
  return (
    task.has_pickup_service === true &&
    task.service_type === 'pickup_delivery'
  )
}

// Cek apakah order HARUS dibayar di DashboardKurir
// Harus di kurir jika: pickup_delivery (selalu) atau delivery_only (jika sudah diserahkan ke kurir dan belum dibayar)
const shouldPayAtDashboardKurir = (task) => {
  return (
    isPickupDeliveryOrder(task) ||
    isDeliveryOnlyWithBayarNanti(task)
  )
}

const getServiceTypeLabel = (serviceType) => {
  const labels = {
    'pickup_only': 'Ambil Saja',
    'delivery_only': 'Antar Saja',
    'pickup_delivery': 'Ambil + Antar'
  }
  return labels[serviceType] || serviceType
}

const getServiceTypeIcon = (serviceType) => {
  const icons = {
    'pickup_only': 'fas fa-hand-holding',
    'delivery_only': 'fas fa-shipping-fast',
    'pickup_delivery': 'fas fa-exchange-alt'
  }
  return icons[serviceType] || 'fas fa-box'
}

const getQuickAmounts = (totalAmount) => {
  const base = Math.ceil(totalAmount / 1000) * 1000
  return [
    totalAmount, // Exact amount
    base,
    base + 5000,
    base + 10000,
    base + 20000,
    base + 50000
  ].filter(amount => amount >= totalAmount)
}

const startTask = async (task) => {
  try {
    const confirmed = await confirmAction(`Mulai pengantaran untuk order ${task.kode_transaksi}?`)
    
    if (confirmed) {
      isUpdatingStatus.value = true
      await updatePickupStatus(task.id, 'diantar')
      showSuccessToast('Pengantaran dimulai!')
    }
  } catch (error) {
    console.error('Error starting task:', error)
    showErrorAlert('Gagal memulai tugas')
  } finally {
    isUpdatingStatus.value = false
  }
}

const completeDelivery = async (task) => {
  try {
    const confirmed = await confirmAction(`Selesaikan pengantaran untuk ${task.customer_name}?\n\nOrder: ${task.kode_transaksi}`)
    
    if (confirmed) {
      isUpdatingStatus.value = true
      await updatePickupStatus(task.id, 'selesai')
      showSuccessAlert('Pengantaran selesai!')
    }
  } catch (error) {
    console.error('Error completing delivery:', error)
    showErrorAlert('Gagal menyelesaikan pengantaran')
  } finally {
    isUpdatingStatus.value = false
  }
}

const completeDeliveryWithPayment = (task, method) => {
  codTask.value = task
  selectedCODMethod.value = method
  codCashAmount.value = method === 'tunai' ? task.total_amount : 0
  showCODModal.value = true
}

const processCODPayment = async () => {
  try {
    isUpdatingStatus.value = true
    
    // Validate codTask exists
    if (!codTask.value || !codTask.value.id) {
      throw new Error('Task data not found. Please try again.')
    }
    
    // Update payment status
    const paymentData = {
      metode_pembayaran: selectedCODMethod.value,
      status_transaksi: 'sukses',
      jumlah_bayar: selectedCODMethod.value === 'tunai' ? codCashAmount.value : codTask.value.total_amount,
      kembalian: selectedCODMethod.value === 'tunai' ? (codCashAmount.value - codTask.value.total_amount) : 0
    }

    console.log('📤 Mengirim pembayaran ke server...', {
      orderId: codTask.value.id,
      orderCode: codTask.value.kode_transaksi || 'N/A',
      method: selectedCODMethod.value,
      amount: paymentData.jumlah_bayar
    })

    await axios.patch(`/api/transaksi/${codTask.value.id}/payment`, paymentData)
    
    console.log('✅ Pembayaran diterima server!')
    
    // Update pickup status to completed
    await updatePickupStatus(codTask.value.id, 'selesai')
    
    // Update local data
    const txIndex = transactions.value.findIndex(tx => tx.id === codTask.value.id)
    if (txIndex !== -1) {
      Object.assign(transactions.value[txIndex], paymentData)
      transactions.value[txIndex].pickup_status = 'selesai'
    }
    
    closeCODModal()
    
    // Show success message with details
    if (codTask.value) {
      const methodText = selectedCODMethod.value === 'tunai' ? 'Tunai' : 'QRIS'
      Swal.fire({
        icon: 'success',
        title: 'Pembayaran Diterima!',
        html: `
          <div style="text-align: left;">
            <p><strong>Order:</strong> ${codTask.value.kode_transaksi || 'N/A'}</p>
            <p><strong>Customer:</strong> ${codTask.value.customer_name || 'N/A'}</p>
            <p><strong>Metode:</strong> ${methodText}</p>
            <p><strong>Jumlah:</strong> Rp ${formatPrice(paymentData.jumlah_bayar)}</p>
            ${paymentData.kembalian > 0 ? `<p><strong>Kembalian:</strong> Rp ${formatPrice(paymentData.kembalian)}</p>` : ''}
            <hr>
            <p style="color: green;"><strong>✅ Pengantaran selesai dan pembayaran tercatat!</strong></p>
            <p style="font-size: 12px; color: #666;">Data sudah dikirim ke Dashboard Kasir</p>
          </div>
        `,
        confirmButtonText: 'OK',
        confirmButtonColor: '#10b981'
      })
    }
    
  } catch (error) {
    console.error('Error processing COD payment:', error)
    showErrorAlert('Gagal memproses pembayaran COD')
  } finally {
    isUpdatingStatus.value = false
  }
}

const updatePickupStatus = async (orderId, newStatus) => {
  try {
    const response = await axios.patch(`/api/transaksi/${orderId}/pickup-status`, {
      pickup_status: newStatus
    })

    if (response.data.success) {
      const txIndex = transactions.value.findIndex(tx => tx.id === orderId)
      if (txIndex !== -1) {
        transactions.value[txIndex].pickup_status = newStatus
      }
    }
  } catch (error) {
    console.error('Update pickup status error:', error)
    throw error
  }
}

const callCustomer = (phone) => {
  window.location.href = `tel:${phone}`
}

const viewTaskDetails = (task) => {
  selectedTask.value = task
  showDetailModal.value = true
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedTask.value = null
}

const closeCODModal = () => {
  showCODModal.value = false
  codTask.value = null
  selectedCODMethod.value = ''
  codCashAmount.value = 0
}

const confirmAction = (message) => {
  return Swal.fire({
    title: 'Konfirmasi',
    text: message,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#ef4444',
    confirmButtonText: 'Ya',
    cancelButtonText: 'Batal'
  }).then((result) => result.isConfirmed)
}

const showSuccessAlert = (message) => {
  Swal.fire({
    title: 'Berhasil!',
    text: message,
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
}

const showSuccessToast = (message) => {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: message,
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true
  })
}

const showErrorAlert = (message) => {
  Swal.fire({
    title: 'Error!',
    text: message,
    icon: 'error',
    confirmButtonText: 'OK'
  })
}

const printEarnings = () => {
  if (filteredCompletedTransactions.value.length === 0) {
    showErrorAlert('Tidak ada data untuk dicetak')
    return
  }

  // Prepare earnings data for print
  const earningsData = {
    filter: earningsFilter.value,
    startDate: customStartDate.value,
    endDate: customEndDate.value,
    totalEarnings: todayEarnings.value,
    totalOrders: todayEarningsCount.value,
    transactions: filteredCompletedTransactions.value,
    courierName: userInfo.value.nama,
    printDate: new Date().toLocaleString('id-ID')
  }

  printHelpers.printCourierEarnings(earningsData)
}

const handleLogout = () => {
  Swal.fire({
    title: 'Konfirmasi Logout',
    text: 'Apakah Anda yakin ingin keluar?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Logout',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      localStorage.removeItem('user')
      localStorage.removeItem('token')
      sessionStorage.clear()
      router.push('/')
      Swal.fire('Berhasil', 'Anda telah logout', 'success')
    }
  })
}

// Initialize component
onMounted(() => {
  // Set current date
  const now = new Date()
  const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
  const bulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ]
  currentDate.value = `${hari[now.getDay()]}, ${now.getDate()} ${bulan[now.getMonth()]} ${now.getFullYear()}`

  // Set user info from localStorage if available
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    try {
      const user = JSON.parse(storedUser)
      if (user.nama) {
        userInfo.value = { ...userInfo.value, ...user }
      }
    } catch (error) {
      console.warn('Error parsing stored user data:', error)
    }
  }

  // Fetch transaction data
  fetchTransactions()
  
  console.log('DashboardKurir initialized - integrated with transaction API')
})
</script>

<style scoped>
@import '../assets/css/DashboardKurir.css';

/* Additional styles for integrated version */
.loading-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.refresh-btn {
  margin-top: 16px;
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background 0.2s ease;
}

.refresh-btn:hover {
  background: #2563eb;
}

.refresh-btn i {
  margin-right: 8px;
}

/* Status-specific styles */
.paid {
  color: #059669;
  font-weight: 600;
}

.cod {
  color: #dc2626;
  font-weight: 600;
}

.unpaid {
  color: #d97706;
  font-weight: 600;
}

/* COD Modal styles */
.cod-modal {
  max-width: 500px;
  width: 90%;
}

.cod-info {
  background: #f0f9ff;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid #0ea5e9;
}

.cod-info p {
  margin: 4px 0;
  color: #0c4a6e;
}

.cod-cash-section {
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  margin-top: 16px;
}

.cod-cash-section h4 {
  margin-bottom: 12px;
  color: #374151;
}

.cash-input-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
}

.cash-input-wrapper {
  position: relative;
  margin-bottom: 12px;
}

.currency-prefix {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-weight: 500;
}

.cash-input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: 2px solid #d1d5db;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 500;
}

.cash-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.quick-amounts {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-bottom: 12px;
}

.quick-amount-btn {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: white;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-amount-btn:hover {
  border-color: #3b82f6;
  background: #f8fafc;
}

.quick-amount-btn.active {
  border-color: #3b82f6;
  background: #3b82f6;
  color: white;
}

.change-info {
  display: flex;
  align-items: center;
  padding: 12px;
  border-radius: 6px;
  font-weight: 500;
  margin-top: 12px;
}

.change-info.success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #10b981;
}

.change-info.error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #ef4444;
}

.change-info i {
  margin-right: 8px;
}

.qris-section {
  background: #f0f9ff;
  padding: 16px;
  border-radius: 8px;
  text-align: center;
  margin-top: 16px;
}

.qris-info i {
  font-size: 48px;
  color: #0369a1;
  margin-bottom: 12px;
}

.qris-info p {
  margin: 8px 0;
  color: #374151;
}

.qris-note {
  font-size: 14px;
  color: #6b7280;
  font-style: italic;
}

/* Modal footer styles */
.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 10px 20px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-confirm {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  background: #3b82f6;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-confirm:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-confirm:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-top: 2px solid transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Enhanced task action buttons */
.btn-action {
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-action.start {
  background: #10b981;
  color: white;
}

.btn-action.start:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
}

.btn-action.complete {
  background: #8b5cf6;
  color: white;
}

.btn-action.complete:hover:not(:disabled) {
  background: #7c3aed;
  transform: translateY(-1px);
}

.btn-action.pay-cash {
  background: #f59e0b;
  color: white;
}

.btn-action.pay-cash:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-1px);
}

.btn-action.pay-qris {
  background: #3b82f6;
  color: white;
}

.btn-action.pay-qris:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-action.info {
  background: #6b7280;
  color: white;
}

.btn-action.info:hover:not(:disabled) {
  background: #4b5563;
  transform: translateY(-1px);
}

/* Earnings Filter Styles */
.earnings-filter-section {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 24px;
  margin: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.filter-container h3 {
  margin-top: 0;
  color: #1e293b;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
}

.filter-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.filter-btn {
  padding: 10px 16px;
  background: white;
  border: 2px solid #cbd5e1;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.filter-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
  background: #f0f9ff;
}

.filter-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.filter-btn.print-btn {
  background: #10b981;
  border-color: #10b981;
  color: white;
  margin-left: auto;
}

.filter-btn.print-btn:hover {
  background: #059669;
  border-color: #059669;
}

.custom-date-range {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.date-input-group {
  display: flex;
  flex-direction: column;
}

.date-input-group label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 6px;
}

.date-input {
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
}

.date-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.earnings-summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  background: white;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f1f5f9;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item span {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

.summary-item strong {
  color: #1e293b;
  font-size: 14px;
  font-weight: 600;
}

/* Responsive design */
@media (max-width: 768px) {
  .quick-amounts {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn-cancel,
  .btn-confirm {
    width: 100%;
    justify-content: center;
  }
  
  .cod-modal {
    max-width: 95%;
    margin: 20px auto;
  }

  .filter-buttons {
    flex-direction: column;
  }

  .filter-btn {
    width: 100%;
    justify-content: center;
  }

  .filter-btn.print-btn {
    margin-left: 0;
  }

  .custom-date-range {
    grid-template-columns: 1fr;
  }

  .earnings-summary {
    grid-template-columns: 1fr;
  }
}
</style>