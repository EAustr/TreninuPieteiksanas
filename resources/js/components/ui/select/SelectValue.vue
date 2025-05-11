<script setup lang="ts">
import { inject } from 'vue';

const select = inject('select');
</script>

<template>
    <span class="block truncate">
        <slot v-if="!select?.modelValue">
            <slot name="placeholder">Select an option</slot>
        </slot>
        <slot v-else :value="select.modelValue" />
    </span>
</template> 