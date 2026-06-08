<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../lib/axios';
import Sidebar from '../components/Sidebar.vue'; // Import Sidebar

// State Data
const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'));
const leaveStats = ref({ remaining: 0, used: 0, pendingRecommendation: 0, pendingApproval: 0 });
const leaveHistory = ref([]);
const nationalHolidays = ref([]);
const isLoading = ref(true);

// Fetch Data
const fetchData = async () => {
    try {
        const { data } = await api.get('/dashboard');
        leaveStats.value = data.data.leaveStats;
        leaveHistory.value = data.data.leaveHistory;
        nationalHolidays.value = data.data.nationalHolidays;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchData);

// --- MODAL STATE ---
const showDetailModal = ref(false);
const selectedLeave = ref(null);

const openDetailModal = (leave) => {
    selectedLeave.value = leave;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedLeave.value = null;
};

// --- ACTION: BATALKAN CUTI ---
const cancelLeave = async (leave) => {
    const today = new Date();
    const startDate = new Date(leave.tgl_mulai_raw);
    const deadline = new Date(startDate);
    deadline.setHours(8, 0, 0, 0);

    if (today > deadline) {
        alert("Maaf, batas waktu pembatalan (Pukul 08:00 pada tanggal mulai cuti) sudah terlewati.");
        return;
    }

    if (confirm(`Apakah Anda yakin ingin membatalkan pengajuan cuti ini? Kuota cuti akan dikembalikan.`)) {
        try {
            await api.patch(`/pengajuan/${leave.id}/cancel`);
            closeDetailModal();
            fetchData(); // Refresh data
            alert('Berhasil dibatalkan');
        } catch (e) {
            alert(e.response?.data?.message || 'Gagal membatalkan');
        }
    }
};

// --- HELPERS (Style sama persis) ---
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'APPROVED': return 'bg-green-100 text-green-800 border border-green-200';
        case 'REJECTED': return 'bg-red-100 text-red-800 border border-red-200';
        case 'CANCELLED': return 'bg-gray-100 text-gray-600 border border-gray-300 decoration-line-through';
        case 'PENDING': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        default: return 'bg-blue-100 text-blue-800 border border-blue-200';
    }
};

const getStatusText = (status) => {
    const texts = { 'PENDING': 'Menunggu Rekomendasi', 'RECOMMENDED': 'Menunggu Persetujuan', 'APPROVED': 'Disetujui', 'REJECTED': 'Ditolak', 'CANCELLED': 'Dibatalkan' };
    return texts[status] || status;
};

const gridColsClass = computed(() => {
    const role = user.value.role;
    if (role === 'HRD') return 'lg:grid-cols-4';
    if (role === 'Manager') return 'lg:grid-cols-3';
    return 'lg:grid-cols-2';
});
</script>

<template>
    <div class="h-screen bg-neutral-50 flex overflow-hidden font-sans">
        <Sidebar /> <main class="flex-1 ml-64 p-8 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-8 text-black">
                Selamat Datang, {{ user.role === 'HRD' ? 'HRD' : user.nama }}
            </h1>

            <div v-if="isLoading" class="text-center py-10">Loading Dashboard...</div>

            <div v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8" :class="gridColsClass">
                    <div class="bg-white rounded-2xl shadow-sm border border-blue-900 border-l-8 p-5 relative h-40 flex flex-col justify-between">
                        <div>
                            <h3 class="text-black text-lg font-semibold">Sisa Cuti</h3>
                            <div class="mt-2 flex flex-col">
                                <span class="text-4xl font-bold text-black leading-none">{{ leaveStats.remaining }}</span>
                                <span class="text-gray-500 text-sm mt-1 font-medium ml-1">hari</span>
                            </div>
                        </div>
                        <div class="absolute bottom-4 right-4 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-red-500 border-l-8 p-5 relative h-40 flex flex-col justify-between">
                        <div>
                            <h3 class="text-black text-lg font-semibold">Cuti Terpakai</h3>
                            <div class="mt-2 flex flex-col">
                                <span class="text-4xl font-bold text-black leading-none">{{ leaveStats.used }}</span>
                                <span class="text-gray-500 text-sm mt-1 font-medium ml-0.5">hari</span>
                            </div>
                        </div>
                        <div class="absolute bottom-4 right-4 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>
                    <div v-if="['Manager', 'HRD'].includes(user.role)" class="bg-white rounded-2xl shadow-sm border border-purple-500 border-l-8 p-5 relative h-40 flex flex-col justify-between">
                        <div>
                            <h3 class="text-black text-base font-semibold leading-tight">Menunggu<br>Rekomendasi</h3>
                            <div class="mt-2 flex flex-col">
                                <span class="text-4xl font-bold text-black">{{ leaveStats.pendingRecommendation }}</span>
                                <span class="text-gray-500 text-sm mt-1 font-medium ml-0.5">orang</span>
                            </div>
                        </div>
                        <div class="absolute bottom-4 right-4 w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 3H7a4 4 0 0 0-4 4v10a4 4 0 0 0 4 4h10a4 4 0 0 0 4-4v-5" /><path d="M13.5 13.5L10 17v-3.5L18.5 5a2.121 2.121 0 1 1 3 3L13.5 13.5z" /></svg>
                        </div>
                    </div>
                    <div v-if="user.role === 'HRD'" class="bg-white rounded-2xl shadow-sm border border-yellow-400 border-l-8 p-5 relative h-40 flex flex-col justify-between">
                        <div>
                            <h3 class="text-black text-base font-semibold leading-tight">Menunggu<br>Persetujuan</h3>
                            <div class="mt-2 flex flex-col">
                                <span class="text-4xl font-bold text-black">{{ leaveStats.pendingApproval }}</span>
                                <span class="text-gray-500 text-sm mt-1 font-medium ml-0.5">orang</span>
                            </div>
                        </div>
                        <div class="absolute bottom-4 right-4 w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-blue-900 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 bg-white"><h2 class="text-lg font-bold text-black">Riwayat Pengajuan Cuti</h2></div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Hari, Tanggal</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Cuti</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Detail</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="leave in leaveHistory" :key="leave.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ leave.date }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ leave.nama_cuti }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span :class="getStatusBadgeClass(leave.status)" class="inline-block whitespace-nowrap px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wide">
                                                {{ getStatusText(leave.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <button @click="openDetailModal(leave)" class="text-blue-600 hover:text-blue-900 text-sm font-semibold hover:underline">Lihat</button>
                                                <button v-if="!['REJECTED', 'CANCELLED'].includes(leave.status)" @click="cancelLeave(leave)" class="text-red-500 hover:text-red-700 text-sm font-semibold hover:underline">Batalkan</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="leaveHistory.length === 0"><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm italic">Belum ada riwayat pengajuan cuti.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-blue-900 overflow-hidden h-fit">
                        <div class="p-6 border-b border-gray-100 bg-white"><h2 class="text-lg font-bold text-black">Daftar Libur Nasional</h2></div>
                        <table class="w-full text-left">
                            <thead class="bg-gray-50">
                                <tr><th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Hari, Tanggal</th><th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Keterangan</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(holiday, idx) in nationalHolidays" :key="idx" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ holiday.date }}</td><td class="px-6 py-4 text-sm text-gray-600 text-right">{{ holiday.description }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" @click="closeDetailModal">
            <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl relative" @click.stop>
                <button @click="closeDetailModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-blue-900 mb-8">Detail Cuti</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12" v-if="selectedLeave">
                        <div class="space-y-4">
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Tgl Pengajuan:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.tgl_pengajuan_format }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Nama:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.nama_pemohon }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Departemen:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.departemen }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Nama Cuti:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.nama_cuti }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Tgl Mulai:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.tgl_mulai }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Tgl Selesai:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.tgl_selesai }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Alasan:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedLeave.alasan }}</div></div>
                            <div class="grid grid-cols-3 items-center">
                                <div class="text-sm text-gray-500">Lampiran:</div>
                                <div class="col-span-2">
                                    <a v-if="selectedLeave.lampiran" :href="selectedLeave.lampiran" target="_blank" class="bg-blue-900 text-white text-xs px-4 py-1.5 rounded-full hover:bg-blue-800 no-underline">Lihat Lampiran</a>
                                    <span v-else class="text-gray-400 text-sm italic">Tidak ada lampiran</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="flex gap-4"><span class="text-sm text-gray-500">Sisa Cuti:</span><span class="text-sm font-semibold text-black">{{ selectedLeave.sisa_cuti_saat_ini }}</span></div>
                            <div class="flex gap-4"><span class="text-sm text-gray-500">Direkomendasikan oleh:</span><span class="text-sm font-semibold text-black">{{ selectedLeave.approver }}</span></div>
                            <div class="flex gap-4 items-center mt-4"><span class="text-sm text-gray-500">Status:</span><span :class="getStatusBadgeClass(selectedLeave.status)" class="px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide">{{ getStatusText(selectedLeave.status) }}</span></div>
                            <div v-if="['CANCELLED', 'REJECTED'].includes(selectedLeave.status)" class="mt-4 p-3 bg-gray-100 rounded text-xs text-gray-600 italic">Pengajuan ini sudah tidak aktif.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>