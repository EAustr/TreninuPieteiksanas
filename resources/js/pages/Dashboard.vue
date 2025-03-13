<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminDashboard from '@/pages/admin/AdminDashboard.vue';
import UserDashboard from '@/pages/user/UserDashboard.vue';

</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Minimal wrapping container -->
        <div class="p-4">
            <!-- Conditionally render different dashboards based on user role -->
            <template v-if="$page.props.auth.user && $page.props.auth.user.role === 'admin'">
                <AdminDashboard />
            </template>
            <template v-else>
                <UserDashboard />
            </template>
        </div>
    </AppLayout>
</template>