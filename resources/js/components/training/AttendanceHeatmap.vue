<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from '@/lib/axios';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface AttendanceRecord {
    date: string;
    attended: boolean;
}

const attendanceData = ref<AttendanceRecord[]>([]);
const loading = ref(true);

const fetchAttendanceData = async () => {
    try {
        const response = await axios.get('/api/training/attendance/heatmap');
        attendanceData.value = response.data;
    } catch (error) {
        console.error('Failed to fetch attendance data:', error);
    } finally {
        loading.value = false;
    }
};

// Generate last 12 weeks of dates
const weeks = computed(() => {
    const today = new Date();
    const weeks = [];
    for (let i = 0; i < 12; i++) {
        const week = [];
        for (let j = 0; j < 7; j++) {
            const date = new Date(today);
            date.setDate(today.getDate() - (i * 7 + (6 - j)));
            week.push({
                date: date.toISOString().split('T')[0],
                attended: attendanceData.value.some(record => 
                    record.date === date.toISOString().split('T')[0] && record.attended
                )
            });
        }
        weeks.unshift(week);
    }
    return weeks;
});

onMounted(() => {
    fetchAttendanceData();
});
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>Training Attendance</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="loading" class="flex justify-center items-center h-32">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
            <div v-else class="flex gap-1">
                <div v-for="(week, weekIndex) in weeks" :key="weekIndex" class="flex flex-col gap-1">
                    <div v-for="(day) in week" :key="day.date" 
                         class="w-3 h-3 rounded-sm"
                         :class="{
                             'bg-green-500 dark:bg-green-400': day.attended,
                             'bg-muted dark:bg-muted/50': !day.attended
                         }"
                         :title="new Date(day.date).toLocaleDateString() + (day.attended ? ' - Attended' : ' - Not attended')"
                    ></div>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                <span>Less</span>
                <div class="w-3 h-3 rounded-sm bg-muted dark:bg-muted/50"></div>
                <div class="w-3 h-3 rounded-sm bg-green-500 dark:bg-green-400"></div>
                <span>More</span>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
.bg-green-500 {
    background-color: #22c55e;
}

.dark .bg-green-500 {
    background-color: #4ade80;
}

.bg-muted {
    background-color: hsl(var(--muted));
}

.dark .bg-muted {
    background-color: hsl(var(--muted) / 0.5);
}
</style> 