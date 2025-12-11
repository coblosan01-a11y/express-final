# 🧾 Receipt Format Documentation - With Unit Display

## 📋 Overview
Dokumentasi lengkap untuk format struk laundry dengan menampilkan **unit (kg/pcs)** dan **harga per unit** secara eksplisit.

---

## 🎯 Format Receipt yang Diterapkan

### Format Baru (Dengan Unit):
```
Cuci Kering Setrika
  2 kg x Rp 10,000/kg     Rp 20,000

Dry Clean Premium
  3 pcs x Rp 15,000/pcs   Rp 45,000

Setrika Saja
  5 kg x Rp 2,000/kg      Rp 10,000
```

---

## 📊 Data Structure Required

Setiap item dalam array `items` atau `details` harus memiliki field berikut:

```javascript
{
  layanan_nama: "Cuci Kering Setrika",  // Nama layanan
  qty: 2,                                 // Jumlah (quantity)
  unit: "kg",                             // Satuan: "kg", "pcs", "meter", dll
  subtotal: 20000                         // Total harga (qty × harga per unit)
}
```

### Field Names yang Didukung:

| Field | Alternative Names | Default |
|-------|------------------|---------|
| `qty` | `jumlah`, `quantity` | `1` |
| `unit` | `satuan` | `"pcs"` |
| `subtotal` | `total` | `0` |

---

## 🔧 Implementation Details

### 1. ESC/POS Thermal Printer (58mm)

**Location:** `generateOptimizedEscPos()` method

**Format:**
```javascript
// Line 1: Item name
"Cuci Kering Setrika\n"

// Line 2: Qty Unit x Price/Unit = Subtotal
"  2 kg x Rp10,000/kg    Rp20,000\n"
```

**Kode:**
```javascript
items.forEach((item) => {
  const qty = item.qty || item.jumlah || item.quantity || 1;
  const unit = item.unit || item.satuan || 'pcs';
  const subtotal = item.subtotal || item.total || 0;
  const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
  
  // Item name line
  commands.push({ 
    type: 'raw', 
    format: 'plain', 
    data: item.layanan_nama + '\n'
  });
  
  // Qty and price line
  const qtyLine = `  ${qty} ${unit} x Rp${formatCurrency(pricePerUnit)}/${unit}`;
  const subtotalStr = 'Rp' + formatCurrency(subtotal);
  commands.push({ 
    type: 'raw', 
    format: 'plain', 
    data: padLine(qtyLine, subtotalStr) + '\n'
  });
});
```

---

### 2. HTML Browser Print (58mm)

**Location:** `generateEnhanced58mmHTML()` method

**Format:**
```html
<div><strong>Cuci Kering Setrika</strong></div>
<div class="flex item-qty">
  <span>2 kg x Rp 10,000/kg</span>
  <span class="bold">Rp 20,000</span>
</div>
```

**CSS Styling:**
```css
.item-qty {
  font-size: 9px;
  color: #555;
  margin-left: 8px;
}
```

**Kode:**
```javascript
items.forEach((item) => {
  const qty = item.qty || item.jumlah || item.quantity || 1;
  const unit = item.unit || item.satuan || 'pcs';
  const subtotal = item.subtotal || item.total || 0;
  const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
  
  html += `<div><strong>${item.layanan_nama}</strong></div>`;
  html += `<div class="flex item-qty">
    <span>${qty} ${unit} x Rp ${formatCurrency(pricePerUnit)}/${unit}</span>
    <span class="bold">Rp ${formatCurrency(subtotal)}</span>
  </div>`;
});
```

---

### 3. Text Receipt (Alert/Console)

**Location:** `showTextReceipt()` method

**Format:**
```
LAYANAN LAUNDRY:
Cuci Kering Setrika
  2 kg x Rp 10,000/kg = Rp 20,000
Dry Clean Premium
  3 pcs x Rp 15,000/pcs = Rp 45,000
```

**Kode:**
```javascript
items.forEach((item) => {
  const qty = item.qty || item.jumlah || item.quantity || 1;
  const unit = item.unit || item.satuan || 'pcs';
  const subtotal = item.subtotal || item.total || 0;
  const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
  
  itemsText += `${item.layanan_nama}\n`;
  itemsText += `  ${qty} ${unit} x Rp ${formatCurrency(pricePerUnit)}/${unit} = Rp ${formatCurrency(subtotal)}\n`;
});
```

---

## 📝 Example Data

### Complete Transaction Example:

```javascript
const transactionData = {
  kode_transaksi: "TRX-001",
  customer_name: "Budi Santoso",
  customer_phone: "081234567890",
  kasir_name: "Ani Kasir",
  total_amount: 75000,
  metode_pembayaran: "tunai",
  jumlah_bayar: 80000,
  kembalian: 5000,
  status_transaksi: "sukses",
  subtotal_layanan: 70000,
  biaya_pickup: 5000,
  
  items: [
    {
      layanan_nama: "Cuci Kering Setrika",
      qty: 3,
      unit: "kg",
      subtotal: 30000  // 3 kg × Rp 10,000/kg
    },
    {
      layanan_nama: "Dry Clean Premium",
      qty: 2,
      unit: "pcs",
      subtotal: 30000  // 2 pcs × Rp 15,000/pcs
    },
    {
      layanan_nama: "Setrika Saja",
      qty: 5,
      unit: "kg",
      subtotal: 10000  // 5 kg × Rp 2,000/kg
    }
  ]
};
```

---

## 🖨️ Expected Output

### Thermal Printer (58mm):

```
================================
        FRESH LAUNDRY
================================
Kode: TRX-001
Tanggal: 02/12/2025
Kasir: Ani Kasir
--------------------------------
Customer: Budi Santoso
Phone: 081234567890
--------------------------------
LAYANAN LAUNDRY
Cuci Kering Setrika
  3 kg x Rp10,000/kg  Rp30,000
Dry Clean Premium
  2 pcs x Rp15,000/pcs Rp30,000
Setrika Saja
  5 kg x Rp2,000/kg    Rp10,000
--------------------------------
Subtotal Layanan:    Rp 70,000
Biaya Pickup:        Rp  5,000
================================
TOTAL:               Rp 75,000
================================
Metode: TUNAI
Status: Belum lunas
Bayar: Rp 80,000
Kembalian: Rp 5,000
================================
      STATUS: LUNAS
      Terima kasih!
Dicetak: 02/12/2025 14:30
    Oleh: Ani Kasir
================================
```

---

## ⚙️ Configuration

### Supported Units:
- `kg` - Kilogram (untuk berat cucian)
- `pcs` - Pieces (untuk item satuan)
- `meter` - Meter (untuk karpet/gorden)
- `pasang` - Pairs (untuk sepatu/tas)
- Custom units (apapun yang Anda set)

### Default Values:
- Jika `unit` tidak ada → default `"pcs"`
- Jika `qty` tidak ada → default `1`
- Jika `subtotal` tidak ada → default `0`

---

## 🧪 Testing

### Test Print Receipt:
```javascript
// Via console
window.megaTestPrint()

// Atau via code
await printHelpers.printTestReceipt()
```

### Test dengan Custom Data:
```javascript
const testData = {
  kode_transaksi: "TEST-001",
  customer_name: "Test Customer",
  customer_phone: "081234567890",
  total_amount: 50000,
  items: [
    {
      layanan_nama: "Test Laundry",
      qty: 2,
      unit: "kg",
      subtotal: 20000
    }
  ]
};

await printHelpers.printReceipt(testData);
```

---

## 🔄 Migration Guide

### Jika Data Lama Tidak Ada Field `unit`:

**Opsi 1: Update Database Schema**
```sql
ALTER TABLE transaction_details 
ADD COLUMN unit VARCHAR(10) DEFAULT 'pcs';
```

**Opsi 2: Set Default di Backend**
```php
// Laravel Controller
$item['unit'] = $item['unit'] ?? 'pcs';
```

**Opsi 3: Handle di Frontend (Already Implemented)**
```javascript
// Sudah otomatis di printHelpers
const unit = item.unit || item.satuan || 'pcs';
```

---

## 🎨 Customization

### Mengubah Format Display:

**Current Format:**
```
2 kg x Rp 10,000/kg     Rp 20,000
```

**Alternative Formats:**

1. **Without /unit suffix:**
```javascript
const qtyLine = `  ${qty} ${unit} x Rp${formatCurrency(pricePerUnit)}`;
// Output: "2 kg x Rp10,000"
```

2. **With @ symbol:**
```javascript
const qtyLine = `  ${qty} ${unit} @ Rp${formatCurrency(pricePerUnit)}/${unit}`;
// Output: "2 kg @ Rp10,000/kg"
```

3. **With equals sign:**
```javascript
const qtyLine = `  ${qty} ${unit} x Rp${formatCurrency(pricePerUnit)}/${unit} =`;
// Output: "2 kg x Rp10,000/kg ="
```

---

## 📱 Real-World Examples

### Example 1: Laundry Kiloan
```javascript
{
  layanan_nama: "Cuci Kering Reguler",
  qty: 5,
  unit: "kg",
  subtotal: 25000  // Rp 5,000/kg
}
```
**Output:** `5 kg x Rp 5,000/kg     Rp 25,000`

---

### Example 2: Dry Clean Satuan
```javascript
{
  layanan_nama: "Dry Clean Jas",
  qty: 2,
  unit: "pcs",
  subtotal: 40000  // Rp 20,000/pcs
}
```
**Output:** `2 pcs x Rp 20,000/pcs   Rp 40,000`

---

### Example 3: Cuci Karpet per Meter
```javascript
{
  layanan_nama: "Cuci Karpet",
  qty: 3,
  unit: "meter",
  subtotal: 45000  // Rp 15,000/meter
}
```
**Output:** `3 meter x Rp 15,000/meter   Rp 45,000`

---

### Example 4: Cuci Sepatu per Pasang
```javascript
{
  layanan_nama: "Cuci Sepatu Premium",
  qty: 1,
  unit: "pasang",
  subtotal: 35000  // Rp 35,000/pasang
}
```
**Output:** `1 pasang x Rp 35,000/pasang   Rp 35,000`

---

## 🐛 Troubleshooting

### Problem: Unit tidak muncul
**Solution:** Check data structure
```javascript
console.log('Item data:', item);
console.log('Unit field:', item.unit || item.satuan);
```

### Problem: Harga per unit salah
**Solution:** Check calculation
```javascript
console.log('Qty:', qty);
console.log('Subtotal:', subtotal);
console.log('Price per unit:', subtotal / qty);
```

### Problem: Format tidak rapi
**Solution:** Check column width
```javascript
// Adjust padding in padLine function
const padLine = (left, right, totalWidth = 32) => {
  // Thermal 58mm = 32 characters
}
```

---

## 📚 References

- **Main File:** `printHelpers-FIXED.js`
- **Methods Updated:**
  - `generateOptimizedEscPos()` - Line ~500-530
  - `generateEnhanced58mmHTML()` - Line ~750-780
  - `showTextReceipt()` - Line ~850-870
- **Test Functions:**
  - `printTestReceipt()` - Line ~1050
  - `printTestReceiptBrowser()` - Line ~1120

---

## ✅ Checklist Implementation

- [x] ESC/POS thermal printer support
- [x] HTML browser print support
- [x] Text receipt support
- [x] Unit field support (kg, pcs, meter, etc)
- [x] Price per unit calculation
- [x] Fallback to default unit 'pcs'
- [x] Multiple field name support (qty/jumlah/quantity)
- [x] Test data with various units
- [x] Documentation complete

---

## 🚀 Next Steps

1. **Test** dengan real data dari database
2. **Update** backend untuk include field `unit` 
3. **Validate** format output di thermal printer
4. **Customize** styling sesuai kebutuhan
5. **Deploy** ke production

---

**Created:** December 2, 2025
**Version:** 2.0 - With Unit Display
**Author:** Frontend Master AI Assistant
