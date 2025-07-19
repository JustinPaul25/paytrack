<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

interface SelectOption {
    value: string | number | null;
    label: string;
}

interface Props {
    modelValue?: string | number | null;
    options: SelectOption[];
    placeholder?: string;
    class?: string;
    disabled?: boolean;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
    disabled: false,
    required: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const isOpen = ref(false);

const selectedOption = computed(() => {
    return props.options.find(option => option.value === props.modelValue);
});

const displayValue = computed(() => {
    return selectedOption.value?.label || props.placeholder;
});

function selectOption(option: SelectOption) {
    emit('update:modelValue', option.value);
    isOpen.value = false;
}

// Close dropdown when clicking outside
function handleClickOutside(event: Event) {
    const target = event.target as HTMLElement;
    if (!target.closest('[data-select]')) {
        isOpen.value = false;
    }
}

// Add/remove click outside listener
watch(isOpen, (open) => {
    if (open) {
        nextTick(() => {
            document.addEventListener('click', handleClickOutside);
        });
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
});
</script>

<template>
    <div data-select class="relative">
        <DropdownMenu :open="isOpen" @update:open="isOpen = $event">
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    :class="cn(
                        'w-full justify-between border-input bg-transparent text-foreground dark:bg-input/30 hover:bg-accent hover:text-accent-foreground',
                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none',
                        'data-[state=open]:border-ring',
                        !selectedOption && 'text-muted-foreground',
                        props.class
                    )"
                    :disabled="disabled"
                >
                    <span class="truncate">{{ displayValue }}</span>
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            
            <DropdownMenuContent 
                class="w-full min-w-[var(--radix-dropdown-menu-trigger-width)] bg-popover text-popover-foreground border border-border"
                align="start"
            >
                <DropdownMenuItem
                    v-for="option in options"
                    :key="String(option.value ?? 'null')"
                    @click="selectOption(option)"
                    :class="[
                        'cursor-pointer',
                        option.value === modelValue ? 'bg-accent text-accent-foreground' : 'hover:bg-accent hover:text-accent-foreground'
                    ]"
                >
                    {{ option.label }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template> 