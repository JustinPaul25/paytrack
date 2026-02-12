<script setup lang="ts">
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { ChevronDown, Search, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface SelectOption {
    value: string | number | null;
    label: string;
    description?: string; // For additional info like price and stock
}

interface Props {
    modelValue?: string | number | null;
    options: SelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    class?: string;
    disabled?: boolean;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
    searchPlaceholder: 'Search...',
    disabled: false,
    required: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement>();
const dropdownRef = ref<HTMLDivElement>();

const selectedOption = computed(() => {
    return props.options.find(option => option.value === props.modelValue);
});

const displayValue = computed(() => {
    if (selectedOption.value) {
        // Only show the label (product name), not the description
        return selectedOption.value.label;
    }
    return props.placeholder;
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return props.options;
    }
    
    const query = searchQuery.value.toLowerCase().trim();
    return props.options.filter(option => {
        // Skip the "Select invoice" option when searching
        if (option.value === null && option.label.toLowerCase().includes('select')) {
            return false;
        }
        
        // Search in the label
        return option.label.toLowerCase().includes(query);
    });
});

function toggleDropdown() {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
}

function selectOption(option: SelectOption, event?: Event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    emit('update:modelValue', option.value);
    isOpen.value = false;
    searchQuery.value = '';
}

function clearSearch(event?: Event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    searchQuery.value = '';
    nextTick(() => {
        if (searchInputRef.value && typeof searchInputRef.value.focus === 'function') {
            searchInputRef.value.focus();
        }
    });
}

function handleSearchInput(event: Event) {
    const target = event.target as HTMLInputElement;
    searchQuery.value = target.value;
}

// Close dropdown when clicking outside
function handleClickOutside(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.closest('[data-search-select]')) {
        isOpen.value = false;
        searchQuery.value = '';
    }
}

// Add/remove click outside listener
watch(isOpen, (open) => {
    if (open) {
        nextTick(() => {
            document.addEventListener('click', handleClickOutside);
            if (searchInputRef.value && typeof searchInputRef.value.focus === 'function') {
                searchInputRef.value.focus();
            }
        });
    } else {
        document.removeEventListener('click', handleClickOutside);
        searchQuery.value = '';
    }
});

// Cleanup on unmount
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div data-search-select class="relative">
        <!-- Trigger Button -->
        <Button
            type="button"
            variant="outline"
            :class="cn(
                'w-full justify-between border-input bg-transparent text-foreground dark:bg-input/30 hover:bg-accent hover:text-accent-foreground',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none',
                isOpen && 'border-ring',
                !selectedOption && 'text-muted-foreground',
                props.class
            )"
            :disabled="disabled"
            @click="toggleDropdown"
        >
            <span class="truncate">{{ displayValue }}</span>
            <ChevronDown class="h-4 w-4 opacity-50" />
        </Button>
        
        <!-- Dropdown Content -->
        <div
            v-if="isOpen"
            ref="dropdownRef"
            class="absolute top-full left-0 right-0 z-[2000] mt-1 bg-popover text-popover-foreground border border-border rounded-md shadow-lg p-0"
        >
            <!-- Search Input -->
            <div class="p-2 border-b border-border">
                <div class="relative">
                    <Search class="absolute left-2 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        :placeholder="searchPlaceholder"
                        class="pl-8 pr-8 border-0 bg-transparent focus-visible:ring-0 focus-visible:ring-offset-0"
                        @input="handleSearchInput"
                    />
                    <Button
                        v-if="searchQuery"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0"
                        @click="clearSearch($event)"
                    >
                        <X class="h-3 w-3" />
                    </Button>
                </div>
            </div>
            
            <!-- Options List -->
            <div class="max-h-60 overflow-y-auto">
                <button
                    v-for="option in filteredOptions"
                    :key="String(option.value ?? 'null')"
                    type="button"
                    @click="selectOption(option, $event)"
                    :class="[
                        'w-full px-2 py-1.5 text-left text-sm cursor-pointer transition-colors',
                        option.value === modelValue 
                            ? 'bg-accent text-accent-foreground' 
                            : 'hover:bg-accent hover:text-accent-foreground'
                    ]"
                >
                    <div v-if="option.description" class="flex flex-col">
                        <span class="font-medium">{{ option.label }}</span>
                        <span class="text-xs text-muted-foreground mt-0.5">{{ option.description }}</span>
                    </div>
                    <span v-else>{{ option.label }}</span>
                </button>
                
                <!-- No results message -->
                <div v-if="searchQuery && filteredOptions.length === 0" class="px-2 py-3 text-center text-muted-foreground text-sm">
                    No results found for "{{ searchQuery }}"
                </div>
            </div>
        </div>
    </div>
</template> 