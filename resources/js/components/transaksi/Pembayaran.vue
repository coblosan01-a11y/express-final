<!-- src/components/transaksi/Pembayaran.vue - SIMPLIFIED: Direct QZ Tray Print Only -->
<template>
  <div class="payment-overlay" @click.self="closeModal">
    <div class="payment-modal">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <h2><img src="../../assets/img/Wallet.png" alt="Logo" class="wallet-logo"/> Pembayaran</h2>
        </div>
        <div class="header-actions">
          <button class="close-btn" @click="closeModal" :disabled="isProcessing">✕</button>
        </div>
      </div>

      <!-- Content -->
      <div class="modal-content">
        <!-- Left Panel - Form Section -->
        <div class="form-section">
          <!-- Customer Info Display (Read-only) -->
          <div class="input-group">
            <label>Customer</label>
            <div class="customer-info-display">
              <div class="customer-details">
                <div class="customer-avatar">{{ customerStore.customerInitials }}</div>
                <div class="customer-data">
                  <span class="customer-name">{{ customerStore.customerDisplayName }}</span>
                  <span class="customer-phone">{{ customerStore.customerPhone }}</span>
                </div>
              </div>
              <span class="customer-status">✅ Terpilih</span>
            </div>
          </div>

          <!-- Payment Methods -->
          <div class="input-group">
            <label><img src="../../assets/img/Wallet.png" alt="Logo" class="wallet-satu-logo"/>Metode Pembayaran</label>
            <div class="payment-methods">
              <div 
                v-for="method in paymentMethods" 
                :key="method.id"
                class="method-card"
                :class="{ active: selectedMethod === method.id }"
                @click="selectPaymentMethod(method.id)"
                :disabled="isProcessing"
              >
                <span class="method-icon">{{ method.icon }}</span>
                <span class="method-name">{{ method.name }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Input -->
          <div v-if="selectedMethod" class="payment-input-section">
            <div class="input-group">
              <label>Bayar</label>
              <div class="amount-wrapper">
                <span class="currency">Rp</span>
                <input 
                  v-model.number="receivedAmount"
                  type="number"
                  class="input-field amount-field"
                  :placeholder="finalTotal.toString()"
                  min="0"
                  :disabled="isProcessing"
                />
              </div>
              <button 
                class="auto-btn" 
                @click="autoFill"
                :disabled="isProcessing"
              >
                📋 Isi Pas
              </button>
            </div>

            <!-- Change Display -->
            <div v-if="selectedMethod === 'tunai' && receivedAmount > 0" class="change-info">
              <div class="change-display" :class="{ insufficient: changeAmount < 0 }">
                <span>Kembalian:</span>
                <span class="change-amount" :class="{ negative: changeAmount < 0 }">
                  Rp {{ formatCurrency(changeAmount) }}
                </span>
              </div>
              <div v-if="changeAmount < 0" class="alert">
                ⚠️ Jumlah kurang dari total pembayaran
              </div>
            </div>

            <div class="input-group">
              <label>📝 Catatan</label>
              <input 
                v-model="notes"
                class="input-field"
                placeholder="Catatan pembayaran..."
                :disabled="isProcessing"
              />
            </div>
          </div>
        </div>

        <!-- Right Panel - Summary Section -->
        <div class="summary-section">
          <h3>📋 Ringkasan Pesanan</h3>
          
          <!-- QZ Tray Status Indicator -->
          <div class="qz-status-indicator" :class="qzTrayStatus.class">
            <span class="status-icon">{{ qzTrayStatus.icon }}</span>
            <span class="status-text">{{ qzTrayStatus.text }}</span>
          </div>
          
          <!-- Pickup Service Summary (if exists) -->
          <div v-if="pickupService" class="pickup-payment-summary">
            <div class="pickup-header">
              <h4>🚚 Layanan Pickup</h4>
            </div>
            <div class="pickup-details">
              <div class="pickup-item">
                <span class="pickup-type">{{ pickupService.serviceName }}</span>
                <span class="pickup-cost">Rp {{ formatCurrency(pickupService.totalCost) }}</span>
              </div>
              <div class="pickup-info-compact">
                <small class="pickup-schedule">📅 {{ formatPickupSchedule(pickupService.pickupDate, pickupService.pickupTime) }}</small>
                <small class="pickup-distance">📏 {{ pickupService.jarak }} km</small>
              </div>
            </div>
          </div>
          
          <!-- Order Items Summary -->
          <div class="order-summary">
            <!-- Empty state when no laundry and no pickup -->
            <div v-if="orderStore.daftarPesanan.length === 0 && !pickupService" class="empty-order">
              <span>Belum ada layanan</span>
              <small>Tambahkan layanan laundry atau pickup</small>
            </div>
            
            <!-- Laundry items with beautiful card styling (matching pickup style) -->
            <div v-else-if="orderStore.daftarPesanan.length > 0" class="laundry-payment-summary">
              <div class="laundry-header">
                <h4>🧺 Layanan Laundry</h4>
              </div>
              <div class="laundry-items-list">
                <div v-for="(pesanan, index) in orderStore.daftarPesanan" :key="pesanan.id" class="laundry-item">
                  <div class="laundry-item-header">
                    <span class="laundry-item-name">{{ pesanan.layanan.nama }}</span>
                    <span class="laundry-item-total">Rp {{ formatCurrency(pesanan.total) }}</span>
                  </div>
                  <div class="laundry-item-details">
                    <div v-for="(qty, satuan) in pesanan.kuantitas" :key="satuan" class="laundry-qty-detail">
                      <strong>{{ qty }}</strong>
                      <span class="unit">{{ satuan }}</span>
                      <span class="price">x Rp {{ formatCurrency(pesanan.layanan?.hargaPerUnit?.[satuan] || 0) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="summary-card">
            <div v-if="orderStore.daftarPesanan.length > 0" class="summary-row">
              <span>Subtotal Layanan:</span>
              <span class="amount">Rp {{ formatCurrency(orderStore.grandTotal) }}</span>
            </div>
            <div v-if="pickupService" class="summary-row">
              <span>Biaya Pickup:</span>
              <span class="amount">Rp {{ formatCurrency(pickupService.totalCost) }}</span>
            </div>
            
            <div class="summary-row total-row">
              <span>Total Pembayaran:</span>
              <span class="amount total-amount">Rp {{ formatCurrency(finalTotal) }}</span>
            </div>
            
            <div v-if="selectedMethod" class="summary-row">
              <span>Metode:</span>
              <span>{{ selectedMethodName || '-' }}</span>
            </div>
            <div v-if="selectedMethod && receivedAmount > 0" class="summary-row">
              <span>Diterima:</span>
              <span class="amount">Rp {{ formatCurrency(receivedAmount) }}</span>
            </div>
            <div v-if="selectedMethod === 'tunai' && receivedAmount > 0" class="summary-row">
              <span>Kembalian:</span>
              <span class="amount" :class="{ negative: changeAmount < 0 }">
                Rp {{ formatCurrency(changeAmount) }}
              </span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="action-buttons">
            <button 
              class="btn-primary"
              @click="processPayment"
              :disabled="!canProcess || isProcessing"
            >
              <span v-if="isProcessing" class="processing-spinner"></span>
              {{ isProcessing ? 'Memproses...' : 'Proses Pembayaran ✅' }}
            </button>
            <button 
              class="btn-secondary" 
              @click="closeModal"
              :disabled="isProcessing"
            >
              Batal ❌
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isProcessing" class="processing-overlay">
      <div class="processing-content">
        <div class="processing-spinner-large"></div>
        <p>{{ processingMessage }}</p>
        <small>Harap tunggu, jangan tutup halaman</small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import Swal from 'sweetalert2'
import axios from 'axios'

// Import print helpers
import { printHelpers } from '../../utils/PrintHelpers.js'

// Import stores
import { useOrderStore } from '../../stores/PiniaOrder.js'
import { useCustomerStore } from '../../stores/PiniaCustomer.js'

// Stores
const orderStore = useOrderStore()
const customerStore = useCustomerStore()

// Props
const props = defineProps({
  pickupService: {
    type: Object,
    default: null
  }
})

// Events
const emit = defineEmits(['close-modal', 'order-completed'])

// Reactive Data
const selectedMethod = ref('')
const receivedAmount = ref(0)
const notes = ref('')
const isProcessing = ref(false)
const processingMessage = ref('Memproses pembayaran...')
const savedTransactionData = ref(null)

// Kasir detection
const detectedKasirName = ref('')

// QZ Tray status tracking
const qzTrayConnected = ref(false)
const qzTrayChecking = ref(false)

// Configuration
const laundryConfig = ref({
  name: 'Fresh Laundry'
})

const paymentMethods = ref([
  { id: 'tunai', name: 'Tunai', icon: '💵' },
  { id: 'qris', name: 'QRIS', icon: '📱' },
  { id: 'bayar-nanti', name: 'Bayar Nanti', icon: '⏰' }
])

// QZ Tray Status Computed
const qzTrayStatus = computed(() => {
  if (qzTrayChecking.value) {
    return {
      class: 'checking',
      icon: '🔄',
      text: 'Checking QZ Tray...'
    }
  } else if (qzTrayConnected.value) {
    return {
      class: 'connected',
      icon: '🟢',
      text: 'QZ Tray Ready'
    }
  } else {
    return {
      class: 'disconnected',
      icon: '🔴',
      text: 'QZ Tray Not Available'
    }
  }
})

// Transaction type information
const transactionTypeInfo = computed(() => {
  const hasLaundry = orderStore.daftarPesanan.length > 0
  const hasPickup = props.pickupService !== null
  
  if (hasLaundry && hasPickup) {
    return {
      icon: '🧺🚚',
      name: 'Kombinasi',
      description: 'Laundry + Pickup Service'
    }
  } else if (hasLaundry && !hasPickup) {
    return {
      icon: '🧺',
      name: 'Laundry Only',
      description: 'Customer antar-jemput sendiri'
    }
  } else if (!hasLaundry && hasPickup) {
    return {
      icon: '🚚',
      name: 'Pickup Only',
      description: 'Hanya layanan pickup'
    }
  }
})

// Computed Properties
const finalTotal = computed(() => {
  let total = 0
  
  if (orderStore.daftarPesanan.length > 0) {
    total += orderStore.grandTotal
  }
  
  if (props.pickupService && props.pickupService.totalCost > 0) {
    total += props.pickupService.totalCost
  }
  
  return total
})

const selectedMethodName = computed(() => {
  const method = paymentMethods.value.find(m => m.id === selectedMethod.value)
  return method ? method.name : ''
})

const changeAmount = computed(() => {
  if (selectedMethod.value === 'tunai') {
    return receivedAmount.value - finalTotal.value
  }
  return 0
})

const canProcess = computed(() => {
  if (!customerStore.customerPhone.trim() || !selectedMethod.value) {
    return false
  }

  const hasLaundryItems = orderStore.daftarPesanan.length > 0
  const hasPickupService = props.pickupService !== null
  const isValidTransaction = finalTotal.value >= 0
  
  if (!hasLaundryItems && !hasPickupService && !isValidTransaction) {
    return false
  }

  if (selectedMethod.value === 'tunai' && receivedAmount.value < finalTotal.value) {
    return false
  }

  if (selectedMethod.value === 'qris' && finalTotal.value > 0 && receivedAmount.value <= 0) {
    return false
  }

  if (selectedMethod.value === 'bayar-nanti') {
    return true
  }

  return true
})

// Watchers
watch(() => finalTotal.value, (newTotal) => {
  if (newTotal > 0 && receivedAmount.value === 0) {
    receivedAmount.value = newTotal
  }
})

// Helper functions
const getCurrentUserId = () => {
  try {
    const userData = JSON.parse(sessionStorage.getItem('user') || localStorage.getItem('user') || '{}')
    return userData.id || 1
  } catch (error) {
    console.error('Error getting user ID:', error)
    return 1
  }
}

const detectAndSaveKasirName = () => {
  const detectedKasir = printHelpers.getKasirName()
  detectedKasirName.value = detectedKasir
  return detectedKasir
}

/**
 * Silent QZ Tray Check
 */
const initializeQzTray = async () => {
  console.log('🔌 Silent QZ Tray check...')
  
  qzTrayChecking.value = true
  
  try {
    const qzTest = await printHelpers.testQzConnection()
    if (qzTest.success) {
      qzTrayConnected.value = true
      console.log('✅ QZ Tray ready:', qzTest.selectedPrinter)
    } else {
      qzTrayConnected.value = false
      console.warn('⚠️ QZ Tray not available')
    }
  } catch (error) {
    qzTrayConnected.value = false
    console.warn('⚠️ QZ Tray check failed:', error.message)
  } finally {
    qzTrayChecking.value = false
  }
}

// Payment Methods
const selectPaymentMethod = (methodId) => {
  if (isProcessing.value) return
  
  selectedMethod.value = methodId
  if (methodId === 'qris' || methodId === 'tunai') {
    receivedAmount.value = finalTotal.value
  } else if (methodId === 'bayar-nanti') {
    receivedAmount.value = 0
  }
}

const autoFill = () => {
  if (isProcessing.value) return
  receivedAmount.value = finalTotal.value
}

const formatCurrency = (amount) => {
  return printHelpers.formatCurrency(amount)
}

const formatMethod = (method) => {
  return printHelpers.formatPaymentMethod(method)
}

const formatPickupSchedule = (date, time) => {
  return printHelpers.formatPickupSchedule(date, time)
}

// Payment Processing
const processPayment = () => {
  if (!canProcess.value) {
    let errorMessage = 'Lengkapi data pembayaran dengan benar'
    
    if (!customerStore.customerPhone.trim()) {
      errorMessage = 'Customer belum dipilih'
    } else if (!selectedMethod.value) {
      errorMessage = 'Pilih metode pembayaran'
    } else if (orderStore.daftarPesanan.length === 0 && !props.pickupService) {
      errorMessage = 'Minimal harus ada satu layanan (Laundry atau Pickup)'
    } else if (selectedMethod.value === 'tunai' && receivedAmount.value < finalTotal.value) {
      errorMessage = 'Jumlah bayar kurang dari total'
    } else if (selectedMethod.value === 'qris' && receivedAmount.value <= 0) {
      errorMessage = 'Masukkan nominal pembayaran QRIS'
    }
    
    Swal.fire({
      title: 'Error!',
      text: errorMessage,
      icon: 'error',
      confirmButtonColor: '#ef4444'
    })
    return
  }

  const customerName = customerStore.customerDisplayName
  let confirmText = buildConfirmationText(customerName)

  Swal.fire({
    title: 'Konfirmasi Pembayaran',
    html: confirmText,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Proses',
    cancelButtonText: 'Batal',
    allowOutsideClick: false
  }).then((result) => {
    if (result.isConfirmed) {
      completeTransaction()
    }
  })
}

const buildConfirmationText = (customerName) => {
  const typeInfo = transactionTypeInfo.value
  const baseInfo = `
    <div style="text-align: left; font-family: Arial, sans-serif; line-height: 1.6;">
      <strong>Customer:</strong> ${customerName}<br>
      <strong>Telepon:</strong> ${customerStore.customerPhone}<br>
      <strong>Jenis Transaksi:</strong> ${typeInfo?.icon} ${typeInfo?.name}<br>
      <strong>Metode:</strong> ${formatMethod(selectedMethod.value)}<br>
  `
  
  let serviceInfo = ''
  
  if (orderStore.daftarPesanan.length > 0) {
    serviceInfo += `<strong>Subtotal Layanan:</strong> Rp ${formatCurrency(orderStore.grandTotal)}<br>`
  }
  
  if (props.pickupService && props.pickupService.totalCost > 0) {
    serviceInfo += `<strong>Biaya Pickup:</strong> Rp ${formatCurrency(props.pickupService.totalCost)}<br>`
  }
  
  if (!serviceInfo) {
    serviceInfo = '<strong>Layanan:</strong> Gratis<br>'
  }
  
  serviceInfo += `<strong>Total:</strong> Rp ${formatCurrency(finalTotal.value)}<br>`
  
  let paymentInfo = ''
  if (selectedMethod.value === 'tunai') {
    paymentInfo = `
      <strong>Diterima:</strong> Rp ${formatCurrency(receivedAmount.value)}<br>
      <strong>Kembalian:</strong> Rp ${formatCurrency(changeAmount.value)}
    `
  } else if (selectedMethod.value === 'qris') {
    paymentInfo = `<strong>Nominal:</strong> Rp ${formatCurrency(receivedAmount.value)}`
  } else if (selectedMethod.value === 'bayar-nanti') {
    paymentInfo = `<strong>Status:</strong> Menunggu pembayaran`
  }
  
  const notesInfo = notes.value ? `<br><strong>Catatan:</strong> ${notes.value}` : ''
  const typeNote = `<br><small style="color: #059669;"><strong>Catatan:</strong> ${typeInfo?.description}</small>`
  
  return baseInfo + serviceInfo + paymentInfo + notesInfo + typeNote + '</div>'
}

const completeTransaction = async () => {
  isProcessing.value = true
  processingMessage.value = 'Menyimpan transaksi...'

  try {
    console.log('💳 Starting transaction processing...')

    const transactionData = buildTransactionData()
    
    console.log('📤 Sending transaction to server...')
    
    const response = await axios.post('/api/transaksi/store', transactionData, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    console.log('📡 Server response:', response.data)

    if (response.data.success) {
      const savedTransaction = response.data.data
      console.log('✅ Transaction saved successfully:', savedTransaction)

      // Prepare data untuk printing
      savedTransactionData.value = {
        ...savedTransaction,
        kasir_name: detectedKasirName.value
      }

      // Show success & auto print
      await showSuccessAndPrint(savedTransaction)

      // Emit completion event
      emit('order-completed', savedTransaction)

    } else {
      throw new Error(response.data.message || 'Gagal menyimpan transaksi')
    }

  } catch (error) {
    console.error('❌ Transaction error:', error)
    await handleTransactionError(error)
  } finally {
    isProcessing.value = false
    processingMessage.value = 'Memproses pembayaran...'
  }
}

const buildTransactionData = () => {
  if (!customerStore.customerPhone || !customerStore.customerDisplayName) {
    throw new Error('Customer data tidak lengkap')
  }
  
  if (!selectedMethod.value) {
    throw new Error('Metode pembayaran belum dipilih')
  }
  
  const ensureStringOrNull = (value) => {
    if (value === null || value === undefined || value === '') {
      return null
    }
    return String(value).trim()
  }
  
  const customerData = {
    id: customerStore.customerInfo?.id || null,
    name: ensureStringOrNull(customerStore.customerDisplayName),
    phone: ensureStringOrNull(customerStore.customerPhone)
  }
  
  const paymentData = {
    method: selectedMethod.value,
    total_amount: parseFloat(finalTotal.value) || 0,
    jumlah_bayar: parseFloat(receivedAmount.value) || 0,
    kembalian: parseFloat(changeAmount.value) || 0,
    subtotal_layanan: parseFloat(orderStore.grandTotal) || 0,
    biaya_pickup: props.pickupService ? (parseFloat(props.pickupService.totalCost) || 0) : 0
  }
  
  if (ensureStringOrNull(notes.value)) {
    paymentData.notes = ensureStringOrNull(notes.value)
  }
  
  const itemsData = orderStore.daftarPesanan.map(pesanan => {
    const item = {
      layanan_id: pesanan.layanan?.id || null,
      layanan_nama: ensureStringOrNull(pesanan.layanan?.nama) || 'Unknown Service',
      kuantitas: pesanan.kuantitas || {},
      harga_satuan: pesanan.layanan?.hargaPerUnit || {},
      subtotal: parseFloat(pesanan.total) || 0
    }
    
    if (ensureStringOrNull(pesanan.catatan)) {
      item.catatan = ensureStringOrNull(pesanan.catatan)
    }
    
    return item
  })
  
  let pickupServiceData = null
  
  if (props.pickupService) {
    const today = new Date().toISOString().split('T')[0]
    
    pickupServiceData = {
      setting_id: props.pickupService.settingId || null,
      service_name: ensureStringOrNull(props.pickupService.serviceName) || 'Layanan Pickup Standar',
      service_type: ensureStringOrNull(props.pickupService.serviceType) || 'pickup_only',
      jarak: Math.max(parseFloat(props.pickupService.jarak) || 1, 0.1),
      pickup_date: props.pickupService.pickupDate || today,
      pickup_time: ensureStringOrNull(props.pickupService.pickupTime) || '09:00',
      total_cost: Math.max(parseFloat(props.pickupService.totalCost) || 0, 0)
    }
    
    if (ensureStringOrNull(props.pickupService.rentang)) {
      pickupServiceData.rentang = ensureStringOrNull(props.pickupService.rentang)
    }
    
    if (parseFloat(props.pickupService.baseCost) > 0) {
      pickupServiceData.base_cost = parseFloat(props.pickupService.baseCost)
    }
    
    if (ensureStringOrNull(props.pickupService.specialInstructions)) {
      pickupServiceData.special_instructions = ensureStringOrNull(props.pickupService.specialInstructions)
    }
  }
  
  const transactionData = {
    customer: customerData,
    payment: paymentData,
    tanggal_transaksi: new Date().toISOString(),
    status_transaksi: selectedMethod.value === 'bayar-nanti' ? 'pending' : 'sukses',
    created_by: getCurrentUserId(),
    kasir_name: detectedKasirName.value
  }
  
  if (itemsData.length > 0) {
    transactionData.items = itemsData
  }
  
  if (pickupServiceData) {
    transactionData.pickup_service = pickupServiceData
  }
  
  return transactionData
}

/**
 * 🚀 SIMPLIFIED: Show success and direct print
 */
const showSuccessAndPrint = async (transaction) => {
  const typeInfo = transactionTypeInfo.value
  
  // Show success message
  Swal.fire({
    title: `Transaksi Berhasil! ${typeInfo?.icon || '🎉'}`,
    html: `
      <div style="text-align: left; font-family: Arial, sans-serif; line-height: 1.6;">
        <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #22c55e;">
          <p style="margin: 0; font-weight: 600; color: #059669;">${typeInfo?.icon} ${typeInfo?.name}</p>
          <small style="color: #16a34a;">${typeInfo?.description}</small>
        </div>
        
        <p><strong>🎫 Kode:</strong> ${transaction.kode_transaksi}</p>
        <p><strong>👤 Customer:</strong> ${transaction.customer_name}</p>
        <p><strong>💰 Total:</strong> Rp ${formatCurrency(transaction.total_amount)}</p>
        <p><strong>💳 Metode:</strong> ${formatMethod(transaction.metode_pembayaran)}</p>
        <p><strong>📊 Status:</strong> ${transaction.status_transaksi === 'sukses' ? 'LUNAS' : 'PENDING'}</p>
      </div>
    `,
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
  })

  // Wait a bit then direct print
  await nextTick()
  
  // Change processing message
  processingMessage.value = 'Mencetak struk...'
  
  // Direct print via QZ Tray
  await directPrint()
}

/**
 * 🚀 DIRECT PRINT - No dialog, pure QZ Tray
 */
const directPrint = async () => {
  if (!savedTransactionData.value) {
    console.error('❌ No transaction data to print')
    return
  }

  try {
    console.log('🖨️ Direct printing via QZ Tray...')

    // Direct call to printReceipt with QZ Tray
    const printResult = await printHelpers.printReceipt(savedTransactionData.value, {
      useQzTray: true,
      laundryInfo: laundryConfig.value,
      printerName: null,
      copies: 1
    })
    
    if (printResult.success) {
      console.log('✅ Print success:', printResult)
      
      // Show brief success notification
      Swal.fire({
        title: 'Struk Dicetak! 🖨️',
        text: 'Transaksi selesai',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })

      // Close modal after print
      setTimeout(() => {
        closeModal()
      }, 1500)

    } else {
      throw new Error(printResult.message || 'Print gagal')
    }
    
  } catch (error) {
    console.error('❌ Print error:', error)
    
    // Show error but don't block closing
    Swal.fire({
      title: 'Print Gagal ⚠️',
      html: `
        <div style="text-align: left;">
          <p><strong>Error:</strong> ${error.message}</p>
          <hr>
          <p><strong>Kemungkinan penyebab:</strong></p>
          <ul style="font-size: 12px;">
            <li>QZ Tray tidak running</li>
            <li>Printer tidak terdeteksi</li>
            <li>Koneksi printer bermasalah</li>
          </ul>
          <p style="color: #059669; font-weight: 600;">✅ Transaksi tetap tersimpan</p>
          <small>Anda bisa print ulang dari menu riwayat transaksi</small>
        </div>
      `,
      icon: 'warning',
      confirmButtonText: 'OK',
      confirmButtonColor: '#f59e0b'
    }).then(() => {
      closeModal()
    })
  }
}

const handleTransactionError = async (error) => {
  console.error('❌ Full transaction error:', error)
  
  let errorMessage = 'Gagal menyimpan transaksi ke database'
  let detailErrors = []
  
  if (error.response?.data) {
    const errorData = error.response.data
    
    if (errorData.errors) {
      detailErrors = Object.entries(errorData.errors).map(([field, messages]) => {
        return `<strong>${field}:</strong> ${Array.isArray(messages) ? messages.join(', ') : messages}`
      })
      errorMessage = `Validation Error`
    } else if (errorData.message) {
      errorMessage = errorData.message
    }
  } else if (error.message) {
    errorMessage = error.message
  }

  await Swal.fire({
    title: `Error ${error.response?.status || 'Unknown'} ❌`,
    html: `
      <div style="text-align: left; font-family: Arial, sans-serif;">
        <p><strong>Pesan Error:</strong></p>
        <div style="background: #fee; padding: 10px; border-left: 4px solid #f56565; font-size: 12px;">${errorMessage}</div>
        ${detailErrors.length > 0 ? `
        <br>
        <p><strong>Detail:</strong></p>
        <div style="background: #fef7f0; padding: 10px; border-left: 4px solid #f59e0b; font-size: 12px;">
          ${detailErrors.join('<br>')}
        </div>
        ` : ''}
      </div>
    `,
    icon: 'error',
    confirmButtonText: 'OK',
    confirmButtonColor: '#ef4444'
  })
}

const closeModal = () => {
  if (isProcessing.value) {
    Swal.fire({
      title: 'Tunggu!',
      text: 'Transaksi sedang diproses, harap tunggu...',
      icon: 'warning',
      timer: 2000
    })
    return
  }

  // Reset all data
  selectedMethod.value = ''
  receivedAmount.value = 0
  notes.value = ''
  savedTransactionData.value = null
  isProcessing.value = false
  qzTrayConnected.value = false
  qzTrayChecking.value = false
  detectedKasirName.value = ''
  
  emit('close-modal')
}

// Lifecycle
onMounted(async () => {
  console.log('💳 Pembayaran component mounted (SIMPLIFIED - Direct Print)')
  console.log('👤 Customer:', customerStore.customerDisplayName)
  console.log('📞 Phone:', customerStore.customerPhone)
  console.log('🧺 Orders:', orderStore.daftarPesanan.length)
  console.log('🚚 Pickup:', props.pickupService ? 'Yes' : 'No')
  console.log('💰 Total:', finalTotal.value)

  // Detect kasir
  const kasirName = detectAndSaveKasirName()
  console.log('✅ Kasir:', kasirName)

  // Validate customer
  if (!customerStore.customerPhone.trim()) {
    await Swal.fire({
      title: 'Error!',
      text: 'Customer belum dipilih',
      icon: 'error',
      confirmButtonText: 'OK'
    })
    emit('close-modal')
    return
  }

  // Validate services
  const hasOrderItems = orderStore.daftarPesanan.length > 0
  const hasPickupService = props.pickupService !== null
  const hasValidTotal = finalTotal.value >= 0
  
  if (!hasOrderItems && !hasPickupService && !hasValidTotal) {
    await Swal.fire({
      title: 'Info!',
      text: 'Tidak ada layanan yang valid untuk diproses',
      icon: 'info',
      confirmButtonText: 'OK'
    })
    emit('close-modal')
    return
  }

  // Auto-fill amount
  if (finalTotal.value > 0 && receivedAmount.value === 0) {
    receivedAmount.value = finalTotal.value
  }

  // Silent QZ Tray check
  await initializeQzTray()

  console.log('✅ Ready for payment')
})
</script>

<style scoped>
@import '../../assets/css/Pembayaran.css';

/* Simplified styles - removed QZ tools button styles */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* QZ Status Indicator */
.qz-status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 12px;
  border: 1px solid;
  transition: all 0.3s ease;
}

.qz-status-indicator.connected {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-color: #22c55e;
  color: #166534;
}

.qz-status-indicator.disconnected {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-color: #ef4444;
  color: #991b1b;
}

.qz-status-indicator.checking {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #f59e0b;
  color: #92400e;
}

.status-icon {
  font-size: 14px;
  animation: pulse 2s infinite;
}

.status-text {
  font-size: 11px;
  font-weight: 500;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Processing overlay */
.processing-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.processing-content {
  background: white;
  padding: 40px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  max-width: 400px;
}

.processing-spinner-large {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #10b981;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.processing-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-top: 2px solid transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 8px;
}

/* Alert styles */
.alert {
  background: #fef2f2;
  color: #dc2626;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  margin-top: 4px;
  border-left: 4px solid #dc2626;
}

.change-info .insufficient {
  background: #fef2f2;
  border-color: #fecaca;
}

.change-amount.negative {
  color: #dc2626;
  font-weight: 700;
}

/* Summary card */
.summary-card {
  background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
  border: 1px solid #f59e0b;
  border-radius: 12px;
  padding: 16px;
  margin: 16px 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 14px;
}

.summary-row.total-row {
  border-top: 2px solid #f59e0b;
  margin-top: 8px;
  padding-top: 12px;
  font-size: 16px;
  font-weight: 700;
}

.total-amount {
  color: #92400e;
  font-weight: 700;
}

/* Button states */
.btn-primary:disabled {
  background: #d1d5db;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-primary:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

/* Pickup display */
.pickup-payment-summary {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #0ea5e9;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 16px;
}

.pickup-header h4 {
  margin: 0 0 8px 0;
  color: #0c4a6e;
  font-size: 14px;
}

.pickup-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.pickup-type {
  font-weight: 600;
  color: #0c4a6e;
}

.pickup-cost {
  font-weight: 700;
  color: #0ea5e9;
}

.pickup-info-compact {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.pickup-schedule,
.pickup-distance {
  font-size: 11px;
  color: #64748b;
  background: rgba(255, 255, 255, 0.5);
  padding: 2px 6px;
  border-radius: 4px;
  width: fit-content;
}

/* Payment method cards */
.payment-methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 8px;
  margin-top: 8px;
}

.method-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: white;
}

.method-card:hover {
  border-color: #3b82f6;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.method-card.active {
  border-color: #10b981;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.method-icon {
  font-size: 24px;
  margin-bottom: 4px;
}

.method-name {
  font-size: 12px;
  font-weight: 600;
  text-align: center;
}

/* Customer info */
.customer-info-display {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.customer-details {
  display: flex;
  align-items: center;
  gap: 12px;
}

.customer-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
}

.customer-data {
  display: flex;
  flex-direction: column;
}

.customer-name {
  font-weight: 600;
  color: #1f2937;
}

.customer-phone {
  font-size: 12px;
  color: #6b7280;
}

.customer-status {
  background: #10b981;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

/* Amount input */
.amount-wrapper {
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
  background: white;
}

.currency {
  background: #f3f4f6;
  padding: 10px 12px;
  font-weight: 600;
  color: #374151;
  border-right: 1px solid #e5e7eb;
}

.amount-field {
  border: none !important;
  flex: 1;
  padding: 10px 12px;
  font-size: 16px;
  font-weight: 600;
}

.amount-field:focus {
  outline: none;
  box-shadow: none;
}

.auto-btn {
  margin-left: 8px;
  padding: 8px 12px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.auto-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

/* Order summary */
.order-summary {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
}

.empty-order {
  text-align: center;
  padding: 20px;
  color: #6b7280;
}

.empty-order span {
  display: block;
  font-weight: 600;
  margin-bottom: 4px;
}

.order-item-compact {
  padding: 8px 0;
  border-bottom: 1px solid #e2e8f0;
}

.order-item-compact:last-child {
  border-bottom: none;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.item-name {
  font-weight: 600;
  color: #1f2937;
}

.item-price {
  font-weight: 700;
  color: #059669;
}

.item-details-compact {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.qty-badge-compact {
  background: #dbeafe;
  color: #1e40af;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .payment-modal {
    width: 95%;
    max-height: 95vh;
  }
  
  .modal-content {
    flex-direction: column;
  }
  
  .form-section,
  .summary-section {
    width: 100%;
  }
}
</style>
