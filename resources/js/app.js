import "./bootstrap";
import router from './routes/routes'; // ✅ Fixed path
import { createApp } from "vue";
import { createPinia } from 'pinia';

import App from "./App.vue";

// Create Vue app
const app = createApp(App);

// Create Pinia instance
const pinia = createPinia();

// Use plugins
app.use(pinia);
app.use(router);

// Mount app
app.mount("#app");

// Optional: Setup untuk development
if (import.meta.env.DEV || process.env.NODE_ENV === 'development') {
  app.config.performance = true;
  console.log('🧺 Laundry POS App started with Pinia tools');
}