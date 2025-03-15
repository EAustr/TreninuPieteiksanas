<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from '@/lib/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Heading } from '@/components/ui/heading';

interface Analytics {
    totalSessions: number;
    totalParticipants: number;
    averageAttendance: number;
    upcomingSessions: number;
}

const analytics = ref<Analytics>({
    totalSessions: 0,
    totalParticipants: 0,
    averageAttendance: 0,
    upcomingSessions: 0,
});

const loading = ref(true);

const fetchAnalytics = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/training-analytics');
        analytics.value = response.data;
    } catch (error) {
        console.error('Error fetching analytics:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAnalytics();
});
</script>

<template>
    <div class="space-y-6">
        <Heading title="Training Analytics" />

        <div v-if="loading" class="text-center py-8">
            Loading analytics...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card>
                <CardHeader>
                    <CardTitle>Total Sessions</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ analytics.totalSessions }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Total Participants</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ analytics.totalParticipants }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Average Attendance</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ analytics.averageAttendance }}%</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Upcoming Sessions</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ analytics.upcomingSessions }}</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>