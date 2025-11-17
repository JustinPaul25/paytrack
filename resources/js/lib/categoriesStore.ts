import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export interface Category {
    id: number;
    name: string;
    description?: string;
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

export const useCategoriesStore = defineStore('categories', () => {
    const categories = ref<Category[]>([]);
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

    async function fetchCategories(params: { search?: string; page?: number } = {}) {
        loading.value = true;
        try {
            const response = await axios.get('/categories', {
                params: {
                    search: params.search ?? search.value,
                    page: params.page ?? pagination.value.current_page,
                    ajax: 1,
                },
            });
            if (response.data.categories && response.data.categories.data) {
                categories.value = response.data.categories.data;
                pagination.value = {
                    ...pagination.value,
                    ...response.data.categories,
                    links: response.data.categories.links,
                };
            } else if (Array.isArray(response.data.categories)) {
                categories.value = response.data.categories;
            } else {
                categories.value = [];
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

    async function deleteCategory(id: number) {
        loading.value = true;
        try {
            await axios.delete(`/categories/${id}`);
            await fetchCategories({ search: search.value, page: pagination.value.current_page });
        } finally {
            loading.value = false;
        }
    }

    return {
        categories,
        pagination,
        search,
        loading,
        fetchCategories,
        setSearch,
        setPage,
        deleteCategory,
    };
}); 