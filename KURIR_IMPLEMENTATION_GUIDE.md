# 🚚 DashboardKurir Implementation Guide

## Quick Reference

### 1. Error Fix - COD Payment Null Handling

**Location:** `DashboardKurir.vue` → `processCODPayment()` function

**What Was Fixed:**
```javascript
// ❌ BEFORE - Would crash if codTask.value is null
Swal.fire({
  html: `<p>${codTask.value.kode_transaksi}</p>`
})

// ✅ AFTER - Safely handles null
if (!codTask.value || !codTask.value.id) {
  throw new Error('Task data not found. Please try again.')
}
// ... then use fallback values
orderCode: codTask.value.kode_transaksi || 'N/A'
```

---

### 2. Earnings Filter Usage

**In Template:**
```vue
<!-- Filter Buttons -->
<button 
  @click="earningsFilter = 'today'"
  :class="['filter-btn', { active: earningsFilter === 'today' }]"
>
  Hari Ini
</button>

<!-- Custom Date Range (conditionally shown) -->
<input v-if="earningsFilter === 'custom'" v-model="customStartDate" type="date">
<input v-if="earningsFilter === 'custom'" v-model="customEndDate" type="date">
```

**In Script:**
```javascript
// State
const earningsFilter = ref('today')
const customStartDate = ref(null)
const customEndDate = ref(null)

// Computed - Auto-filters transactions
const filteredCompletedTransactions = computed(() => {
  // Returns only completed transactions for selected period
})

// Computed - Total earnings
const todayEarnings = computed(() => {
  return filteredCompletedTransactions.value.reduce((total, tx) => {
    return total + (tx.biaya_pickup || 0)
  }, 0)
})

// Computed - Order count
const todayEarningsCount = computed(() => {
  return filteredCompletedTransactions.value.length
})

// Computed - Filter label
const earningsFilterLabel = computed(() => {
  if (earningsFilter.value === 'today') return 'Pendapatan Hari Ini'
  if (earningsFilter.value === 'week') return 'Pendapatan Minggu Ini'
  // ... etc
})
```

---

### 3. Print Earnings Function

**In DashboardKurir.vue:**
```javascript
import { printHelpers } from '../utils/PrintHelpers'

// Print function - called when Print button clicked
const printEarnings = () => {
  if (filteredCompletedTransactions.value.length === 0) {
    showErrorAlert('Tidak ada data untuk dicetak')
    return
  }

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
```

**In PrintHelpers.js:**
```javascript
printCourierEarnings(earningsData) {
  // 1. Validate QZ Tray connection
  if (!qz.websocket || qz.websocket.readyState !== 1) {
    alert('Printer tidak terhubung')
    return
  }

  // 2. Build receipt string with ESC/POS commands
  let receipt = this.qzConfig.thermal58mm.initialize
  receipt += this.qzConfig.thermal58mm.centerAlign
  receipt += "==================================\n"
  // ... add content

  // 3. Send to printer
  const config = [{ iceColor: 'black' }]
  const data = [{ type: 'raw', format: 'command', flavor: 'line', commands: [receipt] }]
  
  qz.print(config, data)
    .then(() => console.log('✅ Printed'))
    .catch((error) => alert('Error: ' + error.message))
}
```

---

## Data Flow Diagrams

### Earnings Filter Flow:
```
User clicks Filter Button
    ↓
earningsFilter ref changes
    ↓
filteredCompletedTransactions computed re-runs
    ↓
Filters transactions by date range
    ↓
todayEarnings computed re-calculates
todayEarningsCount computed re-calculates
earningsFilterLabel computed updates
    ↓
UI reactively updates with new values
```

### Print Flow:
```
User clicks Print Button
    ↓
printEarnings() function executes
    ↓
Validates:
  - Data exists
  - QZ Tray connected
    ↓
Prepares earningsData object with:
  - Filter info
  - Transactions
  - Totals
  - User info
  - Current date
    ↓
Calls printHelpers.printCourierEarnings()
    ↓
Builds ESC/POS thermal format
    ↓
Sends to QZ Tray
    ↓
QZ sends to thermal printer
    ↓
Paper prints and cuts
```

---

## Date Filtering Logic

### Today Filter:
```javascript
const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
// Returns transactions with updated_at >= startOfToday
```

### Week Filter:
```javascript
const startOfToday = new Date(...)
const startOfWeek = new Date(startOfToday)
startOfWeek.setDate(startOfToday.getDate() - startOfToday.getDay())
// Returns transactions with updated_at >= startOfWeek
```

### Custom Filter:
```javascript
const start = new Date(customStartDate.value)
const end = new Date(customEndDate.value)
end.setHours(23, 59, 59, 999)
// Returns transactions with updated_at >= start AND updated_at <= end
```

---

## Error Handling

### COD Payment Error:
```javascript
try {
  // ✓ Check if codTask exists
  if (!codTask.value || !codTask.value.id) {
    throw new Error('Task data not found. Please try again.')
  }
  
  // ✓ Use fallback values when accessing properties
  orderCode: codTask.value.kode_transaksi || 'N/A'
  
  // ✓ Process payment
  // ...
  
} catch (error) {
  console.error('Error processing COD payment:', error)
  showErrorAlert('Gagal memproses pembayaran COD')
} finally {
  isUpdatingStatus.value = false
}
```

### Print Error:
```javascript
try {
  // ✓ Validate data
  if (filteredCompletedTransactions.value.length === 0) {
    showErrorAlert('Tidak ada data untuk dicetak')
    return
  }
  
  // ✓ Prepare and send to printer
  // ...
  
} catch (error) {
  console.error('Error preparing earnings report:', error)
  alert('Error: ' + error.message)
}
```

---

## Responsive Design

### Desktop (> 768px):
- Filter buttons in row: [Today] [Week] [Custom] [Print]
- Date inputs side by side
- Summary in 2-column grid

### Mobile (≤ 768px):
- Filter buttons stacked vertically
- Print button full width
- Date inputs stacked
- Summary in 1-column grid

---

## CSS Classes Reference

| Class | Purpose |
|-------|---------|
| `.earnings-filter-section` | Main filter container |
| `.filter-buttons` | Button container (flex row) |
| `.filter-btn` | Individual filter button |
| `.filter-btn.active` | Active filter button state |
| `.filter-btn.print-btn` | Print button styling |
| `.custom-date-range` | Date picker container |
| `.date-input-group` | Single date input group |
| `.date-input` | Date input field |
| `.earnings-summary` | Summary display container |
| `.summary-item` | Individual summary item |

---

## Testing Scenarios

### Scenario 1: Fix Validation
```javascript
// Before - Crash
codTask.value = null
processCODPayment() // TypeError

// After - Graceful error
codTask.value = null
processCODPayment() // Shows: "Task data not found"
```

### Scenario 2: Today's Earnings
```javascript
// User clicks "Hari Ini" button
earningsFilter.value = 'today'

// filteredCompletedTransactions automatically filters
// Shows only completed orders from today
// todayEarnings recalculates with today's data only
```

### Scenario 3: Custom Date Range
```javascript
// User selects custom date range
customStartDate.value = '2025-12-05'
customEndDate.value = '2025-12-08'

// UI shows date picker
// filteredCompletedTransactions filters by date range
// Print button can now print range
```

### Scenario 4: Print Earnings
```javascript
// User clicks Print button
printEarnings()

// Validates data exists
// Prepares earnings data
// Calls printHelpers.printCourierEarnings()
// Thermal printer outputs formatted report
// Paper cuts automatically
```

---

## Performance Considerations

- **Reactive Updates**: All computed properties reactively update when filter changes
- **Client-side Filtering**: No API calls during filtering (instant updates)
- **Date Calculations**: Efficient JavaScript date operations
- **Print Async**: Print function doesn't block UI
- **Memory**: Transactions already loaded, no additional requests

---

## Dependencies

- `vue` 3.x (Composition API)
- `axios` (for API calls)
- `sweetalert2` (for modals)
- `qz-tray` (for thermal printer)

---

## Future Enhancements

- [ ] Export to CSV/PDF
- [ ] Email report option
- [ ] Multiple currency support
- [ ] Commission calculation options
- [ ] Bonus tier reporting
- [ ] Weekly/Monthly summaries
- [ ] Performance charts/graphs

