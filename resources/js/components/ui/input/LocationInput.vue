<script setup lang="ts">
import { ref, watch } from 'vue'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { LMap, LTileLayer, LMarker } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet's default icon path issues in Vite
import { Icon } from 'leaflet'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

const props = defineProps<{
  modelValue?: { lat: number; lng: number } | null
  defaultValue?: { lat: number; lng: number } | null
  class?: HTMLAttributes['class']
  mapHeight?: string
  mapZoom?: number
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', value: { lat: number; lng: number } | null): void
}>()

const selected = ref<{ lat: number; lng: number } | null>(null)
const mapCenter = ref<[number, number]>([0, 0])
const zoom = ref(props.mapZoom ?? (props.modelValue ? 13 : 2))

watch(
  () => props.modelValue,
  (val) => {
    if (val && typeof val.lat === 'number' && typeof val.lng === 'number' && !isNaN(val.lat) && !isNaN(val.lng)) {
      selected.value = val
      mapCenter.value = [val.lat, val.lng]
      zoom.value = props.mapZoom ?? 13
    } else {
      selected.value = null
      mapCenter.value = [0, 0]
      zoom.value = props.mapZoom ?? 2
    }
  },
  { immediate: true }
)

function onMapClick(e: any) {
  const { latlng } = e
  selected.value = { lat: latlng.lat, lng: latlng.lng }
  emits('update:modelValue', selected.value)
}

function onMarkerDrag(e: any) {
  const { lat, lng } = e.target.getLatLng()
  selected.value = { lat, lng }
  emits('update:modelValue', selected.value)
}
</script>

<template>
  <div :class="cn('w-full', props.class)">
    <LMap
      v-model:center="mapCenter"
      :zoom="zoom"
      :style="{ width: '100%', height: props.mapHeight ?? '300px', borderRadius: '0.5rem', border: '1px solid #e5e7eb', overflow: 'hidden' }"
      @click="onMapClick"
      aria-label="Location map input"
      tabindex="0"
      role="application"
    >
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution="&copy; OpenStreetMap contributors"
        :max-zoom="19"
      />
      <LMarker
        v-if="selected && typeof selected.lat === 'number' && typeof selected.lng === 'number' && !isNaN(selected.lat) && !isNaN(selected.lng)"
        :lat-lng="[selected.lat, selected.lng]"
        :draggable="true"
        @moveend="onMarkerDrag"
      />
    </LMap>
    <div v-if="selected && typeof selected.lat === 'number' && typeof selected.lng === 'number'" class="mt-2 text-xs text-muted-foreground">
      Selected: <span class="font-mono">{{ selected.lat.toFixed(6) }}, {{ selected.lng.toFixed(6) }}</span>
    </div>
    <div v-else-if="selected" class="mt-2 text-xs text-red-500">
      Invalid location data. Please click on the map to select a new location.
    </div>
    <div v-else class="mt-2 text-xs text-muted-foreground">Click on the map to select a location.</div>
  </div>
</template>

<style scoped>
:deep(.leaflet-container) {
  min-height: 200px;
  outline: none;
}
</style> 