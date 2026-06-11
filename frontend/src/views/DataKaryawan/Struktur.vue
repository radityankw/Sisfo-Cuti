<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../../lib/axios';
import OrgNode from './OrgNode.vue';

const roots = ref([]);
const isLoading = ref(true);
const isSidebarOpen = ref(false);

const closeSidebar = () => {
    isSidebarOpen.value = false;
};

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const syncSidebarState = () => {
    if (window.innerWidth >= 1025) {
        isSidebarOpen.value = false;
    }
};

onMounted(async () => {
    syncSidebarState();
    window.addEventListener('resize', syncSidebarState);

    try {
        const { data } = await api.get('/data-karyawan/struktur'); // Pastikan endpoint sesuai API routes
        roots.value = data.roots;
    } catch (e) {
        console.error("Gagal load struktur", e);
    } finally {
        isLoading.value = false;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncSidebarState);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex overflow-x-hidden lg:overflow-hidden font-sans org-main-shell">
        <button type="button" class="mobile-toggle" @click="toggleSidebar" aria-label="Buka menu navigasi">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div v-if="isSidebarOpen" class="sidebar-backdrop" @click="closeSidebar"></div>
        
        <aside class="app-sidebar w-64 bg-white flex flex-col items-center py-8 px-6 fixed h-full border-r border-gray-200 z-50 shadow-sm" :class="{ open: isSidebarOpen }">
            <div class="mb-10 flex flex-col items-center">
                <div class="w-24 h-24 mb-3 flex items-center justify-center">
                    <img src="/images/logo.png" alt="Logo MK" class="w-full h-full object-contain" />
                </div>
                <p class="text-blue-900 text-xl font-bold tracking-wider">Menara Kudus</p>
            </div>
            
            <div class="w-full mt-auto mb-8" @click="closeSidebar">
                <RouterLink to="/data-karyawan" class="flex items-center justify-center gap-2 w-full py-3 bg-white hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition-all border border-gray-200 hover:border-gray-300 shadow-sm hover:shadow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Data
                </RouterLink>
            </div>
        </aside>

        <main class="flex-1 ml-0 lg:ml-64 overflow-visible lg:overflow-hidden relative bg-gray-50">
            <div class="absolute inset-0 pointer-events-none" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px; opacity: 0.5;"></div>

            <div class="absolute top-0 left-0 right-0 z-40 p-8 flex justify-between items-start pointer-events-none org-header-row">
                <div class="pointer-events-auto org-header-title">
                    <h1 class="page-title text-3xl font-bold text-gray-800">Struktur Organisasi</h1>
                    <p class="text-gray-500 text-xl mt-1">PT. Menara Kudus Indonesia</p>
                </div>
                
                <div class="flex flex-col gap-2 pointer-events-auto legend-wrap">
                    <div class="bg-white/80 backdrop-blur px-4 py-2 rounded-lg border border-gray-200 shadow-sm text-xs font-medium flex gap-4 legend-wrap">
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-purple-600"></span> HRD</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Manager</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-gray-500"></span> Staff</div>
                    </div>
                </div>
            </div>

            <div class="org-chart-wrapper h-full w-full overflow-auto pt-32 pb-20 px-4 md:px-8 cursor-grab active:cursor-grabbing">
                <div v-if="isLoading" class="text-center pt-20">Loading Tree...</div>

                <div v-else class="org-card-row min-w-max flex justify-center gap-16 mx-auto">
                    <div v-for="rootUser in roots" :key="rootUser.nik">
                        <OrgNode :user="rootUser" />
                    </div>

                    <div v-if="roots.length === 0" class="flex flex-col items-center justify-center pt-20 opacity-60">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <p class="text-gray-500 font-medium">Struktur organisasi belum terbentuk.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.mobile-toggle {
    display: none;
}

.sidebar-backdrop {
    display: none;
}

.app-sidebar {
    transition: transform 0.3s ease;
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