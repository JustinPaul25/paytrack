<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
  modelValue?: string;
  defaultValue?: string;
  id?: string;
  placeholder?: string;
  required?: boolean;
  class?: HTMLAttributes['class'];
  disabled?: boolean;
}>();

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue || '',
});

const inputRef = ref<HTMLInputElement | null>(null);
const displayValue = ref('');

// Extract only the 10 digits from the model value (removing +63 prefix if present)
watch(modelValue, (newValue) => {
  if (!newValue) {
    displayValue.value = '';
    return;
  }
  
  // Remove all non-digit characters first
  let digits = newValue.replace(/[^0-9]/g, '');
  
  // Handle different formats:
  // - If starts with 63, remove it (country code)
  // - If starts with 0, remove it (local format)
  if (digits.startsWith('63')) {
    digits = digits.substring(2);
  } else if (digits.startsWith('0')) {
    digits = digits.substring(1);
  }
  
  // Limit to 10 digits
  digits = digits.substring(0, 10);
  
  displayValue.value = digits;
}, { immediate: true });

// Update modelValue when displayValue changes
watch(displayValue, (newValue) => {
  // Always store as +63XXXXXXXXXX format (13 characters total)
  if (newValue.length === 10) {
    modelValue.value = '+63' + newValue;
  } else if (newValue.length > 0) {
    modelValue.value = '+63' + newValue;
  } else {
    modelValue.value = '';
  }
});

function onInput(e: Event) {
  const target = e.target as HTMLInputElement;
  let value = target.value;
  
  // Remove all non-digit characters
  value = value.replace(/[^0-9]/g, '');
  
  // Limit to 10 digits
  value = value.substring(0, 10);
  
  displayValue.value = value;
  
  // Update the input field
  if (value !== target.value) {
    target.value = value;
  }
}

function onPaste(e: ClipboardEvent) {
  e.preventDefault();
  const pastedText = e.clipboardData?.getData('text') || '';
  
  // Remove all non-digit characters
  let digits = pastedText.replace(/[^0-9]/g, '');
  
  // Remove +63 prefix if present
  if (digits.startsWith('63')) {
    digits = digits.substring(2);
  }
  
  // Limit to 10 digits
  digits = digits.substring(0, 10);
  
  displayValue.value = digits;
  
  if (inputRef.value) {
    inputRef.value.value = digits;
  }
}
</script>

<template>
  <div :class="cn('flex items-center rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow]', 
    'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
    props.class)">
    <!-- Country Code Prefix -->
    <div class="flex items-center px-3 py-2 border-r border-input text-foreground select-none">
      <span class="text-sm font-medium">+63</span>
    </div>
    
    <!-- Phone Number Input -->
    <input
      ref="inputRef"
      :id="id"
      :value="displayValue"
      :placeholder="placeholder || 'XXXXXXXXXX'"
      :required="required"
      :disabled="disabled"
      type="tel"
      inputmode="numeric"
      maxlength="10"
      class="flex-1 bg-transparent px-3 py-2 text-base md:text-sm text-foreground placeholder:text-muted-foreground outline-none border-0 focus:outline-none"
      @input="onInput"
      @paste="onPaste"
    />
  </div>
</template>

