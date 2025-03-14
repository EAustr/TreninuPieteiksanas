<template>
  <div class="attendance-tracker">
    <div class="flex justify-between items-center mb-6">
      <Heading>Attendance Tracker</Heading>
      <div class="flex gap-2">
        <input
          type="date"
          v-model="selectedDate"
          class="rounded-md border-gray-300 shadow-sm"
        >
      </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Time
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Trainer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Registered
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Attended
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="session in filteredSessions" :key="session.id">
              <td class="px-6 py-4 whitespace-nowrap">
                {{ formatTimeRange(session.start_time, session.end_time) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ session.trainer.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ session.attendanceRecords.length }}/{{ session.max_participants }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ getAttendedCount(session) }}/{{ session.attendanceRecords.length }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  v-if="isTrainer"
                  @click="openAttendanceModal(session)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Mark Attendance
                </button>
              </td>
            </tr>
            <tr v-if="filteredSessions.length === 0">
              <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                No training sessions found for this date
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Attendance Modal -->
    <Modal v-if="selectedTrainingSession" @close="selectedTrainingSession = null">
      <template #title>Mark Attendance</template>
      
      <div class="space-y-4">
        <div class="text-sm text-gray-600 mb-4">
          {{ formatDateTime(selectedTrainingSession.start_time) }}
        </div>

        <div class="space-y-2">
          <div v-for="record in selectedTrainingSession.attendanceRecords" :key="record.id"
            class="flex items-center justify-between p-2 bg-gray-50 rounded-md"
          >
            <span>{{ record.user.name }}</span>
            <div class="flex gap-2">
              <button
                @click="updateAttendance(record.id, 'attended')"
                class="px-3 py-1 rounded-md text-sm"
                :class="record.status === 'attended' ? 'bg-green-600 text-white' : 'bg-gray-200'"
              >
                Present
              </button>
              <button
                @click="updateAttendance(record.id, 'absent')"
                class="px-3 py-1 rounded-md text-sm"
                :class="record.status === 'absent' ? 'bg-red-600 text-white' : 'bg-gray-200'"
              >
                Absent
              </button>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button
          type="button"
          class="btn-primary"
          @click="selectedTrainingSession = null"
        >
          Done
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { format } from 'date-fns'
import axios from 'axios'
import Modal from '@/components/ui/Modal.vue'
import Heading from '@/components/Heading.vue'

const { user } = useAuth()
const isTrainer = computed(() => user.value?.role === 'trainer')

const selectedDate = ref(format(new Date(), 'yyyy-MM-dd'))
const trainingSessions = ref([])
const selectedTrainingSession = ref(null)

const filteredSessions = computed(() => {
  return trainingSessions.value.filter(session => {
    return format(new Date(session.start_time), 'yyyy-MM-dd') === selectedDate.value
  })
})

const fetchTrainingSessions = async () => {
  try {
    const response = await axios.get('/api/training-sessions')
    trainingSessions.value = response.data
  } catch (error) {
    console.error('Error fetching training sessions:', error)
  }
}

const updateAttendance = async (recordId, status) => {
  try {
    await axios.put(`/api/attendance-records/${recordId}`, { status })
    await fetchTrainingSessions()
    // Refresh the selected session data
    if (selectedTrainingSession.value) {
      const updated = trainingSessions.value.find(s => s.id === selectedTrainingSession.value.id)
      selectedTrainingSession.value = updated
    }
  } catch (error) {
    console.error('Error updating attendance:', error)
  }
}

const openAttendanceModal = (session) => {
  selectedTrainingSession.value = session
}

const getAttendedCount = (session) => {
  return session.attendanceRecords.filter(record => record.status === 'attended').length
}

const formatTimeRange = (start, end) => {
  return `${format(new Date(start), 'HH:mm')} - ${format(new Date(end), 'HH:mm')}`
}

const formatDateTime = (datetime) => {
  return format(new Date(datetime), 'MMM d, yyyy HH:mm')
}

onMounted(() => {
  fetchTrainingSessions()
})
</script>

<style scoped>
.btn-primary {
  @apply bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700;
}
</style> 