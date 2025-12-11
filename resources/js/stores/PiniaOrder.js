// resources/js/stores/PiniaOrder.js/
import { defineStore } from 'pinia'

export const useOrderStore = defineStore('order', {
  state: () => ({
    // Data layanan
    daftarLayanan: [],
    isLoadingLayanan: false,
    
    // Data pesanan saat ini
    layananTerpilih: null,
    kuantitas: {},
    catatan: '',
    totalHarga: 0,
    
    // Daftar pesanan yang sudah ditambahkan
    daftarPesanan: [],
    
    // Status loading
    isProcessing: false
  }),

  getters: {
    // Total keseluruhan dari semua pesanan
    grandTotal: (state) => {
      return state.daftarPesanan.reduce((total, pesanan) => total + pesanan.total, 0)
    },

    // Cek apakah ada pesanan yang belum selesai
    hasActiveOrder: (state) => {
      return state.daftarPesanan.length > 0
    },

    // Jumlah item dalam pesanan
    totalItems: (state) => {
      return state.daftarPesanan.length
    },

    // Validasi apakah bisa menambah pesanan
    canAddOrder: (state) => {
      return state.layananTerpilih && state.totalHarga > 0
    }
  },

  actions: {
    // Set daftar layanan
    setDaftarLayanan(layanan) {
      this.daftarLayanan = layanan
    },

    // Set loading status layanan
    setLoadingLayanan(status) {
      this.isLoadingLayanan = status
    },

    // Pilih layanan
    pilihLayanan(layanan) {
      this.layananTerpilih = layanan
      this.kuantitas = {}
      
      // Initialize kuantitas untuk setiap satuan
      Object.keys(layanan.hargaPerUnit).forEach(satuan => {
        this.kuantitas[satuan] = 0
      })
      
      this.totalHarga = 0
      this.catatan = ''
    },

    // Update kuantitas
    setKuantitas(satuan, nilai) {
      if (this.kuantitas.hasOwnProperty(satuan)) {
        this.kuantitas[satuan] = Math.max(0, nilai)
        this.hitungTotal()
      }
    },

    // Tambah kuantitas
    tambahKuantitas(satuan) {
      if (this.kuantitas.hasOwnProperty(satuan)) {
        this.kuantitas[satuan]++
        this.hitungTotal()
      }
    },

    // Kurangi kuantitas
    kurangiKuantitas(satuan) {
      if (this.kuantitas.hasOwnProperty(satuan) && this.kuantitas[satuan] > 0) {
        this.kuantitas[satuan]--
        this.hitungTotal()
      }
    },

    // Hitung total harga
    hitungTotal() {
      if (!this.layananTerpilih) {
        this.totalHarga = 0
        return
      }

      let total = 0
      Object.keys(this.kuantitas).forEach(satuan => {
        const qty = this.kuantitas[satuan] || 0
        const harga = this.layananTerpilih.hargaPerUnit[satuan]
        total += qty * harga
      })
      this.totalHarga = total
    },

    // Set catatan
    setCatatan(catatan) {
      this.catatan = catatan
    },

    // Tambah pesanan ke daftar
    tambahPesanan() {
      if (!this.layananTerpilih || this.totalHarga === 0) {
        throw new Error('Pilih layanan dan masukkan kuantitas terlebih dahulu')
      }

      // Filter kuantitas yang valid (> 0)
      const kuantitasValid = {}
      Object.keys(this.kuantitas).forEach(satuan => {
        if (this.kuantitas[satuan] > 0) {
          kuantitasValid[satuan] = this.kuantitas[satuan]
        }
      })

      if (Object.keys(kuantitasValid).length === 0) {
        throw new Error('Masukkan kuantitas minimal 1')
      }

      // Buat object pesanan
      const pesanan = {
        id: Date.now(), // Temporary ID
        layanan: { ...this.layananTerpilih },
        kuantitas: { ...kuantitasValid },
        catatan: this.catatan,
        total: this.totalHarga,
        timestamp: new Date().toISOString()
      }

      // Tambah ke daftar pesanan
      this.daftarPesanan.push(pesanan)
      
      // Reset form
      this.resetForm()
      
      return pesanan
    },

    // Hapus pesanan dari daftar
    hapusPesanan(index) {
      if (index >= 0 && index < this.daftarPesanan.length) {
        this.daftarPesanan.splice(index, 1)
      }
    },

    // Hapus pesanan berdasarkan ID
    hapusPesananById(id) {
      const index = this.daftarPesanan.findIndex(pesanan => pesanan.id === id)
      if (index !== -1) {
        this.daftarPesanan.splice(index, 1)
      }
    },

    // Update pesanan
    updatePesanan(index, pesananBaru) {
      if (index >= 0 && index < this.daftarPesanan.length) {
        this.daftarPesanan[index] = { ...pesananBaru }
      }
    },

    // Reset form (layanan terpilih, kuantitas, dll)
    resetForm() {
      this.layananTerpilih = null
      this.kuantitas = {}
      this.catatan = ''
      this.totalHarga = 0
    },

    // Clear semua pesanan (setelah transaksi selesai)
    clearAllPesanan() {
      this.daftarPesanan = []
      this.resetForm()
    },

    // Set processing status
    setProcessing(status) {
      this.isProcessing = status
    },

    // Restore pesanan dari localStorage (jika diperlukan)
    restorePesanan() {
      try {
        const savedPesanan = localStorage.getItem('laundry_pesanan')
        if (savedPesanan) {
          const parsed = JSON.parse(savedPesanan)
          this.daftarPesanan = parsed.daftarPesanan || []
          this.layananTerpilih = parsed.layananTerpilih || null
          this.kuantitas = parsed.kuantitas || {}
          this.catatan = parsed.catatan || ''
          this.totalHarga = parsed.totalHarga || 0
        }
      } catch (error) {
        console.error('Error restoring pesanan:', error)
        this.clearAllPesanan()
      }
    },

    // Save pesanan ke localStorage
    savePesanan() {
      try {
        const dataToSave = {
          daftarPesanan: this.daftarPesanan,
          layananTerpilih: this.layananTerpilih,
          kuantitas: this.kuantitas,
          catatan: this.catatan,
          totalHarga: this.totalHarga,
          timestamp: new Date().toISOString()
        }
        localStorage.setItem('laundry_pesanan', JSON.stringify(dataToSave))
      } catch (error) {
        console.error('Error saving pesanan:', error)
      }
    },

    // Clear saved data dari localStorage
    clearSavedPesanan() {
      localStorage.removeItem('laundry_pesanan')
    }
  }
})