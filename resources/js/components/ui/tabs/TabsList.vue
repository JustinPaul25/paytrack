<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { TabsList, type TabsListProps } from 'reka-ui'
import { reactiveOmit } from '@vueuse/core'

const props = withDefaults(defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>(), {})

const delegatedProps = reactiveOmit(props, 'class')
</script>

<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn('inline-flex h-10 items-center justify-center rounded-md bg-muted text-muted-foreground overflow-hidden', props.class)"
  >
    <slot />
  </TabsList>
</template>

