<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import Sidebar from '../components/Sidebar.vue';
import api from '../lib/axios';

const router = useRouter();
const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'));

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const isLoading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const validationErrors = ref({});
const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = async () => {
    isLoading.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    validationErrors.value = {};

    try {
        const response = await api.patch('/password', form.value);
        successMessage.value = response.data.message || 'Password berhasil diubah.';

        localStorage.removeItem('token');
        localStorage.removeItem('user');

        setTimeout(() => {
            router.push('/login');
        }, 1200);
    } catch (error) {
        if (error.response?.status === 422) {
            validationErrors.value = error.response.data.errors || {};
            errorMessage.value = error.response.data.message || 'Validasi gagal.';
        } else {
            errorMessage.value = error.response?.data?.message || 'Gagal mengganti password.';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="h-screen bg-neutral-50 flex overflow-hidden font-sans">
        <Sidebar />

        <main class="flex-1 ml-64 p-8 overflow-y-auto">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-bold text-black mb-2">Ganti Password</h1>
                <p class="text-gray-500 mb-8">Ubah password akun {{ user.nama || 'Anda' }} untuk menjaga keamanan login.</p>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-black mb-2" for="current_password">Password Lama</label>
                            <div class="flex items-center rounded-lg border border-gray-200 focus-within:border-blue-900 focus-within:ring-2 focus-within:ring-blue-100 px-4 h-12">
                                <input
                                    id="current_password"
                                    v-model="form.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    class="w-full border-0 focus:ring-0 outline-none text-sm bg-transparent"
                                    placeholder="Masukkan password lama"
                                    autocomplete="current-password"
                                    required
                                />
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="ml-2 text-gray-500 hover:text-blue-900">
                                    <span class="text-xs font-semibold">{{ showCurrentPassword ? 'Sembunyi' : 'Tampil' }}</span>
                                </button>
                            </div>
                            <p v-if="validationErrors.current_password" class="mt-1 text-xs text-red-500">{{ validationErrors.current_password[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-black mb-2" for="password">Password Baru</label>
                            <div class="flex items-center rounded-lg border border-gray-200 focus-within:border-blue-900 focus-within:ring-2 focus-within:ring-blue-100 px-4 h-12">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="w-full border-0 focus:ring-0 outline-none text-sm bg-transparent"
                                    placeholder="Minimal 8 karakter"
                                    autocomplete="new-password"
                                    required
                                />
                                <button type="button" @click="showPassword = !showPassword" class="ml-2 text-gray-500 hover:text-blue-900">
                                    <span class="text-xs font-semibold">{{ showPassword ? 'Sembunyi' : 'Tampil' }}</span>
                                </button>
                            </div>
                            <p v-if="validationErrors.password" class="mt-1 text-xs text-red-500">{{ validationErrors.password[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-black mb-2" for="password_confirmation">Konfirmasi Password Baru</label>
                            <div class="flex items-center rounded-lg border border-gray-200 focus-within:border-blue-900 focus-within:ring-2 focus-within:ring-blue-100 px-4 h-12">
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    class="w-full border-0 focus:ring-0 outline-none text-sm bg-transparent"
                                    placeholder="Ulangi password baru"
                                    autocomplete="new-password"
                                    required
                                />
                                <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="ml-2 text-gray-500 hover:text-blue-900">
                                    <span class="text-xs font-semibold">{{ showPasswordConfirmation ? 'Sembunyi' : 'Tampil' }}</span>
                                </button>
                            </div>
                            <p v-if="validationErrors.password_confirmation" class="mt-1 text-xs text-red-500">{{ validationErrors.password_confirmation[0] }}</p>
                        </div>

                        <div v-if="errorMessage" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </div>

                        <div v-if="successMessage" class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                            {{ successMessage }}
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="router.push('/dashboard')" class="px-5 py-2.5 rounded-lg border border-gray-200 text-gray-700 font-semibold hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="isLoading" class="px-6 py-2.5 rounded-lg bg-blue-900 text-white font-semibold hover:bg-blue-800 disabled:opacity-70">
                                {{ isLoading ? 'Menyimpan...' : 'Simpan Password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>
