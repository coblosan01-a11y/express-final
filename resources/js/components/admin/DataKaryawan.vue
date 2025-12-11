<template>
    <CardLayout>
        <div class="data-karyawan-container">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h2>Data Karyawan</h2>
                        <p>Daftar semua karyawan yang terdaftar dalam sistem</p>
                    </div>
                    <div class="actions">
                        <button class="btn-blue" @click="showModal = true">
                            Tambah Karyawan
                        </button>
                        <button class="btn-outline" @click="exportCSV">
                            ⬇️ Export CSV
                        </button>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <button 
                        class="filter-tab" 
                        :class="{ active: activeFilter === 'semua' }"
                        @click="setFilter('semua')"
                    >
                        Semua ({{ karyawans.length }})
                    </button>
                    <button 
                        class="filter-tab" 
                        :class="{ active: activeFilter === 'admin' }"
                        @click="setFilter('admin')"
                    >
                        Admin ({{ getCountByJabatan('admin') }})
                    </button>
                    <button 
                        class="filter-tab" 
                        :class="{ active: activeFilter === 'kasir' }"
                        @click="setFilter('kasir')"
                    >
                        Kasir ({{ getCountByJabatan('kasir') }})
                    </button>
                    <button 
                        class="filter-tab" 
                        :class="{ active: activeFilter === 'kurir' }"
                        @click="setFilter('kurir')"
                    >
                        Kurir ({{ getCountByJabatan('kurir') }})
                    </button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Telepon</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th>Access Level</th>
                            <th>Password</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(karyawan, index) in filteredKaryawans" :key="karyawan.id">
                            <td>{{ karyawan.nama }}</td>
                            <td>{{ karyawan.telepon }}</td>
                            <td>
                                <span class="role-badge" :class="karyawan.jabatan">
                                    {{ getRoleDisplay(karyawan.jabatan) }}
                                </span>
                            </td>
                            <td>
                                <span class="status" :class="karyawan.status">
                                    {{ karyawan.status }}
                                </span>
                            </td>
                            <td>
                                <span class="access-level" :class="getAccessLevelClass(karyawan.jabatan)">
                                    {{ getAccessLevelDisplay(karyawan.jabatan) }}
                                </span>
                            </td>
                            <td>
                                <span v-if="karyawan.showPassword">{{
                                    karyawan.password
                                }}</span>
                                <span v-else>••••••••••</span>
                                <button
                                    class="eye-btn"
                                    @click="togglePassword(karyawan)"
                                >
                                    {{ karyawan.showPassword ? "🙈" : "👁️" }}
                                </button>
                            </td>
                            <td class="aksi-buttons">
                                <button
                                    class="btn-small edit"
                                    @click="editKaryawan(karyawan)"
                                >
                                    <img src="../../assets/img/Edit.png" alt="Logo" class="edit-logo" /> Edit
                                </button>
                                <button
                                    class="btn-small pass"
                                    @click="gantiPassword(karyawan)"
                                >
                                    <img src="../../assets/img/Key.png" alt="Logo" class="key-logo"/> Password
                                </button>
                                <button
                                    class="btn-small"
                                    :class="karyawan.status === 'aktif' ? 'warning' : 'success'"
                                    @click="toggleStatus(karyawan, index)"
                                >
                                    {{ karyawan.status === 'aktif' ? '🚫 Nonaktif' : '✅ Aktif' }}
                                </button>
                                <button
                                    class="btn-danger"
                                    @click="hapusKaryawan(karyawan.id, index)"
                                >
                                    <img src="../../assets/img/Delete.png" alt="Logo" class="delete-logo" /> Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="filteredKaryawans.length === 0" class="no-data">
                    <p>📭 Tidak ada karyawan {{ activeFilter !== 'semua' ? `dengan jabatan ${activeFilter}` : '' }}</p>
                </div>
            </div>

            <!-- Modals -->
            <DaftarKaryawan
                v-if="showModal"
                @close="showModal = false"
                @submitted="ambilDataKaryawan"
            />
            <UbahPasswordKaryawan
                v-if="showPasswordModal"
                :id="selectedKaryawan.id"
                :nama="selectedKaryawan.nama"
                @close="showPasswordModal = false"
                @updated="ambilDataKaryawan"
            />
            <EditDataKaryawan
                v-if="showEditModal"
                :karyawan="selectedKaryawanEdit"
                @close="showEditModal = false"
                @updated="ambilDataKaryawan"
            />
        </div>
    </CardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import DaftarKaryawan from "../admin/karyawan-handling/DaftarKaryawan.vue";
import UbahPasswordKaryawan from "../admin/karyawan-handling/UbahPasswordKaryawan.vue";
import EditDataKaryawan from "../admin/karyawan-handling/EditDataKaryawan.vue";

const showModal = ref(false);
const showPasswordModal = ref(false);
const showEditModal = ref(false);

const selectedKaryawan = ref({});
const selectedKaryawanEdit = ref({});
const karyawans = ref([]);
const activeFilter = ref('semua');

// Computed untuk filter karyawan
const filteredKaryawans = computed(() => {
    if (activeFilter.value === 'semua') {
        return karyawans.value;
    }
    return karyawans.value.filter(k => k.jabatan === activeFilter.value);
});

function ambilDataKaryawan() {
    axios
        .get("/api/karyawan")
        .then((res) => {
            karyawans.value = res.data.map((k) => ({
                ...k,
                showPassword: false,
            }));
        })
        .catch((err) => {
            console.error("Error fetching karyawan data:", err);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Gagal memuat data karyawan",
            });
        });
}

function setFilter(filter) {
    activeFilter.value = filter;
}

function getCountByJabatan(jabatan) {
    return karyawans.value.filter(k => k.jabatan === jabatan).length;
}

function getRoleDisplay(jabatan) {
    const roles = {
        'admin': 'ADMIN',
        'kasir': 'KASIR', 
        'kurir': 'KURIR'
    };
    return roles[jabatan] || jabatan.toUpperCase();
}

function getAccessLevelClass(jabatan) {
    const levels = {
        'admin': 'high',
        'kasir': 'medium',
        'kurir': 'low'
    };
    return levels[jabatan] || '';
}

function getAccessLevelDisplay(jabatan) {
    const displays = {
        'admin': 'HIGH ACCESS',
        'kasir': 'MEDIUM ACCESS',
        'kurir': 'LOW ACCESS'
    };
    return displays[jabatan] || '';
}

function togglePassword(karyawan) {
    karyawan.showPassword = !karyawan.showPassword;
}

function hapusKaryawan(id, index) {
    Swal.fire({
        title: "Yakin ingin menghapus?",
        text: "Data karyawan akan dihapus secara permanen.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .delete(`/api/karyawan/${id}`)
                .then(() => {
                    // Refresh data instead of splice
                    ambilDataKaryawan();
                    Swal.fire("Berhasil!", "Karyawan berhasil dihapus.", "success");
                })
                .catch((err) => {
                    const message = err.response?.data?.message || "Tidak bisa menghapus data.";
                    Swal.fire("Gagal!", message, "error");
                });
        }
    });
}

function toggleStatus(karyawan, index) {
    const action = karyawan.status === 'aktif' ? 'nonaktifkan' : 'aktifkan';
    
    Swal.fire({
        title: `Yakin ingin ${action} karyawan?`,
        text: `Karyawan akan di${action}.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: `Ya, ${action}!`,
        cancelButtonText: "Batal",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.put(`/api/karyawan/${karyawan.id}/toggle-status`);
                await ambilDataKaryawan(); // Refresh data
                Swal.fire("Berhasil!", `Karyawan berhasil di${action}.`, "success");
            } catch (error) {
                const message = error.response?.data?.message || `Tidak bisa ${action} karyawan.`;
                Swal.fire("Gagal!", message, "error");
            }
        }
    });
}

function gantiPassword(karyawan) {
    selectedKaryawan.value = karyawan;
    showPasswordModal.value = true;
}

function editKaryawan(karyawan) {
    selectedKaryawanEdit.value = { ...karyawan };
    showEditModal.value = true;
}

function exportCSV() {
    // Implementasi export CSV
    console.log("Export CSV functionality");
}

onMounted(() => ambilDataKaryawan());
</script>

<style scoped>
@import "../../assets/css/DataKaryawan.css";

</style>