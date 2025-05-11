<script setup lang="ts">
import { router } from '@inertiajs/vue3';

router.visit('/dashboard');
</script>

<template>
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <h1 class="text-2xl font-semibold">Redirecting to Dashboard...</h1>
        </div>
    </div>
</template>
