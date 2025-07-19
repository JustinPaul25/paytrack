import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions_count: number;
    updated_at: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

export const useRolesStore = defineStore('roles', () => {
    const roles = ref<Role[]>([]);
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

    async function fetchRoles(params: { search?: string; page?: number } = {}) {
        loading.value = true;
        try {
            const response = await axios.get('/roles', {
                params: {
                    search: params.search ?? search.value,
                    page: params.page ?? pagination.value.current_page,
                    ajax: 1,
                },
            });
            if (response.data.roles && response.data.roles.data) {
                roles.value = response.data.roles.data;
                pagination.value = {
                    ...pagination.value,
                    ...response.data.roles,
                    links: response.data.roles.links,
                };
            } else {
                console.warn('Unexpected roles response:', response.data);
                roles.value = [];
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

    async function deleteRole(id: number) {
        loading.value = true;
        try {
            await axios.delete(`/roles/${id}`);
            await fetchRoles({ search: search.value, page: pagination.value.current_page });
        } finally {
            loading.value = false;
        }
    }

    return {
        roles,
        pagination,
        search,
        loading,
        fetchRoles,
        setSearch,
        setPage,
        deleteRole,
    };
}); 