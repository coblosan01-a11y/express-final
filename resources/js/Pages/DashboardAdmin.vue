<template>
  <div class="dashboard-admin">
    <!-- Navigasi tab dengan router-view sudah di dalam NavigationTabs -->
    <NavigationTabs />
  </div>
</template>

<script setup>
  import NavigationTabs from '../components/navigation/NavSidebarAdmin.vue'
</script>

<style scoped>
.dashboard-admin {
  width: 100%;
  height: 100vh;
}
</style>