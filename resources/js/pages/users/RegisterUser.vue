<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import axios from '../../lib/axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Register User',
        href: '/register-user',
    },
];

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'athlete'
});

const errors = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    general: ''
});

const isSubmitting = ref(false);

const registerUser = async () => {
    try {
        isSubmitting.value = true;
        errors.value = {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            role: '',
            general: ''
        };

        const response = await axios.post('/api/users', form.value);

        if (response.status === 201) {
            router.visit('/dashboard', {
                only: ['flash'],
                onSuccess: () => {
                    // Show success message
                }
            });
        }
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else if (error.response?.data?.message) {
            errors.value.general = error.response.data.message;
        } else {
            errors.value.general = 'An unexpected error occurred';
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>

    <Head title="Register User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-semibold mb-6">Register New User</h2>

                <form @submit.prevent="registerUser" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input id="name" v-model="form.name" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.name }" required>
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" v-model="form.email" type="email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.email }" required>
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" v-model="form.password" type="password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.password }" required>
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm
                            Password</label>
                        <input id="password_confirmation" v-model="form.password_confirmation" type="password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.password_confirmation }" required>
                        <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">{{
                            errors.password_confirmation }}</p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select id="role" v-model="form.role"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.role }" required>
                            <option value="athlete">Athlete</option>
                            <option value="trainer">Trainer</option>
                        </select>
                        <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role }}</p>
                    </div>

                    <!-- General Error -->
                    <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50"
                            :disabled="isSubmitting">
                            {{ isSubmitting ? 'Registering...' : 'Register User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>