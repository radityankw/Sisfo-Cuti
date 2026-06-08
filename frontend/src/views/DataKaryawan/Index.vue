<script setup>
import { ref, onMounted, computed, watch, reactive } from 'vue';
import api from '../../lib/axios';
import Sidebar from '../../components/Sidebar.vue';
import { RouterLink } from 'vue-router';
import debounce from 'lodash/debounce';

const karyawan = ref({ data: [], links: [] });
const managers = ref([]);
const search = ref('');
const isEditing = ref(false);
const showModal = ref(false);
const showPassword = ref(false);

const form = reactive({ original_nik: '', nik: '', nama: '', tglMasuk: '', departemen: '', role: 'Staff', manager_nik: '', password: '' });
const errors = ref({});

const fetchData = async (pageUrl = '/data-karyawan') => {
    try {
        // Handle search query param
        const url = new URL(pageUrl, 'http://dummy.com'); // Hack buat parsing query
        const params = { page: url.searchParams.get('page'), search: search.value };
        
        const { data } = await api.get('/data-karyawan', { params });
        karyawan.value = data.karyawan;
        managers.value = data.managers;
    } catch (e) { console.error(e); }
};

onMounted(() => fetchData());

watch(search, debounce(() => fetchData(), 300));

const openAddModal = () => { isEditing.value = false; Object.assign(form, { nik: '', nama: '', tglMasuk: '', departemen: '', role: 'Staff', manager_nik: '', password: '' }); errors.value = {}; showModal.value = true; };
const openEditModal = (u) => { isEditing.value = true; Object.assign(form, { original_nik: u.nik, nik: u.nik, nama: u.nama, tglMasuk: u.tglMasuk?.split('T')[0], departemen: u.departemen, role: u.role, manager_nik: u.manager_nik, password: '' }); errors.value = {}; showModal.value = true; };
const closeModal = () => showModal.value = false;

const submitForm = async () => {
    try {
        if (isEditing.value) await api.put(`/data-karyawan/${form.original_nik}`, form);
        else await api.post('/data-karyawan', form);
        closeModal(); fetchData();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors;
    }
};

const deleteUser = async (u) => {
    if (confirm(`Hapus ${u.nama}?`)) {
        await api.delete(`/data-karyawan/${u.nik}`);
        fetchData();
    }
};
</script>

<template>
    <div class="h-screen bg-neutral-50 flex overflow-hidden font-sans">
        <Sidebar />
        <main class="flex-1 ml-64 p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-black">Manajemen Data Karyawan</h1>
                <div class="flex gap-3">
                    <RouterLink to="/data-karyawan/struktur" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg font-bold text-sm transition-colors flex items-center gap-2 shadow-sm">
                        Struktur Organisasi
                    </RouterLink>
                    <button @click="openAddModal" class="bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg font-bold text-sm transition-colors flex items-center gap-2">
                        Tambah Karyawan
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-end">
                    <div class="relative w-64">
                        <input v-model="search" type="text" placeholder="Cari Nama atau NIK..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-blue-900 focus:border-blue-900">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-blue-800 text-white">
                            <tr><th class="px-6 py-4 text-xs font-semibold uppercase">NIK</th><th class="px-6 py-4 text-xs font-semibold uppercase">Nama</th><th class="px-6 py-4 text-xs font-semibold uppercase">Tgl Masuk</th><th class="px-6 py-4 text-xs font-semibold uppercase">Departemen</th><th class="px-6 py-4 text-xs font-semibold uppercase">Role</th><th class="px-6 py-4 text-xs font-semibold uppercase">Atasan</th><th class="px-6 py-4 text-center text-xs font-semibold uppercase">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="u in karyawan.data" :key="u.nik" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-600">{{ u.nik }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ u.nama }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ u.tglMasuk }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ u.departemen }}</td>
                                <td class="px-6 py-4 text-sm"><span class="px-2 py-1 text-xs font-bold rounded-full" :class="{'bg-purple-100 text-purple-700': u.role==='HRD', 'bg-blue-100 text-blue-700': u.role==='Manager', 'bg-gray-100 text-gray-700': u.role==='Staff'}">{{ u.role }}</span></td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ u.manager ? u.manager.nama : '-' }}</td>
                                <td class="px-6 py-4 text-center flex justify-center gap-3">
                                    <button @click="openEditModal(u)" class="text-blue-600 font-medium text-sm">Edit</button>
                                    <button @click="deleteUser(u)" class="text-red-600 font-medium text-sm">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-1" v-if="karyawan.links && karyawan.links.length > 3">
                    <button v-for="(link, k) in karyawan.links" :key="k" @click="link.url && fetchData(link.url)" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="{'bg-blue-900 text-white': link.active, 'text-gray-600': !link.active, 'opacity-50': !link.url}" :disabled="!link.url"></button>
                </div>
            </div>
        </main>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4" @click="closeModal">
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-black mb-6">{{ isEditing ? 'Edit Karyawan' : 'Tambah Karyawan Baru' }}</h2>
                    <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                            <input v-model="form.nik" type="text" class="w-full rounded-lg border border-gray-500 focus:ring-blue-900 focus:border-blue-900">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input v-model="form.nama" type="text" class="w-full rounded-lg border border-gray-500 focus:ring-blue-900 focus:border-blue-900">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Masuk</label>
                            <input v-model="form.tglMasuk" type="date" class="w-full rounded-lg border border-gray-500 focus:ring-blue-900 focus:border-blue-900">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                            <select v-model="form.departemen" class="w-full rounded-lg border border-gray-500 focus:ring-blue-900 focus:border-blue-900">
                                <option value="HRD">HRD</option><option value="Keuangan">Keuangan</option><option value="Produksi">Produksi</option><option value="Pemasaran">Pemasaran</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select v-model="form.role" class="w-full rounded-lg border border-gray-500 focus:ring-blue-900 focus:border-blue-900">
                                <option value="Staff">Staff</option><option value="Manager">Manager</option><option value="HRD">HRD</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input v-model="form.password" type="password" class="w-full rounded-lg border border-gray-500 focus:ring-blue-900 focus:border-blue-900">
                        </div>
                        <div class="col-span-2 flex justify-end gap-3 mt-4">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg">Batal</button>
                            <button type="submit" class="px-6 py-2 bg-blue-900 text-white rounded-lg">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>