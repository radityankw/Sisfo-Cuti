<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import api from '../lib/axios';
import Sidebar from '../components/Sidebar.vue';

// State
const recommendationRequests = ref([]);
const approvalRequests = ref([]);
const isLoading = ref(true);
const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'));

// Fetch Data
const fetchData = async () => {
    try {
        const { data } = await api.get('/persetujuan');
        // Pastikan response API struktur datanya sesuai (misal: data.data.recommendationRequests)
        // Sesuaikan dengan controller kamu. Asumsi controller return JSON { recommendationRequests: [], approvalRequests: [] }
        recommendationRequests.value = data.recommendationRequests;
        approvalRequests.value = data.approvalRequests;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchData);

// --- MODAL STATE ---
const showDetailModal = ref(false);
const showRejectModal = ref(false);
const selectedRequest = ref(null);

// Form Action (Manual Reactive Object)
const form = reactive({
    action: '',
    catatan: '',
});

// --- METHODS ---
const openDetailModal = (req) => {
    selectedRequest.value = req;
    showDetailModal.value = true;
    showRejectModal.value = false;
    form.action = '';
    form.catatan = '';
};

const closeAllModals = () => {
    showDetailModal.value = false;
    showRejectModal.value = false;
    selectedRequest.value = null;
};

const handleApproveOrRecommend = async (actionType) => {
    if (!confirm(actionType === 'recommend' ? 'Berikan rekomendasi?' : 'Setujui cuti ini?')) return;
    
    form.action = actionType;
    form.catatan = null;
    await submitForm();
};

const openRejectDialog = () => {
    showRejectModal.value = true;
};

const submitReject = async () => {
    form.action = 'reject';
    await submitForm();
};

const submitForm = async () => {
    try {
        await api.patch(`/persetujuan/${selectedRequest.value.id}`, form);
        closeAllModals();
        fetchData(); // Refresh data table
        alert('Berhasil memproses pengajuan.');
    } catch (e) {
        alert('Gagal memproses data.');
        console.error(e);
    }
};
</script>

<template>
    <div class="min-h-screen lg:h-screen bg-neutral-50 flex overflow-x-hidden lg:overflow-hidden font-sans">
        <Sidebar />

        <main class="flex-1 ml-0 lg:ml-64 p-4 md:p-6 lg:p-8 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-8 text-black">Halaman Persetujuan</h1>

            <div v-if="isLoading" class="text-center py-10">Loading Data...</div>

            <div v-else>
                <div class="mb-10">
                    <h2 class="text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                        <span class="w-2 h-8 bg-purple-600 rounded-full"></span>
                        Permintaan Rekomendasi
                    </h2>
                    
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-100">
                                    <th class="pb-3 font-normal px-2">Tgl Pengajuan</th>
                                    <th class="pb-3 font-normal px-2">Nama Pemohon</th>
                                    <th class="pb-3 font-normal px-2">Jenis Cuti</th>
                                    <th class="pb-3 font-normal px-2">Durasi</th>
                                    <th class="pb-3 font-normal text-center px-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="req in recommendationRequests" :key="req.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50">
                                    <td class="py-3 text-sm text-gray-700 px-2">{{ req.tgl_pengajuan }}</td>
                                    <td class="py-3 text-sm font-semibold text-gray-900 px-2">{{ req.nama_pemohon }}</td>
                                    <td class="py-3 text-sm text-gray-700 px-2">{{ req.nama_cuti }}</td>
                                    <td class="py-3 text-sm text-gray-700 px-2">{{ req.durasi }} Hari</td>
                                    <td class="py-3 text-center px-2">
                                        <button @click="openDetailModal(req)" class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-4 py-2 rounded-full font-medium transition-colors">
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="recommendationRequests.length === 0">
                                    <td colspan="5" class="py-8 text-center text-gray-400 text-sm italic">
                                        Belum ada data cuti yang menunggu rekomendasi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="user.role === 'HRD'" class="mb-8">
                    <h2 class="text-xl font-bold text-blue-900 mb-4 flex items-center gap-2">
                        <span class="w-2 h-8 bg-green-600 rounded-full"></span>
                        Permintaan Persetujuan Cuti 
                    </h2>

                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-100">
                                    <th class="pb-3 font-normal px-2">Tgl Pengajuan</th>
                                    <th class="pb-3 font-normal px-2">Nama Pemohon</th>
                                    <th class="pb-3 font-normal px-2">Jenis Cuti</th>
                                    <th class="pb-3 font-normal px-2">Rekomendasi Oleh</th>
                                    <th class="pb-3 font-normal text-center px-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="req in approvalRequests" :key="req.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50">
                                    <td class="py-3 text-sm text-gray-700 px-2">{{ req.tgl_pengajuan }}</td>
                                    <td class="py-3 text-sm font-semibold text-gray-900 px-2">{{ req.nama_pemohon }}</td>
                                    <td class="py-3 text-sm text-gray-700 px-2">{{ req.nama_cuti }}</td>
                                    <td class="py-3 text-sm text-gray-700 px-2">{{ req.rekomendasi_oleh }}</td>
                                    <td class="py-3 text-center px-2">
                                        <button @click="openDetailModal(req)" class="bg-green-500 hover:bg-green-600 text-white text-xs px-4 py-2 rounded-full font-medium transition-colors">
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="approvalRequests.length === 0">
                                    <td colspan="5" class="py-8 text-center text-gray-400 text-sm italic">
                                        Belum ada data cuti yang menunggu persetujuan akhir.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" @click="closeAllModals">
            <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl relative" @click.stop>
                <button @click="closeAllModals" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div class="p-8">
                    <h2 class="text-2xl font-bold text-blue-900 mb-8">
                        {{ selectedRequest.status === 'PENDING' ? 'Detail Permintaan Rekomendasi' : 'Detail Persetujuan Cuti' }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12" v-if="selectedRequest">
                        <div class="space-y-4">
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Tgl Pengajuan:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.tgl_pengajuan }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Nama:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.nama_pemohon }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">NIK:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.nik_pemohon }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Departemen:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.departemen }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Nama Cuti:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.nama_cuti }}</div></div>
                            <div class="grid grid-cols-3 items-center">
                                <div class="text-sm text-gray-500">Lampiran:</div>
                                <div class="col-span-2">
                                    <a v-if="selectedRequest.lampiran" :href="selectedRequest.lampiran" target="_blank" class="bg-blue-900 text-white text-xs px-4 py-1.5 rounded-full hover:bg-blue-800 no-underline">Lihat Lampiran</a>
                                    <span v-else class="text-gray-400 text-sm italic">Tidak ada lampiran</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Tgl Mulai:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.tgl_mulai }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Tgl Selesai:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.tgl_selesai }}</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Durasi:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.durasi }} Hari</div></div>
                            <div class="grid grid-cols-3"><div class="text-sm text-gray-500">Alasan:</div><div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.alasan }}</div></div>
                            <div v-if="selectedRequest.status === 'RECOMMENDED'" class="grid grid-cols-3">
                                <div class="text-sm text-gray-500">Rekomendasi:</div>
                                <div class="text-sm font-semibold text-black col-span-2">{{ selectedRequest.rekomendasi_oleh }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex justify-end gap-4 border-t pt-6">
                        <button @click="openRejectDialog" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg font-bold transition-colors">
                            Tolak
                        </button>
                        <button v-if="selectedRequest.status === 'PENDING'" @click="handleApproveOrRecommend('recommend')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-bold transition-colors">
                            Beri Rekomendasi
                        </button>
                        <button v-if="selectedRequest.status === 'RECOMMENDED' && user.role === 'HRD'" @click="handleApproveOrRecommend('approve')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-bold transition-colors">
                            Setujui Cuti
                        </button>
                    </div>
                </div>

                <div v-if="showRejectModal" class="absolute inset-0 bg-white/95 backdrop-blur-sm z-10 flex items-center justify-center p-8">
                    <div class="w-full max-w-lg">
                        <h3 class="text-xl font-bold text-black mb-2">Catatan Penolakan</h3>
                        <p class="text-gray-500 text-sm mb-4">Silakan tulis alasan kenapa pengajuan ini ditolak.</p>
                        <textarea v-model="form.catatan" rows="4" class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-red-500 mb-4 p-3" placeholder="Tulis catatan di sini..."></textarea>
                        <div class="flex justify-end gap-3">
                            <button @click="showRejectModal = false" class="text-gray-500 font-semibold px-4 py-2 hover:bg-gray-100 rounded-lg">Batal</button>
                            <button @click="submitReject" :disabled="!form.catatan" class="bg-red-600 text-white font-bold px-6 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50">
                                Kirim Penolakan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>