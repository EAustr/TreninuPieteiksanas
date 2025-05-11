<script setup lang="ts">
import { inject, onMounted, onUnmounted } from 'vue';

const select = inject('select');

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.select-content') && !target.closest('.select-trigger')) {
        select?.setOpen(false);
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div
        v-if="select?.open"
        class="select-content absolute z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-80"
    >
        <div class="p-1">
            <slot />
        </div>
    </div>
</template> 