import { createRouter, createWebHistory } from 'vue-router'

// 1. Import semua halaman yang sudah kita buat
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import Pengajuan from '../views/Pengajuan.vue'
import Persetujuan from '../views/Persetujuan.vue'
import Laporan from '../views/Laporan.vue'
import DataKaryawanIndex from '../views/DataKaryawan/Index.vue'
import DataKaryawanStruktur from '../views/DataKaryawan/Struktur.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { 
      path: '/login', 
      name: 'login',
      component: LoginView 
    },
    { 
      path: '/dashboard', 
      name: 'dashboard',
      component: DashboardView 
    },
    { 
      path: '/pengajuan', 
      name: 'pengajuan',
      component: Pengajuan 
    },
    { 
      path: '/persetujuan', 
      name: 'persetujuan',
      component: Persetujuan 
    },
    { 
      path: '/laporan', 
      name: 'laporan',
      component: Laporan 
    },
    { 
      path: '/data-karyawan', 
      name: 'data-karyawan.index',
      component: DataKaryawanIndex 
    },
    { 
      path: '/data-karyawan/struktur', 
      name: 'data-karyawan.struktur',
      component: DataKaryawanStruktur 
    },
    { 
      path: '/', 
      redirect: '/login' 
    }
  ]
})

// Navigation Guard (Cek Token)
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.path !== '/login' && !token) {
    next('/login')
  } else {
    next()
  }
})

export default router