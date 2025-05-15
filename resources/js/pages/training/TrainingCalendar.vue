<template>
  <AppLayout>
    <div class="training-calendar">
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center space-x-4">
          <Heading title="Training Calendar">Training Calendar</Heading>
        </div>
        <button v-if="isTrainer" @click="showCreateModal = true" class="btn-primary">
          Create Training Session
        </button>
      </div>

      <div class="calendar-grid bg-white rounded-lg shadow">
        <!-- Calendar Header -->
        <div class="grid grid-cols-7 gap-px bg-gray-200 text-center">
          <div v-for="day in weekDays" :key="day" class="bg-gray-50 py-2 font-semibold">
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-px">
          <div v-for="date in calendarDays" :key="date.toISOString()" class="min-h-[120px] p-2 bg-white"
            :class="{ 'bg-gray-50': !isCurrentMonth(date) }">
            <div class="flex justify-between items-center mb-2">
              <span :class="{ 'text-gray-400': !isCurrentMonth(date) }">
                {{ format(date, 'd') }}
              </span>
            </div>

            <!-- Training Sessions for the day -->
            <div class="space-y-1">
              <div v-for="session in getSessionsForDate(date)" :key="session.id"
                class="p-1 rounded text-sm cursor-pointer" :class="getSessionStatusClass(session)"
                @click="selectedSession = session">
                {{ formatTime(session.start_time) }}
                <span class="text-xs">
                  ({{ session.attendance_records?.length || 0 }}/{{ session.max_participants }})
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Training Modal -->
      <Modal v-model="showCreateModal" @close="showCreateModal = false">
        <template #title>
          {{ editingSession ? 'Edit Training Session' : 'Create Training Session' }}
        </template>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Date</label>
            <input type="date" v-model="form.date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Start Time</label>
            <input type="time" v-model="form.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
              step="900">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">End Time</label>
            <input type="time" v-model="form.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
              step="900">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Max Participants</label>
            <input type="number" v-model="form.max_participants"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea v-model="form.notes" rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
          </div>
        </div>

        <template #footer>
          <button type="button" class="btn-secondary mr-2" @click="showCreateModal = false">
            Cancel
          </button>
          <button type="button" class="btn-primary" @click="saveTrainingSession">
            {{ editingSession ? 'Update' : 'Create' }}
          </button>
        </template>
      </Modal>

      <!-- Session Details Modal -->
      <Modal v-model="selectedSession" @close="selectedSession = null">
        <template #title>Training Session Details</template>

        <div v-if="selectedSession" class="space-y-4">
          <div>
            <h3 class="font-medium">Date & Time</h3>
            <p>{{ formatDateTime(selectedSession.start_time) }} - {{ formatTime(selectedSession.end_time) }}</p>
          </div>

          <div>
            <h3 class="font-medium">Participants ({{ selectedSession.attendance_records?.length || 0 }}/{{
              selectedSession.max_participants }})</h3>
            <ul class="mt-2 space-y-2">
              <li v-for="record in selectedSession.attendance_records" :key="record.id">
                {{ record.user.name }} - {{ record.status }}
              </li>
            </ul>
          </div>

          <div v-if="selectedSession.notes">
            <h3 class="font-medium">Notes</h3>
            <p class="text-gray-600">{{ selectedSession.notes }}</p>
          </div>

          <div v-if="canRegister" class="mt-4">
            <button v-if="!isRegistered(selectedSession)" class="btn-primary" :disabled="isSessionFull(selectedSession)"
              @click="registerForSession">
              Register
            </button>
            <button v-else class="btn-danger" @click="cancelRegistration">
              Cancel Registration
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import axios from '../../lib/axios'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth } from 'date-fns'
import Modal from '@/components/ui/Modal.vue'
import Heading from '@/components/Heading.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

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

const { user } = useAuth()
const isTrainer = computed(() => user.value?.role === 'trainer')
const isAdmin = computed(() => user.value?.role === 'admin')
const canRegister = computed(() => !isTrainer.value && !isAdmin.value)

const showCreateModal = ref(false)
const selectedSession = ref<TrainingSession | null>(null)
const editingSession = ref<TrainingSession | null>(null)
const trainingSessions = ref<TrainingSession[]>([])
const currentDate = ref(new Date())

const form = ref({
  date: '',
  start_time: '',
  end_time: '',
  max_participants: 12,
  notes: ''
})

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

// Calendar computations
const calendarDays = computed(() => {
  const start = startOfMonth(currentDate.value)
  const end = endOfMonth(currentDate.value)
  return eachDayOfInterval({ start, end })
})

// Methods
const isCurrentMonth = (date: Date) => {
  return isSameMonth(date, currentDate.value)
}

const fetchTrainingSessions = async () => {
  try {
    const response = await axios.get('/api/training-sessions')
    trainingSessions.value = response.data
  } catch (error) {
    console.error('Error fetching training sessions:', error)
  }
}

const saveTrainingSession = async () => {
  try {
    const formattedData = {
      ...form.value,
      start_time: `${form.value.date} ${form.value.start_time}`,
      end_time: `${form.value.date} ${form.value.end_time}`,
      date: undefined
    }

    let response;
    if (editingSession.value) {
      response = await axios.put(`/api/training-sessions/${editingSession.value.id}`, formattedData)
    } else {
      response = await axios.post('/api/training-sessions', formattedData)
    }

    await fetchTrainingSessions()
    showCreateModal.value = false
    resetForm()
  } catch (error) {
    console.error('Error saving training session:', error)
    if (error && typeof error === 'object' && 'response' in error) {
      console.error('Validation errors:', (error as any).response?.data?.errors)
    }
  }
}

const registerForSession = async () => {
  if (!selectedSession.value) return;
  try {
    await axios.post(`/api/training-sessions/${selectedSession.value.id}/register`);
    await fetchTrainingSessions();
    selectedSession.value = null;
  } catch (error) {
    console.error('Error registering for session:', error);
  }
};

const cancelRegistration = async () => {
  if (!selectedSession.value) return;
  try {
    await axios.delete(`/api/training-sessions/${selectedSession.value.id}/register`);
    await fetchTrainingSessions();
    selectedSession.value = null;
  } catch (error) {
    console.error('Error canceling registration:', error);
  }
};

const getSessionsForDate = (date: Date) => {
  return trainingSessions.value.filter(session => {
    const sessionDate = new Date(session.start_time)
    return format(sessionDate, 'yyyy-MM-dd') === format(date, 'yyyy-MM-dd')
  })
}

const getSessionStatusClass = (session: any) => {
  if (!session.attendance_records) return 'bg-green-100 text-green-800'
  if (session.attendance_records.length >= session.max_participants) {
    return 'bg-red-100 text-red-800'
  }
  return 'bg-green-100 text-green-800'
}

const isRegistered = (session: TrainingSession) => {
  if (!session.attendance_records) return false;
  return session.attendance_records.some((record: AttendanceRecord) => record.user_id === user.value?.id);
}

const isSessionFull = (session: any) => {
  if (!session.attendance_records) return false
  return session.attendance_records.length >= session.max_participants
}

const formatTime = (time: string) => {
  return format(new Date(time), 'HH:mm')
}

const formatDateTime = (datetime: string) => {
  return format(new Date(datetime), 'MMM d, yyyy HH:mm')
}

const resetForm = () => {
  form.value = {
    date: '',
    start_time: '',
    end_time: '',
    max_participants: 12,
    notes: ''
  }
}

onMounted(() => {
  fetchTrainingSessions()
})
</script>

<style scoped>
.btn-primary {
  background-color: #2563eb;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-primary:disabled {
  opacity: 0.5;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}

.btn-danger:hover {
  background-color: #b91c1c;
}

/* Force 24-hour format for time inputs */
input[type="time"]::-webkit-calendar-picker-indicator {
  background: none;
  display: none;
}

input[type="time"] {
  appearance: none;
}

input[type="time"]::-webkit-datetime-edit-ampm-field {
  display: none;
}

input[type="time"]::-moz-time-text {
  -moz-appearance: textfield;
}

/* Hide AM/PM from time input */
input[type="time"]::-webkit-datetime-edit-hour-field,
input[type="time"]::-webkit-datetime-edit-minute-field {
  padding: 0;
}
</style>