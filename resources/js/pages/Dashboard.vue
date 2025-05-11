<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { Calendar, Users, Plus } from 'lucide-vue-next';
import { useAuth } from '@/composables/useAuth';
import axios from '@/utils/axios';
import { format } from 'date-fns';
import Modal from '@/components/ui/Modal.vue';
import AttendanceHeatmap from '@/components/training/AttendanceHeatmap.vue';

interface AttendanceRecord {
    id: number;
    user_id: number;
    user: {
        id: number;
        name: string;
    };
    status: string;
}

interface TrainingSession {
    id: number;
    start_time: string;
    end_time: string;
    max_participants: number;
    notes?: string;
    attendance_records: AttendanceRecord[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const { user } = useAuth();
const isTrainer = computed(() => user.value?.role === 'trainer');
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreateSession = computed(() => isTrainer.value || isAdmin.value);
const canRegister = computed(() => !isTrainer.value && !isAdmin.value);
const upcomingSessions = ref<TrainingSession[]>([]);
const showCreateModal = ref(false);
const editingSession = ref<TrainingSession | null>(null);

const form = ref({
    date: '',
    start_time: '',
    end_time: '',
    max_participants: 12,
    notes: ''
});

const errors = ref({
    date: '',
    start_time: '',
    end_time: '',
    max_participants: '',
    notes: ''
});

const showStartTimePicker = ref(false);
const showEndTimePicker = ref(false);

const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minutes = Array.from({ length: 12 }, (_, i) => (i * 5).toString().padStart(2, '0'));

const selectedStartHour = ref('09');
const selectedStartMinute = ref('00');
const selectedEndHour = ref('10');
const selectedEndMinute = ref('00');

const updateStartTime = () => {
    form.value.start_time = `${selectedStartHour.value}:${selectedStartMinute.value}`;
    showStartTimePicker.value = false;
};

const updateEndTime = () => {
    form.value.end_time = `${selectedEndHour.value}:${selectedEndMinute.value}`;
    showEndTimePicker.value = false;
};

const fetchUpcomingSessions = async () => {
    try {
        const response = await axios.get('/api/training-sessions');
        upcomingSessions.value = response.data
            .filter((session: TrainingSession) => new Date(session.start_time) > new Date())
            .sort((a: TrainingSession, b: TrainingSession) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
            .slice(0, 5);
    } catch (error) {
        console.error('Error fetching upcoming sessions:', error);
    }
};

const formatDateTime = (datetime: string) => {
    return format(new Date(datetime), 'MMM d, yyyy HH:mm');
};

const isRegistered = (session: TrainingSession) => {
    return session.attendance_records.some((record: AttendanceRecord) =>
        record.user_id === user.value?.id &&
        (record.status === 'registered' || record.status === 'present')
    );
};

const isSessionFull = (session: any) => {
    return session.attendance_records.length >= session.max_participants;
};

const registerForSession = async (sessionId: number) => {
    try {
        await axios.post(`/api/training-sessions/${sessionId}/register`);
        await fetchUpcomingSessions();
    } catch (error) {
        console.error('Error registering for session:', error);
    }
};

const unregisterFromSession = async (sessionId: number) => {
    try {
        await axios.delete(`/api/training-sessions/${sessionId}/register`);
        await fetchUpcomingSessions();
    } catch (error) {
        console.error('Error unregistering from session:', error);
    }
};

const editSession = (session: TrainingSession) => {
    editingSession.value = session;
    const startDate = new Date(session.start_time);
    const endDate = new Date(session.end_time);

    selectedStartHour.value = format(startDate, 'HH');
    selectedStartMinute.value = format(startDate, 'mm');
    selectedEndHour.value = format(endDate, 'HH');
    selectedEndMinute.value = format(endDate, 'mm');

    form.value = {
        date: format(startDate, 'yyyy-MM-dd'),
        start_time: format(startDate, 'HH:mm'),
        end_time: format(endDate, 'HH:mm'),
        max_participants: session.max_participants,
        notes: session.notes || ''
    };
    showCreateModal.value = true;
};

const deleteSession = async (sessionId: number) => {
    if (!confirm('Are you sure you want to delete this training session?')) {
        return;
    }
    try {
        await axios.delete(`/api/training-sessions/${sessionId}`);
        await fetchUpcomingSessions();
    } catch (error) {
        console.error('Error deleting session:', error);
    }
};

const createSession = async () => {
    try {
        // Reset errors
        errors.value = {
            date: '',
            start_time: '',
            end_time: '',
            max_participants: '',
            notes: ''
        };

        // Validate form
        if (!form.value.date) {
            errors.value.date = 'Date is required';
            return;
        }
        if (!form.value.start_time) {
            errors.value.start_time = 'Start time is required';
            return;
        }
        if (!form.value.end_time) {
            errors.value.end_time = 'End time is required';
            return;
        }
        if (!form.value.max_participants || form.value.max_participants < 1) {
            errors.value.max_participants = 'Maximum participants must be at least 1';
            return;
        }

        // Format the date and time for the API
        const formattedData = {
            ...form.value,
            start_time: `${form.value.date} ${form.value.start_time}`,
            end_time: `${form.value.date} ${form.value.end_time}`,
        };

        if (editingSession.value) {
            await axios.put(`/api/training-sessions/${editingSession.value.id}`, formattedData);
        } else {
            await axios.post('/api/training-sessions', formattedData);
        }
        showCreateModal.value = false;
        resetForm();
        await fetchUpcomingSessions();
    } catch (error) {
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { errors?: Record<string, string> } } };
            if (axiosError.response?.data?.errors) {
                const serverErrors = axiosError.response.data.errors;
                errors.value = {
                    date: serverErrors.date || '',
                    start_time: serverErrors.start_time || '',
                    end_time: serverErrors.end_time || '',
                    max_participants: serverErrors.max_participants || '',
                    notes: serverErrors.notes || ''
                };
            }
        } else {
            console.error('Error saving session:', error);
        }
    }
};

const resetForm = () => {
    editingSession.value = null;
    form.value = {
        date: '',
        start_time: '',
        end_time: '',
        max_participants: 12,
        notes: ''
    };
};

onMounted(() => {
    fetchUpcomingSessions();
});
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">Welcome back!</h2>
                    <p class="text-muted-foreground">Here's what's happening in your training program</p>
                </div>
            </div>

            <!-- Quick Actions Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Training Calendar Card -->
                <Link href="/training-calendar"
                    class="group relative overflow-hidden rounded-lg border bg-white p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-blue-100 p-3">
                        <Calendar class="h-6 w-6 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Training Calendar</h3>
                        <p class="text-sm text-gray-600">View and manage training sessions</p>
                    </div>
                </div>
                </Link>

                <!-- Create Session Card - Only visible to admin and trainer -->
                <button v-if="canCreateSession" @click="showCreateModal = true"
                    class="group relative overflow-hidden rounded-lg border bg-white p-6 shadow transition-shadow hover:shadow-lg text-left">
                    <div class="flex items-center gap-4">
                        <div class="rounded-full bg-blue-100 p-3">
                            <Plus class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Create Session</h3>
                            <p class="text-sm text-gray-600">Schedule a new training session</p>
                        </div>
                    </div>
                </button>

                <!-- Register User Card - Only visible to admin -->
                <Link v-if="isAdmin" href="/register-user"
                    class="group relative overflow-hidden rounded-lg border bg-white p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-green-100 p-3">
                        <Users class="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Register User</h3>
                        <p class="text-sm text-gray-600">Add a new user to the system</p>
                    </div>
                </div>
                </Link>

                <!-- Attendance Card - Only visible to admin and trainer -->
                <Link v-if="isAdmin || isTrainer" href="/training/attendance"
                    class="group relative overflow-hidden rounded-lg border bg-white p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-purple-100 p-3">
                        <Users class="h-6 w-6 text-purple-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Attendance Tracker</h3>
                        <p class="text-sm text-gray-600">Manage training attendance</p>
                    </div>
                </div>
                </Link>
            </div>

            <!-- Attendance Heatmap -->
            <AttendanceHeatmap v-if="canRegister" />

            <!-- Upcoming Sessions -->
            <div class="rounded-lg border bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Upcoming Sessions</h3>
                <div v-if="upcomingSessions.length > 0" class="space-y-4">
                    <div v-for="session in upcomingSessions" :key="session.id"
                        class="flex items-center justify-between rounded-lg border p-4">
                        <div>
                            <p class="font-medium">{{ formatDateTime(session.start_time) }}</p>
                            <p class="text-sm text-gray-600">
                                {{ session.attendance_records.length }}/{{ session.max_participants }} participants
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <template v-if="isAdmin || isTrainer">
                                <button @click="editSession(session)"
                                    class="rounded-md bg-blue-100 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-200">
                                    Edit
                                </button>
                                <button v-if="isAdmin" @click="deleteSession(session.id)"
                                    class="rounded-md bg-red-100 px-3 py-1.5 text-sm text-red-600 hover:bg-red-200">
                                    Delete
                                </button>
                            </template>
                            <button v-if="canRegister && !isRegistered(session)" @click="registerForSession(session.id)"
                                class="rounded-md bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700"
                                :disabled="isSessionFull(session)">
                                Register
                            </button>
                            <button v-else-if="canRegister" @click="unregisterFromSession(session.id)"
                                class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500">
                    No upcoming sessions
                </div>
            </div>
        </div>

        <!-- Create Session Modal -->
        <Modal v-model="showCreateModal" @close="showCreateModal = false">
            <template #title>{{ editingSession ? 'Edit Training Session' : 'Create Training Session' }}</template>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" v-model="form.date" :min="new Date().toISOString().split('T')[0]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': errors.date }">
                    <p v-if="errors.date" class="mt-1 text-sm text-red-600">{{ errors.date }}</p>
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700">Start Time</label>
                    <button type="button" @click="showStartTimePicker = !showStartTimePicker"
                        class="mt-1 w-full text-left px-3 py-2 rounded-md border border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': errors.start_time }">
                        {{ form.start_time || 'Select time' }}
                    </button>
                    <div v-if="showStartTimePicker"
                        class="absolute z-50 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200">
                        <div class="flex p-2 border-b">
                            <div class="flex-1 pr-2 border-r">
                                <div class="text-center text-sm text-gray-500 mb-1">Hour</div>
                                <div class="h-40 overflow-y-auto scrollbar-thin">
                                    <button v-for="hour in hours" :key="hour" @click="selectedStartHour = hour"
                                        class="block w-full px-2 py-1 text-center hover:bg-blue-50 rounded"
                                        :class="{ 'bg-blue-100 text-blue-600': selectedStartHour === hour }">
                                        {{ hour }}
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 pl-2">
                                <div class="text-center text-sm text-gray-500 mb-1">Minute</div>
                                <div class="h-40 overflow-y-auto scrollbar-thin">
                                    <button v-for="minute in minutes" :key="minute"
                                        @click="selectedStartMinute = minute"
                                        class="block w-full px-2 py-1 text-center hover:bg-blue-50 rounded"
                                        :class="{ 'bg-blue-100 text-blue-600': selectedStartMinute === minute }">
                                        {{ minute }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-2 border-t bg-gray-50">
                            <button type="button" @click="updateStartTime"
                                class="w-full px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Set Time
                            </button>
                        </div>
                    </div>
                    <p v-if="errors.start_time" class="mt-1 text-sm text-red-600">{{ errors.start_time }}</p>
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700">End Time</label>
                    <button type="button" @click="showEndTimePicker = !showEndTimePicker"
                        class="mt-1 w-full text-left px-3 py-2 rounded-md border border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': errors.end_time }">
                        {{ form.end_time || 'Select time' }}
                    </button>
                    <div v-if="showEndTimePicker"
                        class="absolute z-50 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200">
                        <div class="flex p-2 border-b">
                            <div class="flex-1 pr-2 border-r">
                                <div class="text-center text-sm text-gray-500 mb-1">Hour</div>
                                <div class="h-40 overflow-y-auto scrollbar-thin">
                                    <button v-for="hour in hours" :key="hour" @click="selectedEndHour = hour"
                                        class="block w-full px-2 py-1 text-center hover:bg-blue-50 rounded"
                                        :class="{ 'bg-blue-100 text-blue-600': selectedEndHour === hour }">
                                        {{ hour }}
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 pl-2">
                                <div class="text-center text-sm text-gray-500 mb-1">Minute</div>
                                <div class="h-40 overflow-y-auto scrollbar-thin">
                                    <button v-for="minute in minutes" :key="minute" @click="selectedEndMinute = minute"
                                        class="block w-full px-2 py-1 text-center hover:bg-blue-50 rounded"
                                        :class="{ 'bg-blue-100 text-blue-600': selectedEndMinute === minute }">
                                        {{ minute }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-2 border-t bg-gray-50">
                            <button type="button" @click="updateEndTime"
                                class="w-full px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Set Time
                            </button>
                        </div>
                    </div>
                    <p v-if="errors.end_time" class="mt-1 text-sm text-red-600">{{ errors.end_time }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Max Participants</label>
                    <input type="number" v-model="form.max_participants"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': errors.max_participants }" min="1" max="50">
                    <p v-if="errors.max_participants" class="mt-1 text-sm text-red-600">{{ errors.max_participants }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea v-model="form.notes" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': errors.notes }"></textarea>
                    <p v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes }}</p>
                </div>
            </div>

            <template #footer>
                <button type="button"
                    class="mr-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    @click="showCreateModal = false">
                    Cancel
                </button>
                <button type="button"
                    class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                    @click="createSession">
                    Create
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.btn-primary {
    @apply bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300;
}

.btn-danger {
    @apply bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700;
}

/* Force 24-hour format for time inputs */
input[type="time"] {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: textfield;
}

input[type="time"]::-webkit-datetime-edit-ampm-field {
    display: none !important;
}

input[type="time"]::-moz-clock-picker-indicator {
    display: none;
}

input[type="time"]::-webkit-calendar-picker-indicator {
    background: none;
    display: none;
}

input[type="time"]::-webkit-clear-button {
    display: none;
}

/* Hide AM/PM from time input */
input[type="time"]::-webkit-datetime-edit-hour-field,
input[type="time"]::-webkit-datetime-edit-minute-field {
    padding: 0;
}

/* Force 24-hour time picker */
input[type="time"]::-webkit-datetime-edit {
    padding: 0;
}

input[type="time"]::-webkit-inner-spin-button {
    display: none;
}

.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: #e5e7eb transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #e5e7eb;
    border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background-color: #d1d5db;
}
</style>
