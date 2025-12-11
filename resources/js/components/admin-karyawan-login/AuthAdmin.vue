<template>
  <div class="auth-overlay" @click.self="$emit('close')">
    <div class="auth-modal">
      <h2 class="auth-title">👑 Login Admin</h2>
      <p class="auth-subtitle">
        Masukkan nama dan password admin untuk mengakses sistem
        manajemen.
      </p>

      <form @submit.prevent="loginWithValidation" class="auth-form">
        <label for="nama">Nama Admin</label>
        <input
          id="nama"
          v-model="nama"
          type="text"
          placeholder="Masukkan nama admin"
          :class="{ error: inputErrors.nama, success: inputSuccess.nama }"
          required
          autocomplete="username"
        />

        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          placeholder="********"
          :class="{ error: inputErrors.password, success: inputSuccess.password }"
          required
          autocomplete="current-password"
        />

        <div class="auth-actions">
          <button
            type="submit"
            class="btn-submit"
            :disabled="loading"
          >
            {{ loading ? "Memproses..." : "Masuk" }}
          </button>
          <button
            type="button"
            class="btn-cancel"
            @click="$emit('close')"
            :disabled="loading"
          >
            Batal
          </button>
        </div>
        <p class="forgot-info">
          🔑 Lupa password? Hubungi administrator sistem.
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import Swal from "sweetalert2";

const router = useRouter();
const nama = ref("");
const password = ref("");
const loading = ref(false);
const loginAttempts = ref(0);
const maxAttempts = 5;

// Input validation states
const inputErrors = reactive({
  nama: false,
  password: false
});

const inputSuccess = reactive({
  nama: false,
  password: false
});

const emit = defineEmits(["close"]);

// Cek apakah sudah ada admin, kalau belum ada redirect ke setup
const checkAdminExists = async () => {
  try {
    const response = await axios.get("/api/check-admin-exists");
    if (!response.data.has_admin) {
      // Belum ada admin, redirect ke setup
      await Swal.fire({
        icon: 'info',
        title: 'Setup Required',
        text: 'Belum ada admin terdaftar. Anda akan diarahkan ke halaman setup admin.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#3b82f6'
      });
      
      emit("close");
      router.push("/SetupAdmin");
    }
  } catch (error) {
    console.error("Error checking admin:", error);
    
    await Swal.fire({
      icon: 'error',
      title: 'Server Error',
      text: 'Tidak dapat memeriksa status admin. Periksa koneksi server.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#ef4444'
    });
  }
};

// Input validation function
const validateInput = () => {
  let isValid = true;
  
  // Reset states
  inputErrors.nama = false;
  inputErrors.password = false;
  inputSuccess.nama = false;
  inputSuccess.password = false;
  
  // Validate nama
  if (!nama.value.trim()) {
    inputErrors.nama = true;
    Swal.fire({
      icon: 'warning',
      title: 'Nama Admin Diperlukan',
      text: 'Silakan masukkan nama admin.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#f59e0b'
    });
    return false;
  }
  
  if (nama.value.trim().length < 3) {
    inputErrors.nama = true;
    Swal.fire({
      icon: 'warning',
      title: 'Nama Terlalu Pendek',
      text: 'Nama admin harus memiliki minimal 3 karakter.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#f59e0b'
    });
    return false;
  }
  
  // Validate password
  if (!password.value.trim()) {
    inputErrors.password = true;
    Swal.fire({
      icon: 'warning',
      title: 'Password Diperlukan',
      text: 'Silakan masukkan password admin.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#f59e0b'
    });
    return false;
  }
  
  if (password.value.length < 6) {
    inputErrors.password = true;
    Swal.fire({
      icon: 'warning',
      title: 'Password Terlalu Pendek',
      text: 'Password admin harus memiliki minimal 6 karakter.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#f59e0b'
    });
    return false;
  }
  
  // Check rate limiting
  if (loginAttempts.value >= maxAttempts) {
    Swal.fire({
      icon: 'error',
      title: 'Terlalu Banyak Percobaan',
      text: `Anda telah mencoba login ${maxAttempts} kali. Silakan tunggu beberapa menit sebelum mencoba lagi.`,
      confirmButtonText: 'OK',
      confirmButtonColor: '#ef4444'
    });
    return false;
  }
  
  // Set success states if validation passes
  inputSuccess.nama = true;
  inputSuccess.password = true;
  
  return true;
};

// Enhanced login function
const login = async () => {
  loading.value = true;
  loginAttempts.value++;
  
  try {
    console.log(`🔐 Admin login attempt ${loginAttempts.value} for: ${nama.value}`);
    
    const response = await axios.post("/api/login-admin", {
      nama: nama.value.trim(),
      password: password.value,
    });

    const user = response.data.user;
    
    // 🔒 VALIDASI ROLE ADMIN - Izinkan admin dan pemilik
    const userRole = user.role || user.jabatan;
    
    // Daftar role yang diizinkan akses dashboard admin
    const allowedAdminRoles = ['admin', 'pemilik', 'owner', 'super_admin'];
    
    if (!userRole || !allowedAdminRoles.includes(userRole.toLowerCase())) {
      await Swal.fire({
        icon: 'error',
        title: 'Akses Ditolak',
        text: `Akun ini tidak memiliki akses admin. Role Anda: "${userRole}". Hanya admin/pemilik yang dapat mengakses dashboard admin.`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#ef4444'
      });
      
      // Reset form
      nama.value = '';
      password.value = '';
      inputErrors.nama = true;
      inputErrors.password = true;
      inputSuccess.nama = false;
      inputSuccess.password = false;
      
      return;
    }
    
    // Tentukan level akses berdasarkan role
    let accessLevel = 'admin';
    let displayRole = userRole;
    
    if (userRole.toLowerCase() === 'pemilik' || userRole.toLowerCase() === 'owner') {
      accessLevel = 'owner';
      displayRole = 'Pemilik';
    } else if (userRole.toLowerCase() === 'super_admin') {
      accessLevel = 'super_admin';
      displayRole = 'Super Admin';
    } else {
      displayRole = 'Admin';
    }
    
    // Simpan data admin dengan role yang sudah divalidasi
    const adminData = {
      ...user,
      role: userRole,
      access_level: accessLevel,
      is_owner: userRole.toLowerCase() === 'pemilik' || userRole.toLowerCase() === 'owner',
      is_super_admin: userRole.toLowerCase() === 'super_admin',
      login_time: new Date().toISOString(),
      login_type: 'admin_dashboard'
    };
    
    localStorage.setItem("user", JSON.stringify(adminData));
    
    // Log successful admin login
    console.log(`✅ ${displayRole} login berhasil: ${user.nama} (Role: ${userRole})`);
    
    // Reset login attempts on success
    loginAttempts.value = 0;
    
    // Success message dengan informasi role
    let welcomeMessage = `Selamat datang di dashboard admin, ${user.nama}!`;
    if (accessLevel === 'owner') {
      welcomeMessage += ' (Pemilik)';
    } else if (accessLevel === 'super_admin') {
      welcomeMessage += ' (Super Admin)';
    }
    
    await Swal.fire({
      icon: "success",
      title: "Berhasil Login",
      text: welcomeMessage,
      timer: 2500,
      showConfirmButton: false,
      timerProgressBar: true
    });
    
    // Reset form
    nama.value = "";
    password.value = "";
    inputErrors.nama = false;
    inputErrors.password = false;
    inputSuccess.nama = false;
    inputSuccess.password = false;
    
    emit("close");
    router.push("/DashboardAdmin");
    
  } catch (err) {
    console.error('❌ Admin login error:', err);
    
    let errorMessage = 'Terjadi kesalahan saat login admin.';
    let errorTitle = 'Gagal Login';
    let errorIcon = 'error';
    let errorColor = '#ef4444';
    
    if (err.response) {
      const status = err.response.status;
      const responseData = err.response.data;
      
      switch (status) {
        case 401:
          errorMessage = 'Nama admin atau password salah! Periksa kembali kredensial admin Anda.';
          inputErrors.nama = true;
          inputErrors.password = true;
          break;
        case 403:
          errorMessage = 'Akun admin Anda dinonaktifkan atau tidak memiliki akses. Hubungi sistem administrator.';
          break;
        case 404:
          errorMessage = 'Endpoint login admin tidak ditemukan. Periksa konfigurasi server.';
          errorTitle = 'Server Configuration Error';
          break;
        case 422:
          errorMessage = responseData?.message || 'Data login admin tidak valid. Periksa format input.';
          inputErrors.nama = true;
          inputErrors.password = true;
          break;
        case 429:
          errorMessage = 'Terlalu banyak percobaan login admin. Silakan tunggu beberapa menit sebelum mencoba lagi.';
          errorTitle = 'Rate Limit Exceeded';
          errorIcon = 'warning';
          errorColor = '#f59e0b';
          break;
        case 500:
          errorMessage = 'Server mengalami gangguan internal. Silakan coba lagi dalam beberapa saat atau hubungi tim teknis.';
          errorTitle = 'Internal Server Error';
          break;
        default:
          errorMessage = responseData?.message || `Error ${status}: ${err.response.statusText}`;
      }
    } else if (err.request) {
      errorMessage = 'Tidak dapat terhubung ke server admin. Periksa koneksi internet dan status server.';
      errorTitle = 'Koneksi Bermasalah';
      errorIcon = 'warning';
      errorColor = '#f59e0b';
    } else {
      errorMessage = `Network Error: ${err.message}`;
    }
    
    // Show rate limiting warning if approaching limit
    if (loginAttempts.value >= maxAttempts - 1) {
      errorMessage += `\n\nPeringatan: Anda tinggal ${maxAttempts - loginAttempts.value} kali percobaan lagi sebelum akun dikunci sementara.`;
    }
    
    await Swal.fire({
      icon: errorIcon,
      title: errorTitle,
      text: errorMessage,
      confirmButtonText: 'Coba Lagi',
      confirmButtonColor: errorColor
    });
    
    // Reset password field on auth error
    if (err.response?.status === 401) {
      password.value = '';
      inputSuccess.nama = false;
      inputSuccess.password = false;
    }
    
  } finally {
    loading.value = false;
  }
};

// Login with validation wrapper
const loginWithValidation = async () => {
  if (!validateInput()) {
    return;
  }
  
  await login();
};

// Reset rate limiting after timeout
const resetRateLimit = () => {
  setTimeout(() => {
    if (loginAttempts.value > 0) {
      loginAttempts.value = Math.max(0, loginAttempts.value - 1);
      console.log(`Rate limit reset. Remaining attempts: ${maxAttempts - loginAttempts.value}`);
    }
  }, 5 * 60 * 1000); // Reset 1 attempt every 5 minutes
};

// Watch for input changes to clear error states
watch([nama, password], () => {
  // Clear error states when user types
  if (nama.value.trim().length >= 3) {
    inputErrors.nama = false;
  }
  if (password.value.length >= 6) {
    inputErrors.password = false;
  }
});

// Cek saat component dimount
onMounted(() => {
  checkAdminExists();
  resetRateLimit();
  
  console.log('🔐 AuthAdmin component mounted');
  console.log(`Max login attempts: ${maxAttempts}`);
  console.log('Allowed admin roles: admin, pemilik, owner, super_admin');
});
</script>

<style scoped>
@import "../../assets/css/AuthAdmin.css";
</style>