<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <Heading title="Training Calendar">Training Calendar</Heading>
        <button v-if="isTrainer" @click="showCreateModal = true" class="btn-primary">
          Create Training Session
        </button>
      </div>

      <TrainingCalendarView @event-click="handleEventClick" />
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
          <label class="block text-sm font-medium text-gray-700">Category</label>
          <select v-model="form.category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option :value="null">Select a category</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
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

        <div v-if="selectedSession.category">
          <h3 class="font-medium">Category</h3>
          <p class="flex items-center">
            <span class="inline-block w-3 h-3 rounded-full mr-2"
              :style="{ backgroundColor: selectedSession.category.color }"></span>
            {{ selectedSession.category.name }}
          </p>
        </div>

        <div>
          <h3 class="font-medium">Participants ({{ selectedSession.attendance_records?.length || 0 }}/{{
            selectedSession.max_participants }})</h3>
          <ul class="mt-2 space-y-2">
            <li v-for="record in selectedSession.attendance_records" :key="record.id"
              class="flex items-center justify-between">
              <span>{{ record.user.name }}</span>
              <span class="text-sm text-gray-600">{{ record.status }}</span>
            </li>
          </ul>
        </div>

        <div v-if="selectedSession.notes">
          <h3 class="font-medium">Notes</h3>
          <p class="text-gray-600">{{ selectedSession.notes }}</p>
        </div>

        <div v-if="canRegister" class="mt-4 flex gap-2">
          <button v-if="!isRegistered(selectedSession)" class="btn-primary"
            :disabled="isSessionFull(selectedSession) || isSessionInPast(selectedSession)" @click="registerForSession">
            {{ isSessionInPast(selectedSession) ? 'Registration Closed' : 'Register' }}
          </button>
          <button v-else class="btn-danger" :disabled="isSessionInPast(selectedSession)" @click="cancelRegistration">
            {{ isSessionInPast(selectedSession) ? 'Cannot Cancel Past Session' : 'Cancel Registration' }}
          </button>
        </div>

        <div v-if="isTrainer || isAdmin" class="mt-4 flex gap-2">
          <button class="btn-secondary" @click="editSession(selectedSession)">
            Edit Session
          </button>
          <button v-if="isAdmin" class="btn-danger" @click="deleteSession(selectedSession.id)">
            Delete Session
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import TrainingCalendarView from '@/components/training/TrainingCalendarView.vue'
import Modal from '@/components/ui/Modal.vue'
import { useAuth } from '@/composables/useAuth'
import axios from '@/lib/axios'
import { format } from 'date-fns'

interface TrainingCategory {
  id: number
  name: string
  description: string
  color: string
}

interface TrainingSession {
  id: number
  start_time: string
  end_time: string
  max_participants: number
  notes?: string
  category_id?: number
  category?: TrainingCategory
  attendance_records: any[]
}

const { user } = useAuth()
const isTrainer = computed(() => user.value?.role === 'trainer')
const isAdmin = computed(() => user.value?.role === 'admin')
const canRegister = computed(() => !isTrainer.value && !isAdmin.value)

const showCreateModal = ref(false)
const selectedSession = ref<TrainingSession | null>(null)
const editingSession = ref<TrainingSession | null>(null)
const categories = ref<TrainingCategory[]>([])

const form = ref({
  date: '',
  start_time: '',
  end_time: '',
  max_participants: 12,
  notes: '',
  category_id: null as number | null
})

const handleEventClick = (session: TrainingSession) => {
  selectedSession.value = session
}

const formatTime = (time: string) => {
  return format(new Date(time), 'HH:mm')
}

const formatDateTime = (datetime: string) => {
  return format(new Date(datetime), 'MMM d, yyyy HH:mm')
}

const isRegistered = (session: TrainingSession) => {
  return session.attendance_records.some((record: any) => record.user_id === user.value?.id)
}

const isSessionFull = (session: TrainingSession) => {
  return session.attendance_records.length >= session.max_participants
}

const isSessionInPast = (session: TrainingSession) => {
  return new Date(session.start_time) < new Date()
}

const registerForSession = async () => {
  if (!selectedSession.value) return
  try {
    await axios.post(`/api/training-sessions/${selectedSession.value.id}/register`)
    selectedSession.value = null
  } catch (error) {
    console.error('Error registering for session:', error)
  }
}

const cancelRegistration = async () => {
  if (!selectedSession.value) return
  try {
    await axios.delete(`/api/training-sessions/${selectedSession.value.id}/register`)
    selectedSession.value = null
  } catch (error) {
    console.error('Error canceling registration:', error)
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

    if (editingSession.value) {
      await axios.put(`/api/training-sessions/${editingSession.value.id}`, formattedData)
    } else {
      await axios.post('/api/training-sessions', formattedData)
    }

    showCreateModal.value = false
    resetForm()
  } catch (error) {
    console.error('Error saving training session:', error)
  }
}

const resetForm = () => {
  form.value = {
    date: '',
    start_time: '',
    end_time: '',
    max_participants: 12,
    notes: '',
    category_id: null
  }
  editingSession.value = null
}

const editSession = (session: TrainingSession) => {
  selectedSession.value = session
  showCreateModal.value = true
}

const deleteSession = async (id: number) => {
  try {
    await axios.delete(`/api/training-sessions/${id}`)
    selectedSession.value = null
  } catch (error) {
    console.error('Error deleting session:', error)
  }
}
</script>

<style scoped>
.btn-primary {
  @apply bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300;
}

.btn-danger {
  @apply bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700;
}
</style>