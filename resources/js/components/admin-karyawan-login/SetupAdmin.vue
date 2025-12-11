<template>
    <div class="auth-overlay">
        <div class="auth-modal">
            <h2 class="auth-title">🚀 Setup Admin Pertama</h2>
            <p class="auth-subtitle">
                Selamat datang! Daftarkan admin pertama untuk mulai menggunakan sistem.
            </p>

            <form @submit.prevent="registerFirstAdmin" class="auth-form">
                <label for="nama">Nama Admin</label>
                <input
                    id="nama"
                    v-model="nama"
                    type="text"
                    placeholder="Contoh: Ahmad Susanto"
                    required
                />

                <label for="telepon">Nomor Telepon</label>
                <input
                    id="telepon"
                    v-model="telepon"
                    type="text"
                    placeholder="Contoh: 08123456789"
                    required
                />

                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    placeholder="Minimal 6 karakter"
                    required
                    minlength="6"
                />

                <div class="auth-actions">
                    <button
                        type="submit"
                        class="btn-submit"
                        :disabled="loading"
                    >
                        {{ loading ? "Mendaftar..." : "Daftar Admin" }}
                    </button>
                </div>
                <p class="forgot-info">
                    ⚠️ Setelah pendaftaran, sistem akan beralih ke mode login normal.
                </p>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import Swal from "sweetalert2";

const router = useRouter();
const nama = ref("");
const telepon = ref("");
const password = ref("");
const loading = ref(false);

// Cek apakah sudah ada admin, kalau sudah ada redirect ke login
const checkAdminExists = async () => {
    try {
        const response = await axios.get("/api/check-admin-exists");
        if (response.data.has_admin) {
            // Sudah ada admin, redirect ke login
            router.push("/AuthAdmin");
        }
    } catch (error) {
        console.error("Error checking admin:", error);
    }
};

// Registrasi admin pertama
const registerFirstAdmin = async () => {
    loading.value = true;
    try {
        const response = await axios.post("/api/register-first-admin", {
            nama: nama.value,
            telepon: telepon.value,
            password: password.value,
        });

        const user = response.data.user;
        localStorage.setItem("user", JSON.stringify(user));

        Swal.fire({
            icon: "success",
            title: "Setup Berhasil!",
            text: "Admin pertama berhasil didaftarkan. Selamat datang!",
        }).then(() => {
            router.push("/DashboardAdmin");
        });
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Gagal Mendaftar",
            text: err.response?.data?.message || "Terjadi kesalahan saat mendaftar.",
        });
    } finally {
        loading.value = false;
    }
};

// Cek saat component dimount
onMounted(() => {
    checkAdminExists();
});
</script>

<style scoped>
@import "../../assets/css/AuthAdmin.css";
</style>