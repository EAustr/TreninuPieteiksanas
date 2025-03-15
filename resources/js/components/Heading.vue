<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <h1 class="text-2xl font-semibold tracking-tight">
        <slot />
    </h1>
</template>
