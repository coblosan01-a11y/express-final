// tutupKasirPrinter.js - Custom Print khusus untuk Tutup Kasir
// Hanya berisi: Tanggal, Kasir, QRIS, Tunai, Total, Status, Dicetak

import qz from 'qz-tray'

export const tutupKasirPrinter = {
  /**
   * Print tutup kasir dengan format yang sangat sederhana
   * @param {Object} laporanData - Data laporan dari transaksi
   * @param {string} tanggal - Tanggal tutup kasir (YYYY-MM-DD)
   * @param {string} kasirName - Nama kasir yang login
   */
  async printTutupKasir(laporanData, tanggal, kasirName) {
    try {
      console.log('🖨️ Printing tutup kasir via QZ Tray...')
      
      // Connect to QZ Tray
      if (!qz.websocket.isActive()) {
        await qz.websocket.connect()
        console.log('✅ QZ Tray connected')
      }

      // Find printers
      const printers = await qz.printers.find()
      if (printers.length === 0) {
        throw new Error('Tidak ada printer yang terdeteksi')
      }

      // Select printer (prioritas Generic / Text Only)
      const printerName = this.selectBestPrinter(printers)
      console.log(`🎯 Selected printer: ${printerName}`)

      // Create print config
      const config = qz.configs.create(printerName, {
        copies: 1,
        jobName: `TutupKasir-${tanggal}`,
        charset: 'UTF-8'
      })

      // Generate ESC/POS commands
      const commands = this.generateTutupKasirCommands(laporanData, tanggal, kasirName)

      // Print
      await qz.print(config, commands)
      
      console.log('✅ Tutup kasir berhasil dicetak')
      return {
        success: true,
        method: 'qz-tray-custom',
        printer: printerName,
        kasir: kasirName
      }

    } catch (error) {
      console.error('❌ Error printing tutup kasir:', error)
      throw new Error(`Gagal mencetak tutup kasir: ${error.message}`)
    }
  },

  /**
   * Select best printer dari daftar printer yang tersedia
   */
  selectBestPrinter(printers) {
    const preferredPrinters = [
      'Generic / Text Only',
      'POS-80',
      'XP-58',
      'TSP100',
      'TM-T20',
      'USB Printer',
      'Thermal Printer'
    ]

    // Cari printer yang diinginkan
    for (const preferred of preferredPrinters) {
      if (printers.includes(preferred)) {
        return preferred
      }
    }

    // Jika tidak ada, pakai printer pertama
    return printers[0]
  },

  /**
   * Generate ESC/POS commands untuk tutup kasir sederhana
   */
  generateTutupKasirCommands(laporanData, tanggal, kasirName) {
    const commands = []
    const LF = '\n'
    const ESC = '\x1B'
    const GS = '\x1D'
    
    // Initialize printer
    commands.push({ type: 'raw', format: 'plain', data: ESC + '@' })
    
    // Header - Center align + Bold
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'a\x01' }) // Center
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'E\x01' }) // Bold on
    commands.push({ type: 'raw', format: 'plain', data: 'FRESH CLEAN LAUNDRY' + LF })
    commands.push({ type: 'raw', format: 'plain', data: 'TUTUP KASIR HARIAN' + LF })
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'E\x00' }) // Bold off
    
    // Left align untuk content
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'a\x00' })
    commands.push({ type: 'raw', format: 'plain', data: '================================' + LF })
    
    // Tanggal dan Kasir
    const formattedDate = this.formatDate(tanggal)
    commands.push({ type: 'raw', format: 'plain', data: `Tanggal: ${formattedDate}` + LF })
    commands.push({ type: 'raw', format: 'plain', data: `Kasir: ${kasirName}` + LF })
    commands.push({ type: 'raw', format: 'plain', data: '--------------------------------' + LF })
    
    // Pemasukan
    commands.push({ type: 'raw', format: 'plain', data: `Pemasukan TUNAI: Rp ${this.formatCurrency(laporanData.pemasukanTunai)}` + LF })
    commands.push({ type: 'raw', format: 'plain', data: `Pemasukan QRIS: Rp ${this.formatCurrency(laporanData.pemasukanQris)}` + LF })
    commands.push({ type: 'raw', format: 'plain', data: '--------------------------------' + LF })
    
    // Total dengan emphasis
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'E\x01' }) // Bold on
    commands.push({ type: 'raw', format: 'plain', data: `TOTAL: Rp ${this.formatCurrency(laporanData.totalPemasukan)}` + LF })
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'E\x00' }) // Bold off
    
    commands.push({ type: 'raw', format: 'plain', data: '================================' + LF })
    
    // Status
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'a\x01' }) // Center
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'E\x01' }) // Bold on
    commands.push({ type: 'raw', format: 'plain', data: 'STATUS: TUTUP KASIR' + LF })
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'E\x00' }) // Bold off
    
    // Waktu cetak
    const currentTime = new Date()
    const printTime = `${this.formatDate(currentTime.toISOString().split('T')[0])} ${currentTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`
    commands.push({ type: 'raw', format: 'plain', data: `Dicetak: ${printTime}` + LF })
    commands.push({ type: 'raw', format: 'plain', data: ESC + 'a\x00' }) // Left align
    
    // Footer spacing and cut
    commands.push({ type: 'raw', format: 'plain', data: LF + LF + LF })
    commands.push({ type: 'raw', format: 'plain', data: GS + 'V\x00' }) // Cut paper
    
    return commands
  },

  /**
   * Pad line untuk alignment kiri-kanan
   */
  padLine(left, right, totalWidth = 32) {
    const leftStr = String(left || '')
    const rightStr = String(right || '')
    const spacesNeeded = Math.max(0, totalWidth - leftStr.length - rightStr.length)
    return leftStr + ' '.repeat(spacesNeeded) + rightStr
  },

  /**
   * Format currency ke string Indonesia
   */
  formatCurrency(amount) {
    if (!amount || isNaN(amount)) return '0'
    return parseInt(amount).toLocaleString('id-ID')
  },

  /**
   * Format date ke string Indonesia
   */
  formatDate(dateString) {
    if (!dateString) return ''
    try {
      const date = new Date(dateString)
      return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    } catch (error) {
      return dateString
    }
  },

  /**
   * Test connection QZ Tray
   */
  async testConnection() {
    try {
      if (!qz.websocket.isActive()) {
        await qz.websocket.connect()
      }
      
      const printers = await qz.printers.find()
      
      return {
        success: true,
        printers: printers,
        selectedPrinter: this.selectBestPrinter(printers),
        message: 'QZ Tray siap untuk tutup kasir'
      }
    } catch (error) {
      return {
        success: false,
        error: error.message,
        troubleshoot: [
          'Pastikan QZ Tray sudah running',
          'Cek printer sudah connect',
          'Restart QZ Tray jika perlu'
        ]
      }
    }
  },

  /**
   * Print test untuk tutup kasir
   */
  async printTest() {
    const testData = {
      pemasukanTunai: 150000,
      pemasukanQris: 75000,
      totalPemasukan: 225000
    }
    
    const today = new Date().toISOString().split('T')[0]
    const kasirTest = 'Test Kasir'
    
    try {
      const result = await this.printTutupKasir(testData, today, kasirTest)
      return {
        success: true,
        message: 'Test print tutup kasir berhasil',
        details: result
      }
    } catch (error) {
      return {
        success: false,
        error: error.message,
        message: 'Test print tutup kasir gagal'
      }
    }
  }
}

// Export default
export default tutupKasirPrinter