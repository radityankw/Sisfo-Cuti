import axios from 'axios';

const api = axios.create({
    // VITE_API_URL akan dipakai saat di Vercel. 
    // Saat di laptop, akan otomatis *fallback* kembali ke localhost.
    baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api', 
    headers: { 'Content-Type': 'application/json' }
});

api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export default api;