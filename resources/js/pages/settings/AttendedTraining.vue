<template>
    <div class="attended-trainings">
        <h1>Attended Trainings</h1>
        <div v-if="loading" class="loading">Loading...</div>
        <div v-else>
            <div v-if="trainings.length === 0" class="no-trainings">
                No attended trainings found.
            </div>
            <ul v-else class="training-list">
                <li v-for="training in trainings" :key="training.id" class="training-item">
                    <h3>{{ training.title }}</h3>
                    <p>Date: {{ formatDate(training.start_time) }}</p>
                    <p v-if="training.description">{{ training.description }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Training {
    id: number;
    title: string;
    start_time: string;
    description?: string;
}

const trainings = ref<Training[]>([]);
const loading = ref(true);

const fetchTrainings = async () => {
    try {
        const response = await axios.get('/api/settings/attended-training');
        trainings.value = response.data;
    } catch (error) {
        console.error('Failed to fetch trainings:', error);
        trainings.value = [];
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateStr: string) => {
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return new Date(dateStr).toLocaleDateString(undefined, options);
};

onMounted(() => {
    fetchTrainings();
});
</script>

<style scoped>
.attended-trainings {
    max-width: 600px;
    margin: 2rem auto;
    padding: 1rem;
}

.loading {
    text-align: center;
}

.no-trainings {
    color: #888;
    text-align: center;
}

.training-list {
    list-style: none;
    padding: 0;
}

.training-item {
    border-bottom: 1px solid #eee;
    padding: 1rem 0;
}
</style>