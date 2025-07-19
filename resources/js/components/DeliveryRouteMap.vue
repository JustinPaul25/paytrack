<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { LMap, LTileLayer, LMarker, LPolyline } from '@vue-leaflet/vue-leaflet'
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

interface Props {
  customerLocation?: { lat: number; lng: number } | null
  deliveryAddress?: string
  class?: string
  mapHeight?: string
}

const props = withDefaults(defineProps<Props>(), {
  mapHeight: '400px',
  class: ''
})

const currentLocation = ref<{ lat: number; lng: number } | null>(null)
const routeCoordinates = ref<[number, number][]>([])
const routeDistance = ref<number | null>(null)
const routeDuration = ref<number | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const mapCenter = ref<[number, number]>([14.5995, 120.9842]) // Default to Manila
const zoom = ref(10)
const showManualLocationInput = ref(false)
const manualLat = ref('')
const manualLng = ref('')

// Custom icons for markers
const createCustomIcon = (color: string) => {
  return new Icon({
    iconUrl: `data:image/svg+xml;base64,${btoa(`
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${color}" width="24" height="24">
        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
      </svg>
    `)}`,
    iconSize: [24, 24],
    iconAnchor: [12, 24],
    popupAnchor: [0, -24]
  })
}

const currentLocationIcon = createCustomIcon('#3b82f6') // Blue
const customerLocationIcon = createCustomIcon('#ef4444') // Red

// Get current location
const getCurrentLocation = (): Promise<{ lat: number; lng: number }> => {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation is not supported by this browser'))
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          lat: position.coords.latitude,
          lng: position.coords.longitude
        })
      },
      (error) => {
        let errorMessage = 'Unknown geolocation error'
        
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = 'Location access was denied. Please allow location access in your browser settings and refresh the page.'
            break
          case error.POSITION_UNAVAILABLE:
            errorMessage = 'Location information is unavailable. Please check your device location settings.'
            break
          case error.TIMEOUT:
            errorMessage = 'Location request timed out. Please try again.'
            break
          default:
            errorMessage = `Geolocation error: ${error.message}`
        }
        
        reject(new Error(errorMessage))
      },
      {
        enableHighAccuracy: false, // Changed to false for better compatibility
        timeout: 15000, // Increased timeout
        maximumAge: 300000 // 5 minutes cache
      }
    )
  })
}

// Calculate route using OSRM (Open Source Routing Machine) - Free and no API key required
const calculateRoute = async (from: { lat: number; lng: number }, to: { lat: number; lng: number }) => {
  // Validate coordinates before making API call
  if (!from || !to || 
      typeof from.lat !== 'number' || typeof from.lng !== 'number' ||
      typeof to.lat !== 'number' || typeof to.lng !== 'number' ||
      isNaN(from.lat) || isNaN(from.lng) || isNaN(to.lat) || isNaN(to.lng)) {
    console.error('Invalid coordinates for route calculation:', { from, to })
    error.value = 'Invalid coordinates for route calculation'
    return
  }

  try {
    isLoading.value = true
    error.value = null

    // Using OSRM (Open Source Routing Machine) - Free service
    const url = `https://router.project-osrm.org/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=full&geometries=geojson`
    
    const response = await fetch(url)

    if (!response.ok) {
      throw new Error('Failed to calculate route')
    }

    const data = await response.json()
    
    if (data.routes && data.routes[0] && data.routes[0].geometry) {
      const coordinates = data.routes[0].geometry.coordinates
      routeCoordinates.value = coordinates.map((coord: number[]) => [coord[1] as number, coord[0] as number]) // Convert [lng, lat] to [lat, lng]
      
      // Store route information
      routeDistance.value = parseFloat((data.routes[0].distance / 1000).toFixed(1)) // Convert to km
      routeDuration.value = parseFloat((data.routes[0].duration / 60).toFixed(1)) // Convert to minutes
      
      // Log route information
      console.log('Route calculated:', {
        distance: routeDistance.value,
        duration: routeDuration.value,
        coordinates: routeCoordinates.value.length
      })
    } else {
      throw new Error('No route found')
    }
  } catch (err) {
    console.error('Route calculation error:', err)
    error.value = 'Failed to calculate route. Please check your internet connection.'
    
    // Fallback: draw a straight line
    routeCoordinates.value = [[from.lat, from.lng], [to.lat, to.lng]]
  } finally {
    isLoading.value = false
  }
}

// Watch for customer location changes
watch(() => props.customerLocation, async (newLocation) => {
  // Validate customer location data
  if (newLocation && 
      typeof newLocation.lat === 'number' && 
      typeof newLocation.lng === 'number' && 
      !isNaN(newLocation.lat) && 
      !isNaN(newLocation.lng) &&
      newLocation.lat >= -90 && newLocation.lat <= 90 &&
      newLocation.lng >= -180 && newLocation.lng <= 180) {
    
    if (currentLocation.value) {
      await calculateRoute(currentLocation.value, newLocation)
      
      // Update map center to show both points
      const midLat = (currentLocation.value.lat + newLocation.lat) / 2
      const midLng = (currentLocation.value.lng + newLocation.lng) / 2
      mapCenter.value = [midLat, midLng]
      zoom.value = 12
    } else {
      // If no current location, center on customer location
      mapCenter.value = [newLocation.lat, newLocation.lng]
      zoom.value = 13
    }
  } else if (newLocation) {
    // Invalid customer location data
    console.warn('Invalid customer location data:', newLocation)
    error.value = 'Customer location data is invalid or missing. Please update the customer with valid coordinates.'
  }
}, { immediate: true })

// Initialize current location on mount
onMounted(async () => {
  try {
    currentLocation.value = await getCurrentLocation()
    mapCenter.value = [currentLocation.value.lat, currentLocation.value.lng]
    zoom.value = 13
    
    // If customer location is already available, calculate route
    if (props.customerLocation) {
      await calculateRoute(currentLocation.value, props.customerLocation)
    }
  } catch (err) {
    console.error('Failed to get current location:', err)
    error.value = 'Unable to get your current location. Please enable location services.'
  }
})

// Update route information when coordinates change
watch(routeCoordinates, (coordinates) => {
  if (coordinates.length < 2) {
    routeDistance.value = null
    routeDuration.value = null
  }
})

// Set manual location
function setManualLocation() {
  const lat = parseFloat(manualLat.value)
  const lng = parseFloat(manualLng.value)
  
  if (isNaN(lat) || isNaN(lng)) {
    error.value = 'Please enter valid latitude and longitude coordinates'
    return
  }
  
  if (lat < -90 || lat > 90) {
    error.value = 'Latitude must be between -90 and 90'
    return
  }
  
  if (lng < -180 || lng > 180) {
    error.value = 'Longitude must be between -180 and 180'
    return
  }
  
  currentLocation.value = { lat, lng }
  mapCenter.value = [lat, lng]
  zoom.value = 13
  error.value = null
  showManualLocationInput.value = false
  
  // If customer location is available, calculate route
  if (props.customerLocation) {
    calculateRoute(currentLocation.value, props.customerLocation)
  }
}

// Retry getting current location
async function retryGetLocation() {
  error.value = null
  isLoading.value = true
  
  try {
    currentLocation.value = await getCurrentLocation()
    mapCenter.value = [currentLocation.value.lat, currentLocation.value.lng]
    zoom.value = 13
    
    // If customer location is available, calculate route
    if (props.customerLocation) {
      await calculateRoute(currentLocation.value, props.customerLocation)
    }
  } catch (err) {
    console.error('Failed to get current location:', err)
    error.value = err instanceof Error ? err.message : 'Unable to get your current location'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div :class="['w-full', props.class]">
    <!-- Map Container -->
    <div class="relative">
      <LMap
        v-model:center="mapCenter"
        :zoom="zoom"
        :style="{ width: '100%', height: props.mapHeight, borderRadius: '0.5rem', border: '1px solid #e5e7eb', overflow: 'hidden' }"
        aria-label="Delivery route map"
        tabindex="0"
        role="application"
      >
        <LTileLayer
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          attribution="&copy; OpenStreetMap contributors"
          :max-zoom="19"
        />
        
        <!-- Current Location Marker -->
        <LMarker
          v-if="currentLocation && 
                typeof currentLocation.lat === 'number' && 
                typeof currentLocation.lng === 'number' && 
                !isNaN(currentLocation.lat) && 
                !isNaN(currentLocation.lng)"
          :lat-lng="[currentLocation.lat, currentLocation.lng]"
          :icon="currentLocationIcon"
        >
          <template #popup>
            <div class="text-center">
              <div class="font-semibold text-blue-600">Current Location</div>
              <div class="text-sm text-gray-600">{{ Number(currentLocation.lat).toFixed(6) }}, {{ Number(currentLocation.lng).toFixed(6) }}</div>
            </div>
          </template>
        </LMarker>
        
        <!-- Customer Location Marker -->
        <LMarker
          v-if="customerLocation && 
                typeof customerLocation.lat === 'number' && 
                typeof customerLocation.lng === 'number' && 
                !isNaN(customerLocation.lat) && 
                !isNaN(customerLocation.lng)"
          :lat-lng="[customerLocation.lat, customerLocation.lng]"
          :icon="customerLocationIcon"
        >
          <template #popup>
            <div class="text-center">
              <div class="font-semibold text-red-600">Delivery Location</div>
              <div class="text-sm text-gray-600">{{ Number(customerLocation.lat).toFixed(6) }}, {{ Number(customerLocation.lng).toFixed(6) }}</div>
              <div v-if="deliveryAddress" class="text-xs text-gray-500 mt-1">{{ deliveryAddress }}</div>
            </div>
          </template>
        </LMarker>
        
        <!-- Route Line -->
        <LPolyline
          v-if="routeCoordinates.length > 1"
          :lat-lngs="routeCoordinates"
          color="#10b981"
          weight="4"
          opacity="0.8"
        />
      </LMap>
      
      <!-- Loading Overlay -->
      <div
        v-if="isLoading"
        class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center rounded-lg"
      >
        <div class="text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <div class="mt-2 text-sm text-gray-600">Calculating route...</div>
        </div>
      </div>
    </div>
    
    <!-- Route Information -->
    <div v-if="currentLocation && customerLocation" class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-gray-100">Route Information</h3>
          <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
              <span>Current Location</span>
            </div>
            <div class="flex items-center gap-2 mt-1">
              <div class="w-3 h-3 bg-red-500 rounded-full"></div>
              <span>Delivery Location</span>
            </div>
          </div>
        </div>
        <div v-if="routeDistance" class="text-right">
          <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ routeDistance }} km</div>
          <div v-if="routeDuration" class="text-sm text-gray-600 dark:text-gray-400">{{ routeDuration }} min</div>
          <div class="text-xs text-gray-500">Route distance & time</div>
        </div>
      </div>
    </div>
    
    <!-- Error Message -->
    <div v-if="error" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <div class="flex items-center gap-2 mb-3">
        <div class="w-5 h-5 text-red-500">
          <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <span class="text-sm text-red-700 dark:text-red-400">{{ error }}</span>
      </div>
      
      <!-- Action Buttons -->
      <div class="flex gap-2">
        <button
          type="button"
          @click="retryGetLocation"
          class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
        >
          🔄 Retry Location
        </button>
        <button
          type="button"
          @click="showManualLocationInput = !showManualLocationInput"
          class="px-3 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
        >
          📍 Enter Manually
        </button>
      </div>
      
      <!-- Manual Location Input -->
      <div v-if="showManualLocationInput" class="mt-3 p-3 bg-white dark:bg-gray-700 rounded border">
        <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
          Enter your current location coordinates:
        </div>
        <div class="flex gap-2">
          <input
            v-model="manualLat"
            type="number"
            step="any"
            placeholder="Latitude"
            class="flex-1 px-2 py-1 text-xs border rounded"
          />
          <input
            v-model="manualLng"
            type="number"
            step="any"
            placeholder="Longitude"
            class="flex-1 px-2 py-1 text-xs border rounded"
          />
          <button
            type="button"
            @click="setManualLocation"
            class="px-3 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
          >
            Set
          </button>
        </div>
        <div class="text-xs text-gray-500 mt-1">
          Example: 14.5995, 120.9842 (Manila)
        </div>
      </div>
    </div>
    
    <!-- No Location Message -->
    <div v-if="!currentLocation && !customerLocation" class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
      <div class="flex items-center gap-2">
        <div class="w-5 h-5 text-yellow-500">
          <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <span class="text-sm text-yellow-700 dark:text-yellow-400">Select a customer with location data to view the route</span>
      </div>
    </div>
    
    <!-- Location Access Help -->
    <div v-if="!currentLocation" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
      <div class="flex items-start gap-2">
        <div class="w-5 h-5 text-blue-500 mt-0.5">
          <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="flex-1">
          <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">Location Access Help</h4>
          <div class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
            <p><strong>Chrome/Edge:</strong> Click the location icon in the address bar → Allow</p>
            <p><strong>Firefox:</strong> Click the shield icon → Allow location access</p>
            <p><strong>Safari:</strong> Safari → Preferences → Websites → Location → Allow</p>
            <p><strong>Mobile:</strong> Check your device's location settings and browser permissions</p>
          </div>
          <div class="mt-2 text-xs text-blue-600 dark:text-blue-300">
            💡 <strong>Tip:</strong> You can also manually enter your coordinates using the "Enter Manually" option above.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.leaflet-container) {
  min-height: 200px;
  outline: none;
}

:deep(.leaflet-popup-content) {
  margin: 8px 12px;
  font-family: inherit;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style> 