<template>
    <div class="min-h-screen flex flex-col bg-gradient-to-b from-yellow-100 to-blue-200">
        <div class="flex-1 flex flex-col items-center justify-center text-center px-4">
            <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=900&q=80"
                alt="Beach Volleyball" class="w-full max-w-xl rounded-lg shadow-lg mb-8 border-4 border-yellow-300" />
            <h1 class="text-4xl md:text-5xl font-extrabold text-yellow-700 mb-4 drop-shadow">Welcome to Beach Volleyball
                Club!</h1>
            <p class="text-lg md:text-xl text-blue-900 mb-8 max-w-2xl">Join us for fun, fitness, and friendly
                competition on the sand. Whether you're a beginner or a pro, everyone is welcome at our beach volleyball
                community in Mārupe!</p>
            <div class="flex flex-col md:flex-row gap-4 justify-center">
                <a href="/login"
                    class="px-8 py-4 bg-yellow-500 hover:bg-yellow-600 text-white text-xl font-bold rounded shadow transition">Login</a>
                <a href="/about"
                    class="px-8 py-4 bg-blue-500 hover:bg-blue-600 text-white text-xl font-bold rounded shadow transition">About
                    Us</a>
            </div>
        </div>
        <footer class="text-center text-blue-800 py-6 bg-yellow-100 border-t mt-8">
            &copy; {{ new Date().getFullYear() }} Beach Volleyball Club · Mārupe
        </footer>
    </div>
</template>