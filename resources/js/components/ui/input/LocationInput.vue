<script setup lang="ts">
import { ref, watch } from 'vue'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { LMap, LTileLayer, LMarker } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet's default icon path issues in Vite
import { Icon } from 'leaflet'
import type { LatLngExpression } from 'leaflet'
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

// Simple place search (OSM Nominatim)
const searchQuery = ref('')
const searchResults = ref<{ display_name: string; lat: string; lon: string }[]>([])
const searching = ref(false)
let searchTimer: ReturnType<typeof setTimeout> | null = null

async function runSearch(q: string) {
  const query = q.trim()
  if (!query) {
    searchResults.value = []
    return
  }
  searching.value = true
  try {
    const resp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`, {
      headers: {
        'Accept': 'application/json'
      }
    })
    if (!resp.ok) throw new Error('Search failed')
    const data = await resp.json()
    searchResults.value = (data ?? []).slice(0, 5)
  } catch {
    searchResults.value = []
  } finally {
    searching.value = false
  }
}

function onSearchInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  searchQuery.value = value
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => runSearch(value), 400)
}

function selectPlace(p: { display_name: string; lat: string; lon: string }) {
  const lat = parseFloat(p.lat)
  const lng = parseFloat(p.lon)
  if (isNaN(lat) || isNaN(lng)) return
  selected.value = { lat, lng }
  mapCenter.value = [lat, lng]
  zoom.value = 15
  emits('update:modelValue', selected.value)
  // keep chosen text in the field, clear dropdown
  searchQuery.value = p.display_name
  searchResults.value = []
}

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
    <!-- Place Search -->
    <div style="margin-bottom: 0.5rem;">
      <input
        :value="searchQuery"
        @input="onSearchInput"
        type="text"
        placeholder="Search place or address…"
        aria-label="Search place or address"
        style="width: 100%; height: 36px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 0 10px; background: var(--background, #fff); color: inherit;"
      />
      <div v-if="searching" style="margin-top: 4px; font-size: 12px; color: #6b7280;">Searching…</div>
      <div v-if="searchResults.length" style="margin-top: 4px; border: 1px solid #e5e7eb; border-radius: 6px; background: var(--background, #fff); max-height: 180px; overflow: auto;">
        <button
          v-for="res in searchResults"
          :key="res.lat + ',' + res.lon + '-' + res.display_name"
          type="button"
          @click="selectPlace(res)"
          style="display: block; width: 100%; text-align: left; padding: 8px 10px; border: none; background: transparent; cursor: pointer; font-size: 12px; color: inherit;"
        >
          {{ res.display_name }}
        </button>
      </div>
    </div>

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