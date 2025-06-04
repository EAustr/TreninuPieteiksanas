<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from '@/lib/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Heading } from '@/components/ui/heading';
import { Button } from '@/components/ui/button';

interface AttendanceRecord {
    id: number;
    user: {
        id: number;
        name: string;
    };
    status: 'present' | 'absent' | 'late' | 'registered';
    created_at: string;
}

interface TrainingSession {
    id: number;
    start_time: string;
    end_time: string;
    max_participants: number;
    notes: string;
    attendance_records: AttendanceRecord[];
}

const sessions = ref<TrainingSession[]>([]);
const loading = ref(true);

const tab = ref<'upcoming' | 'past'>('upcoming');
const expandedSessionId = ref<number | null>(null);

const now = new Date();

const upcomingSessions = computed(() =>
    sessions.value.filter(s => new Date(s.start_time) >= now)
);
const pastSessions = computed(() =>
    sessions.value.filter(s => new Date(s.start_time) < now)
);

const totalSessions = computed(() => sessions.value.length);

const totalParticipants = computed(() => {
  const userIds = new Set();
  sessions.value.forEach(session => {
    session.attendance_records.forEach(record => {
      userIds.add(record.user.id);
    });
  });
  return userIds.size;
});

const fetchSessions = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/training-sessions');
        sessions.value = response.data.sessions;
    } catch (error) {
        console.error('Error fetching sessions:', error);
    } finally {
        loading.value = false;
    }
};

const updateAttendance = async (recordId: number, status: 'present' | 'absent' | 'late' | 'registered') => {
    try {
        // Find the current record
        const currentRecord = sessions.value
            .flatMap(session => session.attendance_records)
            .find(record => record.id === recordId);

        let newStatus = status;

        // Toggle between present and registered when clicking Present button
        if (status === 'present') {
            if (currentRecord?.status === 'present') {
                newStatus = 'registered';
            } else if (currentRecord?.status === 'registered') {
                newStatus = 'present';
            }
        }

        await axios.put(`/api/attendance-records/${recordId}`, { status: newStatus });
        await fetchSessions();
    } catch (error) {
        console.error('Error updating attendance:', error);
    }
};

onMounted(() => {
    fetchSessions();
});
</script>

<template>
    <div class="space-y-6">
        <Heading title="Attendance Tracker" />

        <!-- Summary Stats -->
        <div class="flex gap-8 mb-4">
            <div>
                <div class="text-lg font-bold">{{ totalSessions }}</div>
                <div class="text-xs text-gray-500">Total Sessions</div>
            </div>
            <div>
                <div class="text-lg font-bold">{{ totalParticipants }}</div>
                <div class="text-xs text-gray-500">Total Participants</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-4">
            <button @click="tab = 'upcoming'"
                :class="['px-4 py-2 rounded', tab === 'upcoming' ? 'bg-blue-600 text-white' : 'bg-gray-200']">Upcoming
                Sessions</button>
            <button @click="tab = 'past'"
                :class="['px-4 py-2 rounded', tab === 'past' ? 'bg-blue-600 text-white' : 'bg-gray-200']">Past
                Sessions</button>
        </div>

        <div v-if="loading" class="text-center py-8">
            Loading sessions...
        </div>

        <div v-else>
            <div v-if="(tab === 'upcoming' ? upcomingSessions : pastSessions).length === 0" class="text-center py-8">
                No training sessions found.
            </div>
            <div v-else class="space-y-4">
                <Card v-for="session in (tab === 'upcoming' ? upcomingSessions : pastSessions)" :key="session.id"
                    class="overflow-hidden">
                    <CardHeader @click="expandedSessionId = expandedSessionId === session.id ? null : session.id"
                        class="cursor-pointer flex justify-between items-center">
                        <CardTitle>
                            Training Session - {{ new Date(session.start_time).toLocaleDateString() }}
                            ({{ new Date(session.start_time).toLocaleTimeString() }} - {{ new
                                Date(session.end_time).toLocaleTimeString() }})
                        </CardTitle>
                        <span class="text-xs text-gray-500">{{ session.attendance_records.length }} participants</span>
                    </CardHeader>
                    <CardContent v-if="expandedSessionId === session.id">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">Participant</th>
                                        <th class="text-left py-2">Status</th>
                                        <th class="text-left py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="record in session.attendance_records" :key="record.id" class="border-b">
                                        <td class="py-2">{{ record.user.name }}</td>
                                        <td class="py-2">
                                            <span :class="{
                                                'text-green-600': record.status === 'present',
                                                'text-red-600': record.status === 'absent',
                                                'text-yellow-600': record.status === 'late',
                                                'text-blue-600': record.status === 'registered'
                                            }">
                                                {{ record.status }}
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex gap-2">
                                                <Button variant="default" size="sm"
                                                    :class="{ 'opacity-50': record.status === 'present' }"
                                                    @click="updateAttendance(record.id, 'present')">
                                                    {{ record.status === 'present' ? 'Mark Registered' : (record.status
                                                        === 'registered' ? 'Mark Present' : 'Present') }}
                                                </Button>
                                                <Button variant="destructive" size="sm"
                                                    :class="{ 'opacity-50': record.status === 'absent' }"
                                                    @click="updateAttendance(record.id, 'absent')">
                                                    Absent
                                                </Button>
                                                <Button variant="outline" size="sm"
                                                    :class="{ 'opacity-50': record.status === 'late' }"
                                                    @click="updateAttendance(record.id, 'late')">
                                                    Late
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>