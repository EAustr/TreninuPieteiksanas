<script setup lang="ts">
defineProps<{
    title: string;
}>();
</script>

<template>
    <div class="flex items-center justify-between space-y-2">
        <h2 class="text-3xl font-bold tracking-tight">{{ title }}</h2>
    </div>
</template>