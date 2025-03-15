import axios from 'axios';

const instance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

// Add a request interceptor to handle CSRF token
instance.interceptors.request.use((config) => {
    // Get the CSRF token from the meta tag
    const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    
    return config;
});

export default instance; 