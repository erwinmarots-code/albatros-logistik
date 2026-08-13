<template>
  <div class="notification-bell" @click="toggleDropdown">
    <i class="fas fa-bell"></i>
    <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    <div v-if="isOpen" class="dropdown">
      <div class="dropdown-header">
        <span>Notifikasi</span>
        <button @click.stop="markAllRead" v-if="unreadCount > 0">Tandai semua sudah dibaca</button>
      </div>
      <ul v-if="notifications.length">
        <li v-for="n in notifications" :key="n.id" :class="{ unread: !n.is_read }" @click="markRead(n.id)">
          <strong>{{ n.title }}</strong>
          <p>{{ n.message }}</p>
          <small>{{ formatTime(n.created_at) }}</small>
        </li>
      </ul>
      <div v-else class="empty">Tidak ada notifikasi</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from '../axios'

const isOpen = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
let interval = null

const fetchNotifications = async () => {
  try {
    const res = await axios.get('/notifications')
    notifications.value = res.data.data || []
    unreadCount.value = res.data.unread_count || 0
  } catch (error) {
    console.error('Gagal ambil notifikasi:', error)
  }
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    fetchNotifications()
  }
}

const markRead = async (id) => {
  try {
    await axios.post(`/notifications/${id}/read`)
    const notif = notifications.value.find(n => n.id === id)
    if (notif) {
      notif.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Gagal tandai baca:', error)
  }
}

const markAllRead = async () => {
  try {
    await axios.post('/notifications/read-all')
    notifications.value.forEach(n => n.is_read = true)
    unreadCount.value = 0
  } catch (error) {
    console.error('Gagal tandai semua baca:', error)
  }
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

// Polling setiap 30 detik
onMounted(() => {
  fetchNotifications()
  interval = setInterval(fetchNotifications, 30000)
})

onUnmounted(() => {
  if (interval) clearInterval(interval)
})
</script>

<style scoped>
.notification-bell {
  position: relative;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  font-size: 20px;
  color: white;
}
.badge {
  position: absolute;
  top: -6px;
  right: -8px;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  padding: 2px 6px;
  font-size: 11px;
  font-weight: 700;
}
.dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  color: #2d3748;
  width: 340px;
  max-height: 400px;
  overflow-y: auto;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
  margin-top: 8px;
  z-index: 1000;
}
.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e9ecef;
  font-weight: 600;
}
.dropdown-header button {
  background: none;
  border: none;
  color: #1a4a7a;
  font-size: 13px;
  cursor: pointer;
  font-weight: 500;
}
.dropdown-header button:hover { text-decoration: underline; }
ul { list-style: none; padding: 0; margin: 0; }
ul li {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f3f5;
  cursor: pointer;
  transition: background 0.15s;
}
ul li:hover { background: #f8fafc; }
ul li.unread { background: #eef6ff; }
ul li.unread:hover { background: #e0edff; }
ul li strong { display: block; font-size: 14px; }
ul li p { margin: 4px 0; font-size: 13px; color: #4a5568; }
ul li small { font-size: 11px; color: #a0aec0; }
.empty { padding: 20px; text-align: center; color: #a0aec0; }
</style>