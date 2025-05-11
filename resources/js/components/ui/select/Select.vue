<script setup lang="ts">
import { provide, ref } from 'vue';

const modelValue = defineModel<string>();
const open = ref(false);

provide('select', {
    modelValue,
    open,
    setOpen: (value: boolean) => open.value = value,
    setValue: (value: string) => {
        modelValue.value = value;
        open.value = false;
    }
});
</script>

<template>
    <div class="relative">
        <slot />
    </div>
</template> 