<template>
  <div class="analytics-dashboard">
    <div class="mb-6">
      <Heading>Training Analytics</Heading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Summary Cards -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Total Sessions</h3>
        <p class="text-3xl font-bold text-blue-600">{{ totalSessions }}</p>
        <p class="text-sm text-gray-500 mt-1">This month</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Average Attendance</h3>
        <p class="text-3xl font-bold text-green-600">{{ averageAttendance }}%</p>
        <p class="text-sm text-gray-500 mt-1">Of registered participants</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Active Athletes</h3>
        <p class="text-3xl font-bold text-purple-600">{{ activeAthletes }}</p>
        <p class="text-sm text-gray-500 mt-1">Attended at least one session</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Capacity Utilization</h3>
        <p class="text-3xl font-bold text-orange-600">{{ capacityUtilization }}%</p>
        <p class="text-sm text-gray-500 mt-1">Average session capacity</p>
      </div>
    </div>

    <!-- Attendance History -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Attendance History</h3>
      <div class="relative h-64">
        <canvas ref="attendanceChart"></canvas>
      </div>
    </div>

    <!-- Top Athletes -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Top Athletes by Attendance</h3>
      </div>
      <div class="divide-y divide-gray-200">
        <div
          v-for="athlete in topAthletes"
          :key="athlete.id"
          class="px-6 py-4 flex items-center justify-between"
        >
          <div>
            <p class="text-sm font-medium text-gray-900">{{ athlete.name }}</p>
            <p class="text-sm text-gray-500">{{ athlete.attendedSessions }} sessions attended</p>
          </div>
          <div class="flex items-center">
            <div class="w-32 bg-gray-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full"
                :style="{ width: `${athlete.attendanceRate}%` }"
              ></div>
            </div>
            <span class="ml-2 text-sm text-gray-600">{{ athlete.attendanceRate }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { format, subDays, eachDayOfInterval } from 'date-fns'
import Chart from 'chart.js/auto'
import axios from 'axios'
import Heading from '@/components/Heading.vue'

// Data
const trainingSessions = ref([])
const attendanceChart = ref(null)
const chartInstance = ref(null)

// Computed Statistics
const totalSessions = computed(() => {
  const now = new Date()
  const thisMonth = trainingSessions.value.filter(session => {
    const sessionDate = new Date(session.start_time)
    return sessionDate.getMonth() === now.getMonth() &&
           sessionDate.getFullYear() === now.getFullYear()
  })
  return thisMonth.length
})

const averageAttendance = computed(() => {
  if (trainingSessions.value.length === 0) return 0
  
  const totalAttendance = trainingSessions.value.reduce((sum, session) => {
    const attended = session.attendanceRecords.filter(record => record.status === 'attended').length
    const registered = session.attendanceRecords.length
    return sum + (registered ? (attended / registered) * 100 : 0)
  }, 0)
  
  return Math.round(totalAttendance / trainingSessions.value.length)
})

const activeAthletes = computed(() => {
  const uniqueAthletes = new Set()
  trainingSessions.value.forEach(session => {
    session.attendanceRecords
      .filter(record => record.status === 'attended')
      .forEach(record => uniqueAthletes.add(record.user_id))
  })
  return uniqueAthletes.size
})

const capacityUtilization = computed(() => {
  if (trainingSessions.value.length === 0) return 0
  
  const utilization = trainingSessions.value.reduce((sum, session) => {
    return sum + (session.attendanceRecords.length / session.max_participants) * 100
  }, 0)
  
  return Math.round(utilization / trainingSessions.value.length)
})

const topAthletes = computed(() => {
  const athleteStats = new Map()
  
  // Collect attendance data for each athlete
  trainingSessions.value.forEach(session => {
    session.attendanceRecords.forEach(record => {
      if (!athleteStats.has(record.user_id)) {
        athleteStats.set(record.user_id, {
          id: record.user_id,
          name: record.user.name,
          attended: 0,
          total: 0
        })
      }
      
      const stats = athleteStats.get(record.user_id)
      stats.total++
      if (record.status === 'attended') {
        stats.attended++
      }
    })
  })
  
  // Convert to array and calculate rates
  return Array.from(athleteStats.values())
    .map(stats => ({
      ...stats,
      attendedSessions: stats.attended,
      attendanceRate: Math.round((stats.attended / stats.total) * 100)
    }))
    .sort((a, b) => b.attendanceRate - a.attendanceRate)
    .slice(0, 5)
})

// Methods
const fetchData = async () => {
  try {
    const response = await axios.get('/api/training-sessions')
    trainingSessions.value = response.data
    renderAttendanceChart()
  } catch (error) {
    console.error('Error fetching training data:', error)
  }
}

const renderAttendanceChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
  }

  const ctx = attendanceChart.value.getContext('2d')
  const last30Days = eachDayOfInterval({
    start: subDays(new Date(), 29),
    end: new Date()
  })

  const data = last30Days.map(date => {
    const sessionsOnDay = trainingSessions.value.filter(session => 
      format(new Date(session.start_time), 'yyyy-MM-dd') === format(date, 'yyyy-MM-dd')
    )

    const attendanceRate = sessionsOnDay.reduce((sum, session) => {
      const attended = session.attendanceRecords.filter(r => r.status === 'attended').length
      const registered = session.attendanceRecords.length
      return sum + (registered ? (attended / registered) * 100 : 0)
    }, 0) / (sessionsOnDay.length || 1)

    return {
      date: format(date, 'MMM d'),
      rate: Math.round(attendanceRate)
    }
  })

  chartInstance.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(d => d.date),
      datasets: [{
        label: 'Attendance Rate',
        data: data.map(d => d.rate),
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.1)',
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: {
            callback: value => `${value}%`
          }
        }
      }
    }
  })
}

onMounted(() => {
  fetchData()
})
</script> 