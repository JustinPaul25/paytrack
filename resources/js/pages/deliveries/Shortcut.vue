<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Deliveries Hub" />
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Deliveries Hub</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Quick access to delivery endpoints with invoice and customer data</p>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Endpoints</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalEndpoints }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
                            <Truck class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Deliveries</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ activeDeliveries }}</p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ pendingDeliveries }}</p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
                            <Clock class="w-6 h-6 text-yellow-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(totalRevenue) }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
                            <DollarSign class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Map View Toggle -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Delivery Endpoints</h2>
            <div class="flex gap-2">
                <Button 
                    variant="outline" 
                    :class="{ 'bg-blue-50 border-blue-200': !showMap }"
                    @click="showMap = false"
                >
                    <Grid class="w-4 h-4 mr-2" />
                    Grid View
                </Button>
                <Button 
                    variant="outline" 
                    :class="{ 'bg-blue-50 border-blue-200': showMap }"
                    @click="showMap = true"
                >
                    <MapPin class="w-4 h-4 mr-2" />
                    Map View
                </Button>
            </div>
        </div>

        <!-- Map View -->
        <div v-if="showMap" class="mb-8">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MapPin class="w-5 h-5 text-blue-600" />
                        Delivery Endpoints Map
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="h-96 w-full rounded-lg overflow-hidden">
                        <l-map 
                            ref="map" 
                            v-model:zoom="mapZoom" 
                            :center="mapCenter" 
                            :use-global-leaflet="false"
                            class="h-full w-full"
                        >
                            <l-tile-layer 
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" 
                                layer-type="base" 
                                name="OpenStreetMap"
                            />
                            
                            <!-- Delivery Endpoint Markers -->
                            <template v-for="endpoint in deliveryEndpoints" :key="endpoint.id">
                                <l-marker
                                    v-if="endpoint.coordinates"
                                    :lat-lng="endpoint.coordinates"
                                    @click="selectEndpoint(endpoint)"
                                >
                                    <l-tooltip
                                        :options="{
                                            permanent: true,
                                            direction: 'top',
                                            offset: [0, -32],
                                            className: 'delivery-tag-tooltip'
                                        }"
                                    >
                                        <div class="map-tag" :style="getTagStyle(endpoint)">
                                            <span class="map-tag__dot"></span>
                                            <span class="map-tag__label">{{ endpoint.tag }}</span>
                                        </div>
                                    </l-tooltip>
                                    <l-popup>
                                        <div class="p-2">
                                            <h3 class="font-semibold text-gray-900">{{ endpoint.name }}</h3>
                                            <p class="text-sm text-gray-600">{{ endpoint.customer?.name ?? '—' }}</p>
                                            <p class="text-sm text-gray-600">
                                                {{ formatCurrency(endpoint.invoice?.total_amount ?? 0) }}
                                            </p>
                                            <span :class="['px-2 py-1 rounded-full text-xs font-medium mt-2 inline-block', getStatusBadgeClass(endpoint.status)]">
                                                {{ endpoint.status }}
                                            </span>
                                        </div>
                                    </l-popup>
                                </l-marker>
                            </template>
                        </l-map>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delivery Endpoints Grid -->
        <div v-if="!showMap" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card 
                v-for="endpoint in deliveryEndpoints" 
                :key="endpoint.id"
                class="group cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-[1.02]"
                @click="selectEndpoint(endpoint)"
                @mouseenter="hoveredEndpoint = endpoint"
                @mouseleave="hoveredEndpoint = null"
            >
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <component :is="endpoint.icon" class="w-5 h-5" :class="endpoint.iconColor" />
                            {{ endpoint.name }}
                        </CardTitle>
                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(endpoint.status)]">
                            {{ endpoint.status }}
                        </span>
                    </div>
                </CardHeader>
                
                <CardContent>
                    <!-- Invoice Data -->
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                            <Receipt class="w-4 h-4 text-blue-600" />
                            Invoice Data
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Invoice #:</span>
                                <span class="font-medium">{{ endpoint.invoice?.id ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount:</span>
                                <span class="font-medium text-green-600">{{ formatCurrency(endpoint.invoice?.total_amount ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                    {{ endpoint.invoice?.status ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Data -->
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                            <Users class="w-4 h-4 text-green-600" />
                            Customer Info
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Name:</span>
                                <span class="font-medium">{{ endpoint.customer?.name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Company:</span>
                                <span class="font-medium">{{ endpoint.customer?.company_name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                                <span class="font-medium">{{ endpoint.customer?.phone ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Details -->
                    <div class="border-t pt-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                            <MapPin class="w-4 h-4 text-orange-600" />
                            Delivery Details
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Address:</span>
                                <span class="font-medium text-right max-w-[150px] truncate">{{ endpoint.delivery_address ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Date:</span>
                                <span class="font-medium">{{ formatDate(endpoint.delivery_date) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Fee:</span>
                                <span class="font-medium text-blue-600">{{ formatCurrency(endpoint.delivery_fee) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hover Overlay -->
                    <div 
                        v-if="hoveredEndpoint?.id === endpoint.id"
                        class="absolute inset-0 bg-black/5 dark:bg-white/5 rounded-lg flex items-center justify-center transition-all duration-200"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-lg border">
                            <h4 class="font-medium mb-2">Quick Actions</h4>
                            <div class="flex gap-2">
                                <Button size="sm" variant="outline">
                                    <Eye class="w-4 h-4 mr-1" />
                                    View
                                </Button>
                                <Button size="sm" variant="outline">
                                    <Edit class="w-4 h-4 mr-1" />
                                    Edit
                                </Button>
                                <Button size="sm" variant="outline">
                                    <Truck class="w-4 h-4 mr-1" />
                                    Track
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Selected Endpoint Modal -->
        <Dialog v-model:open="showEndpointModal" :modal="true" class="z-[9999]">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ selectedEndpoint?.name ?? 'Delivery Details' }} - Detailed View</DialogTitle>
                    <DialogDescription>
                        Complete information for this delivery endpoint
                    </DialogDescription>
                </DialogHeader>
                
                <div v-if="selectedEndpoint" class="space-y-6">
                    <!-- Invoice Section -->
                    <div class="bg-blue-50 dark:bg-blue-950/20 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                            <Receipt class="w-5 h-5" />
                            Invoice Information
                        </h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Invoice Number:</span>
                                    <p class="font-medium">#{{ selectedEndpoint?.invoice?.id ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Total Amount:</span>
                                    <p class="font-medium text-green-600">{{ formatCurrency(selectedEndpoint?.invoice?.total_amount ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Status:</span>
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', selectedEndpoint?.invoice?.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400']">
                                    {{ selectedEndpoint?.invoice?.status ?? 'N/A' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Created:</span>
                                    <p class="font-medium">{{ formatDate(selectedEndpoint?.invoice?.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Section -->
                    <div class="bg-green-50 dark:bg-green-950/20 rounded-lg p-4">
                        <h3 class="font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center gap-2">
                            <Users class="w-5 h-5" />
                            Customer Information
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-green-700 dark:text-green-300">Name:</span>
                                <p class="font-medium">{{ selectedEndpoint?.customer?.name ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-green-700 dark:text-green-300">Company:</span>
                                <p class="font-medium">{{ selectedEndpoint?.customer?.company_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-green-700 dark:text-green-300">Phone:</span>
                                <p class="font-medium">{{ selectedEndpoint?.customer?.phone ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-green-700 dark:text-green-300">Email:</span>
                                <p class="font-medium">{{ selectedEndpoint?.customer?.email ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Section -->
                    <div class="bg-orange-50 dark:bg-orange-950/20 rounded-lg p-4">
                        <h3 class="font-semibold text-orange-900 dark:text-orange-100 mb-3 flex items-center gap-2">
                            <Truck class="w-5 h-5" />
                            Delivery Information
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-orange-700 dark:text-orange-300">Delivery Address:</span>
                                <p class="font-medium mt-1">{{ selectedEndpoint?.delivery_address ?? '—' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Delivery Date:</span>
                                    <p class="font-medium">{{ formatDate(selectedEndpoint?.delivery_date) }}</p>
                                </div>
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Delivery Time:</span>
                                    <p class="font-medium">{{ selectedEndpoint?.delivery_time ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Delivery Fee:</span>
                                    <p class="font-medium text-blue-600">{{ formatCurrency(selectedEndpoint?.delivery_fee) }}</p>
                                </div>
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Status:</span>
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(selectedEndpoint?.status ?? '')]">
                                    {{ selectedEndpoint?.status ?? '—' }}
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showEndpointModal = false">Close</Button>
                    <Button @click="trackDelivery(selectedEndpoint)">Track Delivery</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Ensure modal appears above map */
:deep(.leaflet-container) {
    z-index: 1;
}

:deep([data-radix-dialog-overlay]) {
    z-index: 9998 !important;
}

:deep([data-radix-dialog-content]) {
    z-index: 9999 !important;
}

.delivery-tag-tooltip {
    background: transparent;
    border: none;
    box-shadow: none;
}

.map-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: var(--tag-color, #2563eb);
    color: #ffffff;
    font-size: 12px;
    line-height: 1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
    white-space: nowrap;
}

.map-tag__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.35);
}

.map-tag__label {
    font-weight: 600;
    letter-spacing: 0.01em;
}
</style>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
    Truck, 
    Users, 
    Receipt, 
    MapPin, 
    CheckCircle, 
    Clock, 
    DollarSign,
    Eye,
    Edit,
    Building2,
    Package,
    Car,
    Plane,
    Ship,
    Grid
} from 'lucide-vue-next';
import { LMap, LTileLayer, LMarker, LPopup, LTooltip } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';
import { type BreadcrumbItem } from '@/types';

interface DeliveryInvoice {
    id: number;
    total_amount: number;
    status: string;
    created_at: string | null;
}

interface DeliveryCustomer {
    name: string | null;
    company_name: string | null;
    phone: string | null;
    email: string | null;
}

interface DeliveryEndpoint {
    id: number;
    name: string;
    status: string;
    tag: string;
    tagColor: string;
    coordinates: [number, number] | null;
    delivery_address: string | null;
    delivery_date: string | null;
    delivery_time: string | null;
    delivery_fee: number | null;
    contact_person: string | null;
    contact_phone: string | null;
    invoice: DeliveryInvoice | null;
    customer: DeliveryCustomer | null;
    icon?: any;
    iconColor?: string;
}

interface ShortcutStats {
    totalDeliveries: number;
    inProgressDeliveries: number;
    pendingDeliveries: number;
    completedDeliveries: number;
    cancelledDeliveries: number;
    totalRevenue: number;
}

const props = withDefaults(defineProps<{
    deliveryEndpoints: DeliveryEndpoint[];
    stats: ShortcutStats;
    mapCenter: [number, number];
}>(), {
    deliveryEndpoints: () => [],
    stats: () => ({
        totalDeliveries: 0,
        inProgressDeliveries: 0,
        pendingDeliveries: 0,
        completedDeliveries: 0,
        cancelledDeliveries: 0,
        totalRevenue: 0,
    }),
    mapCenter: () => [14.5995, 120.9842] as [number, number],
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Deliveries', href: '/deliveries' },
    { title: 'Deliveries Hub', href: '/deliveries/shortcut' },
];

const iconPalette = [Car, Truck, Plane, Ship, Package, Building2];
const iconColorPalette = [
    'text-blue-600',
    'text-green-600',
    'text-purple-600',
    'text-orange-600',
    'text-indigo-600',
    'text-red-600',
];

const deliveryEndpoints = computed(() =>
    props.deliveryEndpoints.map((endpoint, index) => {
        const iconIndex = index % iconPalette.length;
        return {
            ...endpoint,
            icon: iconPalette[iconIndex],
            iconColor: iconColorPalette[iconIndex],
        };
    }),
);

const hoveredEndpoint = ref<DeliveryEndpoint | null>(null);
const selectedEndpoint = ref<DeliveryEndpoint | null>(null);
const showEndpointModal = ref(false);
const showMap = ref(false);
const mapZoom = ref(10);
const mapCenter = ref<[number, number]>(props.mapCenter);

watch(
    () => props.mapCenter,
    (center) => {
        if (Array.isArray(center) && center.length === 2) {
            mapCenter.value = [Number(center[0]), Number(center[1])];
        }
    },
    { immediate: true },
);

watch(
    deliveryEndpoints,
    (endpoints) => {
        const firstWithCoordinates = endpoints.find((endpoint) => Array.isArray(endpoint.coordinates));
        if (firstWithCoordinates && firstWithCoordinates.coordinates) {
            mapCenter.value = [
                Number(firstWithCoordinates.coordinates[0]),
                Number(firstWithCoordinates.coordinates[1]),
            ];
        }
    },
    { immediate: true },
);

const totalEndpoints = computed(() => props.stats?.totalDeliveries ?? deliveryEndpoints.value.length);
const activeDeliveries = computed(
    () =>
        props.stats?.inProgressDeliveries ??
        deliveryEndpoints.value.filter((endpoint) => endpoint.status !== 'completed' && endpoint.status !== 'cancelled')
            .length,
);
const pendingDeliveries = computed(
    () =>
        props.stats?.pendingDeliveries ??
        deliveryEndpoints.value.filter((endpoint) => endpoint.status === 'pending').length,
);
const totalRevenue = computed(() => {
    if (props.stats?.totalRevenue) {
        return props.stats.totalRevenue;
    }
    return deliveryEndpoints.value.reduce(
        (sum, endpoint) => sum + (endpoint.invoice?.total_amount ?? 0),
        0,
    );
});

function selectEndpoint(endpoint: DeliveryEndpoint) {
    selectedEndpoint.value = endpoint;
    showEndpointModal.value = true;
}

function formatCurrency(amount: number | null | undefined) {
    const numericAmount = typeof amount === 'number' && !Number.isNaN(amount) ? amount : 0;
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
    }).format(numericAmount);
}

function formatDate(dateString: string | null | undefined) {
    if (!dateString) {
        return '—';
    }
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'active':
        case 'in_progress':
        case 'in-transit':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
        case 'completed':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
}

function getTagStyle(endpoint: DeliveryEndpoint) {
    return {
        '--tag-color': endpoint.tagColor || '#2563eb',
    };
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'active':
        case 'in_progress':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'completed':
            return 'default';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
}

function trackDelivery(endpoint: DeliveryEndpoint | null) {
    if (!endpoint) {
        return;
    }
    alert(`Tracking delivery for ${endpoint.name} ${endpoint.invoice ? `- Invoice #${endpoint.invoice.id}` : ''}`.trim());
    showEndpointModal.value = false;
}
</script>