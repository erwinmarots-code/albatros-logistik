import '@fortawesome/fontawesome-free/css/all.min.css'
import { createApp } from 'vue'
import App from './App.vue'
import axios from 'axios'
axios.defaults.baseURL = '/api'

createApp(App).mount('#app')