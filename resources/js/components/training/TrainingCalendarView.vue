<template>
    <div class="training-calendar-view">
        <FullCalendar ref="calendar" :options="calendarOptions" class="h-[800px]" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import axios from '@/lib/axios';

interface TrainingSession {
    id: number;
    start_time: string;
    end_time: string;
    max_participants: number;
    notes?: string;
    category_id?: number;
    category?: {
        id: number;
        name: string;
        color: string;
    };
    attendance_records: any[];
}

const calendar = ref();
const events = ref<any[]>([]);

const emit = defineEmits(['event-click']);

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    events: events.value,
    firstDay: 1,
    eventClick: (info: any) => {
        // Pass the full session data from extendedProps
        const session = info.event.extendedProps.session;
        if (session) {
            emit('event-click', session);
        }
    },
    eventDidMount: (info: any) => {
        // Add tooltip with additional information
        const event = info.event;
        const tooltip = document.createElement('div');
        tooltip.className = 'fc-tooltip';
        tooltip.innerHTML = `
      <div class="p-2 bg-card rounded shadow-lg">
        <div class="font-semibold text-foreground">${event.title}</div>
        <div class="text-sm text-muted-foreground">
          ${event.extendedProps.participants}/${event.extendedProps.maxParticipants} participants
        </div>
        ${event.extendedProps.notes ? `<div class="text-sm mt-1 text-muted-foreground">${event.extendedProps.notes}</div>` : ''}
      </div>
    `;
        info.el.setAttribute('title', tooltip.innerHTML);
    },
    eventTimeFormat: {
        hour: '2-digit' as const,
        minute: '2-digit' as const,
        hour12: false,
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
    height: 'auto',
    // Add dark mode support
    themeSystem: 'standard',
    // Custom styling for dark mode
    dayHeaderClassNames: 'text-foreground',
    dayCellClassNames: 'text-foreground',
    moreLinkClassNames: 'text-primary hover:text-primary/80',
    buttonClassNames: 'bg-card text-foreground hover:bg-accent hover:text-accent-foreground',
    todayClassNames: 'bg-accent/50',
    eventClassNames: 'dark:text-white',
    // Custom CSS classes for dark mode
    classNames: {
        'fc-theme-standard': 'dark:bg-card dark:text-foreground',
        'fc-daygrid-day': 'dark:border-border',
        'fc-daygrid-day-number': 'dark:text-foreground',
        'fc-daygrid-day-header': 'dark:text-foreground dark:bg-card',
        'fc-col-header-cell': 'dark:bg-card dark:text-foreground dark:border-border',
        'fc-button': 'dark:bg-card dark:text-foreground dark:border-border dark:hover:bg-accent dark:hover:text-accent-foreground',
        'fc-button-active': 'dark:bg-accent dark:text-accent-foreground',
        'fc-button-primary': 'dark:bg-card dark:text-foreground dark:border-border',
        'fc-toolbar': 'dark:text-foreground',
        'fc-toolbar-title': 'dark:text-foreground',
        'fc-list': 'dark:bg-card dark:text-foreground',
        'fc-list-day-cushion': 'dark:bg-accent/50',
        'fc-list-event': 'dark:hover:bg-accent/50',
        'fc-list-event-time': 'dark:text-muted-foreground',
        'fc-list-event-title': 'dark:text-foreground',
        'fc-list-event-dot': 'dark:border-accent-foreground',
        'fc-timegrid-slot': 'dark:border-border',
        'fc-timegrid-slot-label': 'dark:text-muted-foreground',
        'fc-timegrid-axis': 'dark:border-border dark:text-muted-foreground',
        'fc-timegrid-axis-cushion': 'dark:text-muted-foreground',
        'fc-timegrid-slot-minor': 'dark:border-border/50',
        'fc-timegrid-slot-lane': 'dark:border-border',
        'fc-timegrid-now-indicator-line': 'dark:border-destructive',
        'fc-timegrid-now-indicator-arrow': 'dark:border-destructive',
        'fc-event': 'dark:text-white',
        'fc-event-title': 'dark:text-white',
        'fc-event-time': 'dark:text-white/90',
    },
};

const fetchTrainingSessions = async () => {
    try {
        const response = await axios.get('/api/training-sessions');
        const sessions = response.data.sessions;

        // Transform sessions into calendar events
        events.value = sessions.map((session: TrainingSession) => ({
            id: session.id,
            title: session.category?.name || 'Training Session',
            start: session.start_time,
            end: session.end_time,
            backgroundColor: session.category?.color || '#3B82F6',
            borderColor: session.category?.color || '#3B82F6',
            extendedProps: {
                participants: session.attendance_records?.length || 0,
                maxParticipants: session.max_participants,
                notes: session.notes,
                session: session, // Store the full session data
            },
        }));
    } catch (error) {
        console.error('Error fetching training sessions:', error);
    }
};

// Watch for changes in events and update the calendar
watch(
    events,
    (newEvents) => {
        if (calendar.value) {
            calendar.value.getApi().removeAllEvents();
            calendar.value.getApi().addEventSource(newEvents);
        }
    },
    { deep: true },
);

onMounted(() => {
    fetchTrainingSessions();
});
</script>

<style scoped>
/* Calendar Dark Mode Styles */
:deep(.fc) {
    --fc-border-color: hsl(var(--border));
    --fc-button-text-color: hsl(var(--foreground));
    --fc-button-bg-color: hsl(var(--card));
    --fc-button-border-color: hsl(var(--border));
    --fc-button-hover-bg-color: hsl(var(--accent));
    --fc-button-hover-border-color: hsl(var(--accent));
    --fc-button-active-bg-color: hsl(var(--accent));
    --fc-button-active-border-color: hsl(var(--accent));
    --fc-event-bg-color: hsl(var(--primary));
    --fc-event-border-color: hsl(var(--primary));
    --fc-event-text-color: hsl(var(--foreground));
    --fc-today-bg-color: hsl(var(--accent) / 0.2);
    --fc-neutral-bg-color: hsl(var(--card));
    --fc-list-event-hover-bg-color: hsl(var(--accent) / 0.1);
    --fc-highlight-color: hsl(var(--accent) / 0.2);
}

:deep(.fc .fc-event) {
    @apply border-0;
}

:deep(.fc .fc-event-title) {
    @apply font-medium text-foreground;
}

:deep(.fc .fc-event-time) {
    @apply text-muted-foreground;
}

:deep(.fc .fc-event-description) {
    @apply text-muted-foreground;
}

/* Calendar dark mode styles */
.dark .fc {
    --fc-border-color: hsl(var(--border));
    --fc-button-text-color: hsl(var(--foreground));
    --fc-button-bg-color: hsl(var(--card));
    --fc-button-border-color: hsl(var(--border));
    --fc-button-hover-bg-color: hsl(var(--accent));
    --fc-button-hover-border-color: hsl(var(--border));
    --fc-button-active-bg-color: hsl(var(--accent));
    --fc-button-active-border-color: hsl(var(--border));
    --fc-event-bg-color: hsl(var(--primary));
    --fc-event-border-color: hsl(var(--primary));
    --fc-event-text-color: hsl(var(--foreground));
    --fc-today-bg-color: hsl(var(--accent) / 0.5);
    --fc-neutral-bg-color: hsl(var(--card));
    --fc-list-event-hover-bg-color: hsl(var(--accent) / 0.5);
    --fc-page-bg-color: hsl(var(--card));
    --fc-neutral-text-color: hsl(var(--foreground));
    --fc-more-link-bg-color: hsl(var(--accent));
    --fc-more-link-text-color: hsl(var(--accent-foreground));
}

.dark .fc .fc-daygrid-day.fc-day-today {
    background-color: hsl(var(--accent) / 0.5);
}

.dark .fc .fc-col-header-cell {
    background-color: hsl(var(--card));
    color: hsl(var(--foreground));
    border-color: hsl(var(--border));
}

.dark .fc .fc-daygrid-day-header {
    background-color: hsl(var(--card));
    color: hsl(var(--foreground));
    border-color: hsl(var(--border));
}

.dark .fc .fc-button {
    background-color: hsl(var(--card));
    color: hsl(var(--foreground));
    border-color: hsl(var(--border));
}

.dark .fc .fc-button:hover {
    background-color: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
}

.dark .fc .fc-button-active {
    background-color: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
}

.dark .fc .fc-toolbar-title {
    color: hsl(var(--foreground));
}

.dark .fc .fc-list {
    background-color: hsl(var(--card));
    color: hsl(var(--foreground));
}

.dark .fc .fc-list-day-cushion {
    background-color: hsl(var(--accent) / 0.5);
}

.dark .fc .fc-list-event:hover td {
    background-color: hsl(var(--accent) / 0.5);
}

.dark .fc .fc-list-event-time {
    color: hsl(var(--muted-foreground));
}

.dark .fc .fc-list-event-title {
    color: hsl(var(--foreground));
}

.dark .fc .fc-list-event-dot {
    border-color: hsl(var(--accent-foreground));
}

.dark .fc .fc-timegrid-slot {
    border-color: hsl(var(--border));
}

.dark .fc .fc-timegrid-slot-label {
    color: hsl(var(--muted-foreground));
}

.dark .fc .fc-timegrid-axis {
    border-color: hsl(var(--border));
    color: hsl(var(--muted-foreground));
}

.dark .fc .fc-timegrid-slot-minor {
    border-color: hsl(var(--border) / 0.5);
}

.dark .fc .fc-timegrid-slot-lane {
    border-color: hsl(var(--border));
}

.dark .fc .fc-timegrid-now-indicator-line {
    border-color: hsl(var(--destructive));
}

.dark .fc .fc-timegrid-now-indicator-arrow {
    border-color: hsl(var(--destructive));
}

/* Ensure day names are visible in dark mode */
.dark .fc .fc-col-header-cell-cushion {
    color: hsl(var(--foreground));
}

.dark .fc .fc-daygrid-day-number {
    color: hsl(var(--foreground));
}

/* Ensure event text is visible in dark mode */
.dark .fc-event {
    color: hsl(var(--foreground));
}

.dark .fc-event-title {
    color: hsl(var(--foreground));
}

.dark .fc-event-time {
    color: hsl(var(--foreground)) !important;
}

.dark .fc-event-description {
    color: hsl(var(--foreground));
}

/* Ensure today's date is visible in dark mode */
.dark .fc .fc-day-today .fc-daygrid-day-number {
    color: hsl(var(--foreground));
}
</style>
