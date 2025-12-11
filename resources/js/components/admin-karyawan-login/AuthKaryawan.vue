<template>
  <div class="auth-overlay" @click.self="emit('close')">
    <div class="auth-modal">
      <h2 class="auth-title">👨‍💼 Login Karyawan</h2>
      <p class="auth-subtitle">
        Masukkan nama dan password yang telah diberikan oleh admin.
      </p>
      <form @submit.prevent="login" class="auth-form">
        <label for="nama">Nama Karyawan</label>
        <input
          id="nama"
          v-model="nama"
          type="text"
          placeholder="Contoh: Ahmad"
          required
        />
        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          placeholder="********"
          required
        />
        <div class="auth-actions">
          <button type="submit" class="btn-submit" :disabled="loading">
            {{ loading ? "Memproses..." : "Masuk" }}
          </button>
          <button type="button" class="btn-cancel" @click="emit('close')">
            Batal
          </button>
        </div>
        <p class="forgot-info">
          🔒 Lupa password? Hubungi admin untuk reset password.
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

// Definisikan emit terlebih dahulu
const emit = defineEmits(['close'])

const router = useRouter()
const nama = ref('')
const password = ref('')
const loading = ref(false)

const login = async () => {
  loading.value = true
  
  try {
    const response = await axios.post('/api/login-karyawan', {
      nama: nama.value,
      password: password.value,
    })
    
    const user = response.data.user
    
    // Pastikan role ada, gunakan jabatan jika role tidak ada
    const userRole = user.role || user.jabatan
    
    // 🚫 BLOKIR ADMIN - Cek apakah user adalah admin
    if (userRole && userRole.toLowerCase() === 'admin') {
      await Swal.fire({
        icon: 'error',
        title: 'Akses Ditolak',
        text: 'Admin tidak dapat mengakses sistem melalui login Kasir. Silakan gunakan dashboard admin yang terpisah.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ef4444'
      })
      
      // Reset form
      nama.value = ''
      password.value = ''
      
      return // Stop execution
    }
    
    // Validasi role yang diizinkan (hanya kasir dan kurir)
    const allowedRoles = ['kasir', 'kurir']
    const normalizedRole = userRole ? userRole.toLowerCase() : ''
    
    if (!allowedRoles.includes(normalizedRole)) {
      await Swal.fire({
        icon: 'warning',
        title: 'Role Tidak Dikenali',
        text: `Role "${userRole}" tidak memiliki akses ke sistem karyawan. Hubungi admin untuk informasi lebih lanjut.`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#f59e0b'
      })
      
      // Reset form
      nama.value = ''
      password.value = ''
      
      return // Stop execution
    }
    
    // Simpan data user ke localStorage dengan role yang sudah divalidasi
    const userData = {
      ...user,
      role: normalizedRole
    }
    localStorage.setItem('user', JSON.stringify(userData))
    
    // Tentukan route berdasarkan role (hanya kasir dan kurir)
    let targetRoute = '/DashboardKasir' // default untuk kasir
    let welcomeMessage = 'Selamat datang kembali!'
    
    switch (normalizedRole) {
      case 'kasir':
        targetRoute = '/DashboardKasir'
        welcomeMessage = 'Selamat datang di Dashboard Kasir!'
        break
      case 'kurir':
        targetRoute = '/DashboardKurir'
        welcomeMessage = 'Selamat datang di Dashboard Kurir!'
        break
      default:
        // Fallback untuk role yang tidak dikenali (seharusnya tidak sampai sini)
        targetRoute = '/DashboardKasir'
        welcomeMessage = `Selamat datang, ${user.nama}!`
    }
    
    // Log successful login untuk monitoring
    console.log(`✅ Login berhasil: ${user.nama} (${normalizedRole})`)
    
    // Tampilkan notifikasi sukses
    await Swal.fire({
      icon: 'success',
      title: 'Berhasil Login',
      text: welcomeMessage,
      timer: 1500,
      showConfirmButton: false
    })
    
    // Reset form
    nama.value = ''
    password.value = ''
    
    // Emit close event
    emit('close')
    
    // Redirect ke dashboard sesuai role
    await router.push(targetRoute)
    
  } catch (err) {
    console.error('❌ Login error:', err)
    
    let errorMessage = 'Terjadi kesalahan saat login.'
    let errorTitle = 'Gagal Login'
    let errorIcon = 'error'
    
    if (err.response) {
      const status = err.response.status
      const responseData = err.response.data
      
      switch (status) {
        case 401:
          errorMessage = 'Nama atau password salah! Periksa kembali kredensial Anda.'
          break
        case 403:
          errorMessage = 'Akun Anda tidak memiliki akses atau sedang dinonaktifkan. Hubungi admin untuk bantuan.'
          break
        case 404:
          errorMessage = 'Endpoint login tidak ditemukan. Hubungi admin untuk bantuan teknis.'
          break
        case 422:
          errorMessage = responseData?.message || 'Data yang dimasukkan tidak valid.'
          break
        case 429:
          errorMessage = 'Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa menit.'
          errorTitle = 'Rate Limit Exceeded'
          errorIcon = 'warning'
          break
        case 500:
          errorMessage = 'Server mengalami gangguan. Silakan coba lagi dalam beberapa saat.'
          errorTitle = 'Server Error'
          break
        default:
          errorMessage = responseData?.message || `Error ${status}: ${err.response.statusText}`
      }
    } else if (err.request) {
      errorMessage = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'
      errorTitle = 'Koneksi Bermasalah'
      errorIcon = 'warning'
    } else {
      errorMessage = `Error: ${err.message}`
    }
    
    await Swal.fire({
      icon: errorIcon,
      title: errorTitle,
      text: errorMessage,
      confirmButtonText: 'Coba Lagi',
      confirmButtonColor: errorIcon === 'error' ? '#ef4444' : '#f59e0b'
    })
    
    // Reset password field saja jika error 401 (kredensial salah)
    if (err.response?.status === 401) {
      password.value = ''
    }
    
  } finally {
    loading.value = false
  }
}

// Optional: Add method untuk validasi input sebelum submit
const validateInput = () => {
  if (!nama.value.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Nama Diperlukan',
      text: 'Silakan masukkan nama karyawan.',
      confirmButtonText: 'OK'
    })
    return false
  }
  
  if (!password.value.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Password Diperlukan',
      text: 'Silakan masukkan password.',
      confirmButtonText: 'OK'
    })
    return false
  }
  
  if (nama.value.trim().length < 2) {
    Swal.fire({
      icon: 'warning',
      title: 'Nama Terlalu Pendek',
      text: 'Nama harus memiliki minimal 2 karakter.',
      confirmButtonText: 'OK'
    })
    return false
  }
  
  return true
}

// Enhanced login function dengan validasi
const loginWithValidation = async () => {
  if (!validateInput()) {
    return
  }
  
  await login()
}
</script>

<style scoped>
@import '../../assets/css/AuthKaryawan.css';
</style>