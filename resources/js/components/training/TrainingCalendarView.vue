<template>
    <div class="training-calendar-view">
        <FullCalendar ref="calendar" :options="calendarOptions" class="h-[800px]" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import axios from '@/lib/axios'
import { useAuth } from '@/composables/useAuth'

interface TrainingSession {
    id: number
    start_time: string
    end_time: string
    max_participants: number
    notes?: string
    category_id?: number
    category?: {
        id: number
        name: string
        color: string
    }
    attendance_records: any[]
}

const { user } = useAuth()
const calendar = ref()
const events = ref<any[]>([])

const emit = defineEmits(['event-click'])

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: events.value,
    firstDay: 1,
    eventClick: (info: any) => {
        // Pass the full session data from extendedProps
        const session = info.event.extendedProps.session
        if (session) {
            emit('event-click', session)
        }
    },
    eventDidMount: (info: any) => {
        // Add tooltip with additional information
        const event = info.event
        const tooltip = document.createElement('div')
        tooltip.className = 'fc-tooltip'
        tooltip.innerHTML = `
      <div class="p-2 bg-white rounded shadow-lg">
        <div class="font-semibold">${event.title}</div>
        <div class="text-sm text-gray-600">
          ${event.extendedProps.participants}/${event.extendedProps.maxParticipants} participants
        </div>
        ${event.extendedProps.notes ? `<div class="text-sm mt-1">${event.extendedProps.notes}</div>` : ''}
      </div>
    `
        info.el.setAttribute('title', tooltip.innerHTML)
    },
    eventTimeFormat: {
        hour: '2-digit' as const,
        minute: '2-digit' as const,
        hour12: false
    },
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    allDaySlot: false,
    nowIndicator: true,
    editable: false,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    height: 'auto'
}

const fetchTrainingSessions = async () => {
    try {
        const response = await axios.get('/api/training-sessions')
        const sessions = response.data.sessions

        // Transform sessions into calendar events
        const newEvents = sessions.map((session: TrainingSession) => ({
            id: session.id,
            title: session.category?.name || 'Training Session',
            start: session.start_time,
            end: session.end_time,
            backgroundColor: session.category?.color || '#3B82F6',
            borderColor: session.category?.color || '#3B82F6',
            textColor: '#ffffff',
            extendedProps: {
                participants: session.attendance_records?.length || 0,
                maxParticipants: session.max_participants,
                notes: session.notes,
                session: session // Store the full session data
            }
        }))

        events.value = newEvents
    } catch (error) {
        console.error('Error fetching training sessions:', error)
    }
}

// Watch for changes in events and update the calendar
watch(events, (newEvents) => {
    if (calendar.value) {
        calendar.value.getApi().removeAllEvents()
        calendar.value.getApi().addEventSource(newEvents)
    }
}, { deep: true })

onMounted(() => {
    fetchTrainingSessions()
})
</script>

<style>
.training-calendar-view {
    @apply bg-white rounded-lg shadow p-4;
}

.fc {
    @apply h-full;
}

.fc-toolbar-title {
    @apply text-lg font-semibold;
}

.fc-button {
    @apply bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700;
}

.fc-button-active {
    @apply bg-blue-800;
}

.fc-event {
    @apply cursor-pointer;
}

.fc-event:hover {
    @apply opacity-90;
}

.fc-day-today {
    @apply bg-blue-50;
}

.fc-timegrid-slot {
    @apply h-12;
}

.fc-timegrid-slot-label {
    @apply text-sm;
}

.fc-timegrid-slot-lane {
    @apply border-gray-200;
}

.fc-timegrid-now-indicator-line {
    @apply border-red-500;
}

.fc-timegrid-now-indicator-arrow {
    @apply border-red-500;
}

/* Add these new styles for text wrapping */
.fc-event-title {
    @apply whitespace-normal break-words;
    overflow-wrap: break-word;
    word-wrap: break-word;
    hyphens: auto;
}

.fc-event-main {
    @apply p-1;
}

.fc-daygrid-event {
    @apply whitespace-normal;
    min-height: 1.5em;
}

.fc-timegrid-event {
    @apply whitespace-normal;
    min-height: 1.5em;
}

.fc-event-time {
    @apply whitespace-nowrap;
}

/* Ensure the event container can grow */
.fc-daygrid-event-harness {
    @apply min-h-[1.5em];
}

.fc-timegrid-event-harness {
    @apply min-h-[1.5em];
}
</style>