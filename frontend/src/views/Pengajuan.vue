<script setup>
import { ref, onMounted, computed, watch, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../lib/axios';
import Sidebar from '../components/Sidebar.vue';

const router = useRouter();
// Data from API
const leaves = ref([]);
const holidays = ref([]);
const sisaCuti = ref(0);
const isLoadingData = ref(true);

// Form Data (Pengganti useForm)
const form = reactive({
    leave_id: '',
    tglMulai: '',
    tglSelesai: '',
    alasan: '',
    lampiran: null,
});
const errors = ref({}); // Untuk validasi server
const isProcessing = ref(false);

// Logic Variables
const totalHari = ref(0);
const validationMessage = ref('');
const isHolidayCollision = ref(false);
const isMaxExceeded = ref(false);
const isAttachmentRequired = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get('/pengajuan'); // Endpoint fetch data form
        leaves.value = data.leaves;
        holidays.value = data.holidays;
        sisaCuti.value = data.sisaCuti;
    } catch (e) {
        console.error(e);
    } finally {
        isLoadingData.value = false;
    }
});

const isAnnualLeave = computed(() => {
    const selected = leaves.value.find(l => l.id === form.leave_id);
    return selected && selected.namaCuti.toLowerCase().includes('tahunan');
});

// Watcher logic (Sama seperti sebelumnya)
watch(() => form.leave_id, (newVal) => {
    const selected = leaves.value.find(l => l.id === newVal);
    if (selected) {
        isAttachmentRequired.value = !selected.namaCuti.toLowerCase().includes('tahunan');
        calculateDuration();
    }
});

const calculateDuration = () => {
    validationMessage.value = ''; isHolidayCollision.value = false; isMaxExceeded.value = false; totalHari.value = 0;
    if (form.tglMulai && form.tglSelesai) {
        const start = new Date(form.tglMulai);
        const end = new Date(form.tglSelesai);
        const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
        
        if (end < start) { validationMessage.value = "Tanggal selesai tidak boleh lebih awal dari tanggal mulai."; return; }
        totalHari.value = diffDays;

        const selectedLeave = leaves.value.find(l => l.id === form.leave_id);
        if (isAnnualLeave.value) {
            if (diffDays > sisaCuti.value) { isMaxExceeded.value = true; validationMessage.value = `Sisa cuti tahunan tidak mencukupi (Sisa: ${sisaCuti.value} hari).`; }
        } else {
            if (selectedLeave?.maxHari && selectedLeave.maxHari > 0 && diffDays > selectedLeave.maxHari) {
                isMaxExceeded.value = true; validationMessage.value = `Durasi cuti melebihi batas maksimal (${selectedLeave.maxHari} hari).`;
            }
        }

        let currentDate = new Date(start);
        while (currentDate <= end) {
            const dateString = currentDate.toISOString().split('T')[0];
            const holiday = holidays.value.find(h => h.tgl === dateString);
            if (holiday) { isHolidayCollision.value = true; validationMessage.value = `Tabrakan dengan hari libur: ${holiday.deskripsi}`; break; }
            currentDate.setDate(currentDate.getDate() + 1);
        }
    }
};

watch(() => form.tglMulai, calculateDuration);
watch(() => form.tglSelesai, calculateDuration);

const handleFileUpload = (event) => {
    form.lampiran = event.target.files[0];
};

const submit = async () => {
    if (isMaxExceeded.value || isHolidayCollision.value) return;
    isProcessing.value = true;
    errors.value = {};

    // Pakai FormData untuk upload file
    const formData = new FormData();
    formData.append('leave_id', form.leave_id);
    formData.append('tglMulai', form.tglMulai);
    formData.append('tglSelesai', form.tglSelesai);
    formData.append('alasan', form.alasan);
    if (form.lampiran) formData.append('lampiran', form.lampiran);

    try {
        await api.post('/pengajuan', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('Pengajuan Berhasil');
        // Reset form manually
        form.leave_id = ''; form.tglMulai = ''; form.tglSelesai = ''; form.alasan = ''; form.lampiran = null;
        router.push('/dashboard');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors; // Tangkap validasi Laravel
        } else {
            alert('Gagal mengajukan cuti');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen lg:h-screen bg-neutral-50 flex overflow-x-hidden lg:overflow-hidden font-sans">
        <Sidebar />
        <main class="flex-1 ml-0 lg:ml-64 p-4 md:p-6 lg:p-8 overflow-y-auto flex justify-center items-start pt-6 lg:pt-12">
            <div class="w-full max-w-3xl bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <h1 class="text-2xl font-bold text-red-600 mb-6">Form Pengajuan Cuti</h1>

                <div v-if="isLoadingData" class="text-center">Loading Data Form...</div>
                
                <div v-else>
                    <div v-if="validationMessage" class="mb-6 p-4 rounded-lg flex items-start gap-3" :class="isHolidayCollision || isMaxExceeded ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200'">
                        <span class="text-sm font-medium">{{ validationMessage }}</span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-lg font-semibold text-black">Nama Cuti</label>
                            <select v-model="form.leave_id" required 
                                class="w-full h-12 rounded-lg border border-gray-400 text-gray-700 focus:border-blue-900 focus:ring-blue-900 px-4 bg-white">
                                <option value="" disabled>Pilih Jenis Cuti</option>
                                <option v-for="leave in leaves" :key="leave.id" :value="leave.id">
                                    {{ leave.namaCuti }} {{ !leave.namaCuti.toLowerCase().includes('tahunan') && leave.maxHari > 0 ? `(Max: ${leave.maxHari} hari)` : '' }}
                                </option>
                            </select>
                            <div v-if="isAnnualLeave" class="text-blue-700 bg-blue-50 px-3 py-2 rounded-md text-sm font-semibold border border-blue-100 flex items-center gap-2">
                                Sisa Kuota Cuti Tahunan Anda: {{ sisaCuti }} Hari
                            </div>
                            <div v-if="errors.leave_id" class="text-red-500 text-sm">{{ errors.leave_id[0] }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="text-lg font-semibold text-black">Tanggal Mulai</label>
                                <input type="date" v-model="form.tglMulai" required 
                                    class="w-full h-12 rounded-lg border border-gray-400 text-gray-700 px-4 focus:border-blue-900 focus:ring-blue-900">
                                <div v-if="errors.tglMulai" class="text-red-500 text-sm">{{ errors.tglMulai[0] }}</div>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <label class="text-lg font-semibold text-black">Tanggal Selesai</label>
                                <input type="date" v-model="form.tglSelesai" required 
                                    class="w-full h-12 rounded-lg border border-gray-400 text-gray-700 px-4 focus:border-blue-900 focus:ring-blue-900">
                                <div v-if="errors.tglSelesai" class="text-red-500 text-sm">{{ errors.tglSelesai[0] }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-gray-600 font-medium">Total durasi pengajuan:</span>
                            <span class="text-xl font-bold text-blue-900">{{ totalHari }} Hari</span>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-lg font-semibold text-black">Alasan</label>
                            <textarea v-model="form.alasan" required placeholder="Tulis Alasan Anda" rows="3" 
                                class="w-full rounded-lg border border-gray-400 text-gray-700 p-3 focus:border-blue-900 focus:ring-blue-900"></textarea>
                            <div v-if="errors.alasan" class="text-red-500 text-sm">{{ errors.alasan[0] }}</div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-lg font-semibold text-black">
                                Lampiran <span v-if="!isAttachmentRequired" class="text-sm font-normal text-gray-500 ml-1">(Opsional)</span><span v-else class="text-sm font-normal text-red-500 ml-1">(Wajib)</span>
                            </label>
                            <input type="file" @change="handleFileUpload" :required="isAttachmentRequired" 
                                class="block w-full text-sm text-gray-500 border border-gray-400 rounded-lg cursor-pointer bg-white focus:outline-none
                                file:mr-4 file:py-3 file:px-4
                                file:rounded-l-lg file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100" />
                            <div v-if="errors.lampiran" class="text-red-500 text-sm">{{ errors.lampiran[0] }}</div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" :disabled="isProcessing || isHolidayCollision || isMaxExceeded" class="bg-red-600 text-white font-bold py-3 px-10 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isProcessing ? 'Sending...' : 'Submit' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>