<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../lib/axios';
import Sidebar from '../components/Sidebar.vue';
import { useRouter } from 'vue-router';

const router = useRouter();

// State
const absenceLog = ref([]);
const quotaRecap = ref([]);
const summary = ref({ normatif: 0, nonNormatif: 0 });
const departments = ref([]);
const isLoading = ref(true);
const activeTab = ref('absence'); // 'absence' or 'recap'

// Filter State
const filterForm = ref({
    start_date: (() => {
        const date = new Date();
        date.setDate(1);
        return date.toISOString().split('T')[0];
    })(),
    end_date: (() => {
        const date = new Date();
        date.setMonth(date.getMonth() + 1);
        date.setDate(0);
        return date.toISOString().split('T')[0];
    })(),
    departemen: '',
});

// Fetch Data with Filters
const fetchData = async () => {
    isLoading.value = true;
    try {
        const { data } = await api.get('/laporan', { 
            params: filterForm.value
        });
        absenceLog.value = data.absenceLog;
        quotaRecap.value = data.quotaRecap;
        summary.value = data.summary;
        departments.value = data.departments;
    } catch (error) {
        console.error('Error fetching data:', error);
        if (error.response?.status === 403) {
            alert('Anda tidak memiliki akses ke halaman ini.');
            router.push('/dashboard');
        }
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchData);

// Apply Filter
const applyFilter = () => {
    fetchData();
};

// Export PDF Logic
const exportPDF = async () => {
    try {
        const response = await api.get('/laporan/export', {
            params: {
                ...filterForm.value,
                tab: activeTab.value
            },
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const filename = `laporan-cuti-${activeTab.value === 'absence' ? 'ketidakhadiran' : 'kuota'}-${new Date().toISOString().split('T')[0]}.pdf`;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export error:', error);
        alert('Gagal mendownload laporan.');
    }
};
</script>

<template>
    <div class="min-h-screen lg:h-screen bg-neutral-50 flex overflow-x-hidden lg:overflow-hidden font-sans">
        <Sidebar />

        <main class="flex-1 ml-0 lg:ml-64 p-4 md:p-6 lg:p-8 overflow-y-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-black mb-4">Laporan Cuti Karyawan</h1>
                
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-wrap items-center gap-4">
                    
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 font-semibold mb-1 ml-1">Dari Tanggal</span>
                            <input type="date" v-model="filterForm.start_date" class="border-gray-200 rounded-lg text-sm focus:ring-blue-900 focus:border-blue-900">
                        </div>
                        <span class="text-gray-400 mt-5">-</span>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 font-semibold mb-1 ml-1">Sampai Tanggal</span>
                            <input type="date" v-model="filterForm.end_date" class="border-gray-200 rounded-lg text-sm focus:ring-blue-900 focus:border-blue-900">
                        </div>
                    </div>

                    <div class="hidden md:block h-10 w-px bg-gray-200 mx-2"></div>

                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 font-semibold mb-1 ml-1">Departemen</span>
                        <select v-model="filterForm.departemen" class="border-gray-200 rounded-lg text-sm pr-8 focus:ring-blue-900 focus:border-blue-900 min-w-[200px]">
                            <option value="">Semua Departemen</option>
                            <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                        </select>
                    </div>

                    <div class="flex-1 flex justify-end items-end h-full mt-auto pt-5">
                        <button @click="applyFilter" class="bg-green-700 hover:bg-green-800 text-white text-sm font-bold px-6 py-2.5 rounded-lg transition-colors shadow-sm">
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-blue-900 border-l-8 p-5 relative h-32 flex flex-col justify-between">
                    <div>
                        <h3 class="text-black text-lg font-semibold">Total Cuti Normatif</h3>
                        <div class="mt-1">
                            <span class="text-4xl font-bold text-black">{{ summary.normatif }}</span>
                            <span class="text-gray-500 ml-2">hari</span>
                        </div>
                    </div>
                    <div class="absolute bottom-4 right-4 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-red-500 border-l-8 p-5 relative h-32 flex flex-col justify-between">
                    <div>
                        <h3 class="text-black text-lg font-semibold">Total Cuti Non-Normatif</h3>
                        <div class="mt-1">
                            <span class="text-4xl font-bold text-black">{{ summary.nonNormatif }}</span>
                            <span class="text-gray-500 ml-2">hari</span>
                        </div>
                    </div>
                    <div class="absolute bottom-4 right-4 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3H7a4 4 0 0 0-4 4v10a4 4 0 0 0 4 4h10a4 4 0 0 0 4-4v-5" /><path d="M13.5 13.5L10 17v-3.5L18.5 5a2.121 2.121 0 1 1 3 3L13.5 13.5z" />
                        </svg>
                    </div>
                </div>

            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                
                <div class="flex border-b border-gray-100">
                    <button 
                        @click="activeTab = 'absence'"
                        class="flex-1 py-4 text-center font-bold text-sm transition-colors relative"
                        :class="activeTab === 'absence' ? 'text-blue-900 bg-blue-50/50' : 'text-gray-500 hover:bg-gray-50'"
                    >
                        Riwayat Ketidakhadiran
                        <div v-if="activeTab === 'absence'" class="absolute bottom-0 left-0 w-full h-1 bg-blue-900 rounded-t-full"></div>
                    </button>
                    
                    <button 
                        @click="activeTab = 'recap'"
                        class="flex-1 py-4 text-center font-bold text-sm transition-colors relative"
                        :class="activeTab === 'recap' ? 'text-blue-900 bg-blue-50/50' : 'text-gray-500 hover:bg-gray-50'"
                    >
                        Rekapitulasi Kuota
                        <div v-if="activeTab === 'recap'" class="absolute bottom-0 left-0 w-full h-1 bg-blue-900 rounded-t-full"></div>
                    </button>
                </div>

                <div v-if="activeTab === 'absence'" class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-black">Log Karyawan Tidak Masuk</h3>
                        <button @click="exportPDF" class="bg-blue-800 hover:bg-blue-900 text-white text-xs px-4 py-2 rounded-lg font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export PDF
                        </button>
                    </div>

                    <div v-if="isLoading" class="text-center py-8 text-gray-500">Loading data...</div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-100 text-sm">
                                    <th class="pb-3 font-medium px-2">Tanggal</th>
                                    <th class="pb-3 font-medium px-2">Nama</th>
                                    <th class="pb-3 font-medium px-2">Departemen</th>
                                    <th class="pb-3 font-medium px-2">Jenis Cuti</th>
                                    <th class="pb-3 font-medium px-2">Kategori</th>
                                    <th class="pb-3 font-medium text-center px-2">Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in absenceLog" :key="log.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50 text-sm text-gray-700">
                                    <td class="py-3 px-2">{{ log.tgl }}</td>
                                    <td class="py-3 px-2 font-semibold text-gray-900">{{ log.nama }}</td>
                                    <td class="py-3 px-2">{{ log.departemen }}</td>
                                    <td class="py-3 px-2">{{ log.nama_cuti }}</td>
                                    <td class="py-3 px-2">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                            :class="log.kategori === 'Normatif' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'">
                                            {{ log.kategori }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-center">{{ log.durasi }} Hari</td>
                                </tr>
                                <tr v-if="absenceLog.length === 0">
                                    <td colspan="6" class="py-8 text-center text-gray-400 italic">Tidak ada data cuti pada periode ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="activeTab === 'recap'" class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-black">Status Kuota Karyawan (Tahunan)</h3>
                        <button @click="exportPDF" class="bg-blue-700 hover:bg-blue-800 text-white text-xs px-4 py-2 rounded-lg font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export PDF
                        </button>
                    </div>

                    <div v-if="isLoading" class="text-center py-8 text-gray-500">Loading data...</div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-100 text-sm">
                                    <th class="pb-3 font-medium px-2">Nama Karyawan</th>
                                    <th class="pb-3 font-medium px-2">Departemen</th>
                                    <th class="pb-3 font-medium text-center px-2">Kuota Awal</th>
                                    <th class="pb-3 font-medium text-center px-2 text-blue-700">Terpakai (Normatif)</th>
                                    <th class="pb-3 font-medium text-center px-2 text-red-600">Terpakai (Non-Normatif)</th>
                                    <th class="pb-3 font-medium text-center px-2 text-green-700">Sisa Kuota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in quotaRecap" :key="user.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50 text-sm text-gray-700">
                                    <td class="py-3 px-2 font-semibold text-gray-900">{{ user.nama }}</td>
                                    <td class="py-3 px-2">{{ user.departemen }}</td>
                                    <td class="py-3 px-2 text-center">{{ user.kuota_awal }}</td>
                                    <td class="py-3 px-2 text-center font-semibold text-blue-700">{{ user.terpakai_normatif }}</td>
                                    <td class="py-3 px-2 text-center text-red-600">{{ user.terpakai_non_normatif }}</td>
                                    <td class="py-3 px-2 text-center font-bold" 
                                        :class="user.sisa_kuota < 3 ? 'text-red-600' : 'text-green-600'">
                                        {{ user.sisa_kuota }}
                                    </td>
                                </tr>
                                <tr v-if="quotaRecap.length === 0">
                                    <td colspan="6" class="py-8 text-center text-gray-400 italic">Tidak ada data karyawan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>
    </div>
</template>