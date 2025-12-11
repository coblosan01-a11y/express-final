<template>
  <div class="page-container">
    <!-- NavSidebar Component -->
    <NavSidebar @logout="handleLogout" @toggle-sidebar="handleSidebarToggle" />
    
    <!-- Main Content -->
    <div class="main-content" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">

      <!-- Enhanced Filter Section -->
      <div class="filter-section">
        <div class="filter-card">
          <div class="filter-header">
            <h3>Filter Periode Laporan</h3>
            <p>Pilih periode untuk melihat laporan transaksi</p>
          </div>
          
          <!-- Quick Filter Buttons -->
          <div class="quick-filters">
            <h4> <img src="../assets/img/Calendar.png" alt="Logo" class="calendar-logo"/> Filter Cepat</h4>
            <div class="quick-filter-grid">
              <button 
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'today' }"
                @click="setQuickFilter('today')"
              >
                <div class="filter-icon"><img src="../assets/img/Calendar.png" alt="logo" class="calender-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Hari Ini</span>
                  <span class="filter-desc">{{ formatDate(getTodayDate()) }}</span>
                </div>
              </button>
              
              <button 
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'week' }"
                @click="setQuickFilter('week')"
              >
                <div class="filter-icon"><img src="../assets/img/Calendar.png" alt="logo" class="calender-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Minggu Ini</span>
                  <span class="filter-desc">{{ getWeekRange() }}</span>
                </div>
              </button>
              
              <button 
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'month' }"
                @click="setQuickFilter('month')"
              >
                <div class="filter-icon"><img src="../assets/img/Calendar.png" alt="logo" class="calender-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Bulan Ini</span>
                  <span class="filter-desc">{{ getMonthName() }} {{ currentYear }}</span>
                </div>
              </button>
              
              <button 
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'year' }"
                @click="setQuickFilter('year')"
              >
                <div class="filter-icon"><img src="../assets/img/Calendar.png" alt="logo" class="calender-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Tahun Ini</span>
                  <span class="filter-desc">Januari - Desember {{ currentYear }}</span>
                </div>
              </button>
              
              <button 
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'custom' }"
                @click="setQuickFilter('custom')"
              >
                <div class="filter-icon"><img src="../assets/img/Setting.png" alt="logo" class="setting-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Custom</span>
                  <span class="filter-desc">Pilih tanggal sendiri</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Custom Date Range (shown when custom is selected) -->
          <div v-if="selectedQuickFilter === 'custom'" class="custom-date-section">
            <h4>📋 Pilih Rentang Tanggal</h4>
            <div class="date-inputs">
              <div class="date-group">
                <label>Dari Tanggal</label>
                <input 
                  type="date" 
                  v-model="dateFrom"
                  class="date-input"
                  @change="handleCustomDateChange"
                />
                <small class="date-hint">Tanggal mulai periode</small>
              </div>
              <div class="date-group">
                <label>Sampai Tanggal</label>
                <input 
                  type="date" 
                  v-model="dateTo"
                  class="date-input"
                  @change="handleCustomDateChange"
                />
                <small class="date-hint">Tanggal akhir periode</small>
              </div>
            </div>
          </div>

          <!-- Selected Period Display -->
          <div class="selected-period">
            <div class="period-info">
              <span class="period-label">Periode Terpilih:</span>
              <span class="period-value">
                {{ formatSelectedPeriod() }}
              </span>
            </div>
            <div class="period-stats" v-if="hasData">
              <span class="stats-item">📊 {{ transactions.length }} transaksi</span>
              <span class="stats-item">💰 Rp {{ formatCurrency(reportData.totalPemasukan) }}</span>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="filter-actions">
            <button 
              class="filter-btn primary"
              @click="fetchReportData"
              :disabled="!dateFrom || !dateTo || isLoading"
            >
              <span v-if="isLoading" class="btn-spinner"></span>
              <span v-else></span>
              {{ isLoading ? 'Memuat...' : 'Tampilkan Laporan' }}
            </button>
            <button 
              class="filter-btn secondary"
              @click="resetDateFilter"
              :disabled="isLoading"
            >
              Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue"><img src="../assets/img/KarungDollar.png" alt="logo" class="karung-dollar-logo"></div>
          <div class="stat-content">
            <div class="stat-value">{{ reportData.totalOrder }}</div>
            <div class="stat-label">Total Order</div>
            <div class="stat-period">Order dalam periode</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green"><img src="../assets/img/Checklist.png" alt="logo" class="checklist-logo"></div>
          <div class="stat-content">
            <div class="stat-value">Rp {{ formatCurrency(reportData.totalPemasukan) }}</div>
            <div class="stat-label">Total Pemasukan</div>
            <div class="stat-period">Hanya yang sudah lunas</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orange"><img src="../assets/img/Hourglass.png" alt="logo" class="hourglass-logo"></div>
          <div class="stat-content">
            <div class="stat-value">{{ reportData.orderPending }}</div>
            <div class="stat-label">Order Pending</div>
            <div class="stat-period">Belum dibayar</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon success"><img src="../assets/img/Verified.png" alt="logo" class="hourglass-logo"></div>
          <div class="stat-content">
            <div class="stat-value">{{ reportData.orderPaid }}</div>
            <div class="stat-label">Order Lunas</div>
            <div class="stat-period">Sudah dibayar</div>
          </div>
        </div>
      </div>

      <!-- ✅ FIXED: Enhanced Payment Methods Summary with Correct Logic -->
      <div class="payment-summary-grid">
        <div class="payment-card tunai">
          <div class="payment-header">
            <h3><img src="../assets/img/Dollar.png" alt="logo" class="dollar-logo"> Pemasukan Tunai</h3>
            <p>Pembayaran langsung tunai</p>
          </div>
          <div class="payment-amount">
            <span class="amount">Rp {{ formatCurrency(reportData.pemasukanTunai) }}</span>
            <span class="transactions">{{ reportData.transaksiTunai }} order tunai</span>
          </div>
        </div>

        <div class="payment-card qris">
          <div class="payment-header">
            <h3><img src="../assets/img/Qris.png" alt="logo" class="qris-logo"> Pemasukan QRIS</h3>
            <p>Pembayaran langsung QRIS</p>
          </div>
          <div class="payment-amount">
            <span class="amount">Rp {{ formatCurrency(reportData.pemasukanQris) }}</span>
            <span class="transactions">{{ reportData.transaksiQris }} order QRIS</span>
          </div>
        </div>



        <div class="payment-card pickup">
          <div class="payment-header">
            <h3><img src="../assets/img/Delivery.png" alt="logo" class="qris-logo"> Revenue Pickup</h3>
            <p>Pendapatan layanan pickup/delivery</p>
          </div>
          <div class="payment-amount">
            <span class="amount">Rp {{ formatCurrency(reportData.pemasukanPickup) }}</span>
            <span class="transactions">{{ reportData.transaksiPickup }} transaksi pickup</span>
          </div>
        </div>
      </div>

      <!-- Export Section -->
      <div class="export-section">
        <div class="export-card">
          <h3>📥 Export Laporan</h3>
          <p>Download laporan dalam format PDF atau Excel</p>
          
          <div class="export-buttons">
            <button 
              class="export-btn pdf"
              @click="exportToPDF"
              :disabled="isExporting || !hasData"
            >
              <span class="btn-icon"><img src="../assets/img/folder.png" alt="logo" class="folder-logo"></span>
              <span>Export PDF</span>
            </button>
            
            <button 
              class="export-btn excel"
              @click="exportToExcel"
              :disabled="isExporting || !hasData"
            >
              <span class="btn-icon"><img src="../assets/img/folder.png" alt="logo" class="folder-logo"></span>
              <span>Export Excel</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Transaction Details -->
      <div class="details-section">
        <div class="details-card">
          <div class="details-header">
            <h3>📋 Detail Transaksi</h3>
            <p v-if="dateFrom && dateTo">
              Daftar semua transaksi dalam periode {{ formatDate(dateFrom) }} - {{ formatDate(dateTo) }}
            </p>
            <p v-else>
              Pilih rentang tanggal untuk melihat detail transaksi
            </p>
          </div>
          
          <div class="details-content">
            <div v-if="isLoading" class="loading-state">
              <div class="spinner"></div>
              <span>Memuat data transaksi...</span>
            </div>
            
            <div v-else-if="!dateFrom || !dateTo" class="empty-state">
              <span class="empty-icon">📅</span>
              <h4>Pilih Rentang Tanggal</h4>
              <p>Silakan pilih tanggal mulai dan tanggal selesai untuk melihat laporan</p>
            </div>
            
            <div v-else-if="transactions.length === 0" class="empty-state">
              <span class="empty-icon">📭</span>
              <h4>Tidak ada transaksi dalam periode yang dipilih</h4>
              <p>Silakan pilih rentang tanggal yang berbeda</p>
            </div>
            
            <div v-else class="transactions-list">
              <div class="transactions-header">
                <div class="header-item">Kode</div>
                <div class="header-item">Tanggal</div>
                <div class="header-item">Customer</div>
                <div class="header-item">Layanan</div>
                <div class="header-item">Pickup</div>
                <div class="header-item">Metode Bayar</div>
                <div class="header-item">Total</div>
                <div class="header-item">Status</div>
                <div class="header-item">Aksi</div>
              </div>
              
              <div 
                v-for="transaction in paginatedTransactions" 
                :key="transaction.id"
                class="transaction-row"
              >
                <div class="transaction-cell">
                  {{ transaction.kode_transaksi }}
                </div>
                <div class="transaction-cell">
                  {{ formatDateTime(transaction.tanggal_transaksi) }}
                </div>
                <div class="transaction-cell">
                  <div class="customer-info">
                    <div class="customer-name">{{ transaction.customer_name }}</div>
                    <div class="customer-phone">{{ transaction.customer_phone }}</div>
                  </div>
                </div>
                <div class="transaction-cell">
                  <div class="services-list">
                    <span 
                      v-for="(item, index) in transaction.items" 
                      :key="index"
                      class="service-tag"
                    >
                      {{ item.layanan_nama }}
                    </span>
                    <span v-if="transaction.items.length === 0" class="service-tag empty">
                      Tidak ada layanan
                    </span>
                  </div>
                </div>
                <div class="transaction-cell">
                  <div class="pickup-info">
                    <span v-if="transaction.has_pickup_service" class="pickup-badge active">
                      🚚 {{ transaction.pickup_service_name || 'Pickup' }}
                    </span>
                    <span v-else class="pickup-badge inactive">
                      ➖ Tidak ada
                    </span>
                  </div>
                </div>
                <div class="transaction-cell">
                  <!-- ✅ FIXED: Show payment method with original tracking -->
                  <span class="payment-method" :class="getPaymentMethodClass(transaction)">
                    {{ getPaymentMethodIcon(transaction.metode_pembayaran) }} 
                    {{ getPaymentMethodDetail(transaction) }}
                  </span>
                </div>
                <div class="transaction-cell">
                  <div class="amount-breakdown">
                    <span class="amount total">Rp {{ formatCurrency(transaction.total_amount) }}</span>
                    <div class="amount-details">
                      <small v-if="transaction.subtotal_layanan > 0">
                        Layanan: Rp {{ formatCurrency(transaction.subtotal_layanan) }}
                      </small>
                      <small v-if="transaction.biaya_pickup > 0">
                        Pickup: Rp {{ formatCurrency(transaction.biaya_pickup) }}
                      </small>
                    </div>
                  </div>
                </div>
                <div class="transaction-cell">
                  <span class="status" :class="transaction.status_transaksi">
                    {{ getStatusIcon(transaction.status_transaksi) }} {{ formatStatus(transaction.status_transaksi) }}
                  </span>
                </div>
                <div class="transaction-cell">
                  <button 
                    class="detail-btn"
                    @click="showTransactionDetail(transaction)"
                    title="Lihat Detail"
                  >
                    👁️ Detail
                  </button>
                </div>
              </div>
              
              <!-- Pagination -->
              <div v-if="totalPages > 1" class="pagination">
                <button 
                  class="page-btn"
                  @click="currentPage--"
                  :disabled="currentPage <= 1"
                >
                  ⬅️ Prev
                </button>
                
                <span class="page-info">
                  Halaman {{ currentPage }} dari {{ totalPages }}
                </span>
                
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
      </div>
    </div>

    <!-- ✅ ENHANCED: Transaction Detail Modal with Payment Tracking -->
    <div v-if="showDetailModal" class="modal-overlay" @click="closeDetailModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>📋 Detail Transaksi</h3>
          <button class="close-btn" @click="closeDetailModal">✕</button>
        </div>
        
        <div class="modal-body" v-if="selectedTransaction">
          <!-- Transaction Basic Info -->
          <div class="detail-section">
            <h4>🆔 Informasi Transaksi</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Kode Transaksi:</span>
                <span class="value">{{ selectedTransaction.kode_transaksi }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Tanggal Transaksi:</span>
                <span class="value">{{ formatDateTime(selectedTransaction.tanggal_transaksi) }}</span>
              </div>
              <div class="detail-item" v-if="selectedTransaction.tanggal_pelunasan">
                <span class="label">Tanggal Pelunasan:</span>
                <span class="value">{{ formatDateTime(selectedTransaction.tanggal_pelunasan) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Status Transaksi:</span>
                <span class="value">
                  <span class="status" :class="selectedTransaction.status_transaksi">
                    {{ getStatusIcon(selectedTransaction.status_transaksi) }} {{ formatStatus(selectedTransaction.status_transaksi) }}
                  </span>
                </span>
              </div>
              <div class="detail-item">
                <span class="label">Status Cucian:</span>
                <span class="value">
                  <span class="status" :class="selectedTransaction.status_cucian">
                    {{ getStatusIcon(selectedTransaction.status_cucian) }} {{ formatStatus(selectedTransaction.status_cucian) }}
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="detail-section">
            <h4>👤 Informasi Customer</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Nama:</span>
                <span class="value">{{ selectedTransaction.customer_name }}</span>
              </div>
              <div class="detail-item">
                <span class="label">No. Telepon:</span>
                <span class="value">{{ selectedTransaction.customer_phone }}</span>
              </div>
            </div>
          </div>

          <!-- Services -->
          <div class="detail-section">
            <h4>🧺 Layanan</h4>
            <div v-if="selectedTransaction.items && selectedTransaction.items.length > 0" class="services-detail">
              <div v-for="(item, index) in selectedTransaction.items" :key="index" class="service-item">
                <div class="service-info">
                  <span class="service-name">{{ item.layanan_nama }}</span>
                  <span class="service-qty">{{ item.kuantitas }}</span>
                  <span class="service-price">Rp {{ formatCurrency(item.harga_satuan) }}</span>
                  <span class="service-total">Total: Rp {{ formatCurrency(item.subtotal) }}</span>
                </div>
              </div>
              <div class="subtotal-layanan">
                <strong>Subtotal Layanan: Rp {{ formatCurrency(selectedTransaction.subtotal_layanan) }}</strong>
              </div>
            </div>
            <div v-else class="no-services">
              <span class="empty-text">Tidak ada layanan</span>
            </div>
          </div>

          <!-- Pickup Service -->
          <div v-if="selectedTransaction.has_pickup_service" class="detail-section">
            <h4>🚚 Layanan Pickup</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Nama Layanan:</span>
                <span class="value">{{ selectedTransaction.pickup_service_name || 'Pickup Service' }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Biaya Pickup:</span>
                <span class="value">Rp {{ formatCurrency(selectedTransaction.biaya_pickup) }}</span>
              </div>
            </div>
          </div>

          <!-- ✅ ENHANCED: Payment Info with Tracking -->
          <div class="detail-section">
            <h4>💳 Informasi Pembayaran</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Metode Pembayaran:</span>
                <span class="value">
                  <span class="payment-method" :class="selectedTransaction.metode_pembayaran">
                    {{ getPaymentMethodIcon(selectedTransaction.metode_pembayaran) }} 
                    {{ getPaymentMethodDetail(selectedTransaction) }}
                  </span>
                </span>
              </div>
              <div class="detail-item" v-if="selectedTransaction.metode_pembayaran_original && selectedTransaction.metode_pembayaran_original !== selectedTransaction.metode_pembayaran">
                <span class="label">Metode Original:</span>
                <span class="value">{{ formatPaymentMethod(selectedTransaction.metode_pembayaran_original) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Total Amount:</span>
                <span class="value total-amount">Rp {{ formatCurrency(selectedTransaction.total_amount) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Jumlah Bayar:</span>
                <span class="value">Rp {{ formatCurrency(selectedTransaction.jumlah_bayar) }}</span>
              </div>
              <div class="detail-item" v-if="selectedTransaction.kembalian > 0">
                <span class="label">Kembalian:</span>
                <span class="value">Rp {{ formatCurrency(selectedTransaction.kembalian) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Notes -->
          <div v-if="selectedTransaction.catatan_pembayaran" class="detail-section">
            <h4>💬 Catatan Pembayaran</h4>
            <div class="notes-content">
              {{ selectedTransaction.catatan_pembayaran }}
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedTransaction.catatan" class="detail-section">
            <h4>📝 Catatan</h4>
            <div class="notes-content">
              {{ selectedTransaction.catatan }}
            </div>
          </div>

          <!-- Created By -->
          <div class="detail-section">
            <h4>👨‍💼 Informasi Operator</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Dibuat oleh:</span>
                <span class="value">{{ selectedTransaction.created_by || 'N/A' }}</span>
              </div>
              <div class="detail-item" v-if="selectedTransaction.lunas_by">
                <span class="label">Dilunasi oleh:</span>
                <span class="value">{{ selectedTransaction.lunas_by }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn secondary" @click="closeDetailModal">
            Tutup
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
import NavSidebar from '../components/navigation/NavSidebar.vue'

const router = useRouter()

// Current year for auto-filling
const currentYear = new Date().getFullYear()

// Sidebar state
const isSidebarCollapsed = ref(false)

// Modal state
const showDetailModal = ref(false)
const selectedTransaction = ref(null)

// Reactive data
const dateFrom = ref('')
const dateTo = ref('')
const isLoading = ref(false)
const isExporting = ref(false)
const currentPage = ref(1)
const itemsPerPage = 10

// Quick filter state
const selectedQuickFilter = ref('today')

// ✅ FIXED: Enhanced report data dengan tracking yang benar
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
  transaksiBayarNantiLunas: 0,      // ✅ NEW: Bayar nanti yang sudah lunas
  transaksiBayarNantiBelum: 0,      // ✅ NEW: Bayar nanti yang masih pending
  transaksiPickup: 0
})

const transactions = ref([])

// Computed
const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return transactions.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(transactions.value.length / itemsPerPage)
})

const hasData = computed(() => {
  return transactions.value.length > 0
})

// Sidebar toggle handler
function handleSidebarToggle(collapsed) {
  isSidebarCollapsed.value = collapsed
}

// Quick filter functions
function getTodayDate() {
  return new Date().toISOString().split('T')[0]
}

function getWeekRange() {
  const today = new Date()
  const firstDay = new Date(today.setDate(today.getDate() - today.getDay() + 1)) // Monday
  const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 7)) // Sunday
  
  return `${firstDay.getDate()}/${firstDay.getMonth() + 1} - ${lastDay.getDate()}/${lastDay.getMonth() + 1}`
}

function getMonthName() {
  const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ]
  return months[new Date().getMonth()]
}

function setQuickFilter(filterType) {
  selectedQuickFilter.value = filterType
  
  const today = new Date()
  
  switch (filterType) {
    case 'today':
      dateFrom.value = getTodayDate()
      dateTo.value = getTodayDate()
      break
    
    case 'week':
      const firstDay = new Date(today)
      firstDay.setDate(today.getDate() - today.getDay() + 1) // Monday
      const lastDay = new Date(today)
      lastDay.setDate(today.getDate() - today.getDay() + 7) // Sunday
      
      dateFrom.value = firstDay.toISOString().split('T')[0]
      dateTo.value = lastDay.toISOString().split('T')[0]
      break
    
    case 'month':
      const firstDayMonth = new Date(today.getFullYear(), today.getMonth(), 1)
      const lastDayMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0)
      
      dateFrom.value = firstDayMonth.toISOString().split('T')[0]
      dateTo.value = lastDayMonth.toISOString().split('T')[0]
      break
    
    case 'year':
      dateFrom.value = `${currentYear}-01-01`
      dateTo.value = `${currentYear}-12-31`
      break
    
    case 'custom':
      // Don't change dates, let user pick
      break
  }
  
  // Auto fetch data for preset filters
  if (filterType !== 'custom') {
    fetchReportData()
  }
}

function formatSelectedPeriod() {
  if (!dateFrom.value || !dateTo.value) {
    return 'Belum dipilih'
  }
  
  const from = formatDate(dateFrom.value)
  const to = formatDate(dateTo.value)
  
  if (dateFrom.value === dateTo.value) {
    return from
  }
  
  return `${from} - ${to}`
}

function handleCustomDateChange() {
  // When custom dates change, switch to custom mode
  selectedQuickFilter.value = 'custom'
}

// Transaction detail modal functions
function showTransactionDetail(transaction) {
  selectedTransaction.value = transaction
  showDetailModal.value = true
  document.body.style.overflow = 'hidden' // Prevent background scroll
}

function closeDetailModal() {
  showDetailModal.value = false
  selectedTransaction.value = null
  document.body.style.overflow = 'auto' // Restore scroll
}

function resetDateFilter() {
  selectedQuickFilter.value = 'today'
  setQuickFilter('today')
  
  // Reset other data
  transactions.value = []
  resetReportData()
  currentPage.value = 1
}

function resetReportData() {
  reportData.value = {
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
    transaksiBayarNantiLunas: 0,
    transaksiBayarNantiBelum: 0,
    transaksiPickup: 0
  }
}

function validateDateRange() {
  if (!dateFrom.value || !dateTo.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Silakan pilih tanggal mulai dan tanggal selesai',
      icon: 'warning',
      confirmButtonText: 'OK'
    })
    return false
  }
  
  if (new Date(dateFrom.value) > new Date(dateTo.value)) {
    Swal.fire({
      title: 'Error',
      text: 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai',
      icon: 'error',
      confirmButtonText: 'OK'
    })
    return false
  }
  
  return true
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

function formatDateTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
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

// ✅ FIXED: Status functions with correct 'sukses' mapping
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

// ✅ NEW: Enhanced payment method display dengan tracking
function getPaymentMethodDetail(transaction) {
  const original = transaction.metode_pembayaran_original
  const current = transaction.metode_pembayaran
  const status = transaction.status_transaksi
  
  // Jika bayar nanti yang sudah dilunasi dengan metode lain
  if (original === 'bayar-nanti' && current !== 'bayar-nanti' && status === 'sukses') {
    return `Bayar Nanti → ${formatPaymentMethod(current)}`
  }
  
  return formatPaymentMethod(current)
}

// ✅ NEW: Payment method class untuk styling
function getPaymentMethodClass(transaction) {
  const original = transaction.metode_pembayaran_original
  const current = transaction.metode_pembayaran
  
  if (original === 'bayar-nanti' && current !== 'bayar-nanti') {
    return 'payment-converted'
  }
  
  return current
}

// ✅ FIXED: Enhanced transaction data validation dengan tracking fields
function validateTransactionData(transaction) {
  return {
    id: transaction.id,
    kode_transaksi: transaction.kode_transaksi || 'N/A',
    tanggal_transaksi: transaction.tanggal_transaksi,
    tanggal_pelunasan: transaction.tanggal_pelunasan || null,
    customer_name: transaction.customer_name || 'Unknown',
    customer_phone: transaction.customer_phone || 'N/A',
    items: transaction.details || transaction.items || [],
    metode_pembayaran: transaction.metode_pembayaran || 'tunai',
    metode_pembayaran_original: transaction.metode_pembayaran_original || transaction.metode_pembayaran,
    total_amount: parseFloat(transaction.total_amount) || 0,
    status_transaksi: transaction.status_transaksi || 'pending',
    status_cucian: transaction.status_cucian || 'pending',
    jumlah_bayar: parseFloat(transaction.jumlah_bayar) || 0,
    kembalian: parseFloat(transaction.kembalian) || 0,
    catatan: transaction.catatan || '',
    catatan_pembayaran: transaction.catatan_pembayaran || '',
    created_by: transaction.created_by,
    lunas_by: transaction.lunas_by || null,
    
    // Pickup service data
    subtotal_layanan: parseFloat(transaction.subtotal_layanan) || 0,
    biaya_pickup: parseFloat(transaction.biaya_pickup) || 0,
    has_pickup_service: Boolean(transaction.has_pickup_service),
    pickup_service_name: transaction.pickup_service_name || null
  }
}

// ✅ FIXED: Completely corrected report calculation logic
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
    transaksiBayarNantiLunas: 0,
    transaksiBayarNantiBelum: 0,
    transaksiPickup: 0
  }

  transactionsList.forEach(transaction => {
    const amount = parseFloat(transaction.total_amount) || 0
    const originalMethod = transaction.metode_pembayaran_original || transaction.metode_pembayaran
    const currentMethod = transaction.metode_pembayaran
    const status = transaction.status_transaksi
    const pickupCost = parseFloat(transaction.biaya_pickup) || 0

    // Count total orders
    summary.totalOrder++
    
    // ✅ FIXED: Hitung pemasukan HANYA dari transaksi yang sudah lunas
    if (status === 'sukses') {
      summary.totalPemasukan += amount
      summary.orderPaid++
      
      // ✅ FIXED: Pickup revenue hanya dari transaksi lunas
      if (transaction.has_pickup_service && pickupCost > 0) {
        summary.pemasukanPickup += pickupCost
      }
      
      // ✅ FIXED: Revenue breakdown berdasarkan metode ORIGINAL
      if (originalMethod === 'tunai') {
        summary.pemasukanTunai += amount
      } else if (originalMethod === 'qris') {
        summary.pemasukanQris += amount
      } else if (originalMethod === 'bayar-nanti') {
        // ✅ KEY FIX: Bayar nanti yang sudah lunas masuk ke kategori bayar nanti
        summary.pemasukanBayarNanti += amount
      }
    } else if (status === 'pending') {
      summary.orderPending++
    }

    // ✅ Count pickup transactions (regardless of payment status)
    if (transaction.has_pickup_service) {
      summary.transaksiPickup++
    }

    // ✅ FIXED: Transaction count berdasarkan metode ORIGINAL
    if (originalMethod === 'tunai') {
      summary.transaksiTunai++
    } else if (originalMethod === 'qris') {
      summary.transaksiQris++
    } else if (originalMethod === 'bayar-nanti') {
      summary.transaksiBayarNanti++
      
      // ✅ NEW: Track bayar nanti yang sudah lunas vs belum
      if (status === 'sukses') {
        summary.transaksiBayarNantiLunas++
      } else {
        summary.transaksiBayarNantiBelum++
      }
    }
  })

  reportData.value = summary
  
  console.log('📊 FIXED Report Summary:', summary)
  console.log('💰 Total Pemasukan (hanya yang lunas):', summary.totalPemasukan)
  console.log('⏰ Bayar Nanti - Total Order:', summary.transaksiBayarNanti)
  console.log('⏰ Bayar Nanti - Sudah Lunas:', summary.transaksiBayarNantiLunas)
  console.log('⏰ Bayar Nanti - Masih Pending:', summary.transaksiBayarNantiBelum)
  console.log('⏰ Bayar Nanti - Revenue (dari yang lunas):', summary.pemasukanBayarNanti)
}

// ✅ ENHANCED: API calls with better error handling
async function fetchReportData() {
  if (!validateDateRange()) return
  
  isLoading.value = true
  
  try {
    console.log('🔍 Fetching report data for:', dateFrom.value, 'to', dateTo.value)
    
    const response = await axios.get('/api/transaksi', {
      params: {
        date_from: dateFrom.value,
        date_to: dateTo.value
      }
    })
    
    console.log('📡 API Response:', response.data)
    
    const rawTransactions = response.data.data || []
    console.log('📊 Raw transactions count:', rawTransactions.length)
    
    if (rawTransactions.length > 0) {
      console.log('📋 Sample transaction:', rawTransactions[0])
    }
    
    const validatedTransactions = rawTransactions.map(transaction => 
      validateTransactionData(transaction)
    )
    
    transactions.value = validatedTransactions
    calculateReportSummary(validatedTransactions)
    currentPage.value = 1
    
    console.log('✅ Report data loaded:', reportData.value)
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
        text: `Berhasil memuat ${validatedTransactions.length} transaksi`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })
    }
    
  } catch (error) {
    console.error('❌ Error fetching transaction data:', error)
    
    transactions.value = []
    resetReportData()
    
    // Enhanced error handling
    let errorMessage = 'Gagal memuat data transaksi'
    let errorDetails = ''
    
    if (error.response) {
      if (error.response.status === 404) {
        errorMessage = 'Endpoint API tidak ditemukan'
        errorDetails = 'Pastikan /api/transaksi tersedia di server'
      } else if (error.response.status === 401) {
        errorMessage = 'Sesi login telah berakhir'
        errorDetails = 'Silakan login kembali'
        router.push('/login')
        return
      } else if (error.response.status === 500) {
        errorMessage = 'Server error'
        errorDetails = 'Periksa koneksi database dan log server'
      } else {
        errorMessage = `HTTP ${error.response.status}: ${error.response.statusText}`
        errorDetails = error.response.data?.message || 'Unknown error'
      }
    } else if (error.request) {
      errorMessage = 'Tidak dapat terhubung ke server'
      errorDetails = 'Periksa koneksi internet dan URL API'
    } else {
      errorMessage = 'Error tidak diketahui'
      errorDetails = error.message
    }
    
    Swal.fire({
      title: 'Error Database',
      html: `
        <div style="text-align: left;">
          <p><strong>${errorMessage}</strong></p>
          <p style="color: #666; font-size: 14px;">${errorDetails}</p>
          <br>
          <p><strong>Solusi yang bisa dicoba:</strong></p>
          <ul style="text-align: left; margin-left: 20px; font-size: 14px;">
            <li>Periksa koneksi internet</li>
            <li>Pastikan server backend berjalan</li>
            <li>Cek apakah token authentication masih valid</li>
            <li>Periksa log error di server</li>
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
  } finally {
    isLoading.value = false
  }
}

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
    link.setAttribute('download', `laporan-harian-${dateFrom.value}-${dateTo.value}.pdf`)
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
    Swal.fire({
      title: 'Error',
      text: 'Gagal mengexport laporan PDF',
      icon: 'error'
    })
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
    link.setAttribute('download', `laporan-harian-${dateFrom.value}-${dateTo.value}.xlsx`)
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
    Swal.fire({
      title: 'Error',
      text: 'Gagal mengexport laporan Excel',
      icon: 'error'
    })
  } finally {
    isExporting.value = false
  }
}

function handleLogout() {
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
      localStorage.removeItem('token')
      sessionStorage.removeItem('user')
      router.push('/login')
      Swal.fire('Berhasil', 'Anda telah logout', 'success')
    }
  })
}

// Lifecycle
onMounted(() => {
  console.log('✅ FIXED LaporanHarian component mounted')
  console.log('🎯 Key Features:')
  console.log('  - Pemasukan HANYA dari transaksi yang status = "sukses"')
  console.log('  - Bayar nanti revenue = order bayar nanti yang sudah dilunasi')
  console.log('  - Tracking metode pembayaran original vs current')
  console.log('  - Enhanced payment method display')
  console.log('  - Proper pickup revenue calculation')
  console.log('Current year:', currentYear)
  
  // Set default to today
  setQuickFilter('today')
  
  console.log('Default filter set to today')
})
</script>

<style scoped>
@import '../assets/css/LaporanHarian.css';

/* ✅ ENHANCED: CSS untuk payment method tracking */
.payment-method.payment-converted {
  background: linear-gradient(45deg, #fbbf24, #10b981);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 600;
}

.payment-card .payment-note {
  display: block;
  font-size: 11px;
  color: #6b7280;
  margin-top: 4px;
  font-style: italic;
}

/* Enhanced status styling */
.status.sukses {
  color: #059669;
  font-weight: 600;
}

.status.pending {
  color: #d97706;
  font-weight: 600;
}

/* Enhanced detail modal styles */
.detail-section {
  margin-bottom: 24px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.detail-section h4 {
  margin: 0 0 12px 0;
  color: #1f2937;
  font-size: 16px;
  font-weight: 600;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item .label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.detail-item .value {
  font-size: 14px;
  color: #1f2937;
  font-weight: 600;
}

.notes-content {
  background: white;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  font-size: 14px;
  line-height: 1.5;
}

/* Responsive enhancements */
@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .payment-summary-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .quick-filter-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}

/* Animation for loading states */
.btn-spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
</style>