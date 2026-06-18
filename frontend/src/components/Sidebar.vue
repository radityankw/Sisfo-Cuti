<script setup>
import { RouterLink, useRouter } from 'vue-router';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import api from '../lib/axios';

const router = useRouter();
const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'));
const isOpen = ref(false);

const closeSidebar = () => {
    isOpen.value = false;
};

const toggleSidebar = () => {
    isOpen.value = !isOpen.value;
};

const syncSidebarState = () => {
    if (window.innerWidth >= 1025) {
        isOpen.value = false;
    }
};

onMounted(() => {
    syncSidebarState();
    window.addEventListener('resize', syncSidebarState);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncSidebarState);
});

const handleLogout = async () => {
    try {
        await api.post('/logout');
    } catch (e) {
        console.error(e);
    } finally {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        router.push('/login');
    }
};
</script>

<template>
    <button type="button" class="mobile-toggle" @click="toggleSidebar" aria-label="Buka menu navigasi">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div v-if="isOpen" class="sidebar-backdrop" @click="closeSidebar"></div>

    <aside class="app-sidebar w-64 bg-white flex flex-col items-center py-8 px-4 fixed h-full border-r border-gray-100 z-20" :class="{ open: isOpen }">
        <div class="mb-10 flex flex-col items-center w-full">
            <div class="w-24 h-24 mb-3 flex items-center justify-center">
                <img src="/images/logo.png" alt="Logo MK" class="w-full h-full object-contain" />
            </div>
            <p class="text-blue-900 text-xl font-bold tracking-wider">Menara Kudus</p>
        </div>

        <nav class="w-full space-y-1 flex flex-col items-center" @click="closeSidebar">
            <RouterLink to="/dashboard" class="nav-link" active-class="nav-link-active">
                <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>Dashboard</span>
            </RouterLink>

            <RouterLink to="/pengajuan" class="nav-link" active-class="nav-link-active">
                <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <span>Pengajuan</span>
            </RouterLink>

            <RouterLink v-if="['Manager','HRD'].includes(user.role)" to="/persetujuan" class="nav-link" active-class="nav-link-active">
                <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                <span>Persetujuan</span>
            </RouterLink>

            <RouterLink v-if="user.role === 'HRD'" to="/data-karyawan" class="nav-link" active-class="nav-link-active">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Data Karyawan</span>
            </RouterLink>

            <RouterLink v-if="user.role === 'HRD'" to="/laporan" class="nav-link" active-class="nav-link-active">
                <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                <span>Laporan</span>
            </RouterLink>

            <RouterLink to="/ganti-password" class="nav-link" active-class="nav-link-active">
                <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path><path d="M12 15v2"></path></svg>
                <span>Ganti Password</span>
            </RouterLink>
        </nav>

        <div class="mt-auto w-full flex flex-col items-center pb-4">
            <a @click="handleLogout" class="nav-link logout-btn cursor-pointer">
                <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                
                <span>Logout</span>
            </a>
        </div>
    </aside>
</template>

<style scoped>
.nav-link {
    width: 11rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    color: #6b7280;
    font-weight: 600;
    font-size: 1.02rem;
    transition: color 0.2s;
    text-decoration: none;
}

.nav-link:hover {
    color: #1e3a8a;
}

.nav-link-active {
    color: #1e3a8a !important;
    font-weight: 700;
}

.mobile-toggle {
    display: none;
}

.sidebar-backdrop {
    display: none;
}

.app-sidebar {
    transition: transform 0.3s ease;
}

.logout-btn {
    width: 11rem;
}

.logout-btn:hover {
    color: #dc2626 !important;
}

@media (max-width: 1024px) {
    .mobile-toggle {
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 60;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
        border: 1px solid rgba(226, 232, 240, 1);
    }

    .mobile-toggle span {
        display: block;
        width: 1.25rem;
        height: 2px;
        margin: 0 auto;
        border-radius: 9999px;
        background: #1e3a8a;
    }

    .sidebar-backdrop {
        display: block;
        position: fixed;
        inset: 0;
        z-index: 40;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(2px);
    }

    .app-sidebar {
        top: 0;
        left: 0;
        z-index: 50;
        transform: translateX(-100%);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
    }

    .app-sidebar.open {
        transform: translateX(0);
    }
}

@media (min-width: 1025px) {
    .mobile-toggle,
    .sidebar-backdrop {
        display: none !important;
    }

    .app-sidebar {
        transform: translateX(0) !important;
    }
}
</style>