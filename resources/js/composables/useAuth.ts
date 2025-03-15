import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';


export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user);
    const isAuthenticated = computed(() => !!user.value);
    const isTrainer = computed(() => user.value?.role === 'trainer');
    const isAthlete = computed(() => user.value?.role === 'athlete');

    return {
        user,
        isAuthenticated,
        isTrainer,
        isAthlete
    };
} 