<template>
  <div class="navigation-container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <!-- User Profile Header -->
      <div class="sidebar-header">
        <div class="user-profile">
          <div class="user-avatar">{{ getUserIcon() }}</div>
          <div class="user-info">
            <h3 class="user-name">{{ user?.nama || 'Admin' }}</h3>
            <span class="user-role">{{ getUserRole() }}</span>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="nav-menu">
        <router-link
          to="/DashboardAdmin/Laporan"
          class="nav-item"
          :class="{ active: route.path.includes('/DashboardAdmin/Laporan') }"
        >
          <div class="nav-icon">
             <img src="../../assets/img/Grafik.png" alt="Logo" class="grafik-logo" />
          </div>
          <span class="nav-text">Laporan Harian</span>
        </router-link>

        <router-link
          to="/DashboardAdmin/Karyawan"
          class="nav-item"
          :class="{ active: route.path.includes('/DashboardAdmin/Karyawan') }"
        >
          <div class="nav-icon">
            <img src="../../assets/img/User.png" alt="Logo" class="user-logo" />
          </div>
          <span class="nav-text">Data Karyawan</span>
        </router-link>

        <router-link
          to="/DashboardAdmin/Layanan"
          class="nav-item"
          :class="{ active: route.path.includes('/DashboardAdmin/Layanan') }"
        >
          <div class="nav-icon">
            <img src="../../assets/img/Kind.png" alt="Logo" class="kind-logo" />
          </div>
          <span class="nav-text">Jenis Layanan</span>
        </router-link>

        <router-link
          to="/DashboardAdmin/PickupManement"
          class="nav-item"
          :class="{ active: route.path.includes('/DashboardAdmin//PickupManement') }"
        >
          <div class="nav-icon">
            <img src="../../assets/img/Location.png" alt="Logo" class="location-logo" />
          </div>
          <span class="nav-text">Zona Pickup</span>
        </router-link>

        <router-link
          to="/DashboardAdmin/Pengaturan"
          class="nav-item"
          :class="{ active: route.path.includes('/DashboardAdmin/Pengaturan') }"
          v-if="isPemilik()"
        >
          <div class="nav-icon">
            <img src="../../assets/img/Setting.png" alt="Logo" class="setting-logo" />
          </div>
          <span class="nav-text">Pengaturan</span>
        </router-link>
      </nav>

      <!-- Logout Button -->
      <div class="sidebar-footer">
        <button class="logout-btn" @click="logout">
          <div class="nav-icon">🚪</div>
          <span class="nav-text">Logout</span>
        </button>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
      <!-- Dynamic Content -->
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const user = ref(null)

// Load user data
onMounted(() => {
  const userData = localStorage.getItem('user')
  if (userData) {
    user.value = JSON.parse(userData)
  }
})

// Get user icon
const getUserIcon = () => {
  if (!user.value) return '👨‍💼'
  if (user.value.role === 'pemilik' || user.value.jabatan === 'pemilik') return '👑'
  return '👨‍💼'
}

// Get user role
const getUserRole = () => {
  if (!user.value) return 'Administrator'
  if (user.value.role === 'pemilik' || user.value.jabatan === 'pemilik') return 'Pemilik'
  if (user.value.role === 'karyawan_admin' || user.value.jabatan === 'admin') return 'Admin Karyawan'
  return 'Administrator'
}

// Check if user is pemilik (for conditional menu)
const isPemilik = () => {
  if (!user.value) return false
  return user.value.role === 'pemilik' || user.value.jabatan === 'pemilik'
}

// Logout function
const logout = () => {
  Swal.fire({
    title: 'Yakin ingin logout?',
    text: 'Anda akan keluar dari admin panel',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Logout',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'btn-danger',
      cancelButton: 'btn-secondary'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      localStorage.removeItem('user')
      router.push('/')
      Swal.fire({
        icon: 'success',
        title: 'Logout Berhasil',
        timer: 1500,
        showConfirmButton: false
      })
    }
  })
}
</script>

<style scoped>
  @import '../../assets/css/NavSidebarAdmin.css';
</style>