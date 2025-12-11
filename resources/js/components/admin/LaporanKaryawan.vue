<template>
  <div class="admin-report-container">
    <!-- Page Header -->
    <div class="page-header">
    </div>

    <!-- Quick Stats Overview -->
    <div class="quick-stats">
      <div class="stat-card performance">
        <div class="stat-icon">
          <img src="../../assets/img/Verified.png" alt="Logo" class="verified-logo" />
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ orderCompletionRate }}%</div>
          <div class="stat-label">Tingkat Penyelesaian</div>
          <div class="stat-trend">{{ reportData.orderPaid }}/{{ reportData.totalOrder }} order</div>
        </div>
      </div>

      <div class="stat-card revenue">
        <div class="stat-icon">
          <img src="../../assets/img/Salary.png" alt="Logo" class="salary-logo" />
        </div>
        <div class="stat-content">
          <div class="stat-value">Rp {{ formatCurrency(reportData.totalPemasukan) }}</div>
          <div class="stat-label">Total Pendapatan</div>
          <div class="stat-trend">{{ getPeriodText() }}</div>
        </div>
      </div>
      
      <div class="stat-card efficiency">
        <div class="stat-icon">
          <img src="../../assets/img/Flash.png" alt="Logo" class="flash-logo" />
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ reportData.totalOrder }}</div>
          <div class="stat-label">Total Order</div>
          <div class="stat-trend">{{ transactions.length }} transaksi</div>
        </div>
      </div>
      
      <div class="stat-card customer">
        <div class="stat-icon"><img src="../../assets/img/Customer.png" alt="Logo" class="customer-logo" /></div>
        <div class="stat-content">
          <div class="stat-value">{{ uniqueCustomers }}</div>
          <div class="stat-label">Unique Customers</div>
          <div class="stat-trend">Dalam periode ini</div>
        </div>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-card admin">
        <div class="filter-header">
          <h3> Periode Analisis</h3>
          <div class="quick-filters">
            <button 
              class="quick-filter-btn"
              :class="{ active: selectedPeriod === 'today' }"
              @click="setQuickFilter('today')"
            >
              Hari Ini
            </button>
            <button 
              class="quick-filter-btn"
              :class="{ active: selectedPeriod === 'week' }"
              @click="setQuickFilter('week')"
            >
              Minggu Ini
            </button>
            <button 
              class="quick-filter-btn"
              :class="{ active: selectedPeriod === 'month' }"
              @click="setQuickFilter('month')"
            >
              Bulan Ini
            </button>
            <button 
              class="quick-filter-btn"
              :class="{ active: selectedPeriod === 'custom' }"
              @click="setQuickFilter('custom')"
            >
              Custom
            </button>
          </div>
        </div>
        
        <div v-if="selectedPeriod === 'custom'" class="custom-date-range">
          <div class="date-inputs">
            <div class="date-group">
              <label>Dari Tanggal</label>
              <input 
                type="date" 
                v-model="dateFrom"
                class="date-input"
                @change="handleDateChange"
              />
            </div>
            <div class="date-group">
              <label>Sampai Tanggal</label>
              <input 
                type="date" 
                v-model="dateTo"
                class="date-input"
                @change="handleDateChange"
              />
            </div>
          </div>
        </div>
        
        <div class="filter-actions">
          <button 
            class="filter-btn primary"
            @click="fetchReportData"
            :disabled="!canFetchData"
          >
            Analisis Data
          </button>
          <button 
            class="filter-btn secondary"
            @click="resetFilters"
          >
            🔄 Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Main Analytics Grid -->
    <div class="analytics-grid">
      <!-- Revenue Analysis -->
      <div class="analytics-card revenue-analysis">
        <div class="card-header">
          <h3>Analisis Pendapatan</h3>
          <div class="card-period">{{ getPeriodText() }}</div>
        </div>
        <div class="card-content">
          <div class="revenue-metrics">
            <div class="metric-item">
              <span class="metric-label">Total Pendapatan</span>
              <span class="metric-value primary">Rp {{ formatCurrency(reportData.totalPemasukan) }}</span>
            </div>
            <div class="metric-item">
              <span class="metric-label">Rata-rata per Hari</span>
              <span class="metric-value">Rp {{ formatCurrency(reportData.avgDailyRevenue) }}</span>
            </div>
            <div class="metric-item">
              <span class="metric-label">Efisiensi</span>
              <span class="metric-value positive">{{ orderCompletionRate }}%</span>
            </div>
          </div>
          
          <div class="payment-breakdown">
            <h4>Breakdown Metode Pembayaran</h4>
            <div class="payment-items">
              <div class="payment-item tunai">
                <div class="payment-info">
                  <span class="payment-icon">
                      <img src="../../assets/img/Dollar.png" alt="Logo" class="dollar-logo" />
                  </span>
                  <div class="payment-details">
                    <span class="payment-name">Tunai</span>
                    <span class="payment-count">{{ reportData.transaksiTunai }} transaksi</span>
                  </div>
                </div>
                <span class="payment-amount">Rp {{ formatCurrency(reportData.pemasukanTunai) }}</span>
              </div>
              
              <div class="payment-item qris">
                <div class="payment-info">
                  <span class="payment-icon">
                     <img src="../../assets/img/Qris.png" alt="Logo" class="qris-logo" />
                  </span>
                  <div class="payment-details">
                    <span class="payment-name">QRIS</span>
                    <span class="payment-count">{{ reportData.transaksiQris }} transaksi</span>
                  </div>
                </div>
                <span class="payment-amount">Rp {{ formatCurrency(reportData.pemasukanQris) }}</span>
              </div>
              
              <div class="payment-item bayar-nanti">
                <div class="payment-info">
                  <span class="payment-icon">
                     <img src="../../assets/img/Clock.png" alt="Logo" class="clock-logo" />
                  </span>
                  <div class="payment-details">
                    <span class="payment-name">Bayar Nanti</span>
                    <span class="payment-count">{{ reportData.transaksiBayarNanti }} transaksi</span>
                  </div>
                </div>
                <span class="payment-amount">Rp {{ formatCurrency(reportData.pemasukanBayarNanti) }}</span>
              </div>

              <!-- ✅ NEW: Pickup Revenue -->
              <div class="payment-item pickup">
                <div class="payment-info">
                  <span class="payment-icon">
                    <img src="../../assets/img/Delivery.png" alt="Logo" class="delivery-logo" />
                  </span>
                  <div class="payment-details">
                    <span class="payment-name">Pickup Service</span>
                    <span class="payment-count">{{ reportData.transaksiPickup }} transaksi</span>
                  </div>
                </div>
                <span class="payment-amount">Rp {{ formatCurrency(reportData.pemasukanPickup) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Statistics -->
      <div class="analytics-card order-stats">
        <div class="card-header">
          <h3>Statistik Order</h3>
          <div class="card-actions">
            <button class="export-mini-btn" @click="exportOrderStats">📋</button>
          </div>
        </div>
        <div class="card-content">
          <div class="order-overview">
            <div class="order-stat-item total">
              <div class="stat-number">{{ reportData.totalOrder }}</div>
              <div class="stat-label">Total Order</div>
            </div>
            <div class="order-stat-item completed">
              <div class="stat-number">{{ reportData.orderPaid }}</div>
              <div class="stat-label">Selesai</div>
            </div>
            <div class="order-stat-item pending">
              <div class="stat-number">{{ reportData.orderPending }}</div>
              <div class="stat-label">Pending</div>
            </div>
          </div>
          
          <div class="order-efficiency">
            <div class="efficiency-bar">
              <div class="efficiency-fill" :style="{ width: orderCompletionRate + '%' }"></div>
            </div>
            <div class="efficiency-text">
              <span>Tingkat Penyelesaian: {{ orderCompletionRate }}%</span>
            </div>
          </div>
          
          <div class="popular-services">
            <h4>Layanan Terpopuler</h4>
            <div class="service-list">
              <div 
                v-for="(service, index) in popularServices" 
                :key="index"
                class="service-item"
              >
                <span class="service-rank">#{{ index + 1 }}</span>
                <span class="service-name">{{ service.name }}</span>
                <span class="service-count">{{ service.count }} order</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Transactions Table -->
    <div class="transactions-section">
      <div class="section-header">
        <h3>Detail Transaksi</h3>
        <div class="section-actions">
          <button class="action-btn" @click="refreshTransactions">🔄 Refresh</button>
          <button class="action-btn" @click="exportAllTransactions">📤 Export All</button>
        </div>
      </div>
      
      <div class="transactions-content">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <span>Memuat data transaksi...</span>
        </div>
        
        <div v-else-if="transactions.length === 0" class="empty-state">
          <span class="empty-icon">📭</span>
          <h4>Tidak ada transaksi dalam periode yang dipilih</h4>
          <p>Silakan pilih rentang tanggal yang berbeda atau cek koneksi database</p>
        </div>
        
        <div v-else class="transactions-table-container">
          <table class="transactions-table">
            <thead>
              <tr>
                <th>Kode Transaksi</th>
                <th>Tanggal & Waktu</th>
                <th>Customer</th>
                <th>Layanan</th>
                <th>Pickup</th>
                <th>Metode Bayar</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="transaction in paginatedTransactions" 
                :key="transaction.id"
                class="transaction-row"
              >
                <td>
                  <span class="transaction-code">{{ transaction.kode_transaksi }}</span>
                </td>
                <td>
                  <div class="datetime-cell">
                    <span class="date">{{ formatDate(transaction.tanggal_transaksi) }}</span>
                    <span class="time">{{ formatTime(transaction.tanggal_transaksi) }}</span>
                  </div>
                </td>
                <td>
                  <div class="customer-cell">
                    <span class="customer-name">{{ transaction.customer_name }}</span>
                    <span class="customer-phone">{{ transaction.customer_phone }}</span>
                  </div>
                </td>
                <td>
                  <div class="services-cell">
                    <span 
                      v-for="(item, index) in transaction.items.slice(0, 2)" 
                      :key="index"
                      class="service-tag"
                    >
                      {{ item.layanan_nama }}
                    </span>
                    <span v-if="transaction.items.length > 2" class="more-services">
                      +{{ transaction.items.length - 2 }} lainnya
                    </span>
                    <span v-if="transaction.items.length === 0" class="service-tag empty">
                      Tidak ada layanan
                    </span>
                  </div>
                </td>
                <td>
                  <div class="pickup-cell">
                    <span v-if="transaction.has_pickup_service" class="pickup-badge active">
                      🚚 {{ transaction.pickup_service_name || 'Pickup' }}
                    </span>
                    <span v-else class="pickup-badge inactive">
                      ➖ Tidak ada
                    </span>
                  </div>
                </td>
                <td>
                  <span class="payment-method" :class="transaction.metode_pembayaran">
                    {{ getPaymentMethodIcon(transaction.metode_pembayaran) }} 
                    {{ formatPaymentMethod(transaction.metode_pembayaran) }}
                  </span>
                </td>
                <td>
                  <div class="amount-cell">
                    <span class="amount total">Rp {{ formatCurrency(transaction.total_amount) }}</span>
                    <div class="amount-breakdown">
                      <small v-if="transaction.subtotal_layanan > 0">
                        Layanan: Rp {{ formatCurrency(transaction.subtotal_layanan) }}
                      </small>
                      <small v-if="transaction.biaya_pickup > 0">
                        Pickup: Rp {{ formatCurrency(transaction.biaya_pickup) }}
                      </small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="status" :class="transaction.status_transaksi">
                    {{ getStatusIcon(transaction.status_transaksi) }} 
                    {{ formatStatus(transaction.status_transaksi) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination">
            <button 
              class="page-btn"
              @click="currentPage--"
              :disabled="currentPage <= 1"
            >
              ⬅️ Prev
            </button>
            
            <div class="page-numbers">
              <button
                v-for="page in visiblePages"
                :key="page"
                class="page-number"
                :class="{ active: page === currentPage }"
                @click="currentPage = page"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              class="page-btn"
              @click="currentPage++"
              :disabled="currentPage >= totalPages"
            >
              Next ➡️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Options -->
    <div class="export-section admin">
      <div class="export-card">
        <h3>📥 Export Laporan Lengkap</h3>
        <p>Download laporan komprehensif untuk analisis lanjutan</p>
        
        <div class="export-options">
          <div class="export-option">
            <button 
              class="export-btn pdf admin"
              @click="exportToPDF"
              :disabled="isExporting || !hasData"
            >
              <span class="btn-icon">📄</span>
              <div class="btn-content">
                <span class="btn-title">Executive Report (PDF)</span>
                <span class="btn-subtitle">Laporan ringkas untuk manajemen</span>
              </div>
            </button>
          </div>
          
          <div class="export-option">
            <button 
              class="export-btn excel admin"
              @click="exportToExcel"
              :disabled="isExporting || !hasData"
            >
              <span class="btn-icon">📊</span>
              <div class="btn-content">
                <span class="btn-title">Detailed Analytics (Excel)</span>
                <span class="btn-subtitle">Data lengkap untuk analisis mendalam</span>
              </div>
            </button>
          </div>
          
          <div class="export-option">
            <button 
              class="export-btn dashboard admin"
              @click="exportToDashboard"
              :disabled="isExporting || !hasData"
            >
              <span class="btn-icon">📈</span>
              <div class="btn-content">
                <span class="btn-title">Dashboard Summary</span>
                <span class="btn-subtitle">Ringkasan untuk dashboard real-time</span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

// Current year
const currentYear = new Date().getFullYear()

// Reactive data
const selectedPeriod = ref('today')
const dateFrom = ref('')
const dateTo = ref('')
const isLoading = ref(false)
const isExporting = ref(false)
const currentPage = ref(1)
const itemsPerPage = 15

// ✅ FIXED: Using same structure as LaporanHarian.vue
const reportData = ref({
  totalOrder: 0,
  totalPemasukan: 0,
  orderPending: 0,
  orderPaid: 0,
  pemasukanTunai: 0,
  pemasukanQris: 0,
  pemasukanBayarNanti: 0,
  pemasukanPickup: 0,
  transaksiTunai: 0,
  transaksiQris: 0,
  transaksiBayarNanti: 0,
  transaksiPickup: 0,
  avgDailyRevenue: 0
})

const transactions = ref([])

// Computed properties
const canFetchData = computed(() => {
  if (selectedPeriod.value === 'custom') {
    return dateFrom.value && dateTo.value
  }
  return true
})

const orderCompletionRate = computed(() => {
  if (reportData.value.totalOrder === 0) return 0
  return Math.round((reportData.value.orderPaid / reportData.value.totalOrder) * 100)
})

const uniqueCustomers = computed(() => {
  const customerPhones = new Set()
  transactions.value.forEach(transaction => {
    if (transaction.customer_phone) {
      customerPhones.add(transaction.customer_phone)
    }
  })
  return customerPhones.size
})

const popularServices = computed(() => {
  const serviceCount = {}
  transactions.value.forEach(transaction => {
    if (transaction.items && Array.isArray(transaction.items)) {
      transaction.items.forEach(item => {
        const serviceName = item.layanan_nama
        if (serviceName) {
          serviceCount[serviceName] = (serviceCount[serviceName] || 0) + 1
        }
      })
    }
  })
  
  return Object.entries(serviceCount)
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
})

const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return transactions.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(transactions.value.length / itemsPerPage)
})

const visiblePages = computed(() => {
  const delta = 2
  const range = []
  const rangeWithDots = []
  
  for (let i = Math.max(2, currentPage.value - delta); 
       i <= Math.min(totalPages.value - 1, currentPage.value + delta); 
       i++) {
    range.push(i)
  }
  
  if (currentPage.value - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }
  
  rangeWithDots.push(...range)
  
  if (currentPage.value + delta < totalPages.value - 1) {
    rangeWithDots.push('...', totalPages.value)
  } else {
    rangeWithDots.push(totalPages.value)
  }
  
  return rangeWithDots
})

const hasData = computed(() => {
  return transactions.value.length > 0
})

// ✅ FIXED: Functions copied from LaporanHarian.vue
function setQuickFilter(period) {
  selectedPeriod.value = period
  const today = new Date()
  
  switch (period) {
    case 'today':
      dateFrom.value = today.toISOString().split('T')[0]
      dateTo.value = today.toISOString().split('T')[0]
      break
    case 'week':
      const weekStart = new Date(today.setDate(today.getDate() - today.getDay()))
      const weekEnd = new Date(today.setDate(today.getDate() - today.getDay() + 6))
      dateFrom.value = weekStart.toISOString().split('T')[0]
      dateTo.value = weekEnd.toISOString().split('T')[0]
      break
    case 'month':
      const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
      const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0)
      dateFrom.value = monthStart.toISOString().split('T')[0]
      dateTo.value = monthEnd.toISOString().split('T')[0]
      break
    case 'custom':
      if (!dateFrom.value || !dateTo.value) {
        const currentMonth = new Date()
        dateFrom.value = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1).toISOString().split('T')[0]
        dateTo.value = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0).toISOString().split('T')[0]
      }
      break
  }
  
  if (period !== 'custom') {
    fetchReportData()
  }
}

function getPeriodText() {
  if (!dateFrom.value || !dateTo.value) return 'Pilih periode'
  
  if (dateFrom.value === dateTo.value) {
    return formatDate(dateFrom.value)
  }
  
  return `${formatDate(dateFrom.value)} - ${formatDate(dateTo.value)}`
}

function resetFilters() {
  selectedPeriod.value = 'today'
  setQuickFilter('today')
}

function handleDateChange() {
  if (selectedPeriod.value === 'custom' && dateFrom.value && dateTo.value) {
    if (new Date(dateFrom.value) > new Date(dateTo.value)) {
      Swal.fire({
        title: 'Error',
        text: 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai',
        icon: 'error',
        confirmButtonText: 'OK'
      })
      return
    }
  }
}

function formatCurrency(amount) {
  return amount ? amount.toLocaleString('id-ID') : '0'
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function formatTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getPaymentMethodIcon(method) {
  const icons = {
    'tunai': '💵',
    'qris': '📱',
    'bayar-nanti': '⏰'
  }
  return icons[method] || '💳'
}

function formatPaymentMethod(method) {
  const methods = {
    'tunai': 'Tunai',
    'qris': 'QRIS',
    'bayar-nanti': 'Bayar Nanti'
  }
  return methods[method] || method
}

// ✅ FIXED: Using 'sukses' status like LaporanHarian.vue
function getStatusIcon(status) {
  const icons = {
    'sukses': '✅',
    'pending': '⏳',
    'cancelled': '❌'
  }
  return icons[status] || '📄'
}

function formatStatus(status) {
  const statuses = {
    'sukses': 'Lunas',
    'pending': 'Pending',
    'cancelled': 'Dibatalkan'
  }
  return statuses[status] || status
}

// ✅ FIXED: Using same validation as LaporanHarian.vue
function validateTransactionData(transaction) {
  return {
    id: transaction.id,
    kode_transaksi: transaction.kode_transaksi || 'N/A',
    tanggal_transaksi: transaction.tanggal_transaksi,
    customer_name: transaction.customer_name || 'Unknown',
    customer_phone: transaction.customer_phone || 'N/A',
    // ✅ FIXED: Use 'details' from TransaksiController
    items: transaction.details || transaction.items || [],
    metode_pembayaran: transaction.metode_pembayaran || 'tunai',
    total_amount: parseFloat(transaction.total_amount) || 0,
    status_transaksi: transaction.status_transaksi || 'pending',
    status_cucian: transaction.status_cucian || 'pending',
    jumlah_bayar: parseFloat(transaction.jumlah_bayar) || 0,
    kembalian: parseFloat(transaction.kembalian) || 0,
    catatan: transaction.catatan || '',
    created_by: transaction.created_by,
    // ✅ NEW: Add pickup service data
    subtotal_layanan: parseFloat(transaction.subtotal_layanan) || 0,
    biaya_pickup: parseFloat(transaction.biaya_pickup) || 0,
    has_pickup_service: Boolean(transaction.has_pickup_service),
    pickup_service_name: transaction.pickup_service_name || null
  }
}

// ✅ FIXED: Same calculation as LaporanHarian.vue with pickup support
function calculateReportSummary(transactionsList) {
  const summary = {
    totalOrder: 0,
    totalPemasukan: 0,
    orderPending: 0,
    orderPaid: 0,
    pemasukanTunai: 0,
    pemasukanQris: 0,
    pemasukanBayarNanti: 0,
    pemasukanPickup: 0,
    transaksiTunai: 0,
    transaksiQris: 0,
    transaksiBayarNanti: 0,
    transaksiPickup: 0
  }

  transactionsList.forEach(transaction => {
    const amount = parseFloat(transaction.total_amount) || 0
    const method = transaction.metode_pembayaran
    const status = transaction.status_transaksi
    const pickupCost = parseFloat(transaction.biaya_pickup) || 0

    summary.totalOrder++
    
    // ✅ FIXED: Use 'sukses' status
    if (status === 'sukses') {
      summary.totalPemasukan += amount
      summary.orderPaid++
    } else if (status === 'pending') {
      summary.orderPending++
    }

    // Count pickup transactions
    if (transaction.has_pickup_service) {
      summary.transaksiPickup++
      if (status === 'sukses') {
        summary.pemasukanPickup += pickupCost
      }
    }

    // Payment method breakdown
    if (method === 'tunai') {
      summary.transaksiTunai++
      if (status === 'sukses') {
        summary.pemasukanTunai += amount
      }
    } else if (method === 'qris') {
      summary.transaksiQris++
      if (status === 'sukses') {
        summary.pemasukanQris += amount
      }
    } else if (method === 'bayar-nanti') {
      summary.transaksiBayarNanti++
      if (status === 'sukses') {
        summary.pemasukanBayarNanti += amount
      }
    }
  })

  // Calculate additional metrics
  const daysDiff = Math.ceil((new Date(dateTo.value) - new Date(dateFrom.value)) / (1000 * 60 * 60 * 24)) + 1
  summary.avgDailyRevenue = daysDiff > 0 ? summary.totalPemasukan / daysDiff : 0

  reportData.value = summary
}

// ✅ FIXED: API calls using same approach as LaporanHarian.vue
async function fetchReportData() {
  if (!canFetchData.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Silakan pilih periode tanggal terlebih dahulu',
      icon: 'warning',
      confirmButtonText: 'OK'
    })
    return
  }
  
  isLoading.value = true
  
  try {
    console.log('🔍 Fetching admin report data...')
    
    // ✅ FIXED: Use /api/transaksi endpoint like LaporanHarian.vue
    const response = await axios.get('/api/transaksi', {
      params: {
        date_from: dateFrom.value,
        date_to: dateTo.value
      }
    })
    
    console.log('📡 API Response:', response.data)
    
    // Handle response structure like LaporanHarian.vue
    const rawTransactions = response.data.data || []
    const validatedTransactions = rawTransactions.map(transaction => 
      validateTransactionData(transaction)
    )
    
    transactions.value = validatedTransactions
    calculateReportSummary(validatedTransactions)
    currentPage.value = 1
    
    console.log('✅ Admin report data loaded:', reportData.value)
    console.log('✅ Transactions loaded:', transactions.value.length)
    
    if (validatedTransactions.length === 0) {
      Swal.fire({
        title: 'Info',
        text: 'Tidak ada transaksi dalam periode yang dipilih',
        icon: 'info',
        confirmButtonText: 'OK'
      })
    } else {
      Swal.fire({
        title: 'Berhasil!',
        text: `Data laporan berhasil dimuat (${transactions.value.length} transaksi)`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })
    }
    
  } catch (error) {
    console.error('❌ Error fetching admin report data:', error)
    handleFetchError(error)
  } finally {
    isLoading.value = false
  }
}

function handleFetchError(error) {
  transactions.value = []
  
  const errorStatus = error.response?.status
  const errorMessage = error.response?.data?.message || error.message
  
  console.error('❌ API Error Details:', {
    status: errorStatus,
    message: errorMessage,
    url: error.config?.url,
    method: error.config?.method
  })
  
  let userMessage = 'Gagal memuat data transaksi'
  
  if (errorStatus === 404) {
    userMessage = 'Endpoint API tidak ditemukan. Periksa routes /api/transaksi.'
  } else if (errorStatus === 401) {
    userMessage = 'Token authentication expired. Silakan login ulang.'
  } else if (errorStatus === 500) {
    userMessage = 'Server error. Periksa log server untuk detail.'
  }
  
  Swal.fire({
    title: 'Error Database',
    html: `
      <div style="text-align: left;">
        <p><strong>${userMessage}</strong></p>
        <p><strong>Status:</strong> ${errorStatus || 'Network Error'}</p>
        <p><strong>Detail:</strong> ${errorMessage}</p>
        <hr>
        <p><strong>Langkah perbaikan:</strong></p>
        <ul style="text-align: left; margin-left: 20px;">
          <li>Pastikan endpoint '/api/transaksi' tersedia</li>
          <li>Periksa database berisi data transaksi</li>
          <li>Cek koneksi database</li>
          <li>Verifikasi token authentication</li>
        </ul>
      </div>
    `,
    icon: 'error',
    confirmButtonText: 'Coba Lagi',
    showCancelButton: true,
    cancelButtonText: 'OK'
  }).then((result) => {
    if (result.isConfirmed) {
      fetchReportData()
    }
  })
}

// ✅ FIXED: Export functions using fallback approach
async function exportToPDF() {
  if (!hasData.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Tidak ada data untuk diekspor',
      icon: 'warning'
    })
    return
  }
  
  isExporting.value = true
  
  try {
    // Try the export endpoint first
    const response = await axios.get('/api/laporan/export/pdf', {
      params: {
        date_from: dateFrom.value,
        date_to: dateTo.value
      },
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-admin-${dateFrom.value}-${dateTo.value}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Laporan PDF berhasil didownload',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    })
    
  } catch (error) {
    console.error('Error exporting PDF:', error)
    
    // Fallback: Generate simple PDF with current data
    generateSimplePDFReport()
  } finally {
    isExporting.value = false
  }
}

async function exportToExcel() {
  if (!hasData.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Tidak ada data untuk diekspor',
      icon: 'warning'
    })
    return
  }
  
  isExporting.value = true
  
  try {
    // Try the export endpoint first
    const response = await axios.get('/api/laporan/export/excel', {
      params: {
        date_from: dateFrom.value,
        date_to: dateTo.value
      },
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-admin-${dateFrom.value}-${dateTo.value}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Laporan Excel berhasil didownload',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    })
    
  } catch (error) {
    console.error('Error exporting Excel:', error)
    
    // Fallback: Generate simple Excel/CSV with current data
    generateSimpleExcelReport()
  } finally {
    isExporting.value = false
  }
}

function generateSimplePDFReport() {
  const reportWindow = window.open('', '_blank')
  reportWindow.document.write(`
    <html>
      <head>
        <title>Laporan Admin ${getPeriodText()}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .header { text-align: center; margin-bottom: 30px; }
          .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
          .stat-card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
          .payment-breakdown { margin-bottom: 30px; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
          th { background-color: #f5f5f5; font-weight: bold; }
          .total-row { background-color: #f0f9ff; font-weight: bold; }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>📊 Laporan Admin Laundry</h1>
          <h2>Periode: ${getPeriodText()}</h2>
          <p>Generated: ${new Date().toLocaleString('id-ID')}</p>
        </div>
        
        <div class="stats">
          <div class="stat-card">
            <h3>Total Pendapatan</h3>
            <h2>Rp ${formatCurrency(reportData.value.totalPemasukan)}</h2>
          </div>
          <div class="stat-card">
            <h3>Total Order</h3>
            <h2>${reportData.value.totalOrder}</h2>
          </div>
          <div class="stat-card">
            <h3>Order Lunas</h3>
            <h2>${reportData.value.orderPaid}</h2>
          </div>
          <div class="stat-card">
            <h3>Tingkat Penyelesaian</h3>
            <h2>${orderCompletionRate.value}%</h2>
          </div>
        </div>
        
        <div class="payment-breakdown">
          <h3>Breakdown Pembayaran:</h3>
          <ul>
            <li>Tunai: Rp ${formatCurrency(reportData.value.pemasukanTunai)} (${reportData.value.transaksiTunai} transaksi)</li>
            <li>QRIS: Rp ${formatCurrency(reportData.value.pemasukanQris)} (${reportData.value.transaksiQris} transaksi)</li>
            <li>Bayar Nanti: Rp ${formatCurrency(reportData.value.pemasukanBayarNanti)} (${reportData.value.transaksiBayarNanti} transaksi)</li>
            <li>Pickup Service: Rp ${formatCurrency(reportData.value.pemasukanPickup)} (${reportData.value.transaksiPickup} transaksi)</li>
          </ul>
        </div>
        
        <table>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Tanggal</th>
              <th>Customer</th>
              <th>Metode</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${transactions.value.map(t => `
              <tr>
                <td>${t.kode_transaksi}</td>
                <td>${formatDate(t.tanggal_transaksi)}</td>
                <td>${t.customer_name}</td>
                <td>${formatPaymentMethod(t.metode_pembayaran)}</td>
                <td>Rp ${formatCurrency(t.total_amount)}</td>
                <td>${formatStatus(t.status_transaksi)}</td>
              </tr>
            `).join('')}
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4"><strong>TOTAL</strong></td>
              <td><strong>Rp ${formatCurrency(reportData.value.totalPemasukan)}</strong></td>
              <td><strong>${reportData.value.orderPaid} Lunas</strong></td>
            </tr>
          </tfoot>
        </table>
      </body>
    </html>
  `)
  reportWindow.document.close()
  reportWindow.print()
  
  Swal.fire({
    title: 'PDF Generated!',
    text: 'Laporan PDF berhasil dibuat (fallback mode)',
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
}

function generateSimpleExcelReport() {
  const csvContent = [
    ['Laporan Admin Laundry'],
    [`Periode: ${getPeriodText()}`],
    [`Generated: ${new Date().toLocaleString('id-ID')}`],
    [],
    ['RINGKASAN'],
    ['Total Pendapatan', `Rp ${formatCurrency(reportData.value.totalPemasukan)}`],
    ['Total Order', reportData.value.totalOrder],
    ['Order Lunas', reportData.value.orderPaid],
    ['Order Pending', reportData.value.orderPending],
    ['Tingkat Penyelesaian', `${orderCompletionRate.value}%`],
    [],
    ['BREAKDOWN PEMBAYARAN'],
    ['Tunai', `Rp ${formatCurrency(reportData.value.pemasukanTunai)}`, `${reportData.value.transaksiTunai} transaksi`],
    ['QRIS', `Rp ${formatCurrency(reportData.value.pemasukanQris)}`, `${reportData.value.transaksiQris} transaksi`],
    ['Bayar Nanti', `Rp ${formatCurrency(reportData.value.pemasukanBayarNanti)}`, `${reportData.value.transaksiBayarNanti} transaksi`],
    ['Pickup Service', `Rp ${formatCurrency(reportData.value.pemasukanPickup)}`, `${reportData.value.transaksiPickup} transaksi`],
    [],
    ['DETAIL TRANSAKSI'],
    ['Kode Transaksi', 'Tanggal', 'Customer', 'Telepon', 'Metode Bayar', 'Total', 'Status'],
    ...transactions.value.map(t => [
      t.kode_transaksi,
      formatDate(t.tanggal_transaksi),
      t.customer_name,
      t.customer_phone,
      formatPaymentMethod(t.metode_pembayaran),
      t.total_amount,
      formatStatus(t.status_transaksi)
    ])
  ]
  
  const csvString = csvContent.map(row => row.join(',')).join('\n')
  const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  
  if (link.download !== undefined) {
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', `laporan-admin-${dateFrom.value}-${dateTo.value}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
  
  Swal.fire({
    title: 'CSV Generated!',
    text: 'Laporan CSV berhasil dibuat (fallback mode)',
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })
}

async function exportToDashboard() {
  if (!hasData.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Tidak ada data untuk diekspor',
      icon: 'warning'
    })
    return
  }
  
  isExporting.value = true
  
  try {
    const dashboardData = {
      period: getPeriodText(),
      summary: {
        totalRevenue: reportData.value.totalPemasukan,
        totalOrders: reportData.value.totalOrder,
        completionRate: orderCompletionRate.value,
        avgDailyRevenue: reportData.value.avgDailyRevenue,
        uniqueCustomers: uniqueCustomers.value
      },
      paymentBreakdown: {
        tunai: reportData.value.pemasukanTunai,
        qris: reportData.value.pemasukanQris,
        bayarNanti: reportData.value.pemasukanBayarNanti,
        pickup: reportData.value.pemasukanPickup
      },
      orderBreakdown: {
        total: reportData.value.totalOrder,
        paid: reportData.value.orderPaid,
        pending: reportData.value.orderPending
      },
      popularServices: popularServices.value,
      generatedAt: new Date().toISOString()
    }
    
    const dataStr = JSON.stringify(dashboardData, null, 2)
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
    
    const link = document.createElement('a')
    link.setAttribute('href', dataUri)
    link.setAttribute('download', `dashboard-summary-${dateFrom.value}-${dateTo.value}.json`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Dashboard Summary berhasil didownload',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    })
    
  } catch (error) {
    console.error('Error exporting dashboard data:', error)
    Swal.fire({
      title: 'Error',
      text: 'Gagal mengexport dashboard summary',
      icon: 'error'
    })
  } finally {
    isExporting.value = false
  }
}

// Additional export functions
async function exportOrderStats() {
  try {
    const orderStatsData = {
      period: getPeriodText(),
      totalOrders: reportData.value.totalOrder,
      completedOrders: reportData.value.orderPaid,
      pendingOrders: reportData.value.orderPending,
      completionRate: orderCompletionRate.value,
      popularServices: popularServices.value,
      exportedAt: new Date().toISOString()
    }
    
    const dataStr = JSON.stringify(orderStatsData, null, 2)
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
    
    const link = document.createElement('a')
    link.setAttribute('href', dataUri)
    link.setAttribute('download', `order-stats-${dateFrom.value}-${dateTo.value}.json`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Statistik order berhasil diexport',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Error exporting order stats:', error)
    Swal.fire('Error', 'Gagal export statistik order', 'error')
  }
}

// Transaction actions
function viewTransactionDetail(transaction) {
  const itemsList = transaction.items.length > 0 
    ? transaction.items.map(item => `<li>${item.layanan_nama}</li>`).join('')
    : '<li><em>Tidak ada layanan laundry</em></li>'
    
  const pickupInfo = transaction.has_pickup_service
    ? `<p><strong>🚚 Pickup Service:</strong> ${transaction.pickup_service_name}</p>
       <p><strong>Biaya Pickup:</strong> Rp ${formatCurrency(transaction.biaya_pickup)}</p>`
    : '<p><strong>🚚 Pickup Service:</strong> Tidak ada</p>'

  Swal.fire({
    title: `Detail Transaksi ${transaction.kode_transaksi}`,
    html: `
      <div style="text-align: left;">
        <p><strong>👤 Customer:</strong> ${transaction.customer_name}</p>
        <p><strong>📞 Telepon:</strong> ${transaction.customer_phone}</p>
        <p><strong>📅 Tanggal:</strong> ${formatDate(transaction.tanggal_transaksi)} ${formatTime(transaction.tanggal_transaksi)}</p>
        <p><strong>💳 Metode Bayar:</strong> ${formatPaymentMethod(transaction.metode_pembayaran)}</p>
        <p><strong>💰 Total:</strong> Rp ${formatCurrency(transaction.total_amount)}</p>
        ${transaction.subtotal_layanan > 0 ? `<p><strong>🧺 Subtotal Layanan:</strong> Rp ${formatCurrency(transaction.subtotal_layanan)}</p>` : ''}
        ${pickupInfo}
        <p><strong>📊 Status:</strong> ${formatStatus(transaction.status_transaksi)}</p>
        <p><strong>👨‍💼 Staff:</strong> ${transaction.created_by || 'System'}</p>
        ${transaction.catatan ? `<p><strong>📝 Catatan:</strong> ${transaction.catatan}</p>` : ''}
        <hr>
        <p><strong>🧺 Layanan:</strong></p>
        <ul>${itemsList}</ul>
      </div>
    `,
    width: 600,
    confirmButtonText: 'Tutup'
  })
}

async function printTransaction(transaction) {
  try {
    // Try to use receipt endpoint if available
    const response = await axios.get(`/api/transaksi/${transaction.id}/receipt`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `struk-${transaction.kode_transaksi}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Struk berhasil didownload',
      icon: 'success',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Error printing transaction:', error)
    
    // Fallback: Generate simple receipt
    generateSimpleReceipt(transaction)
  }
}

function generateSimpleReceipt(transaction) {
  const itemsList = transaction.items.length > 0 
    ? transaction.items.map(item => `<p>- ${item.layanan_nama}</p>`).join('')
    : '<p>- <em>Tidak ada layanan laundry</em></p>'
    
  const pickupInfo = transaction.has_pickup_service
    ? `<p>- ${transaction.pickup_service_name}: Rp ${formatCurrency(transaction.biaya_pickup)}</p>`
    : ''

  const receiptWindow = window.open('', '_blank')
  receiptWindow.document.write(`
    <html>
      <head><title>Struk ${transaction.kode_transaksi}</title></head>
      <body style="font-family: monospace; padding: 20px; max-width: 300px;">
        <div style="text-align: center;">
          <h2>🧺 LAUNDRY RECEIPT</h2>
          <hr>
        </div>
        <p><strong>Kode:</strong> ${transaction.kode_transaksi}</p>
        <p><strong>Tanggal:</strong> ${formatDate(transaction.tanggal_transaksi)}</p>
        <p><strong>Customer:</strong> ${transaction.customer_name}</p>
        <p><strong>Phone:</strong> ${transaction.customer_phone}</p>
        <hr>
        <p><strong>🧺 Layanan Laundry:</strong></p>
        ${itemsList}
        ${pickupInfo ? `<hr><p><strong>🚚 Pickup Service:</strong></p>${pickupInfo}` : ''}
        <hr>
        ${transaction.subtotal_layanan > 0 ? `<p>Subtotal Layanan: Rp ${formatCurrency(transaction.subtotal_layanan)}</p>` : ''}
        ${transaction.biaya_pickup > 0 ? `<p>Biaya Pickup: Rp ${formatCurrency(transaction.biaya_pickup)}</p>` : ''}
        <p><strong>Total: Rp ${formatCurrency(transaction.total_amount)}</strong></p>
        <p>Metode: ${formatPaymentMethod(transaction.metode_pembayaran)}</p>
        <p>Status: ${formatStatus(transaction.status_transaksi)}</p>
        <hr>
        <div style="text-align: center;">
          <p>Terima kasih! 🙏</p>
        </div>
      </body>
    </html>
  `)
  receiptWindow.document.close()
  receiptWindow.print()
  
  Swal.fire({
    title: 'Receipt Generated!',
    text: 'Struk berhasil dibuat',
    icon: 'success',
    timer: 1500,
    showConfirmButton: false
  })
}

function refreshTransactions() {
  fetchReportData()
}

async function exportAllTransactions() {
  if (!hasData.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Tidak ada data untuk diekspor',
      icon: 'warning'
    })
    return
  }
  
  try {
    const exportData = {
      period: getPeriodText(),
      transactions: transactions.value,
      summary: {
        totalTransactions: reportData.value.totalOrder,
        totalRevenue: reportData.value.totalPemasukan,
        completedOrders: reportData.value.orderPaid,
        pendingOrders: reportData.value.orderPending,
        paymentBreakdown: {
          tunai: reportData.value.pemasukanTunai,
          qris: reportData.value.pemasukanQris,
          bayarNanti: reportData.value.pemasukanBayarNanti,
          pickup: reportData.value.pemasukanPickup
        }
      },
      popularServices: popularServices.value,
      exportedAt: new Date().toISOString()
    }
    
    const dataStr = JSON.stringify(exportData, null, 2)
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr)
    
    const link = document.createElement('a')
    link.setAttribute('href', dataUri)
    link.setAttribute('download', `all-transactions-${dateFrom.value}-${dateTo.value}.json`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    Swal.fire({
      title: 'Berhasil!',
      text: 'Data transaksi berhasil diexport',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Error exporting transactions:', error)
    Swal.fire('Error', 'Gagal export transaksi', 'error')
  }
}

// Lifecycle
onMounted(() => {
  console.log('✅ LaporanKaryawan (Admin) component mounted')
  
  // Set default to today
  setQuickFilter('today')
  
  console.log('✅ Admin report ready with working data flow')
})

// Watch for period changes
watch(selectedPeriod, (newPeriod) => {
  if (newPeriod !== 'custom') {
    setQuickFilter(newPeriod)
  }
})
</script>

<style scoped>
@import '../../assets/css/LaporanKaryawan.css';
</style>