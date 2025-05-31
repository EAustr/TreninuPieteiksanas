<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <Heading title="Training Calendar">Training Calendar</Heading>
                <button v-if="isTrainer" @click="showCreateModal = true" class="btn-primary">
                    Create Training Session
                </button>
            </div>

            <TrainingCalendarView />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import TrainingCalendarView from '@/components/training/TrainingCalendarView.vue'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()
const isTrainer = computed(() => user.value?.role === 'trainer')
const showCreateModal = ref(false)
</script>

<style scoped>
.btn-primary {
    @apply bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700;
}
</style>