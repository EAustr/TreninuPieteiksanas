<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import axios from '../../lib/axios';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/composables/useAuth';
import { computed } from 'vue';

interface Props {
    userId: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'User Management',
        href: '/users',
    },
    {
        title: 'Edit User',
        href: `/users/${props.userId}/edit`,
    },
];

const form = ref({
    name: '',
    email: '',
    role: 'athlete',
});

const errors = ref({
    name: '',
    email: '',
    role: '',
    general: '',
});

const isSubmitting = ref(false);
const { user } = useAuth();
const isAdmin = computed(() => user.value?.role === 'admin');

const fetchUser = async () => {
    try {
        const response = await axios.get(`/api/users/${props.userId}`);
        const userData = response.data;
        form.value = {
            name: userData.name,
            email: userData.email,
            role: String(userData.role),
        };
    } catch (error) {
        console.error('Error fetching user:', error);
        router.visit('/users');
    }
};

const updateUser = async () => {
    try {
        isSubmitting.value = true;
        errors.value = {
            name: '',
            email: '',
            role: '',
            general: '',
        };

        await axios.put(`/api/users/${props.userId}`, form.value);
        router.visit('/users');
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

onMounted(() => {
    fetchUser();
});
</script>

<template>

    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-semibold mb-6">Edit User</h2>

                <form @submit.prevent="updateUser" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <Input id="name" v-model="form.name" type="text" :class="{ 'border-red-500': errors.name }"
                            required />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <Input id="email" v-model="form.email" type="email" :class="{ 'border-red-500': errors.email }"
                            required />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select id="role" v-model="form.role" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': errors.role }">
                            <option value="admin">Admin</option>
                            <option value="trainer">Trainer</option>
                            <option value="athlete">Athlete</option>
                        </select>
                        <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role }}</p>
                    </div>

                    <!-- General Error -->
                    <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-4">
                        <Button type="button" variant="outline" @click="router.visit('/users')"
                            :disabled="isSubmitting">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>