<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import axios from '../../lib/axios';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, Edit, Trash2, Plus } from 'lucide-vue-next';
import { useAuth } from '@/composables/useAuth';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'User Management',
        href: '/users',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const users = ref<User[]>([]);
const searchQuery = ref('');
const sortField = ref('name');
const sortDirection = ref('asc');
const isSubmitting = ref(false);

const { user } = useAuth();
const isAdmin = computed(() => user.value?.role === 'admin');

const roleOrder = ['admin', 'trainer', 'athlete'];

const filteredUsers = computed(() => {
    let result = [...users.value];

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            user.role.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    result.sort((a, b) => {
        if (sortField.value === 'role') {
            return sortDirection.value === 'asc'
                ? roleOrder.indexOf(a.role) - roleOrder.indexOf(b.role)
                : roleOrder.indexOf(b.role) - roleOrder.indexOf(a.role);
        }
        const aValue = a[sortField.value as keyof User];
        const bValue = b[sortField.value as keyof User];
        if (typeof aValue === 'string' && typeof bValue === 'string') {
            return sortDirection.value === 'asc'
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        }
        return 0;
    });

    return result;
});

const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/users');
        users.value = response.data;
    } catch (error) {
        console.error('Error fetching users:', error);
    }
};

const deleteUser = async (userId: number) => {
    if (!confirm('Are you sure you want to delete this user?')) return;

    try {
        isSubmitting.value = true;
        await axios.delete(`/api/users/${userId}`);
        await fetchUsers();
    } catch (error) {
        console.error('Error deleting user:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const editUser = (userId: number) => {
    router.visit(`/users/${userId}/edit`);
};

const toggleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>

    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold">User Management</h1>
                <Button @click="router.visit('/register-user')" class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Add User
                </Button>
            </div>

            <!-- Search and Filter Section -->
            <div class="mb-6 flex items-center gap-4">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input v-model="searchQuery" type="text" placeholder="Search users..." class="pl-10" />
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-lg border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="cursor-pointer" @click="toggleSort('name')">
                                Name {{ sortField === 'name' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="toggleSort('email')">
                                Email {{ sortField === 'email' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="toggleSort('role')">
                                Role {{ sortField === 'role' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="toggleSort('created_at')">
                                Created At {{ sortField === 'created_at' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in filteredUsers" :key="user.id">
                            <TableCell>{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>{{ user.role }}</TableCell>
                            <TableCell>{{ new Date(user.created_at).toLocaleDateString() }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Button variant="ghost" size="icon" @click="editUser(user.id)"
                                        :disabled="isSubmitting">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteUser(user.id)"
                                        :disabled="isSubmitting">
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>