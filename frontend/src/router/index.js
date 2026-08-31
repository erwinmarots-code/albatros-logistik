import { createRouter, createWebHistory } from 'vue-router'

// ============================================================
// IMPORT SEMUA KOMPONEN
// ============================================================
import LandingPage from '../views/LandingPage.vue'
import Login from '../components/Login.vue'
import Dashboard from '../components/Dashboard.vue'
import ClientList from '../components/ClientList.vue'
import VehicleList from '../components/VehicleList.vue'
import DriverList from '../components/DriverList.vue'
import ProjectList from '../components/ProjectList.vue'
import ProjectDetail from '../components/ProjectDetail.vue'
import DeliveryTaskList from '../components/DeliveryTaskList.vue'
import FuelExpenseList from '../components/FuelExpenseList.vue'
import MaintenanceRequestList from '../components/MaintenanceRequestList.vue'
import MaintenanceRequestDetail from '../components/MaintenanceRequestDetail.vue'
import InvoiceList from '../components/InvoiceList.vue'
import FinancialDashboard from '../components/FinancialDashboard.vue'
import UserList from '../components/UserList.vue'
import SparePartList from '../components/SparePartList.vue'
import Settings from '../views/Settings.vue'
import PermissionManager from '../components/PermissionManager.vue' // 🔥 TAMBAHKAN INI

// ============================================================
// DEFINE ROUTES
// ============================================================
const routes = [
  // Halaman publik (guest)
  { path: '/', component: LandingPage, meta: { guest: true } },
  { path: '/login', component: Login, meta: { guest: true } },

  // ============================================================
  // ROUTE YANG MEMBUTUHKAN AUTHENTIKASI (requiresAuth: true)
  // ============================================================

  // Dashboard – semua role
  { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },

  // Data Master
  { path: '/clients', component: ClientList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'admin_transport', 'branch_admin'] } },
  { path: '/vehicles', component: VehicleList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'admin_transport', 'branch_admin'] } },
  { path: '/drivers', component: DriverList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'admin_transport', 'branch_admin'] } },

  // Operasional
  { path: '/projects', component: ProjectList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'branch_admin'] } },
  { path: '/projects/:id', name: 'ProjectDetail', component: ProjectDetail, props: true, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'branch_admin'] } },
  { path: '/projects/:id/edit', name: 'ProjectEdit', component: ProjectDetail, props: true, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'branch_admin'] } },
  { path: '/delivery-tasks', component: DeliveryTaskList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'branch_admin'] } },
  { path: '/fuel-expenses', component: FuelExpenseList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_project', 'admin_finance', 'branch_admin', 'staff'] } },

  // Perawatan
  { path: '/maintenance-requests', component: MaintenanceRequestList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_transport', 'admin_finance', 'branch_admin'] } },
  { path: '/maintenance-requests/:id', name: 'MaintenanceRequestDetail', component: MaintenanceRequestDetail, props: true, meta: { requiresAuth: true, roles: ['super_admin', 'admin_transport', 'admin_finance', 'branch_admin'] } },

  // Inventory Spare Part
  { path: '/spare-parts', component: SparePartList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_transport', 'admin_project'] } },

  // Keuangan
  { path: '/invoices', component: InvoiceList, meta: { requiresAuth: true, roles: ['super_admin', 'admin_finance'] } },
  { path: '/financial', component: FinancialDashboard, meta: { requiresAuth: true, roles: ['super_admin', 'admin_finance'] } },

  // Manajemen User (Super Admin Only)
  { path: '/users', component: UserList, meta: { requiresAuth: true, roles: ['super_admin'] } },

  // 🔥 MANAJEMEN AKSES (Super Admin Only)
  { path: '/permissions', component: PermissionManager, meta: { requiresAuth: true, roles: ['super_admin'] } },

  // Pengaturan Sistem (Super Admin Only)
  { path: '/settings', component: Settings, meta: { requiresAuth: true, roles: ['super_admin'] } },

  // Fallback (redirect sesuai status login)
  { path: '/:pathMatch(.*)*', redirect: () => {
      const token = localStorage.getItem('token')
      return token ? '/dashboard' : '/'
    }
  }
]

// ============================================================
// BUAT ROUTER INSTANCE
// ============================================================
const router = createRouter({
  history: createWebHistory(),
  routes,
})

// ============================================================
// ROUTE GUARD (Cek Autentikasi & Role)
// ============================================================
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const role = user.role || ''

  // 1. Jika halaman butuh auth dan tidak ada token → redirect ke login
  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }

  // 2. Jika sudah login dan mencoba akses halaman guest → redirect ke dashboard
  if (to.meta.guest && token) {
    next('/dashboard')
    return
  }

  // 3. Jika halaman memiliki daftar role yang diizinkan
  if (to.meta.roles && token) {
    const allowedRoles = to.meta.roles
    if (!allowedRoles.includes(role)) {
      // Jika role tidak diizinkan, redirect ke dashboard
      next('/dashboard')
      return
    }
  }

  // 4. Selain itu, lanjutkan
  next()
})

export default router