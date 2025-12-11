// router/index.js - Fixed Version
import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    component: () => import("../Pages/LandingPage.vue"),
  },
  {
    path: "/AuthKaryawan",
    component: () => import("../components/admin-karyawan-login/AuthKaryawan.vue"),
  },
  // 🆕 Setup Admin (untuk admin pertama)
  {
    path: "/SetupAdmin",
    component: () => import("../components/admin-karyawan-login/SetupAdmin.vue"),
  },
  // ✅ Auth Admin (untuk login normal)
  {
    path: "/AuthAdmin",
    component: () => import("../components/admin-karyawan-login/AuthAdmin.vue"),
  },

  //Redirect login dari AuthKaryawan
  {
    path: '/DashboardKasir',
    name: 'Dashboard',
    component: () => import('../Pages/DashboardKasir.vue')
  },
  {
    path: '/DashboardKurir',
    name: 'DashboardKurir', 
    component: () => import('../Pages/DashboardKurir.vue')   // ← Path ini harus benar
  },
  {
    path: '/TransaksiOrder',
    name: 'Order',
    component: () => import('../Pages/TransaksiOrder.vue')
  },
  {
    path: '/LaporanHarian',
    name: 'Laporan',
    component: () => import('../Pages/LaporanHarian.vue')
  },
  {
    path: '/Notifikasi',
    name: 'Notifikasi',
    component: () => import('../Pages/Notifikasi.vue')
  },
  
  // ✅ Dashboard Admin dengan nested routes
  {
    path: "/DashboardAdmin",
    component: () => import("../Pages/DashboardAdmin.vue"),
    children: [
      {
        path: "",
        redirect: "/DashboardAdmin/Karyawan",
      },
      {
        path: "Karyawan",
        component: () => import("../components/admin/DataKaryawan.vue"),
      },
      {
        path: "Layanan",
        component: () => import("../components/admin/JenisLayanan.vue"),
      },
      {
        path: "PickupManement",
        component: () => import("../components/admin/PickupManegement.vue"), // 🔧 Fixed path
      },
      {
        path: "Pengaturan",
        component: () => import("../components/admin/Pengaturan.vue"),
      },
      {
        path: "Laporan",
        name: "admin-laporan",
        component: () => import("../components/admin/LaporanKaryawan.vue"),
      },
    ],
  }
]

export default createRouter({
  history: createWebHistory(),
  routes,
})