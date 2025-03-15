<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AttendanceTracker from './AttendanceTracker.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Attendance Tracker',
        href: '/training/attendance',
    },
];
</script>

<template>

    <Head title="Attendance Tracker" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AttendanceTracker />
    </AppLayout>
</template>