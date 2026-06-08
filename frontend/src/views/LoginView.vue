<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router'; // Ganti Inertia router
import api from '../lib/axios';

const nik = ref('');
const password = ref('');
const showPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');
const router = useRouter();

const submit = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.post('/login', {
            nik: nik.value,
            password: password.value
        });

        // Simpan Token & User
        localStorage.setItem('token', response.data.access_token);
        localStorage.setItem('user', JSON.stringify(response.data.user));

        // Redirect ke Dashboard
        router.push('/dashboard');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Login gagal.';
        password.value = ''; // Reset password
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="h-screen w-full bg-neutral-100 flex items-center justify-center overflow-hidden font-sans">
        <div class="w-full max-w-[550px] bg-white rounded-lg shadow-sm relative flex flex-col items-center py-8 px-8 md:px-10">
            <div class="mb-4">
                <img src="/images/logo.png" alt="Logo Menara Kudus" class="h-24 w-auto object-contain" />
            </div>

            <h1 class="text-blue-900 text-xl md:text-2xl font-bold text-center mb-1">
                PT MENARA KUDUS INDONESIA
            </h1>
            <h2 class="text-blue-900 text-xl font-semibold text-center mb-6">
                Login
            </h2>

            <form @submit.prevent="submit" class="w-full flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <label for="nik" class="text-black text-base font-semibold">Nomor Induk Karyawan</label>
                    <div class="w-full h-12 bg-white rounded-lg outline outline-1 outline-gray-200 flex items-center px-4 overflow-hidden focus-within:outline-blue-900 focus-within:outline-2 transition-all">
                        <input id="nik" type="text" v-model="nik" class="w-full border-none focus:ring-0 text-sm font-normal text-gray-700 bg-transparent placeholder-gray-400 outline-none" placeholder="Masukkan NIK" required autofocus />
                    </div>
                </div>

                <div class="flex flex-col gap-1 relative">
                    <label for="password" class="text-black text-base font-semibold">Password</label>
                    <div class="w-full h-12 bg-white rounded-lg outline outline-1 outline-gray-200 flex items-center px-4 overflow-hidden focus-within:outline-blue-900 focus-within:outline-2 transition-all">
                        <input id="password" :type="showPassword ? 'text' : 'password'" v-model="password" class="w-full border-none focus:ring-0 text-sm font-normal text-gray-700 bg-transparent placeholder-gray-400 outline-none" placeholder="Masukkan Password" required />
                        
                        <button type="button" @click="showPassword = !showPassword" class="ml-2 text-gray-500 hover:text-blue-900 focus:outline-none">
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>
                    </div>
                    <div v-if="errorMessage" class="text-red-500 text-xs mt-1">{{ errorMessage }}</div>
                </div>

                <button type="submit" :disabled="isLoading" class="w-full h-14 mt-4 bg-blue-900 rounded-lg flex justify-center items-center gap-2 hover:bg-blue-800 transition-colors disabled:opacity-75">
                    <span class="text-neutral-100 text-sm font-bold leading-4">{{ isLoading ? 'Memproses...' : 'Log In' }}</span>
                </button>
            </form>
        </div>
    </div>
</template>