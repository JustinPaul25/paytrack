<script setup lang="ts">
import { TabsRoot, type TabsRootEmits, type TabsRootProps, useForwardPropsEmits } from 'reka-ui'

const props = withDefaults(defineProps<TabsRootProps>(), {
  defaultValue: 'sales-prediction',
})

const emits = defineEmits<TabsRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <TabsRoot
    data-slot="tabs"
    v-bind="forwarded"
  >
    <slot />
  </TabsRoot>
</template>

