// resources/js/stores/PiniaCustomer.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useCustomerStore = defineStore('customer', {
  state: () => ({
    // Daftar semua customer
    allCustomers: [],
    
    // Customer yang sedang dipilih untuk transaksi
    selectedCustomer: null,
    
    // Data input customer
    customerPhone: '',
    customerInfo: null,
    
    // Status loading dan search
    isLoadingCustomers: false,
    isSearching: false,
    searchAttempted: false,
    
    // Cache untuk performance
    customerCache: new Map()
  }),

  getters: {
    // Nama customer yang ditampilkan
    customerDisplayName: (state) => {
      if (!state.customerInfo) return 'Customer Baru'
      return state.customerInfo.nama_pelanggan || state.customerInfo.nama || 'Customer'
    },

    // Phone number yang bersih (hanya angka)
    cleanPhone: (state) => {
      return state.customerPhone.replace(/[^\d]/g, '')
    },

    // Cek apakah customer valid
    isCustomerValid: (state) => {
      return state.customerPhone.trim().length >= 8
    },

    // Customer initials untuk avatar
    customerInitials: (state) => {
      const name = state.customerDisplayName
      if (!name || typeof name !== 'string') return '?'
      return name.charAt(0).toUpperCase()
    }
  },

  actions: {
    // Set loading status
    setLoadingCustomers(status) {
      this.isLoadingCustomers = status
    },

    // Set searching status
    setSearching(status) {
      this.isSearching = status
    },

    // Load semua customer dari API
    async loadAllCustomers() {
      this.setLoadingCustomers(true)
      
      try {
        const response = await axios.get('/api/pelanggan')
        
        // Handle different response structures
        if (Array.isArray(response.data)) {
          this.allCustomers = response.data
        } else if (response.data && Array.isArray(response.data.data)) {
          this.allCustomers = response.data.data
        } else {
          this.allCustomers = []
        }
        
        // Update cache
        this.updateCustomerCache()
        
        console.log('Customers loaded:', this.allCustomers.length)
        return this.allCustomers
        
      } catch (error) {
        console.error('Error loading customers:', error)
        this.allCustomers = []
        throw error
      } finally {
        this.setLoadingCustomers(false)
      }
    },

    // Update customer cache untuk performance
    updateCustomerCache() {
      this.customerCache.clear()
      this.allCustomers.forEach(customer => {
        const phone = (customer.telepon || customer.phone || '').replace(/[^\d]/g, '')
        if (phone) {
          this.customerCache.set(phone, customer)
        }
      })
    },

    // Set customer phone
    setCustomerPhone(phone) {
      this.customerPhone = phone.replace(/[^\d]/g, '')
      
      // Reset customer info when phone changes
      if (this.customerInfo) {
        this.customerInfo = null
        this.searchAttempted = false
      }
    },

    // Search customer by phone
    async searchCustomer(phone = null) {
      const searchPhone = phone || this.customerPhone
      
      if (!searchPhone.trim()) {
        this.searchAttempted = false
        this.customerInfo = null
        return null
      }
      
      this.setSearching(true)
      this.searchAttempted = true
      
      try {
        const cleanPhone = searchPhone.replace(/[^\d]/g, '')
        
        // Cari dari cache dulu
        if (this.customerCache.has(cleanPhone)) {
          this.customerInfo = this.customerCache.get(cleanPhone)
          console.log('Customer found in cache:', this.customerInfo)
          return this.customerInfo
        }
        
        // Jika tidak ada di cache, cari dari array
        const foundCustomer = this.allCustomers.find(customer => {
          const phoneToCheck = (customer.telepon || customer.phone || '').replace(/[^\d]/g, '')
          return phoneToCheck === cleanPhone
        })
        
        if (foundCustomer) {
          this.customerInfo = foundCustomer
          this.customerCache.set(cleanPhone, foundCustomer)
          console.log('Customer found:', foundCustomer)
        } else {
          this.customerInfo = null
          console.log('Customer not found for phone:', searchPhone)
        }
        
        return this.customerInfo
        
      } catch (error) {
        console.error('Error searching customer:', error)
        this.customerInfo = null
        return null
      } finally {
        this.setSearching(false)
      }
    },

    // Set selected customer
    setSelectedCustomer(customer) {
      this.selectedCustomer = customer
      this.customerInfo = customer
      
      if (customer) {
        this.customerPhone = customer.telepon || customer.phone || ''
        this.searchAttempted = true
      }
    },

    // Clear customer selection
    clearCustomerSelection() {
      this.selectedCustomer = null
      this.customerInfo = null
      this.customerPhone = ''
      this.searchAttempted = false
    },

    // Add new customer to list
    addCustomer(newCustomer) {
      this.allCustomers.push(newCustomer)
      this.updateCustomerCache()
      
      // Auto select the new customer
      this.setSelectedCustomer(newCustomer)
    },

    // Update existing customer
    updateCustomer(updatedCustomer) {
      const index = this.allCustomers.findIndex(customer => customer.id === updatedCustomer.id)
      if (index !== -1) {
        this.allCustomers[index] = updatedCustomer
        this.updateCustomerCache()
        
        // Update selected customer if it's the same
        if (this.selectedCustomer && this.selectedCustomer.id === updatedCustomer.id) {
          this.setSelectedCustomer(updatedCustomer)
        }
      }
    },

    // Remove customer
    removeCustomer(customerId) {
      const index = this.allCustomers.findIndex(customer => customer.id === customerId)
      if (index !== -1) {
        this.allCustomers.splice(index, 1)
        this.updateCustomerCache()
        
        // Clear selection if removed customer was selected
        if (this.selectedCustomer && this.selectedCustomer.id === customerId) {
          this.clearCustomerSelection()
        }
      }
    },

    // Save customer selection to session storage (untuk navigasi antar halaman)
    saveCustomerToSession() {
      if (this.customerInfo) {
        try {
          sessionStorage.setItem('selectedCustomer', JSON.stringify(this.customerInfo))
        } catch (error) {
          console.error('Error saving customer to session:', error)
        }
      }
    },

    // Restore customer from session storage
    restoreCustomerFromSession() {
      try {
        const savedCustomer = sessionStorage.getItem('selectedCustomer')
        if (savedCustomer) {
          const customer = JSON.parse(savedCustomer)
          this.setSelectedCustomer(customer)
          sessionStorage.removeItem('selectedCustomer')
          return customer
        }
      } catch (error) {
        console.error('Error restoring customer from session:', error)
      }
      return null
    },

    // Get customer by ID
    getCustomerById(id) {
      return this.allCustomers.find(customer => customer.id === id)
    },

    // Search customers by name or phone
    searchCustomers(query) {
      if (!query.trim()) return this.allCustomers
      
      const searchTerm = query.toLowerCase()
      return this.allCustomers.filter(customer => {
        const name = (customer.nama_pelanggan || customer.nama || '').toLowerCase()
        const phone = (customer.telepon || customer.phone || '').replace(/[^\d]/g, '')
        const searchPhone = query.replace(/[^\d]/g, '')
        
        return name.includes(searchTerm) || phone.includes(searchPhone)
      })
    }
  }
})