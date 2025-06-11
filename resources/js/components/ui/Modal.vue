<template>
    <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50" @click="$emit('close')"></div>

        <!-- Modal Content -->
        <div class="relative z-10 w-full max-w-lg rounded-lg bg-card p-6 shadow-xl">
            <!-- Header -->
            <div class="mb-4">
                <h3 class="text-lg font-medium" v-if="$slots.title">
                    <slot name="title" />
                </h3>
            </div>

            <!-- Body -->
            <div class="space-y-4">
                <slot />
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-end space-x-2" v-if="$slots.footer">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props<T> {
    modelValue?: T;
}

const props = defineProps<Props<any>>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'close'): void;
}>();

const close = () => {
    emit('update:modelValue', null);
    emit('close');
};
</script>