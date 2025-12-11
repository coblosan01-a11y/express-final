<template>
  <div class="landing-container">
    <!-- Main Card -->
    <div class="landing-card">
      <!-- Logo Section -->
      <div class="header-section">
        <div class="logo-container">
          <img src="../assets/img/Logo-navsidebar.png" alt="Logo" class="logo-image" />
        </div>
        <h1 class="title">Laundry Fresh Clean</h1>
        <p class="subtitle">Masuk ke sistem manajemen laundry</p>
      </div>

      <!-- Role Selection -->
      <div class="role-section">
        <button 
          @click="openAdminModal"
          class="role-btn admin-btn"
        >
          <div class="btn-content">
            <span class="btn-icon">👑</span>
            <span class="btn-text">ADMIN PANEL</span>
          </div>
        </button>

        <button 
          @click="openKaryawanModal"
          class="role-btn karyawan-btn"
        >
          <div class="btn-content">
            <span class="btn-icon">👨‍💼</span>
            <span class="btn-text">KARYAWAN</span>
          </div>
        </button>
      </div>
    </div>

    <!-- Modal Components -->
    <AuthAdmin v-if="showAdminModal" @close="closeAdminModal" />
    <AuthKaryawan v-if="showKaryawanModal" @close="closeKaryawanModal" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AuthAdmin from '../components/admin-karyawan-login/AuthAdmin.vue'
import AuthKaryawan from '../components/admin-karyawan-login/AuthKaryawan.vue'

const showAdminModal = ref(false)
const showKaryawanModal = ref(false)

const openAdminModal = () => {
  showAdminModal.value = true
}

const closeAdminModal = () => {
  showAdminModal.value = false
}

const openKaryawanModal = () => {
  showKaryawanModal.value = true
}

const closeKaryawanModal = () => {
  showKaryawanModal.value = false
}


</script>

<style scoped>
@import '../assets/css/LandingPage.css';
</style>