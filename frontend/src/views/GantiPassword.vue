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
    <div class="min-h-screen lg:h-screen bg-neutral-50 flex overflow-x-hidden lg:overflow-hidden font-sans">
        <Sidebar />

        <main class="flex-1 ml-0 lg:ml-64 p-4 md:p-6 lg:p-8 overflow-y-auto">
            <div class="max-w-3xl">
                <h1 class="page-title text-2xl font-bold text-black mb-2">Ganti Password</h1>
                <p class="text-gray-500 mb-8">Ubah password akun Anda untuk menjaga keamanan login.</p>

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
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="ml-2 text-gray-500 hover:text-blue-900 focus:outline-none">
                                    <svg v-if="!showCurrentPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
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
                                <button type="button" @click="showPassword = !showPassword" class="ml-2 text-gray-500 hover:text-blue-900 focus:outline-none">
                                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
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
                                <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="ml-2 text-gray-500 hover:text-blue-900 focus:outline-none">
                                    <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
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
