<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import axios from '../../lib/axios';

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
    <AppLayout>

        <Head title="Register User" />
        <div class="max-w-lg mx-auto mt-12 bg-card rounded-lg shadow p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-foreground">Register New User</h2>
            <form @submit.prevent="registerUser" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="name" class="text-foreground">Name</Label>
                        <Input id="name" type="text" required autofocus autocomplete="name" v-model="form.name"
                            placeholder="Full name" class="text-foreground" />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email" class="text-foreground">Email address</Label>
                        <Input id="email" type="email" required autocomplete="email" v-model="form.email"
                            placeholder="email@example.com" class="text-foreground" />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password" class="text-foreground">Password</Label>
                        <Input id="password" type="password" required autocomplete="new-password"
                            v-model="form.password" placeholder="Password" class="text-foreground" />
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-foreground">Confirm password</Label>
                        <Input id="password_confirmation" type="password" required autocomplete="new-password"
                            v-model="form.password_confirmation" placeholder="Confirm password"
                            class="text-foreground" />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="role" class="text-foreground">Role</Label>
                        <select id="role" v-model="form.role"
                            class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                            <option value="athlete">Athlete</option>
                            <option value="trainer">Trainer</option>
                        </select>
                        <InputError :message="errors.role" />
                    </div>
                    <p v-if="errors.general" class="text-sm text-destructive">{{ errors.general }}</p>
                    <Button type="submit" class="mt-2 w-full" :disabled="isSubmitting">
                        <LoaderCircle v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                        Register User
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>