<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../../lib/axios';
import OrgNode from './OrgNode.vue';

const roots = ref([]);
const isLoading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get('/data-karyawan/struktur'); // Pastikan endpoint sesuai API routes
        roots.value = data.roots;
    } catch (e) {
        console.error("Gagal load struktur", e);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex overflow-hidden font-sans">
        
        <aside class="w-64 bg-white flex flex-col items-center py-8 px-6 fixed h-full border-r border-gray-200 z-50 shadow-sm">
            <div class="mb-10 flex flex-col items-center">
                <div class="w-24 h-24 mb-3 flex items-center justify-center">
                    <img src="/images/logo.png" alt="Logo MK" class="w-full h-full object-contain" />
                </div>
                <p class="text-blue-900 text-xl font-bold tracking-wider">Menara Kudus</p>
            </div>
            
            <div class="w-full mt-auto mb-8">
                <RouterLink to="/data-karyawan" class="flex items-center justify-center gap-2 w-full py-3 bg-white hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition-all border border-gray-200 hover:border-gray-300 shadow-sm hover:shadow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Data
                </RouterLink>
            </div>
        </aside>

        <main class="flex-1 ml-64 overflow-hidden relative bg-gray-50">
            <div class="absolute inset-0 pointer-events-none" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px; opacity: 0.5;"></div>

            <div class="absolute top-0 left-0 right-0 z-40 p-8 flex justify-between items-start pointer-events-none">
                <div class="pointer-events-auto">
                    <h1 class="text-3xl font-bold text-gray-800">Struktur Organisasi</h1>
                    <p class="text-gray-500 text-xl mt-1">PT. Menara Kudus Indonesia</p>
                </div>
                
                <div class="flex flex-col gap-2 pointer-events-auto">
                    <div class="bg-white/80 backdrop-blur px-4 py-2 rounded-lg border border-gray-200 shadow-sm text-xs font-medium flex gap-4">
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-purple-600"></span> HRD</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Manager</div>
                        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-gray-500"></span> Staff</div>
                    </div>
                </div>
            </div>

            <div class="h-full w-full overflow-auto pt-32 pb-20 px-8 cursor-grab active:cursor-grabbing">
                <div v-if="isLoading" class="text-center pt-20">Loading Tree...</div>

                <div v-else class="min-w-max flex justify-center gap-16 mx-auto">
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