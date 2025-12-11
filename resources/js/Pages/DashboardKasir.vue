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
            <h3>Dashboard Kasir</h3>
            <p>Kelola pesanan yang sudah dibayar dan tracking status cucian</p>
          </div>
         
          <!-- Quick Filter Buttons -->
          <div class="quick-filters">
            <h4>Filter Cepat Order</h4>
            <div class="quick-filter-grid">
              <button
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'today' }"
                @click="setQuickFilter('today')"
              >
                <div class="filter-icon"><img src="../assets/img/Detail.png" alt="logo" class="detail-logo"></div>
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
                <div class="filter-icon"><img src="../assets/img/Detail.png" alt="logo" class="detail-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Minggu Ini</span>
                  <span class="filter-desc">{{ getWeekRange() }}</span>
                </div>
              </button>
             
              <button
                class="quick-filter-btn"
                :class="{ active: selectedQuickFilter === 'all' }"
                @click="setQuickFilter('all')"
              >
                <div class="filter-icon"><img src="../assets/img/Detail.png" alt="logo" class="detail-logo"></div>
                <div class="filter-text">
                  <span class="filter-title">Semua Order</span>
                  <span class="filter-desc">Tampilkan semua</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Selected Period Display -->
          <div class="selected-period">
            <div class="period-info">
              <span class="period-label">Periode Terpilih:</span>
              <span class="period-value">{{ formatSelectedPeriod() }}</span>
            </div>
            <div class="period-stats" v-if="hasData">
              <span class="stats-item">{{ filteredOrders.length }} order</span>
              <span class="stats-item">Rp {{ formatCurrency(getTotalRevenue()) }}</span>
            </div>
          </div>
         
          <!-- Action Buttons -->
          <div class="filter-actions">
            <button
              class="filter-btn primary"
              @click="fetchOrdersWithFilter"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="btn-spinner"></span>
              <span v-else></span>
              {{ isLoading ? 'Memuat...' : 'Refresh Data' }}
            </button>
            <button
              class="filter-btn secondary"
              @click="resetFilters"
              :disabled="isLoading"
            >
              Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- Enhanced Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue"><img src="../assets/img/Checklist.png" alt="logo" class="checklist-logo"></div>
          <div class="stat-content">
            <div class="stat-value">{{ orderStats.totalOrder }}</div>
            <div class="stat-label">Total Order</div>
            <div class="stat-period">Dalam periode terpilih</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orange">
            <img src="../assets/img/Hourglass.png" alt="logo" class="hourglass-logo">
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ getOrdersByStatus('completed').length }}</div>
            <div class="stat-label">Siap Diambil</div>
            <div class="stat-period">Menunggu customer</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <img src="../assets/img/KarungDollar.png" alt="logo" class="horas-logo">
          </div>
          <div class="stat-content">
            <div class="stat-value">Rp {{ formatCurrency(orderStats.totalRevenue) }}</div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-period">Periode terpilih</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon success"> <img src="../assets/img/Delivery.png" alt="logo" class="horas-logo"></div>
          <div class="stat-content">
            <div class="stat-value">{{ orderStats.pickupOrders }}</div>
            <div class="stat-label">Order Pickup</div>
            <div class="stat-period">Layanan pickup</div>
          </div>
        </div>
      </div>

      <!-- Enhanced Orders Management Section -->
      <div class="orders-section">
        <!-- Tab Navigation for Order Status -->
        <div class="order-tabs">
          <button
            v-for="tab in orderTabs"
            :key="tab.key"
            @click="activeOrderTab = tab.key"
            :class="['tab-btn', { active: activeOrderTab === tab.key }]"
          >
            <i :class="tab.icon"></i>
            {{ tab.label }}
            <span class="badge" v-if="getOrdersByStatus(tab.status).length > 0">
              {{ getOrdersByStatus(tab.status).length }}
            </span>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <span>Memuat data order...</span>
        </div>

        <!-- Orders List -->
        <div v-else class="orders-list">
          <div v-if="filteredOrdersByTab.length === 0" class="empty-state">
            <span class="empty-icon">📭</span>
            <h3>Belum ada order {{ getCurrentTabLabel() }}</h3>
            <p>Order yang sudah dibayar akan muncul di sini sesuai status tracking</p>
          </div>

          <!-- Order Cards with Pagination -->
          <div v-else class="order-cards">
            <div
              v-for="order in paginatedOrders"
              :key="order.id"
              :class="['order-card', getOrderStatusClass(order.status_cucian)]"
            >
              <div class="order-header">
                <div class="order-info">
                  <h4>{{ order.customer_name }}</h4>
                  <div class="order-meta">
                    <span class="order-id">ID: {{ order.kode_transaksi }}</span>
                    <span class="order-payment-status">
                      <i :class="order.status_transaksi === 'sukses' ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                      {{ order.status_transaksi === 'sukses' ? 'LUNAS' : 'BELUM LUNAS' }}
                    </span>
                    <span class="order-payment-method">
                      <i :class="getPaymentMethodIcon(order.metode_pembayaran)"></i>
                      {{ formatPaymentMethod(order.metode_pembayaran) }}
                    </span>
                    
                    <!-- Service Type Badge - TEKS YANG BENAR -->
                    <span v-if="order.has_pickup_service" class="service-type-badge" :class="getServiceTypeClass(order.service_type)">
                      <i :class="getServiceTypeIcon(order.service_type)"></i>
                      {{ getServiceTypeLabel(order.service_type) }}
                    </span>
                  </div>
                </div>

                <div class="order-status">
                  <span :class="['status-badge', getOrderStatusClass(order.status_cucian)]">
                    {{ getStatusLabel(order.status_cucian) }}
                  </span>
                  <div class="order-price">Rp {{ formatCurrency(order.total_amount) }}</div>
                  <div v-if="order.status_transaksi !== 'sukses'" class="unpaid-badge">Belum Lunas</div>
                </div>
              </div>

              <div class="order-details">
                <div class="detail-row">
                  <span>Layanan:</span>
                  <span>{{ getServicesText(order.items) }}</span>
                </div>
                <div class="detail-row" v-if="order.biaya_pickup > 0">
                  <span>Biaya Pickup:</span>
                  <span>Rp {{ formatCurrency(order.biaya_pickup) }}</span>
                </div>
                <div class="detail-row" v-if="order.customer_phone">
                  <span>Telepon:</span>
                  <span>{{ order.customer_phone }}</span>
                </div>
                <div class="detail-row">
                  <span>Tanggal Transaksi:</span>
                  <span>{{ formatDateTime(order.tanggal_transaksi) }}</span>
                </div>
                <div class="detail-row" v-if="order.catatan">
                  <span>Catatan:</span>
                  <span>{{ order.catatan }}</span>
                </div>
              </div>

              <!-- PICKUP TRACKING - HANYA untuk delivery_only & pickup_delivery -->
              <div v-if="needsKurirTracking(order)" class="pickup-tracking-readonly">
                <div class="tracking-header">
                  <h5>Status Pickup/Delivery</h5>
                  <span :class="['current-status', getPickupStatusClass(order.pickup_status)]">
                    {{ getPickupStatusLabel(order.pickup_status) }}
                  </span>
                </div>
                
                <!-- Timeline History - READ ONLY untuk Kasir -->
                <div class="tracking-timeline">
                  <div
                    v-for="(log, index) in getPickupLogs(order)"
                    :key="index"
                    :class="['timeline-step', { 'completed': log.completed, 'current': log.is_current }]"
                  >
                    <div class="step-icon">
                      <i :class="log.icon"></i>
                    </div>
                    <div class="step-content">
                      <div class="step-title">{{ log.title }}</div>
                      <div class="step-time" v-if="log.timestamp">
                        {{ formatDateTime(log.timestamp) }}
                      </div>
                      <div class="step-note" v-if="log.note">
                        {{ log.note }}
                      </div>
                      <div class="step-user" v-if="log.updated_by">
                        oleh: {{ log.updated_by }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Auto-refresh indicator -->
                <div class="auto-refresh-info">
                  <i class="fas fa-sync-alt" :class="{ 'spinning': isAutoRefreshing }"></i>
                  <span>Update otomatis setiap 30 detik</span>
                  <button @click="refreshPickupStatus(order.id)" class="refresh-btn-small">
                    Refresh sekarang
                  </button>
                </div>
              </div>

              <!-- Action Buttons berdasarkan status -->
              <div class="order-actions">
                <!-- Status: Pending (Order Masuk) -->
                <template v-if="order.status_cucian === 'pending'">
                  <button class="btn-action start" @click="startProcessing(order)" :disabled="isUpdatingStatus">
                    <i class="fas fa-play"></i>
                    Mulai Proses Cucian
                  </button>
                  <button class="btn-action detail" @click="viewOrderDetail(order)">
                    <i class="fas fa-eye"></i>
                    Lihat Detail
                  </button>
                </template>

                <!-- Status: Processing (Sedang Proses) -->
                <template v-if="order.status_cucian === 'processing'">
                  <button class="btn-action complete" @click="markAsReady(order)" :disabled="isUpdatingStatus">
                    <i class="fas fa-check-circle"></i>
                    Selesai Dicuci
                  </button>
                  <button class="btn-action detail" @click="viewOrderDetail(order)">
                    <i class="fas fa-eye"></i>
                    Lihat Detail
                  </button>
                </template>

                <!-- Status: Completed (Siap Diambil) - LOGIC UTAMA -->
                <template v-if="order.status_cucian === 'completed'">
                  <!-- 🔹 KATEGORI A: NO PICKUP SERVICE (Laundry Only - tanpa kurir) -->
                  <!-- Pembayaran diselesaikan SEPENUHNYA di DashboardKasir -->
                  <template v-if="!order.has_pickup_service">
                    <!-- A1: Laundry only + belum dibayar (bayar nanti / tunai / qris) -->
                    <template v-if="order.status_transaksi === 'pending'">
                      <button class="btn-action pay-now" @click="openPaymentModal(order)" :disabled="isUpdatingStatus">
                        <i class="fas fa-credit-card"></i>
                        Bayar Sekarang
                      </button>
                      <button class="btn-action detail" @click="viewOrderDetail(order)">
                        <i class="fas fa-eye"></i>
                        Lihat Detail
                      </button>
                    </template>
                    <!-- A2: Laundry only + sudah dibayar -->
                    <template v-else>
                      <button class="btn-action complete" @click="markAsPickedUp(order)" :disabled="isUpdatingStatus">
                        <i class="fas fa-check-circle"></i>
                        Selesai
                      </button>
                      <button class="btn-action detail" @click="viewOrderDetail(order)">
                        <i class="fas fa-eye"></i>
                        Lihat Detail
                      </button>
                    </template>
                  </template>
                  
                  <!-- 🔹 KATEGORI B: WITH PICKUP SERVICE (ada kurir) -->
                  <!-- Pembayaran bisa di kasir atau kurir tergantung layanan -->
                  <template v-else>
                    <!-- B1: pickup_only + bayar-nanti + belum lunas 
                         HARUS dibayar di DashboardKasir -->
                    <template v-if="isPickupOnlyWithBayarNanti(order)">
                      <button class="btn-action pay-pickup" @click="openPaymentModal(order)" :disabled="isUpdatingStatus">
                        <i class="fas fa-hand-holding-usd"></i>
                        Customer Bayar & Ambil
                      </button>
                      <button class="btn-action detail" @click="viewOrderDetail(order)">
                        <i class="fas fa-eye"></i>
                        Lihat Detail
                      </button>
                    </template>
                    
                    <!-- B2: delivery_only + bayar-nanti + belum lunas
                         Langsung serahkan ke kurir (customer bayar nanti di kurir) -->
                    <template v-else-if="isDeliveryOnlyWithBayarNanti(order)">
                      <button class="btn-action pickup" @click="markAsPickedUp(order)" :disabled="isUpdatingStatus">
                        <i class="fas fa-check-double"></i>
                        Verifikasi
                      </button>
                      <button class="btn-action detail" @click="viewOrderDetail(order)">
                        <i class="fas fa-eye"></i>
                        Lihat Detail
                      </button>
                    </template>
                    
                    <!-- B3: pickup_delivery (Ambil + Antar) + bayar-nanti + belum lunas
                         Langsung serahkan ke kurir (customer bayar nanti di kurir) -->
                    <template v-else-if="isPickupDeliveryOrder(order) && order.metode_pembayaran === 'bayar-nanti' && order.status_transaksi === 'pending'">
                      <button class="btn-action pickup" @click="markAsPickedUp(order)" :disabled="isUpdatingStatus">
                        <i class="fas fa-check-double"></i>
                        Verifikasi
                      </button>
                      <button class="btn-action detail" @click="viewOrderDetail(order)">
                        <i class="fas fa-eye"></i>
                        Lihat Detail
                      </button>
                    </template>
                    
                    <!-- B4: Dengan pickup service tapi sudah lunas atau tidak bayar nanti -->
                    <template v-else>
                      <button class="btn-action pickup" @click="markAsPickedUp(order)" :disabled="isUpdatingStatus">
                        <i class="fas fa-check-double"></i>
                        {{ getPickupButtonLabel(order) }}
                      </button>
                      <button class="btn-action detail" @click="viewOrderDetail(order)">
                        <i class="fas fa-eye"></i>
                        Lihat Detail
                      </button>
                    </template>
                  </template>
                </template>

                <!-- Status: Delivered (Selesai) -->
                <template v-if="order.status_cucian === 'delivered'">
                  <button class="btn-action detail" @click="viewOrderDetail(order)">
                    <i class="fas fa-eye"></i>
                    Lihat Detail
                  </button>
                  <button class="btn-action print" @click="reprintReceipt(order)">
                    <i class="fas fa-print"></i>
                    Cetak Ulang
                  </button>
                </template>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination">
              <button
                class="page-btn"
                @click="currentPage--"
                :disabled="currentPage <= 1"
              >
                Prev
              </button>
             
              <span class="page-info">
                Halaman {{ currentPage }} dari {{ totalPages }}
              </span>
             
              <button
                class="page-btn"
                @click="currentPage++"
                :disabled="currentPage >= totalPages"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Payment Modal with Cash Input -->
    <div v-if="showPaymentModal" class="modal-overlay">
      <div class="modal-content payment-modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-credit-card"></i>
            Pembayaran Order
          </h3>
          <button class="modal-close" @click="closePaymentModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="paymentOrder">
          <!-- Order Info -->
          <div class="payment-order-info">
            <div class="info-row">
              <span>Order:</span>
              <span class="bold">{{ paymentOrder.kode_transaksi }}</span>
            </div>
            <div class="info-row">
              <span>Customer:</span>
              <span>{{ paymentOrder.customer_name }}</span>
            </div>
            <div class="info-row">
              <span>Total Tagihan:</span>
              <span class="total-amount">Rp {{ formatCurrency(paymentOrder.total_amount) }}</span>
            </div>

            <!-- Show service type info -->
            <div v-if="paymentOrder.service_type" class="info-row highlight">
              <span>Layanan:</span>
              <span class="service-label">
                <i :class="getServiceTypeIcon(paymentOrder.service_type)"></i>
                {{ getServiceTypeLabel(paymentOrder.service_type) }}
                <template v-if="isDeliveryOnlyWithBayarNanti(paymentOrder)">
                  - Pembayaran di Kasir atau bisa di Kurir (jika belum dibayar)
                </template>
              </span>
            </div>
          </div>

          <!-- Payment Method Selection -->
          <div class="payment-methods">
            <h4>Pilih Metode Pembayaran:</h4>
            <div class="method-buttons">
              <button
                @click="selectPaymentMethod('qris')"
                :class="['method-btn', { active: selectedPaymentMethod === 'qris' }]"
                :disabled="isProcessingPayment"
              >
                <i class="fas fa-qrcode"></i>
                <span>QRIS</span>
              </button>
              <button
                @click="selectPaymentMethod('tunai')"
                :class="['method-btn', { active: selectedPaymentMethod === 'tunai' }]"
                :disabled="isProcessingPayment"
              >
                <i class="fas fa-money-bill"></i>
                <span>Tunai</span>
              </button>
            </div>
          </div>

          <!-- Cash Payment Input - Show only when Tunai selected -->
          <div v-if="selectedPaymentMethod === 'tunai'" class="cash-payment-section">
            <h4>Input Pembayaran Tunai:</h4>
            <div class="cash-input-group">
              <label for="cashAmount">Jumlah Uang yang Diterima:</label>
              <div class="cash-input-wrapper">
                <span class="currency-prefix">Rp</span>
                <input
                  id="cashAmount"
                  type="number"
                  v-model="cashAmount"
                  :min="paymentOrder.total_amount"
                  step="1000"
                  placeholder="Masukkan jumlah uang"
                  class="cash-input"
                  @input="calculateChange"
                >
              </div>
             
              <!-- Quick Amount Buttons -->
              <div class="quick-amounts">
                <button
                  v-for="amount in getQuickAmounts(paymentOrder.total_amount)"
                  :key="amount"
                  @click="setCashAmount(amount)"
                  class="quick-amount-btn"
                  :class="{ active: cashAmount == amount }"
                >
                  Rp {{ formatCurrency(amount) }}
                </button>
              </div>

              <!-- Change Calculation -->
              <div v-if="cashAmount >= paymentOrder.total_amount" class="change-calculation">
                <div class="change-info success">
                  <i class="fas fa-check-circle"></i>
                  <span>Kembalian: <strong>Rp {{ formatCurrency(calculateChange()) }}</strong></span>
                </div>
              </div>
              <div v-else-if="cashAmount > 0" class="change-calculation">
                <div class="change-info error">
                  <i class="fas fa-exclamation-triangle"></i>
                  <span>Uang kurang Rp {{ formatCurrency(paymentOrder.total_amount - cashAmount) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- QRIS Payment Info -->
          <div v-if="selectedPaymentMethod === 'qris'" class="qris-payment-section">
            <div class="qris-info">
              <i class="fas fa-qrcode"></i>
              <p>Customer telah membayar via QRIS</p>
              <p class="qris-note">Pastikan pembayaran sudah diterima sebelum konfirmasi</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            @click="closePaymentModal"
            class="btn-cancel"
            :disabled="isProcessingPayment"
          >
            Batal
          </button>
          <button
            @click="processPayment"
            class="btn-confirm"
            :disabled="!canProcessPayment || isProcessingPayment"
          >
            <span v-if="isProcessingPayment" class="btn-spinner"></span>
            <span v-else>
              {{ getPaymentButtonLabel() }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Order Detail Modal -->
    <div v-if="showDetailModal" class="modal-overlay" @click="closeDetailModal">
      <div class="modal-content detail-modal" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-file-alt"></i>
            Detail Order
          </h3>
          <button class="modal-close" @click="closeDetailModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="selectedOrder">
          <!-- Basic Info -->
          <div class="detail-section">
            <h4>Informasi Order</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Kode Transaksi:</span>
                <span class="value">{{ selectedOrder.kode_transaksi }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Status Pembayaran:</span>
                <span class="value">
                  <span class="status" :class="selectedOrder.status_transaksi">
                    {{ selectedOrder.status_transaksi === 'sukses' ? 'LUNAS' : 'BELUM LUNAS' }}
                  </span>
                </span>
              </div>
              <div class="detail-item">
                <span class="label">Status Cucian:</span>
                <span class="value">
                  <span class="status" :class="selectedOrder.status_cucian">
                    {{ getStatusLabel(selectedOrder.status_cucian) }}
                  </span>
                </span>
              </div>
              <div class="detail-item">
                <span class="label">Tanggal Transaksi:</span>
                <span class="value">{{ formatDateTime(selectedOrder.tanggal_transaksi) }}</span>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="detail-section">
            <h4>Informasi Customer</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Nama:</span>
                <span class="value">{{ selectedOrder.customer_name }}</span>
              </div>
              <div class="detail-item">
                <span class="label">No. Telepon:</span>
                <span class="value">{{ selectedOrder.customer_phone }}</span>
              </div>
            </div>
          </div>

          <!-- Services -->
          <div class="detail-section">
            <h4>Layanan</h4>
            <div v-if="selectedOrder.items && selectedOrder.items.length > 0" class="services-detail">
              <div v-for="(item, index) in selectedOrder.items" :key="index" class="service-item">
                <div class="service-info">
                  <span class="service-name">{{ item.layanan_nama }}</span>
                  <span class="service-details">{{ item.kuantitas }} - Rp {{ formatCurrency(item.subtotal) }}</span>
                </div>
              </div>
              <div class="subtotal-layanan">
                <strong>Subtotal Layanan: Rp {{ formatCurrency(selectedOrder.subtotal_layanan) }}</strong>
              </div>
            </div>
            <div v-else class="no-services">
              <span class="empty-text">Tidak ada layanan laundry</span>
            </div>
          </div>

          <!-- Pickup Service -->
          <div v-if="selectedOrder.has_pickup_service" class="detail-section">
            <h4>Layanan Pickup</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Tipe Layanan:</span>
                <span class="value">{{ getServiceTypeLabel(selectedOrder.service_type) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Biaya Pickup:</span>
                <span class="value">Rp {{ formatCurrency(selectedOrder.biaya_pickup) }}</span>
              </div>
              <div class="detail-item" v-if="needsKurirTracking(selectedOrder)">
                <span class="label">Status:</span>
                <span class="value">{{ getPickupStatusLabel(selectedOrder.pickup_status) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="detail-section">
            <h4>Informasi Pembayaran</h4>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="label">Metode:</span>
                <span class="value">{{ formatPaymentMethod(selectedOrder.metode_pembayaran) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Total:</span>
                <span class="value total-amount">Rp {{ formatCurrency(selectedOrder.total_amount) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Jumlah Bayar:</span>
                <span class="value">Rp {{ formatCurrency(selectedOrder.jumlah_bayar) }}</span>
              </div>
              <div class="detail-item" v-if="selectedOrder.kembalian > 0">
                <span class="label">Kembalian:</span>
                <span class="value">Rp {{ formatCurrency(selectedOrder.kembalian) }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedOrder.catatan" class="detail-section">
            <h4>Catatan</h4>
            <div class="notes-content">
              {{ selectedOrder.catatan }}
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn secondary" @click="closeDetailModal">
            Tutup
          </button>
          <button class="modal-btn primary" @click="reprintReceipt(selectedOrder)">
            <i class="fas fa-print"></i>
            Cetak Ulang Struk
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import NavSidebar from '../components/navigation/NavSidebar.vue'
import { printHelpers } from '../utils/printHelpers'

const router = useRouter()

// Simple debounce function
function debounce(func, delay) {
  let timeoutId
  return function (...args) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func.apply(this, args), delay)
  }
}

// Sidebar state
const isSidebarCollapsed = ref(false)

// Loading states
const isLoading = ref(false)
const isUpdatingStatus = ref(false)
const isProcessingPayment = ref(false)
const isAutoRefreshing = ref(false)

// Filter state
const selectedQuickFilter = ref('today')
const currentFilter = ref({
  dateFrom: '',
  dateTo: '',
  status: 'all'
})

// Pagination state
const currentPage = ref(1)
const itemsPerPage = 10

// Modal states
const showPaymentModal = ref(false)
const showDetailModal = ref(false)
const paymentOrder = ref(null)
const selectedOrder = ref(null)

// Payment states
const selectedPaymentMethod = ref('')
const cashAmount = ref(0)

// Data
const orders = ref([])
const activeOrderTab = ref('pending')

// Real-time tracking
const pickupStatusInterval = ref(null)
let websocket = null

// Order Tabs Configuration
const orderTabs = [
  { key: 'pending', label: 'Order Masuk', icon: 'fas fa-inbox', status: 'pending' },
  { key: 'processing', label: 'Sedang Proses', icon: 'fas fa-cog fa-spin', status: 'processing' },
  { key: 'completed', label: 'Siap Diambil', icon: 'fas fa-box-open', status: 'completed' },
  { key: 'delivered', label: 'Selesai', icon: 'fas fa-check-circle', status: 'delivered' }
]

// Cache for optimization
const ordersCache = new Map()
const CACHE_DURATION = 5 * 60 * 1000

// Feature flags
const ENABLE_WEBSOCKET = false
const ENABLE_AUTO_REFRESH_PICKUP = false
const ENABLE_FAST_PAYMENT_POLLING = true // ✅ Enable real-time payment updates
const PAYMENT_POLL_INTERVAL = 2000 // 2 detik - cek pembayaran dari kurir setiap 2 detik

// ========================================
// COMPUTED PROPERTIES
// ========================================

const filteredOrders = computed(() => {
  let filtered = orders.value

  if (currentFilter.value.dateFrom && currentFilter.value.dateTo) {
    const fromDate = new Date(currentFilter.value.dateFrom)
    const toDate = new Date(currentFilter.value.dateTo)
    toDate.setHours(23, 59, 59, 999)
   
    filtered = filtered.filter(order => {
      const orderDate = new Date(order.tanggal_transaksi)
      return orderDate >= fromDate && orderDate <= toDate
    })
  }

  return filtered
})

const filteredOrdersByTab = computed(() => {
  const currentTab = orderTabs.find(tab => tab.key === activeOrderTab.value)
  return filteredOrders.value.filter(order => order.status_cucian === currentTab.status)
})

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredOrdersByTab.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredOrdersByTab.value.length / itemsPerPage)
})

const hasData = computed(() => {
  return filteredOrders.value.length > 0
})

const orderStats = computed(() => {
  const totalOrder = filteredOrders.value.length
  const totalRevenue = filteredOrders.value
    .filter(order => order.status_transaksi === 'sukses')
    .reduce((total, order) => total + (parseFloat(order.total_amount) || 0), 0)
 
  const pickupOrders = filteredOrders.value.filter(order => order.has_pickup_service).length

  return {
    totalOrder,
    totalRevenue,
    pickupOrders
  }
})

const canProcessPayment = computed(() => {
  if (!selectedPaymentMethod.value) return false
 
  if (selectedPaymentMethod.value === 'tunai') {
    return cashAmount.value >= paymentOrder.value?.total_amount
  }
 
  return true
})

// ========================================
// SERVICE TYPE HELPER FUNCTIONS
// ========================================

// Fungsi untuk cek apakah order butuh tracking kurir
const needsKurirTracking = (order) => {
  return order.has_pickup_service &&
         (order.service_type === 'pickup_delivery' || order.service_type === 'delivery_only')
}

// Fungsi KUNCI: Cek apakah pickup_only + bayar-nanti + belum lunas
// Pickup Only (Ambil Saja): Pembayaran HARUS di DashboardKasir
const isPickupOnlyWithBayarNanti = (order) => {
  return (
    order.has_pickup_service === true &&
    order.service_type === 'pickup_only' &&
    order.metode_pembayaran === 'bayar-nanti' &&
    order.status_transaksi === 'pending'
  )
}

// Delivery Only (Antar Saja): Pembayaran bisa di DashboardKasir atau DashboardKurir
// Tergantung kapan dibayar - jika masih di kasir, bayar di DashboardKasir; jika sudah dikirim kurir, bayar di DashboardKurir
const isDeliveryOnlyWithBayarNanti = (order) => {
  return (
    order.has_pickup_service === true &&
    order.service_type === 'delivery_only' &&
    order.metode_pembayaran === 'bayar-nanti' &&
    order.status_transaksi === 'pending'
  )
}

// Pickup And Delivery (Ambil + Antar): Pembayaran HARUS di DashboardKurir
// Karena sudah di tangan kurir
const isPickupDeliveryOrder = (order) => {
  return (
    order.has_pickup_service === true &&
    order.service_type === 'pickup_delivery'
  )
}

// Cek apakah order BOLEH dibayar di DashboardKasir
// Boleh dibayar di kasir jika: pickup_only (selalu) atau delivery_only (jika belum diserahkan ke kurir)
const canPayAtDashboardKasir = (order) => {
  return (
    isPickupOnlyWithBayarNanti(order) ||
    (isDeliveryOnlyWithBayarNanti(order) && order.status_cucian === 'completed')
  )
}

// Cek apakah order HARUS dibayar di DashboardKurir
// Harus di kurir jika: pickup_delivery (selalu) atau delivery_only (jika sudah diserahkan ke kurir)
const shouldPayAtDashboardKurir = (order) => {
  return (
    isPickupDeliveryOrder(order) ||
    (isDeliveryOnlyWithBayarNanti(order) && order.pickup_status && order.pickup_status !== 'pending')
  )
}

// Label yang BENAR untuk badge
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

const getServiceTypeClass = (serviceType) => {
  return `service-${serviceType}`
}

// Label tombol dinamis
const getPickupButtonLabel = (order) => {
  if (!order.has_pickup_service) {
    return 'Diambil Customer'
  }
 

  //btn-action pickup
  switch (order.service_type) {
    case 'pickup_only':
      return 'Customer Ambil di Outlet'
    case 'delivery_only':
      return 'Selesai'
    case 'pickup_delivery':
      return 'selesai'
    default:
      return 'Diambil Customer'
  }
}

// Label tombol payment modal
const getPaymentButtonLabel = () => {
  if (isPickupOnlyWithBayarNanti(paymentOrder.value)) {
    return selectedPaymentMethod.value === 'tunai'
      ? 'Konfirmasi Pembayaran & Selesai'
      : 'Konfirmasi Pembayaran QRIS & Selesai'
  }
  
  return selectedPaymentMethod.value === 'tunai'
    ? 'Konfirmasi Pembayaran Tunai'
    : 'Konfirmasi Pembayaran QRIS'
}

// ========================================
// PICKUP TRACKING FUNCTIONS
// ========================================

function getPickupLogs(order) {
  if (order.pickup_logs && order.pickup_logs.length > 0) {
    return order.pickup_logs
  }
 
  return generatePickupLogs(order.pickup_status, order.last_pickup_update || order.updated_at)
}

function generatePickupLogs(currentStatus, lastUpdate) {
  const logs = [
    {
      title: "Order Siap di Outlet",
      icon: "fas fa-box",
      completed: true,
      timestamp: lastUpdate,
      note: "Order sudah siap untuk diambil kurir",
      updated_by: "System"
    }
  ]

  if (currentStatus === 'diantar' || currentStatus === 'selesai') {
    logs.push({
      title: "Kurir Mulai Pengantaran",
      icon: "fas fa-truck",
      completed: true,
      is_current: currentStatus === 'diantar',
      timestamp: lastUpdate,
      note: "Kurir sudah mengambil dan mulai mengantar ke customer",
      updated_by: "Kurir"
    })
  }

  if (currentStatus === 'selesai') {
    logs.push({
      title: "Berhasil Diantar ke Customer",
      icon: "fas fa-check-circle",
      completed: true,
      is_current: true,
      timestamp: lastUpdate,
      note: "Order sudah sampai ke customer",
      updated_by: "Kurir"
    })
  }

  return logs
}

function getPickupStatusClass(status) {
  const statusClasses = {
    dioutlet: 'bg-purple-100 text-purple-800',
    diantar: 'bg-orange-100 text-orange-800',
    selesai: 'bg-green-100 text-green-800'
  }
  return statusClasses[status] || ''
}

function getPickupStatusLabel(status) {
  const statusLabels = {
    dioutlet: 'Di Outlet',
    diantar: 'Sedang Diantar',
    selesai: 'Selesai'
  }
  return statusLabels[status] || status
}

const refreshPickupStatus = async (orderId) => {
  if (!ENABLE_AUTO_REFRESH_PICKUP) {
    console.log('⚠️ Auto refresh pickup disabled')
    return
  }

  try {
    isAutoRefreshing.value = true
   
    const response = await axios.get(`/api/transaksi/${orderId}/pickup-status`)
   
    if (response.data.success) {
      const orderIndex = orders.value.findIndex(o => o.id === orderId)
      if (orderIndex !== -1) {
        orders.value[orderIndex].pickup_status = response.data.pickup_status
        orders.value[orderIndex].pickup_logs = response.data.pickup_logs ||
          generatePickupLogs(response.data.pickup_status, response.data.last_update)
        orders.value[orderIndex].last_pickup_update = response.data.last_update
      }
    }
  } catch (error) {
    if (error.response?.status === 404) {
      console.warn('⚠️ Pickup status endpoint not available')
    } else if (error.response?.status !== 401) {
      console.error('Error refreshing pickup status:', error.message)
    }
  } finally {
    isAutoRefreshing.value = false
  }
}

const startPickupStatusRefresh = () => {
  if (!ENABLE_AUTO_REFRESH_PICKUP) {
    console.log('⚠️ Pickup status auto-refresh disabled')
    return
  }

  pickupStatusInterval.value = setInterval(async () => {
    if (document.visibilityState === 'visible' && !isLoading.value) {
      const pickupOrders = filteredOrders.value.filter(order =>
        needsKurirTracking(order) &&
        order.pickup_status !== 'selesai'
      )
     
      if (pickupOrders.length === 0) return
     
      for (const order of pickupOrders) {
        try {
          await refreshPickupStatus(order.id)
          await new Promise(resolve => setTimeout(resolve, 200))
        } catch (error) {
          console.warn(`Skip refresh for order ${order.id}`)
        }
      }
    }
  }, 30000)
}

const stopPickupStatusRefresh = () => {
  if (pickupStatusInterval.value) {
    clearInterval(pickupStatusInterval.value)
    pickupStatusInterval.value = null
  }
}

// ========================================
// PAYMENT MODAL FUNCTIONS
// ========================================

function openPaymentModal(order) {
  paymentOrder.value = order
  selectedPaymentMethod.value = ''
  cashAmount.value = 0
  showPaymentModal.value = true
  document.body.style.overflow = 'hidden'
}

function closePaymentModal() {
  showPaymentModal.value = false
  paymentOrder.value = null
  selectedPaymentMethod.value = ''
  cashAmount.value = 0
  document.body.style.overflow = 'auto'
}

function selectPaymentMethod(method) {
  selectedPaymentMethod.value = method
  if (method === 'tunai') {
    cashAmount.value = paymentOrder.value?.total_amount || 0
  }
}

function getQuickAmounts(totalAmount) {
  const base = Math.ceil(totalAmount / 1000) * 1000
  return [
    totalAmount,
    base,
    base + 5000,
    base + 10000,
    base + 20000,
    base + 50000
  ].filter(amount => amount >= totalAmount)
}

function setCashAmount(amount) {
  cashAmount.value = amount
}

function calculateChange() {
  if (!paymentOrder.value || cashAmount.value < paymentOrder.value.total_amount) {
    return 0
  }
  return cashAmount.value - paymentOrder.value.total_amount
}

// ========================================
// PROCESS PAYMENT - LOGIC UTAMA
// ========================================

const processPayment = async () => {
  if (!canProcessPayment.value) return
 
  isProcessingPayment.value = true

  try {
    const paymentData = {
      metode_pembayaran: selectedPaymentMethod.value,
      status_transaksi: 'sukses',
      jumlah_bayar: selectedPaymentMethod.value === 'tunai' ? cashAmount.value : paymentOrder.value.total_amount,
      kembalian: selectedPaymentMethod.value === 'tunai' ? calculateChange() : 0
    }

    const response = await axios.patch(`/api/transaksi/${paymentOrder.value.id}/payment`, paymentData)

    if (response.data.success) {
      const orderIndex = orders.value.findIndex(o => o.id === paymentOrder.value.id)
      if (orderIndex !== -1) {
        Object.assign(orders.value[orderIndex], paymentData)
      }
     
      const updatedOrder = { ...paymentOrder.value, ...paymentData }
     
      // CEK: Jenis order apa?
      const isPickupOnly = isPickupOnlyWithBayarNanti(paymentOrder.value)
      const isLaundryOnly = !paymentOrder.value.has_pickup_service
     
      if (isPickupOnly || isLaundryOnly) {
        // LANGSUNG UPDATE STATUS KE DELIVERED (SELESAI)
        await updateOrderStatus(paymentOrder.value.id, 'delivered')
       
        if (orderIndex !== -1) {
          orders.value[orderIndex].status_cucian = 'delivered'
        }
       
        if (isPickupOnly) {
          showSuccessAlert('Pembayaran berhasil! Order selesai - customer sudah mengambil cucian.')
        } else {
          showSuccessAlert('Pembayaran berhasil! Order sudah selesai.')
        }
      } else {
        // Flow untuk order dengan delivery/pickup service: bayar saja dulu, nanti diserahkan ke kurir
        showSuccessAlert('Pembayaran berhasil dicatat! Order siap diserahkan ke kurir.')
      }
     
      closePaymentModal()
     
      // Print receipt
      try {
        const printResult = await printHelpers.printReceipt(updatedOrder, {
          useQzTray: true,
          laundryInfo: {
            name: "FRESH LAUNDRY",
            address: "Jl. Laundry No. 123",
            phone: "081234567890"
          }
        })
       
        if (printResult.success) {
          showSuccessToast('Struk berhasil dicetak!')
        } else {
          showErrorToast('Pembayaran berhasil, tapi gagal cetak struk')
        }
      } catch (printError) {
        console.error('Print error:', printError)
        showErrorToast('Pembayaran berhasil, tapi gagal cetak struk')
      }
     
      ordersCache.clear()
     
      // Jika pickup_only atau laundry only, pindah ke tab delivered
      if (isPickupOnly || isLaundryOnly) {
        activeOrderTab.value = 'delivered'
        await fetchOrdersOptimized(true)
      }
    }
  } catch (error) {
    console.error('Payment error:', error)
    showErrorAlert('Gagal memproses pembayaran')
  } finally {
    isProcessingPayment.value = false
  }
}

// ========================================
// QUICK FILTER FUNCTIONS
// ========================================

function getTodayDate() {
  return new Date().toISOString().split('T')[0]
}

function getWeekRange() {
  const today = new Date()
  const firstDay = new Date(today.setDate(today.getDate() - today.getDay() + 1))
  const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 7))
 
  return `${firstDay.getDate()}/${firstDay.getMonth() + 1} - ${lastDay.getDate()}/${lastDay.getMonth() + 1}`
}

function setQuickFilter(filterType) {
  selectedQuickFilter.value = filterType
 
  const today = new Date()
 
  switch (filterType) {
    case 'today':
      currentFilter.value.dateFrom = getTodayDate()
      currentFilter.value.dateTo = getTodayDate()
      break
   
    case 'week':
      const firstDay = new Date(today)
      firstDay.setDate(today.getDate() - today.getDay() + 1)
      const lastDay = new Date(today)
      lastDay.setDate(today.getDate() - today.getDay() + 7)
     
      currentFilter.value.dateFrom = firstDay.toISOString().split('T')[0]
      currentFilter.value.dateTo = lastDay.toISOString().split('T')[0]
      break
   
    case 'all':
      currentFilter.value.dateFrom = ''
      currentFilter.value.dateTo = ''
      break
  }
 
  currentPage.value = 1
  fetchOrdersWithFilter()
}

function formatSelectedPeriod() {
  if (!currentFilter.value.dateFrom || !currentFilter.value.dateTo) {
    return 'Semua periode'
  }
 
  const from = formatDate(currentFilter.value.dateFrom)
  const to = formatDate(currentFilter.value.dateTo)
 
  if (currentFilter.value.dateFrom === currentFilter.value.dateTo) {
    return from
  }
 
  return `${from} - ${to}`
}

function resetFilters() {
  selectedQuickFilter.value = 'today'
  setQuickFilter('today')
  activeOrderTab.value = 'pending'
  currentPage.value = 1
}

function getTotalRevenue() {
  return filteredOrders.value
    .filter(order => order.status_transaksi === 'sukses')
    .reduce((total, order) => total + (parseFloat(order.total_amount) || 0), 0)
}

// ========================================
// SIDEBAR HANDLING
// ========================================

function handleSidebarToggle(collapsed) {
  isSidebarCollapsed.value = collapsed
}

// ========================================
// FETCH ORDERS
// ========================================

const fetchOrdersOptimized = async (forceRefresh = false) => {
  const cacheKey = `orders_${currentFilter.value.dateFrom}_${currentFilter.value.dateTo}`
  const cached = ordersCache.get(cacheKey)
 
  if (!forceRefresh && cached && (Date.now() - cached.timestamp < CACHE_DURATION)) {
    orders.value = cached.data
    return
  }
 
  try {
    console.log('Fetching orders from API...')
   
    const params = {}
    if (currentFilter.value.dateFrom && currentFilter.value.dateTo) {
      params.date_from = currentFilter.value.dateFrom
      params.date_to = currentFilter.value.dateTo
    }
   
    const response = await axios.get('/api/transaksi', { params })
   
    if (response.data.success) {
      const validatedOrders = response.data.data.map(order => validateOrderData(order))
      orders.value = validatedOrders
     
      ordersCache.set(cacheKey, {
        data: validatedOrders,
        timestamp: Date.now()
      })
     
      console.log('✅ Orders loaded successfully:', orders.value.length)
    } else {
      throw new Error(response.data.message || 'API returned success: false')
    }
  } catch (error) {
    console.error('❌ Error fetching orders:', error)
   
    if (cached) {
      orders.value = cached.data
      console.warn('⚠️ Using cached data due to API error')
      showErrorToast('Menggunakan data cache karena gagal terhubung ke server')
    } else {
      orders.value = []
      handleAPIError(error)
    }
  }
}

const fetchOrdersWithFilter = debounce(async () => {
  isLoading.value = true
  try {
    await fetchOrdersOptimized()
  } finally {
    isLoading.value = false
  }
}, 300)

// ========================================
// DATA VALIDATION
// ========================================

function validateOrderData(order) {
  return {
    id: order.id,
    kode_transaksi: order.kode_transaksi || 'N/A',
    customer_name: order.customer_name || 'Unknown',
    customer_phone: order.customer_phone || 'N/A',
    tanggal_transaksi: order.tanggal_transaksi,
    status_transaksi: order.status_transaksi || 'pending',
    status_cucian: order.status_cucian || 'pending',
    metode_pembayaran: order.metode_pembayaran || 'tunai',
    total_amount: parseFloat(order.total_amount) || 0,
    subtotal_layanan: parseFloat(order.subtotal_layanan) || 0,
    biaya_pickup: parseFloat(order.biaya_pickup) || 0,
    jumlah_bayar: parseFloat(order.jumlah_bayar) || 0,
    kembalian: parseFloat(order.kembalian) || 0,
    items: order.items || order.details || [],
    has_pickup_service: Boolean(order.has_pickup_service),
    service_type: order.service_type || 'pickup_delivery',
    pickup_status: order.pickup_status || 'dioutlet',
    pickup_logs: order.pickup_logs || generatePickupLogs(order.pickup_status || 'dioutlet', order.updated_at),
    last_pickup_update: order.last_pickup_update || order.updated_at,
    alamat_pickup: order.alamat_pickup || '',
    catatan: order.catatan || '',
    created_by: order.created_by || 'System'
  }
}

// ========================================
// ERROR HANDLING
// ========================================

function handleAPIError(error) {
  let errorMessage = 'Gagal memuat data order'
 
  if (error.response) {
    if (error.response.status === 401) {
      errorMessage = 'Session expired, silakan login kembali'
      router.push('/login')
      return
    } else if (error.response.status === 404) {
      errorMessage = 'Endpoint API tidak ditemukan'
    } else if (error.response.status === 500) {
      errorMessage = 'Server error, coba lagi nanti'
    } else {
      errorMessage = `Error ${error.response.status}: ${error.response.data?.message || 'Unknown error'}`
    }
  } else if (error.request) {
    errorMessage = 'Tidak dapat terhubung ke server'
  }
 
  showErrorAlert(errorMessage)
}

// ========================================
// TOAST NOTIFICATIONS
// ========================================

function showErrorToast(message) {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'warning',
    title: message,
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  })
}

function showSuccessToast(message) {
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

// ========================================
// ORDER MANAGEMENT
// ========================================

const startProcessing = async (order) => {
  if (await confirmAction(`Mulai proses cucian untuk order ${order.kode_transaksi}?`)) {
    isUpdatingStatus.value = true
    try {
      await updateOrderStatus(order.id, 'processing')
      showSuccessToast('Order mulai diproses')
    } finally {
      isUpdatingStatus.value = false
    }
  }
}

const markAsReady = async (order) => {
  if (await confirmAction(`Tandai order ${order.kode_transaksi} sudah selesai dicuci?`)) {
    isUpdatingStatus.value = true
    try {
      await updateOrderStatus(order.id, 'completed')
      showSuccessAlert('Cucian selesai! Order siap diambil.')
      activeOrderTab.value = 'completed'
    } finally {
      isUpdatingStatus.value = false
    }
  }
}

const markAsPickedUp = async (order) => {
  let action = 'diambil customer'
  let confirmMessage = `Konfirmasi order ${order.kode_transaksi} sudah ${action}?`
 
  if (order.has_pickup_service) {
    switch (order.service_type) {
      case 'pickup_only':
        action = 'diambil customer di outlet'
        confirmMessage = `Konfirmasi order ${order.kode_transaksi} sudah diambil customer?\n\nCustomer telah mengambil cucian bersih di outlet.`
        break
      case 'delivery_only':
      case 'pickup_delivery':
        action = 'diserahkan ke kurir untuk diantar'
        confirmMessage = `Serahkan order ${order.kode_transaksi} ke kurir?\n\nOrder akan diserahkan ke kurir untuk diantar ke customer.`
        break
    }
  }
 
  if (await confirmAction(confirmMessage)) {
    isUpdatingStatus.value = true
    try {
      await updateOrderStatus(order.id, 'delivered')
     
      let successMessage = 'Order selesai!'
      if (order.service_type === 'delivery_only' || order.service_type === 'pickup_delivery') {
        successMessage = 'Order diserahkan ke kurir untuk pengantaran!'
      }
     
      showSuccessAlert(successMessage)
      activeOrderTab.value = 'delivered'
    } finally {
      isUpdatingStatus.value = false
    }
  }
}

const updateOrderStatus = async (orderId, newStatus) => {
  try {
    const order = orders.value.find(o => o.id === orderId)
    const updatePayload = {
      status_cucian: newStatus
    }
    
    // Jika status menjadi delivered dan ada pickup service, update pickup_status ke dioutlet
    if (newStatus === 'delivered' && order?.has_pickup_service) {
      updatePayload.pickup_status = 'dioutlet'
    }
    
    const response = await axios.patch(`/api/transaksi/${orderId}/status`, updatePayload)

    if (response.data.success) {
      const orderIndex = orders.value.findIndex(o => o.id === orderId)
      if (orderIndex !== -1) {
        orders.value[orderIndex].status_cucian = newStatus
        if (updatePayload.pickup_status) {
          orders.value[orderIndex].pickup_status = updatePayload.pickup_status
        }
      }
     
      ordersCache.clear()
    }
  } catch (error) {
    console.error('Update status error:', error)
    showErrorAlert('Gagal mengupdate status order')
    throw error
  }
}

const viewOrderDetail = (order) => {
  selectedOrder.value = order
  showDetailModal.value = true
  document.body.style.overflow = 'hidden'
}

const reprintReceipt = async (order) => {
  try {
    showSuccessToast('Mencetak ulang struk...')
   
    const printResult = await printHelpers.printReceipt(order, {
      useQzTray: true,
      laundryInfo: {
        name: "FRESH LAUNDRY",
        address: "Jl. Laundry No. 123",
        phone: "081234567890"
      }
    })
   
    if (printResult.success) {
      showSuccessToast('Struk berhasil dicetak ulang!')
    } else {
      showErrorToast('Gagal mencetak struk')
    }
  } catch (error) {
    console.error('Print error:', error)
    showErrorAlert('Gagal mencetak struk')
  }
}

// ========================================
// HELPER FUNCTIONS
// ========================================

const getOrdersByStatus = (status) => {
  return filteredOrders.value.filter(order => order.status_cucian === status)
}

const getCurrentTabLabel = () => {
  const currentTab = orderTabs.find(tab => tab.key === activeOrderTab.value)
  return currentTab ? currentTab.label.toLowerCase() : ''
}

const getOrderStatusClass = (status) => {
  const statusClasses = {
    pending: 'status-pending',
    processing: 'status-processing',
    completed: 'status-completed',
    delivered: 'status-delivered'
  }
  return statusClasses[status] || ''
}

const getStatusLabel = (status) => {
  const statusLabels = {
    pending: 'Order Masuk',
    processing: 'Sedang Diproses',
    completed: 'Siap Diambil',
    delivered: 'Selesai'
  }
  return statusLabels[status] || status
}

const getPaymentMethodIcon = (method) => {
  const icons = {
    'tunai': 'fas fa-money-bill',
    'qris': 'fas fa-qrcode',
    'bayar-nanti': 'fas fa-clock'
  }
  return icons[method] || 'fas fa-credit-card'
}

const formatPaymentMethod = (method) => {
  const methods = {
    'tunai': 'Tunai',
    'qris': 'QRIS',
    'bayar-nanti': 'Bayar Nanti'
  }
  return methods[method] || method
}

const getServicesText = (items) => {
  if (!items || items.length === 0) return 'Tidak ada layanan'
  return items.map(item => item.layanan_nama || item.nama_layanan || 'Unknown Service').join(', ')
}

// ========================================
// FORMATTING FUNCTIONS
// ========================================

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID').format(parseFloat(amount) || 0)
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
// MODAL FUNCTIONS
// ========================================

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedOrder.value = null
  document.body.style.overflow = 'auto'
}

// ========================================
// ALERT FUNCTIONS
// ========================================

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

const showErrorAlert = (message) => {
  Swal.fire({
    title: 'Error!',
    text: message,
    icon: 'error',
    confirmButtonText: 'OK'
  })
}

// ========================================
// LOGOUT
// ========================================

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

// ========================================
// WEBSOCKET
// ========================================

const initWebSocket = () => {
  if (!ENABLE_WEBSOCKET) {
    console.log('⚠️ WebSocket disabled - using polling only')
    return
  }

  if (!websocket || websocket.readyState === WebSocket.CLOSED) {
    try {
      websocket = new WebSocket('ws://localhost:8080/pickup-tracking')
     
      websocket.onopen = () => {
        console.log('✅ WebSocket connected for pickup tracking')
      }
     
      websocket.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data)
         
          if (data.type === 'pickup_status_update') {
            handlePickupStatusUpdate(data)
          }
        } catch (error) {
          console.error('WebSocket message parse error:', error)
        }
      }
     
      websocket.onclose = () => {
        console.log('WebSocket disconnected')
      }
     
      websocket.onerror = (error) => {
        console.warn('⚠️ WebSocket error (optional feature)')
      }
     
      window.websocket = websocket
    } catch (error) {
      console.warn('⚠️ WebSocket not available:', error.message)
    }
  }
}

const handlePickupStatusUpdate = (data) => {
  const orderIndex = orders.value.findIndex(o => o.id === data.order_id)
  if (orderIndex !== -1) {
    orders.value[orderIndex].pickup_status = data.new_status
    orders.value[orderIndex].last_pickup_update = new Date().toISOString()
   
    refreshPickupStatus(data.order_id)
   
    showSuccessToast(`Order ${orders.value[orderIndex].kode_transaksi} - Status pickup diupdate oleh ${data.updated_by}`)
  }
}

// ========================================
// AUTO-REFRESH
// ========================================

let autoRefreshInterval = null

// ========================================
// TRACK PAYMENT UPDATES FROM KURIR - REAL-TIME
// ========================================

let lastPaymentCheckTime = null
let paymentPollingInterval = null
let isCheckingPayment = false

const checkPaymentUpdates = async () => {
  // Prevent multiple simultaneous checks
  if (isCheckingPayment) return
  
  try {
    isCheckingPayment = true
    
    const params = {}
    if (lastPaymentCheckTime) {
      params.since = lastPaymentCheckTime
    }
    
    const response = await axios.get('/api/kasir/payment-updates', { params })
    
    if (response.data.success && response.data.count > 0) {
      console.log('💰 PAYMENT UPDATE DETECTED! Count:', response.data.count)
      console.log('   Orders:', response.data.data.map(o => o.kode_transaksi).join(', '))
      
      // Show notification untuk setiap payment
      response.data.data.forEach(payment => {
        showSuccessToast(`💰 ${payment.kode_transaksi} - Rp ${formatCurrency(payment.total_amount)} sudah dibayar!`)
      })
      
      // Refresh orders untuk update payment status SEKETIKA
      await fetchOrdersOptimized(true)
      
      // Update last check time
      lastPaymentCheckTime = response.data.timestamp
    }
  } catch (error) {
    console.error('Error checking payment updates:', error)
    // Tidak perlu show error, ini background check
  } finally {
    isCheckingPayment = false
  }
}

// Start fast polling untuk payment updates
const startPaymentPolling = () => {
  if (!ENABLE_FAST_PAYMENT_POLLING) return
  
  console.log('🚀 Payment polling started - check every', PAYMENT_POLL_INTERVAL + 'ms')
  
  paymentPollingInterval = setInterval(() => {
    if (document.visibilityState === 'visible') {
      checkPaymentUpdates()
    }
  }, PAYMENT_POLL_INTERVAL)
}

// Stop payment polling
const stopPaymentPolling = () => {
  if (paymentPollingInterval) {
    clearInterval(paymentPollingInterval)
    paymentPollingInterval = null
    console.log('⏹️ Payment polling stopped')
  }
}

const startAutoRefresh = () => {
  autoRefreshInterval = setInterval(() => {
    if (document.visibilityState === 'visible' && !isLoading.value) {
      fetchOrdersOptimized(false)
    }
  }, 30000)
}

const stopAutoRefresh = () => {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval)
    autoRefreshInterval = null
  }
}

// ========================================
// WATCHERS
// ========================================

watch(activeOrderTab, () => {
  currentPage.value = 1
})

watch(selectedQuickFilter, (newFilter) => {
  if (newFilter !== 'custom') {
    currentPage.value = 1
  }
})

// ========================================
// LIFECYCLE HOOKS
// ========================================

onMounted(() => {
  console.log('✅ DashboardKasir mounted - Enhanced with 3 service types support')
 
  setQuickFilter('today')
  startAutoRefresh()
  startPaymentPolling()
  startPickupStatusRefresh()
  initWebSocket()
 
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      fetchOrdersOptimized(false)
    }
  })
 
  console.log('✅ DashboardKasir initialized')
  console.log(`  - WebSocket: ${ENABLE_WEBSOCKET ? 'Enabled' : 'Disabled'}`)
  console.log(`  - Fast Payment Polling: ${ENABLE_FAST_PAYMENT_POLLING ? 'Enabled (' + PAYMENT_POLL_INTERVAL + 'ms)' : 'Disabled'}`)
  console.log(`  - Pickup Auto-refresh: ${ENABLE_AUTO_REFRESH_PICKUP ? 'Enabled' : 'Disabled'}`)
})

onUnmounted(() => {
  stopAutoRefresh()
  stopPaymentPolling()
  stopPickupStatusRefresh()
  ordersCache.clear()
 
  if (websocket) {
    websocket.close()
  }
 
  document.removeEventListener('visibilitychange', () => {})
  document.body.style.overflow = 'auto'
 
  console.log('🧹 DashboardKasir cleanup completed')
})
</script>

<style scoped>
/* Enhanced styles */
@import '../assets/css/DashboardKasir.css';

/* ========================================
   SERVICE TYPE BADGE STYLES - WARNA BERBEDA
   ======================================== */

.service-type-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  letter-spacing: 0.3px;
}

/* Ambil Saja - Warna Biru */
.service-type-badge.service-pickup_only {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  color: #1e40af;
  border: 1px solid #93c5fd;
}

/* Antar Saja - Warna Orange */
.service-type-badge.service-delivery_only {
  background: linear-gradient(135deg, #fed7aa, #fdba74);
  color: #9a3412;
  border: 1px solid #fb923c;
}

/* Ambil + Antar - Warna Hijau */
.service-type-badge.service-pickup_delivery {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: #065f46;
  border: 1px solid #6ee7b7;
}

/* ========================================
   PAYMENT MODAL HIGHLIGHT
   ======================================== */

.payment-order-info .info-row.highlight {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  padding: 12px;
  border-radius: 8px;
  margin-top: 12px;
  border-left: 4px solid #3b82f6;
}

.payment-order-info .service-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #1e40af;
  font-weight: 600;
  font-size: 14px;
}

.payment-order-info .service-label i {
  font-size: 16px;
}

/* ========================================
   ENHANCED PICKUP ONLY BUTTON
   ======================================== */

.btn-action.pay-pickup {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  border: none;
  padding: 12px 18px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-action.pay-pickup:hover:not(:disabled) {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-action.pay-pickup i {
  font-size: 16px;
}

/* ========================================
   PICKUP TRACKING STYLES (READ-ONLY)
   ======================================== */

.pickup-tracking-readonly {
  background: linear-gradient(135deg, #f8fafc, #e2e8f0);
  padding: 18px;
  border-radius: 12px;
  margin-top: 16px;
  border-left: 4px solid #3b82f6;
  position: relative;
}

.tracking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e2e8f0;
}

.tracking-header h5 {
  margin: 0;
  color: #1e293b;
  font-weight: 700;
  font-size: 15px;
}

.current-status {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tracking-timeline {
  position: relative;
  margin-left: 14px;
}

.tracking-timeline::before {
  content: '';
  position: absolute;
  left: 12px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e2e8f0;
}

.timeline-step {
  position: relative;
  padding-left: 45px;
  margin-bottom: 18px;
}

.timeline-step:last-child {
  margin-bottom: 0;
}

.step-icon {
  position: absolute;
  left: 0;
  top: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  transition: all 0.3s ease;
}

.timeline-step.completed .step-icon {
  background: #10b981;
  border-color: #10b981;
  color: white;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.timeline-step.current .step-icon {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
  animation: pulse 2s infinite;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.step-content {
  margin-top: -2px;
}

.step-title {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
  font-size: 13px;
}

.timeline-step.completed .step-title {
  color: #059669;
}

.timeline-step.current .step-title {
  color: #2563eb;
}

.step-time {
  font-size: 11px;
  color: #64748b;
  margin-bottom: 3px;
}

.step-note {
  font-size: 12px;
  color: #475569;
  margin-bottom: 3px;
}

.step-user {
  font-size: 10px;
  color: #64748b;
  font-style: italic;
}

.auto-refresh-info {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #e2e8f0;
  font-size: 11px;
  color: #64748b;
}

.auto-refresh-info .fas.spinning {
  animation: spin 1s linear infinite;
}

.refresh-btn-small {
  padding: 5px 10px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 10px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.refresh-btn-small:hover {
  background: #2563eb;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Status colors for pickup tracking */
.bg-purple-100.text-purple-800 {
  background-color: #e9d5ff;
  color: #6b21a8;
}

.bg-orange-100.text-orange-800 {
  background-color: #fed7aa;
  color: #9a3412;
}

.bg-green-100.text-green-800 {
  background-color: #dcfce7;
  color: #166534;
}

/* ========================================
   STATUS CLASSES
   ======================================== */

.status-pending {
  background-color: #fef3c7;
  color: #92400e;
  border-left: 4px solid #f59e0b;
}

.status-processing {
  background-color: #dbeafe;
  color: #1e40af;
  border-left: 4px solid #3b82f6;
}

.status-completed {
  background-color: #d1fae5;
  color: #065f46;
  border-left: 4px solid #10b981;
}

.status-delivered {
  background-color: #f3e8ff;
  color: #6b21a8;
  border-left: 4px solid #8b5cf6;
}

/* Status badges */
.status-badge.status-pending {
  background-color: #fbbf24;
  color: white;
}

.status-badge.status-processing {
  background-color: #3b82f6;
  color: white;
}

.status-badge.status-completed {
  background-color: #10b981;
  color: white;
}

.status-badge.status-delivered {
  background-color: #8b5cf6;
  color: white;
}

/* ========================================
   ENHANCED PAYMENT MODAL STYLES
   ======================================== */

.payment-modal {
  max-width: 550px;
  width: 90%;
}

.payment-order-info {
  background: #f8fafc;
  padding: 18px;
  border-radius: 10px;
  margin-bottom: 22px;
  border: 1px solid #e2e8f0;
}

.payment-order-info .info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.payment-order-info .total-amount {
  font-weight: bold;
  color: #059669;
  font-size: 20px;
}

.payment-methods h4 {
  margin-bottom: 14px;
  color: #374151;
  font-weight: 600;
}

.method-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 22px;
}

.method-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 18px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.method-btn:hover {
  border-color: #3b82f6;
  background: #f8fafc;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.method-btn.active {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #1d4ed8;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}

.method-btn i {
  font-size: 28px;
  margin-bottom: 10px;
}

.cash-payment-section {
  background: #f0f9ff;
  padding: 18px;
  border-radius: 10px;
  margin-top: 18px;
  border: 1px solid #bae6fd;
}

.cash-payment-section h4 {
  margin-bottom: 14px;
  color: #0369a1;
  font-weight: 600;
}

.cash-input-group label {
  display: block;
  margin-bottom: 10px;
  font-weight: 500;
  color: #374151;
}

.cash-input-wrapper {
  position: relative;
  margin-bottom: 14px;
}

.currency-prefix {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-weight: 500;
}

.cash-input {
  width: 86%;
  padding: 14px 14px 14px 45px;
  border: 2px solid #d1d5db;
  border-radius: 8px;
  font-size: 17px;
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
  gap: 10px;
  margin-bottom: 14px;
}

.quick-amount-btn {
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
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

.change-calculation {
  margin-top: 14px;
}

.change-info {
  display: flex;
  align-items: center;
  padding: 14px;
  border-radius: 8px;
  font-weight: 500;
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
  margin-right: 10px;
  font-size: 16px;
}

.qris-payment-section {
  background: #f0f9ff;
  padding: 18px;
  border-radius: 10px;
  text-align: center;
  margin-top: 18px;
  border: 1px solid #bae6fd;
}

.qris-info i {
  font-size: 52px;
  color: #0369a1;
  margin-bottom: 14px;
}

.qris-info p {
  margin: 10px 0;
  color: #374151;
}

.qris-note {
  font-size: 14px;
  color: #6b7280;
  font-style: italic;
}

/* ========================================
   ENHANCED ACTION BUTTONS
   ======================================== */

.btn-action.pay-now {
  background: linear-gradient(135deg, #059669, #10b981);
  color: white;
  border: none;
  padding: 12px 18px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-action.pay-now:hover:not(:disabled) {
  background: linear-gradient(135deg, #047857, #059669);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4);
}

/* ========================================
   MODAL FOOTER STYLES
   ======================================== */

.modal-footer {
  display: flex;
  gap: 14px;
  justify-content: flex-end;
  margin-top: 26px;
  padding-top: 18px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 12px 22px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
}

.btn-cancel:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-confirm {
  padding: 12px 22px;
  border: none;
  border-radius: 8px;
  background: #3b82f6;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 10px;
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

/* Spinner */
.btn-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-top: 2px solid transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 70px 20px;
  color: #6b7280;
}

.spinner {
  width: 45px;
  height: 45px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 18px;
}

/* ========================================
   RESPONSIVE STYLES
   ======================================== */

@media (max-width: 768px) {
  .quick-filter-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }
 
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
  }
 
  .order-tabs {
    flex-direction: column;
    align-items: stretch;
  }
 
  .tab-btn {
    margin-bottom: 6px;
  }
 
  .method-buttons {
    grid-template-columns: 1fr;
    gap: 10px;
  }
 
  .quick-amounts {
    grid-template-columns: repeat(2, 1fr);
  }
 
  .payment-modal {
    max-width: 95%;
    margin: 20px auto;
  }
 
  .modal-footer {
    flex-direction: column;
  }
 
  .btn-cancel,
  .btn-confirm {
    width: 100%;
    justify-content: center;
  }
 
  .tracking-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
 
  .auto-refresh-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
 
  .pickup-tracking-readonly {
    padding: 14px;
  }

  .service-type-badge {
    font-size: 10px;
    padding: 3px 8px;
  }
}

/* ========================================
   ADDITIONAL STYLES FROM ORIGINAL
   ======================================== */

.filter-section {
  margin-bottom: 24px;
}

.filter-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.filter-header h3 {
  color: #1e293b;
  margin-bottom: 4px;
}

.filter-header p {
  color: #64748b;
  font-size: 14px;
  margin-bottom: 16px;
}

.quick-filters h4 {
  color: #374151;
  margin-bottom: 12px;
  font-size: 16px;
}

.quick-filter-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.quick-filter-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-filter-btn:hover {
  border-color: #3b82f6;
  background: #f8fafc;
}

.quick-filter-btn.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.filter-icon {
  font-size: 20px;
}

.filter-text {
  display: flex;
  flex-direction: column;
}

.filter-title {
  font-weight: 600;
  color: #1e293b;
}

.filter-desc {
  font-size: 12px;
  color: #64748b;
}

.selected-period {
  background: #f8fafc;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
}

.period-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.period-label {
  font-weight: 500;
  color: #374151;
}

.period-value {
  color: #059669;
  font-weight: 600;
}

.period-stats {
  display: flex;
  gap: 16px;
}

.stats-item {
  font-size: 14px;
  color: #64748b;
}

.filter-actions {
  display: flex;
  gap: 12px;
}

.filter-btn {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-btn.primary {
  background: #3b82f6;
  color: white;
}

.filter-btn.primary:hover:not(:disabled) {
  background: #2563eb;
}

.filter-btn.secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #d1d5db;
}

.filter-btn.secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.filter-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.stat-icon.blue {
  background: #dbeafe;
  color: #1e40af;
}

.stat-icon.orange {
  background: #fed7aa;
  color: #c2410c;
}

.stat-icon.green {
  background: #d1fae5;
  color: #059669;
}

.stat-icon.success {
  background: #f3e8ff;
  color: #7c3aed;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.stat-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 2px;
}

.stat-period {
  font-size: 12px;
  color: #64748b;
}


</style>