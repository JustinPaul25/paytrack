import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export interface User {
    id: number;
    name: string;
    email: string;
    updated_at: string;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: any[];
}

export const useUsersStore = defineStore('users', () => {
    const users = ref<User[]>([]);
    const pagination = ref<Pagination>({
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0,
        links: [],
    });
    const search = ref('');
    const loading = ref(false);

    async function fetchUsers(params: { search?: string; page?: number } = {}) {
        loading.value = true;
        try {
            const response = await axios.get('/users', {
                params: {
                    search: params.search ?? search.value,
                    page: params.page ?? pagination.value.current_page,
                    ajax: 1,
                },
            });
            if (response.data.users && response.data.users.data) {
                users.value = response.data.users.data;
                pagination.value = {
                    ...pagination.value,
                    ...response.data.users,
                    links: response.data.users.links,
                };
            } else {
                users.value = [];
            }
        } finally {
            loading.value = false;
        }
    }

    function setSearch(val: string) {
        search.value = val;
    }

    function setPage(page: number) {
        pagination.value.current_page = page;
    }

    async function deleteUser(id: number) {
        loading.value = true;
        try {
            await axios.delete(`/users/${id}`);
            await fetchUsers({ search: search.value, page: pagination.value.current_page });
        } finally {
            loading.value = false;
        }
    }

    return {
        users,
        pagination,
        search,
        loading,
        fetchUsers,
        setSearch,
        setPage,
        deleteUser,
    };
}); 