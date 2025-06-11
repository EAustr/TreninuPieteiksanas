<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import axios from '../../lib/axios';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, Edit, Trash2, Plus, Calendar, CalendarRange } from 'lucide-vue-next';
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
const startDate = ref('');
const endDate = ref('');
const singleDate = ref('');
const isDateRange = ref(false);

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

    // Apply date filtering
    if (isDateRange.value) {
        // Date range filtering
        if (startDate.value) {
            result = result.filter(user => new Date(user.created_at) >= new Date(startDate.value));
        }
        if (endDate.value) {
            result = result.filter(user => new Date(user.created_at) <= new Date(endDate.value));
        }
    } else {
        // Single date filtering
        if (singleDate.value) {
            const selectedDate = new Date(singleDate.value);
            result = result.filter(user => {
                const userDate = new Date(user.created_at);
                return userDate.toDateString() === selectedDate.toDateString();
            });
        }
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
                <h1 class="text-2xl font-bold text-foreground">User Management</h1>
                <Button @click="router.visit('/register-user')" class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Add User
                </Button>
            </div>

            <!-- Search and Filter Section -->
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <div class="relative flex-1 min-w-[200px]">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="searchQuery" type="text" placeholder="Search users..." class="pl-10" />
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="isDateRange = !isDateRange"
                        class="flex items-center gap-2">
                        <CalendarRange v-if="isDateRange" class="h-4 w-4" />
                        <Calendar v-else class="h-4 w-4" />
                        {{ isDateRange ? 'Date Range' : 'Single Date' }}
                    </Button>

                    <template v-if="isDateRange">
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="startDate" type="date" class="pl-10" placeholder="Start date" />
                        </div>
                        <span class="text-muted-foreground">to</span>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="endDate" type="date" class="pl-10" placeholder="End date" />
                        </div>
                    </template>
                    <template v-else>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="singleDate" type="date" class="pl-10" placeholder="Select date" />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-lg border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="cursor-pointer text-foreground" @click="toggleSort('name')">
                                Name {{ sortField === 'name' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="cursor-pointer text-foreground" @click="toggleSort('email')">
                                Email {{ sortField === 'email' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="cursor-pointer text-foreground" @click="toggleSort('role')">
                                Role {{ sortField === 'role' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="cursor-pointer text-foreground" @click="toggleSort('created_at')">
                                Created At {{ sortField === 'created_at' ? (sortDirection === 'asc' ? '↑' : '↓') : '' }}
                            </TableHead>
                            <TableHead class="text-foreground">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in filteredUsers" :key="user.id" class="hover:bg-muted/50">
                            <TableCell class="text-foreground">{{ user.name }}</TableCell>
                            <TableCell class="text-foreground">{{ user.email }}</TableCell>
                            <TableCell class="text-foreground">{{ user.role }}</TableCell>
                            <TableCell class="text-foreground">{{ new Date(user.created_at).toLocaleDateString() }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Button variant="ghost" size="icon" @click="editUser(user.id)"
                                        :disabled="isSubmitting" class="hover:bg-muted">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteUser(user.id)"
                                        :disabled="isSubmitting" class="hover:bg-muted">
                                        <Trash2 class="h-4 w-4 text-destructive" />
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