<script setup lang="ts">
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { ChevronDown, Search, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface SelectOption {
    value: string | number | null;
    label: string;
    description?: string;
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
const triggerRef = ref<HTMLDivElement>();
const searchInputRef = ref<HTMLInputElement>();

// Teleported dropdown position
const dropdownStyle = ref({ top: '0px', left: '0px', width: '0px' });

const selectedOption = computed(() =>
    props.options.find((o) => o.value === props.modelValue),
);

const displayValue = computed(() =>
    selectedOption.value ? selectedOption.value.label : props.placeholder,
);

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const q = searchQuery.value.toLowerCase().trim();
    return props.options.filter((o) => {
        if (o.value === null && o.label.toLowerCase().includes('select')) return false;
        return o.label.toLowerCase().includes(q);
    });
});

function calcPosition() {
    if (!triggerRef.value) return;
    const rect = triggerRef.value.getBoundingClientRect();
    dropdownStyle.value = {
        top: `${rect.bottom + 4}px`,
        left: `${rect.left}px`,
        width: `${rect.width}px`,
    };
}

function open() {
    if (props.disabled) return;
    calcPosition();
    isOpen.value = true;
}

function toggleDropdown() {
    if (isOpen.value) {
        isOpen.value = false;
    } else {
        open();
    }
}

function selectOption(option: SelectOption, event?: Event) {
    event?.preventDefault();
    event?.stopPropagation();
    emit('update:modelValue', option.value);
    isOpen.value = false;
    searchQuery.value = '';
}

function clearSearch(event?: Event) {
    event?.preventDefault();
    event?.stopPropagation();
    searchQuery.value = '';
    nextTick(() => searchInputRef.value?.focus());
}

function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (
        !target.closest('[data-search-select-trigger]') &&
        !target.closest('[data-search-select-dropdown]')
    ) {
        isOpen.value = false;
        searchQuery.value = '';
    }
}

function handleScrollOrResize() {
    if (isOpen.value) calcPosition();
}

watch(isOpen, (open) => {
    if (open) {
        nextTick(() => {
            document.addEventListener('mousedown', handleClickOutside);
            window.addEventListener('scroll', handleScrollOrResize, true);
            window.addEventListener('resize', handleScrollOrResize);
            searchInputRef.value?.focus();
        });
    } else {
        document.removeEventListener('mousedown', handleClickOutside);
        window.removeEventListener('scroll', handleScrollOrResize, true);
        window.removeEventListener('resize', handleScrollOrResize);
        searchQuery.value = '';
    }
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    window.removeEventListener('scroll', handleScrollOrResize, true);
    window.removeEventListener('resize', handleScrollOrResize);
});
</script>

<template>
    <div ref="triggerRef" data-search-select-trigger class="relative">
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
            <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
        </Button>

        <!-- Teleported dropdown — escapes overflow:hidden/auto parents -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                data-search-select-dropdown
                :style="dropdownStyle"
                class="fixed z-[9999] min-w-[10rem] bg-popover text-popover-foreground border border-border rounded-md shadow-lg p-0"
            >
                <!-- Search -->
                <div class="p-2 border-b border-border">
                    <div class="relative">
                        <Search class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                        <Input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            :placeholder="searchPlaceholder"
                            class="pl-8 pr-8 border-0 bg-transparent focus-visible:ring-0 focus-visible:ring-offset-0"
                        />
                        <Button
                            v-if="searchQuery"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="absolute right-1 top-1/2 -translate-y-1/2 h-6 w-6 p-0"
                            @click="clearSearch($event)"
                        >
                            <X class="h-3 w-3" />
                        </Button>
                    </div>
                </div>

                <!-- Options -->
                <div class="max-h-60 overflow-y-auto">
                    <button
                        v-for="option in filteredOptions"
                        :key="String(option.value ?? 'null')"
                        type="button"
                        @mousedown.prevent="selectOption(option, $event)"
                        :class="[
                            'w-full px-3 py-1.5 text-left text-sm cursor-pointer transition-colors',
                            option.value === modelValue
                                ? 'bg-accent text-accent-foreground'
                                : 'hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <div v-if="option.description" class="flex flex-col">
                            <span class="font-medium">{{ option.label }}</span>
                            <span class="text-xs text-muted-foreground mt-0.5">{{ option.description }}</span>
                        </div>
                        <span v-else>{{ option.label }}</span>
                    </button>

                    <div
                        v-if="searchQuery && filteredOptions.length === 0"
                        class="px-3 py-3 text-center text-muted-foreground text-sm"
                    >
                        No results for "{{ searchQuery }}"
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
