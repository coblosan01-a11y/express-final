// src/utils/printHelpers.js - MEGA FIX: Complete Solution untuk Kasir Name Problem
// 🔥 UPDATED: Fixed qty and price per item display

import qz from "qz-tray";

export const printHelpers = {
  /**
   * ⚡ QZ TRAY CONFIGURATION
   */
  qzConfig: {
    defaultPrinterName: "Generic / Text Only",
    fallbackPrinters: [
      "Generic / Text Only",
      "POS-80", 
      "XP-58", 
      "TSP100",
      "TM-T20",
      "USB Printer",
      "Thermal Printer",
      "Receipt Printer"
    ],
    thermal58mm: {
      charset: "UTF-8",
      columns: 32,
      lineFeed: "\n",
      paperCut: "\x1D\x56\x00",
      initialize: "\x1B\x40",
      centerAlign: "\x1B\x61\x01",
      leftAlign: "\x1B\x61\x00", 
      bold: "\x1B\x45\x01",
      boldOff: "\x1B\x45\x00"
    }
  },

  /**
   * 🔥 MEGA FIX: Smart Kasir Name Detection dengan Multiple Fallbacks
   */
  getKasirName(transactionData = null) {
    console.log('🔥 MEGA DEBUG: Starting kasir name detection...');
    console.log('📋 Transaction data input:', transactionData);
    
    try {
      // PRIORITAS 1: Dari transaction data yang udah ada kasir_name
      if (transactionData && transactionData.kasir_name && transactionData.kasir_name !== 'Admin' && transactionData.kasir_name !== 'Kasir') {
        console.log('✅ PRIORITY 1: Using kasir_name from transaction:', transactionData.kasir_name);
        return transactionData.kasir_name;
      }

      // PRIORITAS 2: Manual override (untuk testing)
      const manualOverride = this.getManualKasirOverride();
      if (manualOverride) {
        console.log('✅ PRIORITY 2: Using manual override:', manualOverride);
        return manualOverride;
      }

      // PRIORITAS 3: Dari storage dengan smart detection
      const storageKasir = this.getKasirFromStorage();
      if (storageKasir && storageKasir !== 'Admin') {
        console.log('✅ PRIORITY 3: Using storage kasir:', storageKasir);
        return storageKasir;
      }

      // PRIORITAS 4: Dari current login session
      const sessionKasir = this.getKasirFromSession();
      if (sessionKasir && sessionKasir !== 'Admin') {
        console.log('✅ PRIORITY 4: Using session kasir:', sessionKasir);
        return sessionKasir;
      }

      // PRIORITAS 5: Fallback berdasarkan waktu
      const timeBasedKasir = this.getTimeBasedKasir();
      if (timeBasedKasir) {
        console.log('✅ PRIORITY 5: Using time-based kasir:', timeBasedKasir);
        return timeBasedKasir;
      }

      console.warn('❌ All kasir detection methods failed, using default');
      return 'Kasir';
      
    } catch (error) {
      console.error('❌ Error in kasir detection:', error);
      return 'Kasir';
    }
  },

  /**
   * 🔥 MEGA METHOD: Get kasir from storage dengan super smart detection
   */
  getKasirFromStorage() {
    console.log('🔍 Checking storage for kasir...');
    
    try {
      // Check multiple storage locations
      const storageKeys = ['user', 'currentUser', 'loginData', 'authUser', 'karyawan', 'kasir'];
      const storageTypes = [sessionStorage, localStorage];
      
      for (const storage of storageTypes) {
        for (const key of storageKeys) {
          const data = storage.getItem(key);
          if (data) {
            try {
              const parsed = JSON.parse(data);
              console.log(`📦 Found data in ${storage === sessionStorage ? 'session' : 'local'} storage [${key}]:`, parsed);
              
              const kasirName = this.extractKasirFromUserData(parsed);
              if (kasirName && kasirName !== 'Admin') {
                console.log(`✅ Extracted kasir from ${key}:`, kasirName);
                return kasirName;
              }
            } catch (e) {
              console.warn(`⚠️ Error parsing ${key}:`, e);
            }
          }
        }
      }
      
      return null;
    } catch (error) {
      console.error('❌ Error checking storage:', error);
      return null;
    }
  },

  /**
   * 🔥 MEGA METHOD: Extract kasir name dari user data object
   */
  extractKasirFromUserData(userData) {
    if (!userData || typeof userData !== 'object') return null;
    
    console.log('🔍 Extracting kasir from user data:', userData);
    console.log('🔍 Available fields:', Object.keys(userData));
    
    // Priority order untuk field names
    const fieldPriority = [
      'nama',           // Dari karyawans table
      'nama_karyawan',  // Alternative
      'full_name',      // Alternative
      'display_name',   // Alternative
      'real_name',      // Alternative
      'name',           // Generic (tapi bisa "Admin")
      'username',       // Last resort
      'email'           // Very last resort
    ];
    
    for (const field of fieldPriority) {
      if (userData[field] && typeof userData[field] === 'string') {
        const value = userData[field].trim();
        console.log(`🔍 Checking field ${field}:`, value);
        
        // Skip generic admin names
        if (value && !this.isGenericAdminName(value)) {
          console.log(`✅ Found valid kasir name in ${field}:`, value);
          return value;
        }
      }
    }
    
    // Special case: jika ada jabatan, kombinasi dengan nama
    if (userData.jabatan && userData.name) {
      const combined = `${userData.name} (${userData.jabatan})`;
      if (!this.isGenericAdminName(userData.name)) {
        console.log('✅ Using combined name with jabatan:', combined);
        return combined;
      }
    }
    
    console.log('❌ No valid kasir name found in user data');
    return null;
  },

  /**
   * 🔥 MEGA METHOD: Check if name is generic admin name
   */
  isGenericAdminName(name) {
    const genericNames = [
      'admin', 'administrator', 'root', 'superuser', 'user', 'default', 'kasir', 'operator'
    ];
    return genericNames.includes(name.toLowerCase());
  },

  /**
   * 🔥 MEGA METHOD: Get kasir from current session/context
   */
  getKasirFromSession() {
    console.log('🔍 Checking current session context...');
    
    try {
      // Check if there's any global user context
      if (typeof window !== 'undefined') {
        // Check Vue app instance or global variables
        const possibleGlobals = ['$user', 'currentUser', 'authUser', 'loginUser'];
        
        for (const globalVar of possibleGlobals) {
          if (window[globalVar]) {
            console.log(`🔍 Found global ${globalVar}:`, window[globalVar]);
            const kasir = this.extractKasirFromUserData(window[globalVar]);
            if (kasir) return kasir;
          }
        }
      }
      
      return null;
    } catch (error) {
      console.error('❌ Error checking session context:', error);
      return null;
    }
  },

  /**
   * 🔥 MEGA METHOD: Manual kasir override untuk testing
   */
  getManualKasirOverride() {
    try {
      const override = sessionStorage.getItem('kasir_override') || localStorage.getItem('kasir_override');
      if (override) {
        const data = JSON.parse(override);
        if (data.nama && data.active !== false) {
          console.log('🔧 Manual kasir override active:', data.nama);
          return data.nama;
        }
      }
      return null;
    } catch (error) {
      console.error('❌ Error checking manual override:', error);
      return null;
    }
  },

  /**
   * 🔥 MEGA METHOD: Time-based kasir fallback
   */
  getTimeBasedKasir() {
    const hour = new Date().getHours();
    const shift = hour < 12 ? 'Pagi' : hour < 18 ? 'Siang' : 'Malam';
    return `Kasir ${shift}`;
  },

  /**
   * 🔥 MEGA UTILITY: Set manual kasir override
   */
  setKasirOverride(nama, options = {}) {
    try {
      const overrideData = {
        nama: nama,
        timestamp: new Date().toISOString(),
        active: true,
        ...options
      };
      
      sessionStorage.setItem('kasir_override', JSON.stringify(overrideData));
      localStorage.setItem('kasir_override', JSON.stringify(overrideData));
      
      console.log('✅ Kasir override set:', overrideData);
      return true;
    } catch (error) {
      console.error('❌ Error setting kasir override:', error);
      return false;
    }
  },

  /**
   * 🔥 MEGA UTILITY: Clear kasir override
   */
  clearKasirOverride() {
    sessionStorage.removeItem('kasir_override');
    localStorage.removeItem('kasir_override');
    console.log('✅ Kasir override cleared');
  },

  /**
   * 🔥 MEGA DEBUG: Complete debug info
   */
  debugKasirInfo() {
    console.log('🔥 MEGA DEBUG: Complete Kasir Information');
    console.log('===============================================');
    
    // Storage check
    console.log('📦 STORAGE CHECK:');
    console.log('- sessionStorage.user:', sessionStorage.getItem('user'));
    console.log('- localStorage.user:', localStorage.getItem('user'));
    console.log('- sessionStorage.currentUser:', sessionStorage.getItem('currentUser'));
    console.log('- localStorage.currentUser:', localStorage.getItem('currentUser'));
    
    // Parse and analyze
    try {
      const sessionUser = JSON.parse(sessionStorage.getItem('user') || '{}');
      const localUser = JSON.parse(localStorage.getItem('user') || '{}');
      
      console.log('📋 PARSED DATA:');
      console.log('- Session user fields:', Object.keys(sessionUser));
      console.log('- Local user fields:', Object.keys(localUser));
      console.log('- Session user:', sessionUser);
      console.log('- Local user:', localUser);
      
    } catch (e) {
      console.error('❌ Error parsing storage data:', e);
    }
    
    // Test all methods
    console.log('🧪 METHOD TESTS:');
    console.log('- getKasirFromStorage():', this.getKasirFromStorage());
    console.log('- getKasirFromSession():', this.getKasirFromSession());
    console.log('- getManualKasirOverride():', this.getManualKasirOverride());
    console.log('- getTimeBasedKasir():', this.getTimeBasedKasir());
    
    // Final result
    const finalKasir = this.getKasirName();
    console.log('✅ FINAL KASIR NAME:', finalKasir);
    
    return {
      storageData: {
        session: sessionStorage.getItem('user'),
        local: localStorage.getItem('user')
      },
      detectedKasir: finalKasir,
      timestamp: new Date().toISOString()
    };
  },

  /**
   * Get current user ID
   */
  getCurrentUserId() {
    try {
      const userData = JSON.parse(sessionStorage.getItem('user') || localStorage.getItem('user') || '{}');
      return userData.id || userData.user_id || 1;
    } catch (error) {
      console.error('❌ Error getting user ID:', error);
      return 1;
    }
  },

  /**
   * Format functions
   */
  formatCurrency(amount) {
    if (!amount || isNaN(amount)) return "0";
    return parseInt(amount).toLocaleString("id-ID");
  },

  formatDate(dateString) {
    if (!dateString) return "-";
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString("id-ID");
    } catch {
      return "-";
    }
  },

  formatTime(dateString) {
    if (!dateString) return "-";
    try {
      const date = new Date(dateString);
      return date.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
      });
    } catch {
      return "-";
    }
  },

  formatPaymentMethod(method) {
    const methods = {
      tunai: "TUNAI",
      qris: "QRIS",
      "bayar-nanti": "BAYAR NANTI",
    };
    return methods[method] || "TUNAI";
  },

  formatPickupSchedule(date, time) {
    if (!date) return "-";
    try {
      const d = new Date(date);
      return (
        d.toLocaleDateString("id-ID") + (time ? `, ${time}` : "")
      );
    } catch {
      return "-";
    }
  },

  /**
   * QZ Tray initialization
   */
  async initializeQzTray() {
    console.log("🔌 Initializing QZ Tray connection...");
    try {
      if (typeof qz === 'undefined') {
        throw new Error("QZ Tray library tidak ditemukan");
      }

      if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
        console.log("✅ QZ Tray websocket connected");
      }

      const printers = await qz.printers.find();
      console.log("🖨️ Available printers:", printers);

      if (printers.length === 0) {
        throw new Error("Tidak ada printer yang terdeteksi");
      }

      return {
        success: true,
        printers: printers,
        message: "QZ Tray berhasil terhubung"
      };

    } catch (error) {
      console.error("❌ QZ Tray initialization failed:", error);
      return {
        success: false,
        error: error.message,
        troubleshoot: [
          "Pastikan QZ Tray desktop app sudah running",
          "Cek koneksi websocket ke localhost:8182",
          "Restart QZ Tray service jika perlu",
          "Pastikan printer sudah connect dan online"
        ]
      };
    }
  },

  /**
   * Smart printer selection
   */
  async getOptimalPrinter(preferredName = null) {
    try {
      const printers = await qz.printers.find();//
      
      if (preferredName && printers.includes(preferredName)) {
        console.log(`✅ Using preferred printer: ${preferredName}`);
        return preferredName;
      }
      
      if (printers.includes(this.qzConfig.defaultPrinterName)) {
        console.log(`✅ Using default printer: ${this.qzConfig.defaultPrinterName}`);
        return this.qzConfig.defaultPrinterName;
      }
      
      for (const fallback of this.qzConfig.fallbackPrinters) {
        if (printers.includes(fallback)) {
          console.log(`✅ Using fallback printer: ${fallback}`);
          return fallback;
        }
      }
      
      if (printers.length > 0) {
        console.log(`⚠️ Using first available printer: ${printers[0]}`);
        return printers[0];
      }
      
      throw new Error("Tidak ada printer yang tersedia");
      
    } catch (error) {
      console.error("❌ Error getting printer:", error);
      throw error;
    }
  },

  /**
   * 🔥 MEGA MAIN PRINT FUNCTION
   */
  async printReceipt(transactionData, options = {}) {
    console.log("🔥 MEGA PRINT START - Advanced Kasir Detection");
    console.log("📋 Input transaction data:", transactionData);
    
    try {
      if (!transactionData || !transactionData.kode_transaksi) {
        throw new Error("Data transaksi tidak lengkap");
      }

      // 🔥 MEGA KASIR INJECTION dengan Advanced Detection
      console.log('🔥 MEGA KASIR DETECTION START...');
      const detectedKasir = this.getKasirName(transactionData);
      
      // Force inject kasir name
      transactionData.kasir_name = detectedKasir;
      console.log("🔥 FINAL KASIR NAME INJECTED:", detectedKasir);

      // Auto-inject kasir_id
      if (!transactionData.kasir_id) {
        transactionData.kasir_id = this.getCurrentUserId();
        console.log("✅ Auto-injected kasir ID:", transactionData.kasir_id);
      }

      const useQzTray = options.useQzTray !== false;

      if (useQzTray) {
        console.log("🎯 PRIORITAS: QZ Tray Mode");
        return await this.printWithQzTrayEnhanced(transactionData, options);
      } else {
        console.log("🌐 FALLBACK: Browser Mode");
        return await this.printWithBrowser(transactionData, options);
      }

    } catch (error) {
      console.error("❌ PRINT ERROR:", error);
      
      if (error.message.includes("QZ") || error.message.includes("websocket")) {
        console.log("🔄 QZ Tray gagal, fallback ke browser print...");
        return await this.printWithBrowser(transactionData, options);
      }
      
      return this.showTextReceipt(transactionData);
    }
  },

  /**
   * QZ Tray print enhanced
   */
  async printWithQzTrayEnhanced(transactionData, options = {}) {
    console.log("🚀 ENHANCED QZ Tray printing...");
    
    try {
      const initResult = await this.initializeQzTray();
      if (!initResult.success) {
        throw new Error(`QZ Tray init failed: ${initResult.error}`);
      }

      const printerName = await this.getOptimalPrinter(options.printerName);
      console.log(`🎯 Selected printer: ${printerName}`);

      const config = qz.configs.create(printerName, {
        copies: options.copies || 1,
        jobName: `Receipt-${transactionData.kode_transaksi}`,
        charset: this.qzConfig.thermal58mm.charset
      });

      const escPosData = this.generateOptimizedEscPos(transactionData, options.laundryInfo);

      console.log("📡 Sending print job to QZ Tray...");
      await qz.print(config, escPosData);

      console.log("✅ QZ TRAY PRINT SUCCESS!");
      return { 
        success: true, 
        message: "Print berhasil via QZ Tray",
        printer: printerName,
        method: "qz-tray",
        kasir: transactionData.kasir_name
      };

    } catch (error) {
      console.error("❌ QZ Tray Enhanced Error:", error);
      
      if (error.message.includes("websocket")) {
        throw new Error("QZ Tray tidak running. Pastikan aplikasi QZ Tray sudah dijalankan.");
      } else if (error.message.includes("printer")) {
        throw new Error(`Printer error: ${error.message}`);
      } else {
        throw new Error(`QZ Tray error: ${error.message}`);
      }
    }
  },

  /**
   * 🔥 OPTIMIZED ESC/POS Generation dengan Fixed Kasir dan QTY + PRICE PER ITEM
   */
  generateOptimizedEscPos(data, laundryInfo = {}) {
    const cfg = this.qzConfig.thermal58mm;
    const commands = [];

    const padLine = (left, right, totalWidth = cfg.columns) => {
      const leftStr = String(left || '');
      const rightStr = String(right || '');
      const spacesNeeded = Math.max(0, totalWidth - leftStr.length - rightStr.length);
      return leftStr + ' '.repeat(spacesNeeded) + rightStr;
    };

    const centerText = (text, width = cfg.columns) => {
      const textStr = String(text || '').trim();
      if (textStr.length >= width) return textStr.substring(0, width);
      
      const totalPadding = width - textStr.length;
      const leftPadding = Math.floor(totalPadding / 2);
      const rightPadding = totalPadding - leftPadding;
      
      return ' '.repeat(leftPadding) + textStr + ' '.repeat(rightPadding);
    };

    try {
      // Initialize printer
      commands.push({ type: 'raw', format: 'plain', data: cfg.initialize });

      // Header
      commands.push({ type: 'raw', format: 'plain', data: cfg.centerAlign });
      commands.push({ type: 'raw', format: 'plain', data: cfg.bold });
      
      const shopName = laundryInfo?.name || "FRESH LAUNDRY";
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: '\x1D\x21\x01' + centerText(shopName, cfg.columns) + cfg.lineFeed
      });
      commands.push({ type: 'raw', format: 'plain', data: '\x1D\x21\x00' });
      commands.push({ type: 'raw', format: 'plain', data: cfg.boldOff });
      
      commands.push({ type: 'raw', format: 'plain', data: cfg.leftAlign });
      commands.push({ type: 'raw', format: 'plain', data: '='.repeat(cfg.columns) + cfg.lineFeed });

      // Transaction info dengan FIXED kasir name
      const currentDate = new Date();
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('Kode:', data.kode_transaksi) + cfg.lineFeed 
      });
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('Tanggal:', currentDate.toLocaleDateString('id-ID')) + cfg.lineFeed 
      });
      
      // 🔥 MEGA FIXED: Kasir name dengan detection terbaru
      const kasirName = data.kasir_name || this.getKasirName(data);
      console.log('🔥 ESC/POS Kasir Name:', kasirName);
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('Kasir:', kasirName) + cfg.lineFeed 
      });
      
      commands.push({ type: 'raw', format: 'plain', data: '-'.repeat(cfg.columns) + cfg.lineFeed });

      // Customer info
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('Customer:', data.customer_name) + cfg.lineFeed 
      });
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('Phone:', data.customer_phone) + cfg.lineFeed 
      });
      
      commands.push({ type: 'raw', format: 'plain', data: '-'.repeat(cfg.columns) + cfg.lineFeed });

      // Pickup service
      if (data.biaya_pickup > 0 || data.pickup_service) {
        commands.push({ type: 'raw', format: 'plain', data: cfg.bold });
        commands.push({ type: 'raw', format: 'plain', data: 'LAYANAN PICKUP' + cfg.lineFeed });
        commands.push({ type: 'raw', format: 'plain', data: cfg.boldOff });
        
        const pickup = data.pickup_service || {};
        const serviceName = pickup.service_name || 'Pickup Service';
        const pickupCost = 'Rp ' + this.formatCurrency(data.biaya_pickup);
        
        commands.push({ 
          type: 'raw', 
          format: 'plain', 
          data: padLine(serviceName.substring(0, 20), pickupCost) + cfg.lineFeed 
        });
        
        if (pickup.jarak) {
          commands.push({ 
            type: 'raw', 
            format: 'plain', 
            data: `Jarak: ${pickup.jarak} km` + cfg.lineFeed 
          });
        }
        
        if (pickup.pickup_date) {
          commands.push({ 
            type: 'raw', 
            format: 'plain', 
            data: `Jadwal: ${this.formatPickupSchedule(pickup.pickup_date, pickup.pickup_time)}` + cfg.lineFeed 
          });
        }
        
        commands.push({ type: 'raw', format: 'plain', data: '-'.repeat(cfg.columns) + cfg.lineFeed });
      }

      // 🔥 FIXED: Laundry items dengan QTY, UNIT, dan PRICE PER ITEM
      // Support Laravel array-based structure: kuantitas = {kg: 2, pcs: 3}
      const items = data.details || data.items || [];
      if (items.length > 0) {
        commands.push({ type: 'raw', format: 'plain', data: cfg.bold });
        commands.push({ type: 'raw', format: 'plain', data: 'LAYANAN LAUNDRY' + cfg.lineFeed });
        commands.push({ type: 'raw', format: 'plain', data: cfg.boldOff });
        
        items.forEach((item) => {
          // Line 1: Item name
          const itemName = item.layanan_nama.substring(0, cfg.columns - 1);
          commands.push({ 
            type: 'raw', 
            format: 'plain', 
            data: itemName + cfg.lineFeed 
          });
          
          // 🔥 Handle Laravel array-based kuantitas structure
          if (item.kuantitas && typeof item.kuantitas === 'object' && !Array.isArray(item.kuantitas)) {
            // Laravel structure: kuantitas = {kg: 2, pcs: 3}, harga_satuan = {kg: 10000, pcs: 5000}
            Object.entries(item.kuantitas).forEach(([unit, qty]) => {
              const hargaSatuan = item.harga_satuan?.[unit] || 0;
              const subtotalUnit = qty * hargaSatuan;
              
              const qtyLine = `  ${qty} ${unit} x Rp${this.formatCurrency(hargaSatuan)}/${unit}`;
              const subtotalStr = 'Rp' + this.formatCurrency(subtotalUnit);
              commands.push({ 
                type: 'raw', 
                format: 'plain', 
                data: padLine(qtyLine, subtotalStr) + cfg.lineFeed 
              });
            });
          } else {
            // Simple structure: qty, unit, subtotal (fallback)
            const qty = item.qty || item.jumlah || item.quantity || item.kuantitas || 1;
            const unit = item.unit || item.satuan || 'pcs';
            const subtotal = item.subtotal || item.total || 0;
            const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
            
            const qtyLine = `  ${qty} ${unit} x Rp${this.formatCurrency(pricePerUnit)}/${unit}`;
            const subtotalStr = 'Rp' + this.formatCurrency(subtotal);
            commands.push({ 
              type: 'raw', 
              format: 'plain', 
              data: padLine(qtyLine, subtotalStr) + cfg.lineFeed 
            });
          }
        });
        
        commands.push({ type: 'raw', format: 'plain', data: '-'.repeat(cfg.columns) + cfg.lineFeed });
      }

      // Totals
      if (data.subtotal_layanan > 0) {
        commands.push({ 
          type: 'raw', 
          format: 'plain', 
          data: padLine('Subtotal Layanan:', 'Rp ' + this.formatCurrency(data.subtotal_layanan)) + cfg.lineFeed 
        });
      }
      
      if (data.biaya_pickup > 0) {
        commands.push({ 
          type: 'raw', 
          format: 'plain', 
          data: padLine('Biaya Pickup:', 'Rp ' + this.formatCurrency(data.biaya_pickup)) + cfg.lineFeed 
        });
      }
      
      commands.push({ type: 'raw', format: 'plain', data: '='.repeat(cfg.columns) + cfg.lineFeed });
      
      // GRAND TOTAL
      commands.push({ type: 'raw', format: 'plain', data: cfg.bold });
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('TOTAL:', 'Rp ' + this.formatCurrency(data.total_amount)) + cfg.lineFeed 
      });
      commands.push({ type: 'raw', format: 'plain', data: cfg.boldOff });
      
      // Payment details
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: padLine('Metode:', this.formatPaymentMethod(data.metode_pembayaran)) + cfg.lineFeed 
      });
      
      if (data.jumlah_bayar > 0) {
        commands.push({ 
          type: 'raw', 
          format: 'plain', 
          data: padLine('Bayar:', 'Rp ' + this.formatCurrency(data.jumlah_bayar)) + cfg.lineFeed 
        });
      }
      
      if (data.kembalian > 0) {
        commands.push({ 
          type: 'raw', 
          format: 'plain', 
          data: padLine('Kembalian:', 'Rp ' + this.formatCurrency(data.kembalian)) + cfg.lineFeed 
        });
      }
      
      commands.push({ type: 'raw', format: 'plain', data: '='.repeat(cfg.columns) + cfg.lineFeed });

      // Footer
      commands.push({ type: 'raw', format: 'plain', data: cfg.centerAlign });
      commands.push({ type: 'raw', format: 'plain', data: cfg.bold });
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: centerText(`STATUS: ${data.status_transaksi === 'sukses' ? 'LUNAS' : 'PENDING'}`) + cfg.lineFeed 
      });
      commands.push({ type: 'raw', format: 'plain', data: cfg.boldOff });
      
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: centerText('Terima kasih!') + cfg.lineFeed 
      });
      
      const currentDateTime = new Date();
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: centerText(`Dicetak: ${currentDateTime.toLocaleDateString('id-ID')} ${currentDateTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`) + cfg.lineFeed 
      });
      
      commands.push({ 
        type: 'raw', 
        format: 'plain', 
        data: centerText(`Oleh: ${kasirName}`) + cfg.lineFeed 
      });

      // Final spacing and cut
      commands.push({ type: 'raw', format: 'plain', data: cfg.lineFeed + cfg.lineFeed + cfg.lineFeed });
      commands.push({ type: 'raw', format: 'plain', data: cfg.paperCut });

      console.log("✅ ESC/POS commands generated successfully");
      console.log(`📏 Total commands: ${commands.length}`);
      console.log(`🔥 FINAL KASIR IN ESC/POS: ${kasirName}`);
      
      return commands;

    } catch (error) {
      console.error("❌ Error generating ESC/POS:", error);
      throw new Error(`Gagal generate print data: ${error.message}`);
    }
  },

  /**
   * Browser print dengan fixed kasir
   */
  async printWithBrowser(transactionData, options = {}) {
    // Force inject kasir name untuk browser print juga
    if (!transactionData.kasir_name) {
      transactionData.kasir_name = this.getKasirName(transactionData);
    }

    const html = this.generateEnhanced58mmHTML(transactionData, options.laundryInfo);

    const printWindow = window.open("", "_blank", "width=300,height=600");

    if (!printWindow) {
      console.warn("Popup diblokir, fallback ke text receipt");
      return this.showTextReceipt(transactionData);
    }

    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Receipt-${transactionData.kode_transaksi}</title>
        <style>
          @page { 
            size: 58mm auto; 
            margin: 0; 
            -webkit-print-color-adjust: exact; 
          }
          body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.2;
            padding: 0;
            margin: 0;
            width: 54mm;
            color: #000;
            background: #fff;
          }
          .center { text-align: center; }
          .bold { font-weight: bold; }
          .shop-name { 
            font-size: 14px;           
            font-weight: 700;          
            margin: 6px 0;
            letter-spacing: 0.5px;       
            text-transform: uppercase;
            text-align: center;
            display: block;
            width: 100%;
          }
          .line { 
            border-top: 1px dashed #000; 
            margin: 2px 0; 
            height: 0;
          }
          .double-line {
            border-top: 1px solid #000;
            margin: 2px 0;
            height: 0;
          }
          .flex { 
            display: flex; 
            justify-content: space-between; 
            margin: 1px 0;
          }
          .left { text-align: left; }
          .right { text-align: right; }
          .indent { margin-left: 8px; font-size: 9px; color: #333; }
          h3, h4, p { margin: 1px 0; padding: 0; }
          small { font-size: 8px; }
          .spacing { margin: 4px 0; }
          .cut-line { 
            text-align: center; 
            font-size: 8px; 
            color: #666; 
            margin: 8px 0; 
          }
          .kasir-info {
            font-size: 8px;
            color: #555;
            text-align: center;
            margin: 2px 0;
            font-weight: bold;
          }
          .item-qty {
            font-size: 9px;
            color: #555;
            margin-left: 8px;
          }
        </style>
      </head>
      <body>
        ${html}
        <div class="cut-line">--- POTONG DI SINI ---</div>
        <script>
          setTimeout(() => {
            window.print();
            setTimeout(() => window.close(), 500);
          }, 300);
        </script>
      </body>
      </html>
    `);

    printWindow.document.close();

    console.log("✅ BROWSER PRINT SUCCESS");
    return { 
      success: true, 
      message: "Print berhasil (browser)", 
      method: "browser",
      kasir: transactionData.kasir_name
    };
  },

  /**
   * 🔥 FIXED: Generate HTML dengan QTY dan PRICE PER ITEM
   */
  generateEnhanced58mmHTML(data, laundryInfo = {}) {
    const shop = laundryInfo?.name || "LAUNDRY SYSTEM";
    
    // 🔥 MEGA FIXED: Kasir name dengan detection terbaru
    const kasirName = data.kasir_name || this.getKasirName(data);
    console.log('🔥 HTML Kasir Name:', kasirName);

    let html = `
      <div class="center spacing">
        <h2 class="bold shop-name">${shop}</h2>
      </div>
      <div class="double-line"></div>
      
      <div>
        <div class="flex"><span>Kode:</span><span class="bold">${data.kode_transaksi}</span></div>
        <div class="flex"><span>Tanggal:</span><span>${new Date().toLocaleDateString('id-ID')}</span></div>
        <div class="flex"><span>Kasir:</span><span class="bold">${kasirName}</span></div>
      </div>
      <div class="line"></div>
      
      <div>
        <div class="flex"><span>Customer:</span><span class="bold">${data.customer_name}</span></div>
        <div class="flex"><span>Phone:</span><span>${data.customer_phone}</span></div>
      </div>
      <div class="line"></div>
    `;

    // Pickup service section
    if (data.biaya_pickup > 0 || data.pickup_service) {
      const pickup = data.pickup_service || {};
      html += `
        <div class="spacing">
          <h4 class="bold">LAYANAN PICKUP</h4>
          <div class="flex">
            <span>${pickup.service_name || "Pickup Service"}</span>
            <span class="bold">Rp ${this.formatCurrency(data.biaya_pickup)}</span>
          </div>
          ${pickup.jarak ? `<small>Jarak: ${pickup.jarak} km</small><br>` : ""}
          ${pickup.pickup_date ? `<small>Jadwal: ${this.formatPickupSchedule(pickup.pickup_date, pickup.pickup_time)}</small>` : ""}
        </div>
        <div class="line"></div>
      `;
    }

    // 🔥 FIXED: Laundry items dengan QTY, UNIT, dan PRICE PER ITEM
    // Support Laravel array-based structure
    const items = data.details || data.items || [];
    if (items.length > 0) {
      html += `<div class="spacing"><h4 class="bold">LAYANAN LAUNDRY</h4>`;
      items.forEach((item) => {
        // Item name
        html += `<div><strong>${item.layanan_nama}</strong></div>`;
        
        // 🔥 Handle Laravel array-based kuantitas structure
        if (item.kuantitas && typeof item.kuantitas === 'object' && !Array.isArray(item.kuantitas)) {
          // Laravel structure: kuantitas = {kg: 2, pcs: 3}
          Object.entries(item.kuantitas).forEach(([unit, qty]) => {
            const hargaSatuan = item.harga_satuan?.[unit] || 0;
            const subtotalUnit = qty * hargaSatuan;
            
            html += `<div class="flex item-qty">
              <span>${qty} ${unit} x Rp ${this.formatCurrency(hargaSatuan)}/${unit}</span>
              <span class="bold">Rp ${this.formatCurrency(subtotalUnit)}</span>
            </div>`;
          });
        } else {
          // Simple structure (fallback)
          const qty = item.qty || item.jumlah || item.quantity || item.kuantitas || 1;
          const unit = item.unit || item.satuan || 'pcs';
          const subtotal = item.subtotal || item.total || 0;
          const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
          
          html += `<div class="flex item-qty">
            <span>${qty} ${unit} x Rp ${this.formatCurrency(pricePerUnit)}/${unit}</span>
            <span class="bold">Rp ${this.formatCurrency(subtotal)}</span>
          </div>`;
        }
      });
      html += `</div><div class="line"></div>`;
    }

    // Totals section
    html += `<div class="spacing">`;
    
    if (data.subtotal_layanan > 0) {
      html += `<div class="flex"><span>Subtotal Layanan:</span><span>Rp ${this.formatCurrency(data.subtotal_layanan)}</span></div>`;
    }
    
    if (data.biaya_pickup > 0) {
      html += `<div class="flex"><span>Biaya Pickup:</span><span>Rp ${this.formatCurrency(data.biaya_pickup)}</span></div>`;
    }
    
    html += `
      <div class="double-line"></div>
      <div class="flex bold">
        <span>TOTAL:</span>
        <span>Rp ${this.formatCurrency(data.total_amount)}</span>
      </div>
      <div class="line"></div>
      
      <div class="flex"><span>Metode:</span><span class="bold">${this.formatPaymentMethod(data.metode_pembayaran)}</span></div>
    `;
    
    if (data.jumlah_bayar > 0) {
      html += `<div class="flex"><span>Bayar:</span><span>Rp ${this.formatCurrency(data.jumlah_bayar)}</span></div>`;
    }
    
    if (data.kembalian > 0) {
      html += `<div class="flex"><span>Kembalian:</span><span class="bold">Rp ${this.formatCurrency(data.kembalian)}</span></div>`;
    }
    
    html += `</div><div class="double-line"></div>`;

    // Footer dengan kasir yang benar
    html += `
      <div class="center spacing">
        <p class="bold">STATUS: ${data.status_transaksi === "sukses" ? "LUNAS" : "PENDING"}</p>
        <p class="bold">Terima kasih!</p>
        <small>Dicetak: ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</small>
        <div class="kasir-info">Oleh: ${kasirName}</div>
      </div>
    `;

    return html;
  },

  /**
   * Text receipt dengan fixed kasir
   */
  showTextReceipt(data) {
    if (!data) return;
    
    // 🔥 MEGA FIXED: Auto-inject kasir name untuk text receipt
    const kasirName = data.kasir_name || this.getKasirName(data);
    
    // 🔥 Build items detail dengan qty, unit, dan price per unit
    const items = data.details || data.items || [];
    let itemsText = '';
    
    if (items.length > 0) {
      itemsText = '\nLAYANAN LAUNDRY:\n';
      items.forEach((item) => {
        const qty = item.qty || item.jumlah || item.quantity || 1;
        const unit = item.unit || item.satuan || 'pcs';
        const subtotal = item.subtotal || item.total || 0;
        const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
        
        itemsText += `${item.layanan_nama}\n`;
        itemsText += `  ${qty} ${unit} x Rp ${this.formatCurrency(pricePerUnit)}/${unit} = Rp ${this.formatCurrency(subtotal)}\n`;
      });
      itemsText += '=====================================\n';
    }
    
    const text = `
🔥 MEGA RECEIPT - Advanced Kasir Detection
=====================================
Kode: ${data.kode_transaksi}
Tanggal: ${new Date().toLocaleDateString('id-ID')}
Kasir: ${kasirName}
=====================================
Customer: ${data.customer_name}
Phone: ${data.customer_phone}
=====================================
${itemsText}${data.subtotal_layanan > 0 ? `Subtotal Layanan: Rp ${this.formatCurrency(data.subtotal_layanan)}\n` : ''}${data.biaya_pickup > 0 ? `Biaya Pickup: Rp ${this.formatCurrency(data.biaya_pickup)}\n` : ''}Total: Rp ${this.formatCurrency(data.total_amount)}
Metode: ${this.formatPaymentMethod(data.metode_pembayaran)}
${data.jumlah_bayar > 0 ? `Bayar: Rp ${this.formatCurrency(data.jumlah_bayar)}\n` : ''}${data.kembalian > 0 ? `Kembalian: Rp ${this.formatCurrency(data.kembalian)}\n` : ''}Status: ${data.status_transaksi === "sukses" ? "LUNAS" : "PENDING"}
=====================================
Terima kasih!
Dicetak: ${new Date().toLocaleString('id-ID')}
Oleh: ${kasirName}
=====================================
🔥 MEGA DEBUG INFO:
- Kasir detection method: Advanced Multi-Priority
- Storage check: OK
- Manual override: ${this.getManualKasirOverride() ? 'Active' : 'None'}
- Final kasir: ${kasirName}
    `;

    alert(text);
    console.log("🔥 MEGA TEXT RECEIPT:\n", text);
    return { 
      success: true, 
      message: "Mega text receipt with advanced kasir detection", 
      kasir: kasirName,
      method: "text"
    };
  },

  /**
   * QZ Tray testing functions
   */
  async testQzConnection() {
    console.log("🧪 Testing QZ Tray connection...");
    
    try {
      const initResult = await this.initializeQzTray();
      
      if (initResult.success) {
        const printers = initResult.printers;
        const optimalPrinter = await this.getOptimalPrinter();
        
        return {
          success: true,
          printers: printers,
          selectedPrinter: optimalPrinter,
          config: this.qzConfig,
          message: "QZ Tray siap digunakan!",
          details: {
            totalPrinters: printers.length,
            websocketActive: qz.websocket.isActive(),
            defaultPrinter: this.qzConfig.defaultPrinterName,
            fallbackPrinters: this.qzConfig.fallbackPrinters,
            kasirName: this.getKasirName(),
            userId: this.getCurrentUserId()
          }
        };
      } else {
        return initResult;
      }
      
    } catch (error) {
      console.error("❌ QZ Connection test failed:", error);
      return {
        success: false,
        error: error.message,
        troubleshoot: [
          "Download dan install QZ Tray dari: https://qz.io/download/",
          "Jalankan aplikasi QZ Tray di system tray",
          "Pastikan websocket port 8182 tidak diblokir firewall",
          "Restart browser setelah install QZ Tray",
          "Cek printer sudah terdeteksi di Windows"
        ]
      };
    }
  },

  /**
   * Test print dengan mega kasir detection
   */
  async printTestReceipt() {
    console.log("🔥 MEGA TEST RECEIPT - Advanced Kasir Detection");
    
    const testData = {
      kode_transaksi: "MEGA-TEST-" + Date.now(),
      customer_name: "CUSTOMER MEGA TEST",
      customer_phone: "081234567890",
      tanggal_transaksi: new Date().toISOString(),
      kasir_name: this.getKasirName(), // Will be auto-detected
      kasir_id: this.getCurrentUserId(),
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
          subtotal: 30000
        },
        {
          layanan_nama: "Dry Clean",
          qty: 2,
          unit: "pcs",
          subtotal: 30000
        },
        {
          layanan_nama: "Setrika Saja",
          qty: 5,
          unit: "kg",
          subtotal: 10000
        }
      ],
      pickup_service: {
        service_name: "Pickup Express",
        jarak: 2.5,
        pickup_date: new Date().toISOString().split('T')[0],
        pickup_time: "09:00"
      }
    };

    console.log("🔥 Test data prepared with kasir:", testData.kasir_name);

    try {
      const result = await this.printReceipt(testData, {
        useQzTray: true,
        laundryInfo: {
          name: "MEGA LAUNDRY TEST",
          address: "Jl. Test No. 123, Test City",
          phone: "081234567890"
        }
      });

      return {
        success: true,
        message: "Mega test receipt berhasil dicetak!",
        details: result,
        kasirInfo: {
          name: testData.kasir_name,
          id: testData.kasir_id,
          detectionMethod: "Advanced Multi-Priority"
        }
      };

    } catch (error) {
      console.error("❌ Mega test print failed:", error);
      return {
        success: false,
        error: error.message,
        recommendation: "Coba printTestReceiptBrowser() untuk fallback test"
      };
    }
  },

  /**
   * Browser test print
   */
  async printTestReceiptBrowser() {
    console.log("🔥 MEGA BROWSER TEST - Advanced Kasir Detection");
    
    const testData = {
      kode_transaksi: "MEGA-BROWSER-" + Date.now(),
      customer_name: "CUSTOMER BROWSER MEGA",
      customer_phone: "081234567890",
      tanggal_transaksi: new Date().toISOString(),
      kasir_name: this.getKasirName(),
      kasir_id: this.getCurrentUserId(),
      total_amount: 20000,
      metode_pembayaran: "qris",
      jumlah_bayar: 20000,
      kembalian: 0,
      status_transaksi: "sukses",
      subtotal_layanan: 20000,
      biaya_pickup: 0,
      items: [
        {
          layanan_nama: "Cuci Kering",
          qty: 4,
          unit: "kg",
          subtotal: 20000
        }
      ]
    };

    return await this.printReceipt(testData, {
      useQzTray: false,
      laundryInfo: {
        name: "MEGA LAUNDRY BROWSER",
        address: "Jl. Browser Test No. 123",
        phone: "081234567890"
      }
    });
  },

  /**
   * 🚚 PRINT COURIER EARNINGS - Laporan Pendapatan Kurir
   * Format: Thermal Printer 58mm
   */
  printCourierEarnings(earningsData) {
    console.log('🚚 Printing courier earnings report...', earningsData);
    
    if (!qz.websocket || qz.websocket.readyState !== 1) {
      console.error('❌ QZ Tray not connected');
      alert('Printer tidak terhubung. Pastikan QZ Tray sudah berjalan.');
      return;
    }

    try {
      const { 
        courierName = 'Kurir', 
        totalEarnings = 0, 
        totalOrders = 0, 
        transactions = [],
        filter = 'today',
        startDate = null,
        endDate = null,
        printDate = ''
      } = earningsData;

      // Header
      let receipt = this.qzConfig.thermal58mm.initialize;
      receipt += this.qzConfig.thermal58mm.centerAlign;
      receipt += "==================================\n";
      receipt += "  LAPORAN PENDAPATAN KURIR\n";
      receipt += "==================================\n";
      receipt += this.qzConfig.thermal58mm.lineFeed;

      // Courier Info
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += "KURIR: " + courierName + "\n";
      receipt += this.qzConfig.thermal58mm.boldOff;
      receipt += this.qzConfig.thermal58mm.leftAlign;

      // Filter Type
      let filterText = 'HARI INI';
      if (filter === 'week') {
        filterText = 'MINGGU INI';
      } else if (filter === 'custom' && startDate && endDate) {
        filterText = `${startDate} s/d ${endDate}`;
      }
      receipt += "Periode: " + filterText + "\n";
      receipt += "Cetak: " + printDate + "\n";
      receipt += "==================================\n\n";

      // Transactions Detail
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += "DETAIL TRANSAKSI:\n";
      receipt += this.qzConfig.thermal58mm.boldOff;
      receipt += "-----------------------------------\n";

      let orderNum = 1;
      transactions.forEach((tx) => {
        // Order header
        receipt += "#" + orderNum + " " + (tx.kode_transaksi || 'N/A') + "\n";
        
        // Customer & Amount
        receipt += "Customer: " + (tx.customer_name || 'N/A') + "\n";
        receipt += "Total Pesanan: Rp " + this.formatCurrencySimple(tx.total_amount) + "\n";
        
        // Service Type
        if (tx.service_type) {
          const serviceLabel = tx.service_type === 'pickup_only' ? 'Ambil Saja' :
                              tx.service_type === 'delivery_only' ? 'Antar Saja' :
                              tx.service_type === 'pickup_delivery' ? 'Ambil & Antar' : tx.service_type;
          receipt += "Layanan: " + serviceLabel + "\n";
        }

        // Pickup Fee (Courier Earnings)
        const earnings = tx.biaya_pickup || 0;
        receipt += this.qzConfig.thermal58mm.bold;
        receipt += "Earnings: Rp " + this.formatCurrencySimple(earnings) + "\n";
        receipt += this.qzConfig.thermal58mm.boldOff;

        // Status
        const statusText = tx.status_transaksi === 'sukses' ? 'LUNAS' : 'PENDING';
        receipt += "Status: " + statusText + "\n";
        
        // Date
        const txDate = new Date(tx.updated_at);
        receipt += "Tgl: " + txDate.toLocaleDateString('id-ID', { month: '2-digit', day: '2-digit', year: '2-digit' }) + "\n";
        
        receipt += "-----------------------------------\n";
        orderNum++;
      });

      // Summary
      receipt += "\n";
      receipt += "==================================\n";
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += this.qzConfig.thermal58mm.centerAlign;
      receipt += "RINGKASAN PENDAPATAN\n";
      receipt += this.qzConfig.thermal58mm.boldOff;
      receipt += this.qzConfig.thermal58mm.leftAlign;
      receipt += "==================================\n";
      receipt += "Total Pesanan Selesai: " + totalOrders + " order\n";
      receipt += "Total Pendapatan: Rp " + this.formatCurrencySimple(totalEarnings) + "\n";
      
      if (totalOrders > 0) {
        const avgEarnings = Math.round(totalEarnings / totalOrders);
        receipt += "Rata-rata/Order: Rp " + this.formatCurrencySimple(avgEarnings) + "\n";
      }

      receipt += "==================================\n\n";
      receipt += this.qzConfig.thermal58mm.centerAlign;
      receipt += "Terima kasih telah bekerja!\n";
      receipt += "Semoga sehat selalu.\n";
      receipt += this.qzConfig.thermal58mm.lineFeed;
      receipt += this.qzConfig.thermal58mm.paperCut;

      // Send to printer
      const config = [{
        iceColor: 'black'
      }];

      const data = [{
        type: 'raw',
        format: 'command',
        flavor: 'line',
        commands: [receipt]
      }];

      console.log('📤 Sending to printer...', data);
      
      qz.print(config, data)
        .then(() => {
          console.log('✅ Courier earnings report printed successfully');
        })
        .catch((error) => {
          console.error('❌ Print error:', error);
          alert('Gagal mencetak laporan. Silakan coba lagi.');
        });

    } catch (error) {
      console.error('❌ Error preparing earnings report:', error);
      alert('Error: ' + error.message);
    }
  },

  /**
   * Simple currency formatter (without currency symbol)
   */
  formatCurrencySimple(amount) {
    if (!amount) return '0';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  },

  /**
   * 💰 PRINT CASHIER REVENUE - Laporan Revenue Kasir (Format Detail Per Order)
   * Format: Thermal Printer 58mm dengan detail items seperti receipt
   */
  printCashierRevenue(revenueData) {
    console.log('💰 Printing cashier revenue report...', revenueData);
    
    if (!qz.websocket || qz.websocket.readyState !== 1) {
      console.error('❌ QZ Tray not connected');
      alert('Printer tidak terhubung. Pastikan QZ Tray sudah berjalan.');
      return;
    }

    try {
      const { 
        cashierName = 'Kasir', 
        totalRevenue = 0, 
        totalOrders = 0, 
        orders = [],
        filter = 'today',
        period = '',
        printDate = ''
      } = revenueData;

      // Header
      let receipt = this.qzConfig.thermal58mm.initialize;
      receipt += this.qzConfig.thermal58mm.centerAlign;
      receipt += "==================================\n";
      receipt += "  FRESH LAUNDRY\n";
      receipt += "==================================\n";
      receipt += this.qzConfig.thermal58mm.lineFeed;

      // Period Info
      receipt += this.qzConfig.thermal58mm.leftAlign;
      receipt += "Periode: " + period + "\n";
      receipt += "Cetak: " + printDate + "\n";
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += "Kasir: " + cashierName + "\n";
      receipt += this.qzConfig.thermal58mm.boldOff;
      receipt += "==================================\n\n";

      // Payment Method Summary
      let tunaiTotal = 0;
      let qrisTotal = 0;
      let bayarNantiTotal = 0;

      orders.forEach((order) => {
        // Method summary
        if (order.metode_pembayaran === 'tunai') {
          tunaiTotal += order.total_amount || 0;
        } else if (order.metode_pembayaran === 'qris') {
          qrisTotal += order.total_amount || 0;
        } else if (order.metode_pembayaran === 'bayar_nanti') {
          bayarNantiTotal += order.total_amount || 0;
        }
      });

      // Summary Section
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += "RINGKASAN METODE PEMBAYARAN:\n";
      receipt += this.qzConfig.thermal58mm.boldOff;
      receipt += "-----------------------------------\n";
      receipt += "Tunai: Rp " + this.formatCurrencySimple(tunaiTotal) + "\n";
      receipt += "QRIS: Rp " + this.formatCurrencySimple(qrisTotal) + "\n";
      receipt += "Bayar Nanti: Rp " + this.formatCurrencySimple(bayarNantiTotal) + "\n";
      receipt += "-----------------------------------\n\n";

      // Detail Orders
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += "DETAIL PESANAN:\n";
      receipt += this.qzConfig.thermal58mm.boldOff;

      let orderNum = 1;
      orders.forEach((order) => {
        receipt += "-----------------------------------\n";
        
        // Order header
        receipt += this.qzConfig.thermal58mm.bold;
        receipt += "Order #" + orderNum + " - " + (order.kode_transaksi || 'N/A') + "\n";
        receipt += this.qzConfig.thermal58mm.boldOff;
        receipt += "Tgl: " + this.formatDateSimple(order.tanggal_transaksi) + "\n";
        receipt += "Customer: " + (order.customer_name || 'N/A') + "\n";
        
        // Items detail jika ada
        if (order.items && order.items.length > 0) {
          receipt += "\nLayanan:\n";
          let subtotalItems = 0;
          
          order.items.forEach((item) => {
            const qty = item.qty || item.jumlah || 1;
            const unit = item.unit || item.satuan || 'pcs';
            const subtotal = item.subtotal || item.total || 0;
            const pricePerUnit = qty > 0 ? Math.round(subtotal / qty) : subtotal;
            
            // Item name
            receipt += "  " + (item.layanan_nama || 'Item') + "\n";
            // Qty x Price = Subtotal
            receipt += "    " + qty + " " + unit + " x Rp" + this.formatCurrencySimple(pricePerUnit) + 
                      " = Rp" + this.formatCurrencySimple(subtotal) + "\n";
            subtotalItems += subtotal;
          });
          
          // Subtotal
          receipt += "Sub Total: Rp " + this.formatCurrencySimple(subtotalItems) + "\n";
        }

        // Biaya Pickup
        if (order.biaya_pickup && order.biaya_pickup > 0) {
          receipt += "Biaya Pickup: Rp " + this.formatCurrencySimple(order.biaya_pickup) + "\n";
        }

        // Total Amount
        receipt += this.qzConfig.thermal58mm.bold;
        receipt += "Total: Rp " + this.formatCurrencySimple(order.total_amount || 0) + "\n";
        receipt += this.qzConfig.thermal58mm.boldOff;
        
        // Payment Method & Status
        const methodLabel = order.metode_pembayaran === 'tunai' ? 'Tunai' :
                           order.metode_pembayaran === 'qris' ? 'QRIS' : 'Bayar Nanti';
        const statusText = order.status_transaksi === 'sukses' ? 'LUNAS' : 'PENDING';
        
        receipt += "Metode: " + methodLabel + "\n";
        receipt += "Status: " + statusText + "\n";

        // Payment details
        if (order.jumlah_bayar) {
          receipt += "Bayar: Rp " + this.formatCurrencySimple(order.jumlah_bayar) + "\n";
        }
        if (order.kembalian && order.kembalian > 0) {
          receipt += "Kembalian: Rp " + this.formatCurrencySimple(order.kembalian) + "\n";
        }

        orderNum++;
      });

      // Total Summary
      receipt += "-----------------------------------\n\n";
      receipt += "==================================\n";
      receipt += this.qzConfig.thermal58mm.bold;
      receipt += this.qzConfig.thermal58mm.centerAlign;
      receipt += "TOTAL REVENUE\n";
      receipt += this.qzConfig.thermal58mm.boldOff;
      receipt += this.qzConfig.thermal58mm.leftAlign;
      receipt += "==================================\n";
      receipt += "Total Orders: " + totalOrders + "\n";
      receipt += "Total Amount: Rp " + this.formatCurrencySimple(totalRevenue) + "\n";
      
      if (totalOrders > 0) {
        const avgRevenue = Math.round(totalRevenue / totalOrders);
        receipt += "Rata-rata/Order: Rp " + this.formatCurrencySimple(avgRevenue) + "\n";
      }

      receipt += "==================================\n\n";
      receipt += this.qzConfig.thermal58mm.centerAlign;
      receipt += "Laporan Revenue\n";
      receipt += "Terima kasih!\n\n";
      receipt += this.qzConfig.thermal58mm.lineFeed;
      receipt += this.qzConfig.thermal58mm.paperCut;

      // Send to printer
      const config = [{
        iceColor: 'black'
      }];

      const data = [{
        type: 'raw',
        format: 'command',
        flavor: 'line',
        commands: [receipt]
      }];

      console.log('📤 Sending cashier revenue report to printer...', data);
      
      qz.print(config, data)
        .then(() => {
          console.log('✅ Cashier revenue report printed successfully');
        })
        .catch((error) => {
          console.error('❌ Print error:', error);
          alert('Gagal mencetak laporan. Silakan coba lagi.');
        });

    } catch (error) {
      console.error('❌ Error preparing revenue report:', error);
      alert('Error: ' + error.message);
    }
  },

  /**
   * Simple date formatter for thermal print
   */
  formatDateSimple(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return day + '/' + month + '/' + year;
  }
};

// 🔥 MEGA GLOBAL FUNCTIONS untuk debugging di console
if (typeof window !== 'undefined') {
  window.megaDebugKasir = () => printHelpers.debugKasirInfo();
  window.megaSetKasir = (nama) => printHelpers.setKasirOverride(nama);
  window.megaClearKasir = () => printHelpers.clearKasirOverride();
  window.megaTestPrint = () => printHelpers.printTestReceipt();
  
  console.log('🔥 MEGA DEBUG FUNCTIONS AVAILABLE:');
  console.log('- window.megaDebugKasir() - Debug kasir info');
  console.log('- window.megaSetKasir("Nama Kasir") - Set manual kasir');
  console.log('- window.megaClearKasir() - Clear manual kasir');
  console.log('- window.megaTestPrint() - Test print receipt');
}

export default printHelpers;