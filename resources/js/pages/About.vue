<template>
    <div class="min-h-screen flex flex-col bg-gradient-to-b from-blue-100 to-yellow-100">
        <div class="flex-1 flex flex-col items-center justify-center text-center px-4">
            <h1 class="text-4xl md:text-5xl font-extrabold text-blue-700 mb-4 drop-shadow">About Our Beach Volleyball
                Club</h1>
            <p class="text-lg md:text-xl text-yellow-900 mb-6 max-w-2xl">
                We are a passionate community of beach volleyball enthusiasts based in Mārupe. Our club welcomes players
                of all skill levels to join our regular training sessions, tournaments, and social events. Come play,
                learn, and enjoy the sun and sand with us!
            </p>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-blue-800 mb-2">Location</h2>
                <p class="text-lg text-blue-900 mb-4">Lavandas, Mārupes pagasts, Mārupe Municipality, LV-2167</p>
                <div class="w-full max-w-xl mx-auto rounded-lg shadow-lg border-4 border-blue-300 overflow-hidden">
                    <div id="map" style="width:100%;height:300px;"></div>
                </div>
            </div>
            <a href="/"
                class="px-8 py-4 bg-yellow-500 hover:bg-yellow-600 text-white text-xl font-bold rounded shadow transition">Back
                to Home</a>
        </div>
        <footer class="text-center text-blue-800 py-6 bg-blue-100 border-t mt-8">
            &copy; {{ new Date().getFullYear() }} Beach Volleyball Club · Mārupe
        </footer>
    </div>
</template>

<script setup lang="ts">
/// <reference types="google.maps" />
import { onMounted } from 'vue';

declare global {
    interface Window {
        initMap: () => (void);
    }
}

onMounted(() => {
    const apiKey = import.meta.env.VITE_GOOGLE_API_KEY;
    const scriptId = 'google-maps-script';
    if (!document.getElementById(scriptId)) {
        const script = document.createElement('script');
        script.id = scriptId;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=initMap`;
        script.async = true;
        window.initMap = function () {
            const map = new window.google.maps.Map(document.getElementById('map')!, {
                center: { lat: 56.921167, lng: 24.001545 },
                zoom: 17,
            });
            new window.google.maps.Marker({
                position: { lat: 56.921167, lng: 24.001545 },
                map,
                title: 'Ruukki, Pludmales sporta centrs',
            });
        };
        document.head.appendChild(script);
    } else if (window.google && window.google.maps) {
        window.initMap();
    }
});
</script>
