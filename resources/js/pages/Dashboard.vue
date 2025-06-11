<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { Calendar, Users, Plus } from 'lucide-vue-next';
import { useAuth } from '@/composables/useAuth';
import axios from '@/lib/axios';
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

interface TrainingCategory {
    id: number;
    name: string;
    description: string;
    color: string;
}

interface Trainer {
    id: number;
    name: string;
    email: string;
}

interface TrainingSession {
    id: number;
    start_time: string;
    end_time: string;
    max_participants: number;
    notes?: string;
    category_id?: number;
    category?: TrainingCategory;
    trainer_id: number;
    trainer?: Trainer;
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

const trainers = ref<Trainer[]>([]);

const form = ref({
    date: '',
    start_time: '',
    end_time: '',
    max_participants: 12,
    notes: '',
    category_id: null as number | null,
    trainer_id: null as number | null
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

const selectedStartHour = ref('09');
const selectedStartMinute = ref('00');
const selectedEndHour = ref('10');
const selectedEndMinute = ref('00');

const categories = ref<TrainingCategory[]>([]);


const fetchUpcomingSessions = async () => {
    try {
        const response = await axios.get('/api/training-sessions');
        upcomingSessions.value = response.data.sessions
            .filter((session: any) => new Date(session.start_time) > new Date())
            .sort((a: any, b: any) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime())
            .slice(0, 5);
        categories.value = response.data.categories;
    } catch (error) {
        console.error('Error fetching upcoming sessions:', error);
    }
};

const fetchTrainers = async () => {
    try {
        const response = await axios.get('/api/trainers');
        trainers.value = response.data;
        // If user is a trainer, set their ID as default
        if (isTrainer.value && user.value) {
            form.value.trainer_id = user.value.id;
        }
    } catch (error) {
        console.error('Error fetching trainers:', error);
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
        notes: session.notes || '',
        category_id: session.category_id || null,
        trainer_id: session.trainer_id
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
    form.value = {
        date: '',
        start_time: '',
        end_time: '',
        max_participants: 12,
        notes: '',
        category_id: null,
        trainer_id: null
    };
    editingSession.value = null;
};

onMounted(() => {
    fetchUpcomingSessions();
    fetchTrainers();
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
                    class="group relative overflow-hidden rounded-lg border bg-card p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-3">
                        <Calendar class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Training Calendar</h3>
                        <p class="text-sm text-muted-foreground">View and manage training sessions</p>
                    </div>
                </div>
                </Link>

                <!-- Create Session Card - Only visible to admin and trainer -->
                <button v-if="canCreateSession" @click="showCreateModal = true"
                    class="group relative overflow-hidden rounded-lg border bg-card p-6 shadow transition-shadow hover:shadow-lg text-left">
                    <div class="flex items-center gap-4">
                        <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-3">
                            <Plus class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Create Session</h3>
                            <p class="text-sm text-muted-foreground">Schedule a new training session</p>
                        </div>
                    </div>
                </button>

                <!-- Register User Card - Only visible to admin -->
                <Link v-if="isAdmin" href="/register-user"
                    class="group relative overflow-hidden rounded-lg border bg-card p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-3">
                        <Users class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Register User</h3>
                        <p class="text-sm text-muted-foreground">Add a new user to the system</p>
                    </div>
                </div>
                </Link>

                <!-- User Management Card - Only visible to admin -->
                <Link v-if="isAdmin" href="/users"
                    class="group relative overflow-hidden rounded-lg border bg-card p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-3">
                        <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold">User Management</h3>
                        <p class="text-sm text-muted-foreground">Manage all users in the system</p>
                    </div>
                </div>
                </Link>

                <!-- Attendance Card - Only visible to admin and trainer -->
                <Link v-if="isAdmin || isTrainer" href="/training/attendance"
                    class="group relative overflow-hidden rounded-lg border bg-card p-6 shadow transition-shadow hover:shadow-lg">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-purple-100 dark:bg-purple-900/30 p-3">
                        <Users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Attendance Tracker</h3>
                        <p class="text-sm text-muted-foreground">Manage training attendance</p>
                    </div>
                </div>
                </Link>
            </div>

            <!-- Attendance Heatmap -->
            <AttendanceHeatmap v-if="canRegister" />

            <!-- Upcoming Sessions -->
            <div class="rounded-lg border bg-card p-6">
                <h3 class="mb-4 text-lg font-semibold">Upcoming Sessions</h3>
                <div v-if="upcomingSessions.length > 0" class="space-y-4">
                    <div v-for="session in upcomingSessions" :key="session.id"
                        class="flex items-center justify-between rounded-lg border bg-card p-4">
                        <div>
                            <p class="font-medium">{{ formatDateTime(session.start_time) }}</p>
                            <p class="text-sm text-muted-foreground">
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
                    <label class="block text-sm font-medium text-foreground">Date</label>
                    <input type="date" v-model="form.date" :min="new Date().toISOString().split('T')[0]"
                        class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        :class="{ 'border-destructive': errors.date }">
                    <p v-if="errors.date" class="mt-1 text-sm text-destructive">{{ errors.date }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground">Start Time</label>
                    <input type="time" v-model="form.start_time" @focus="showStartTimePicker = true"
                        class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        :class="{ 'border-destructive': errors.start_time }">
                    <p v-if="errors.start_time" class="mt-1 text-sm text-destructive">{{ errors.start_time }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground">End Time</label>
                    <input type="time" v-model="form.end_time" @focus="showEndTimePicker = true"
                        class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        :class="{ 'border-destructive': errors.end_time }">
                    <p v-if="errors.end_time" class="mt-1 text-sm text-destructive">{{ errors.end_time }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground">Max Participants</label>
                    <input type="number" v-model="form.max_participants"
                        class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        :class="{ 'border-destructive': errors.max_participants }">
                    <p v-if="errors.max_participants" class="mt-1 text-sm text-destructive">{{ errors.max_participants
                        }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground">Category</label>
                    <select v-model="form.category_id"
                        class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option :value="null">Select a category</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div v-if="isAdmin">
                    <label class="block text-sm font-medium text-foreground">Trainer</label>
                    <select v-model="form.trainer_id" required class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option :value="null">Select a trainer</option>
                        <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                            {{ trainer.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground">Notes</label>
                    <textarea v-model="form.notes" rows="3"
                        class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <button type="button" class="btn-secondary" @click="showCreateModal = false">
                    Cancel
                </button>
                <button type="button" class="btn-primary" @click="createSession">
                    {{ editingSession ? 'Update' : 'Create' }}
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.btn-primary {
    @apply bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90;
}

.btn-secondary {
    @apply bg-secondary text-secondary-foreground px-4 py-2 rounded-md hover:bg-secondary/80;
}

.btn-danger {
    @apply bg-destructive text-destructive-foreground px-4 py-2 rounded-md hover:bg-destructive/90;
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
