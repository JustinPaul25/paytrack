<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Deliveries Hub - Demo" />
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Deliveries Hub</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Quick access to delivery endpoints with invoice and customer data</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Demo Mode</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
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
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ deliveryEndpoints.length }}</p>
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
                            <l-marker 
                                v-for="endpoint in deliveryEndpoints" 
                                :key="endpoint.id"
                                :lat-lng="endpoint.coordinates || [0, 0]"
                                @click="selectEndpoint(endpoint)"
                            >
                                <l-popup>
                                    <div class="p-2">
                                        <h3 class="font-semibold text-gray-900">{{ endpoint.name }}</h3>
                                        <p class="text-sm text-gray-600">{{ endpoint.customer.name }}</p>
                                        <p class="text-sm text-gray-600">{{ formatCurrency(endpoint.invoice.total_amount) }}</p>
                                        <span :class="['px-2 py-1 rounded-full text-xs font-medium mt-2 inline-block', getStatusBadgeClass(endpoint.status)]">
                                            {{ endpoint.status }}
                                        </span>
                                    </div>
                                </l-popup>
                            </l-marker>
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
                                <span class="font-medium">{{ endpoint.invoice.id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Amount:</span>
                                <span class="font-medium text-green-600">{{ formatCurrency(endpoint.invoice.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                    {{ endpoint.invoice.status }}
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
                                <span class="font-medium">{{ endpoint.customer.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Company:</span>
                                <span class="font-medium">{{ endpoint.customer.company_name || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                                <span class="font-medium">{{ endpoint.customer.phone }}</span>
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
                                <span class="font-medium text-right max-w-[150px] truncate">{{ endpoint.delivery_address }}</span>
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
                    <DialogTitle>{{ selectedEndpoint?.name }} - Detailed View</DialogTitle>
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
                                <p class="font-medium">#{{ selectedEndpoint.invoice.id }}</p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Total Amount:</span>
                                <p class="font-medium text-green-600">{{ formatCurrency(selectedEndpoint.invoice.total_amount) }}</p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Status:</span>
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', selectedEndpoint.invoice.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400']">
                                    {{ selectedEndpoint.invoice.status }}
                                </span>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Created:</span>
                                <p class="font-medium">{{ formatDate(selectedEndpoint.invoice.created_at) }}</p>
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
                                <p class="font-medium">{{ selectedEndpoint.customer.name }}</p>
                            </div>
                            <div>
                                <span class="text-green-700 dark:text-green-300">Company:</span>
                                <p class="font-medium">{{ selectedEndpoint.customer.company_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-green-700 dark:text-green-300">Phone:</span>
                                <p class="font-medium">{{ selectedEndpoint.customer.phone }}</p>
                            </div>
                            <div>
                                <span class="text-green-700 dark:text-green-300">Email:</span>
                                <p class="font-medium">{{ selectedEndpoint.customer.email }}</p>
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
                                <p class="font-medium mt-1">{{ selectedEndpoint.delivery_address }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Delivery Date:</span>
                                    <p class="font-medium">{{ formatDate(selectedEndpoint.delivery_date) }}</p>
                                </div>
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Delivery Time:</span>
                                    <p class="font-medium">{{ selectedEndpoint.delivery_time }}</p>
                                </div>
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Delivery Fee:</span>
                                    <p class="font-medium text-blue-600">{{ formatCurrency(selectedEndpoint.delivery_fee) }}</p>
                                </div>
                                <div>
                                    <span class="text-orange-700 dark:text-orange-300">Status:</span>
                                                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(selectedEndpoint.status)]">
                                    {{ selectedEndpoint.status }}
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
</style>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Deliveries', href: '/deliveries' },
    { title: 'Deliveries Hub', href: '/deliveries/shortcut' },
];

// Demo data for delivery endpoints
const deliveryEndpoints = ref([
    {
        id: 1,
        name: 'Metro Manila Express',
        status: 'active',
        icon: Car,
        iconColor: 'text-blue-600',
        coordinates: [14.5547, 121.0244] as [number, number], // Makati
        invoice: {
            id: 1001,
            total_amount: 25000,
            status: 'paid',
            created_at: '2024-12-20'
        },
        customer: {
            name: 'ABC Corporation',
            company_name: 'ABC Corp',
            phone: '+63 912 345 6789',
            email: 'contact@abccorp.com'
        },
        delivery_address: '123 Business Ave, Makati City, Metro Manila',
        delivery_date: '2024-12-25',
        delivery_time: '09:00 AM - 12:00 PM',
        delivery_fee: 500
    },
    {
        id: 2,
        name: 'Provincial Delivery',
        status: 'active',
        icon: Truck,
        iconColor: 'text-green-600',
        coordinates: [10.3157, 123.8854] as [number, number], // Cebu
        invoice: {
            id: 1002,
            total_amount: 45000,
            status: 'pending',
            created_at: '2024-12-21'
        },
        customer: {
            name: 'XYZ Industries',
            company_name: 'XYZ Industries Ltd',
            phone: '+63 923 456 7890',
            email: 'info@xyzindustries.com'
        },
        delivery_address: '456 Industrial Park, Cebu City, Cebu',
        delivery_date: '2024-12-28',
        delivery_time: '02:00 PM - 05:00 PM',
        delivery_fee: 1200
    },
    {
        id: 3,
        name: 'Air Freight Express',
        status: 'active',
        icon: Plane,
        iconColor: 'text-purple-600',
        coordinates: [7.1907, 125.4553] as [number, number], // Davao
        invoice: {
            id: 1003,
            total_amount: 75000,
            status: 'paid',
            created_at: '2024-12-22'
        },
        customer: {
            name: 'Tech Solutions Inc',
            company_name: 'Tech Solutions',
            phone: '+63 934 567 8901',
            email: 'delivery@techsolutions.com'
        },
        delivery_address: '789 Tech Hub, Davao City, Davao del Sur',
        delivery_date: '2024-12-26',
        delivery_time: '10:00 AM - 11:00 AM',
        delivery_fee: 2500
    },
    {
        id: 4,
        name: 'Sea Cargo Delivery',
        status: 'pending',
        icon: Ship,
        iconColor: 'text-orange-600',
        coordinates: [14.5995, 120.9842] as [number, number], // Manila
        invoice: {
            id: 1004,
            total_amount: 120000,
            status: 'paid',
            created_at: '2024-12-23'
        },
        customer: {
            name: 'Maritime Trading Co',
            company_name: 'Maritime Trading',
            phone: '+63 945 678 9012',
            email: 'logistics@maritimetrading.com'
        },
        delivery_address: '321 Port Area, Manila Bay, Manila',
        delivery_date: '2024-12-30',
        delivery_time: '08:00 AM - 12:00 PM',
        delivery_fee: 3500
    },
    {
        id: 5,
        name: 'Local Package Delivery',
        status: 'active',
        icon: Package,
        iconColor: 'text-indigo-600',
        coordinates: [14.6760, 121.0437] as [number, number], // Quezon City
        invoice: {
            id: 1005,
            total_amount: 15000,
            status: 'paid',
            created_at: '2024-12-24'
        },
        customer: {
            name: 'Local Business Hub',
            company_name: 'LBH Enterprises',
            phone: '+63 956 789 0123',
            email: 'orders@lbhenterprises.com'
        },
        delivery_address: '654 Local St, Quezon City, Metro Manila',
        delivery_date: '2024-12-27',
        delivery_time: '01:00 PM - 04:00 PM',
        delivery_fee: 300
    },
    {
        id: 6,
        name: 'Corporate Express',
        status: 'active',
        icon: Building2,
        iconColor: 'text-red-600',
        coordinates: [14.5547, 121.0244] as [number, number], // BGC, Taguig
        invoice: {
            id: 1006,
            total_amount: 85000,
            status: 'pending',
            created_at: '2024-12-25'
        },
        customer: {
            name: 'Global Enterprises',
            company_name: 'Global Corp',
            phone: '+63 967 890 1234',
            email: 'logistics@globalcorp.com'
        },
        delivery_address: '987 Corporate Plaza, Bonifacio Global City, Taguig',
        delivery_date: '2024-12-29',
        delivery_time: '11:00 AM - 02:00 PM',
        delivery_fee: 800
    }
]);

// Reactive state
const hoveredEndpoint = ref<any>(null);
const selectedEndpoint = ref<any>(null);
const showEndpointModal = ref(false);
const showMap = ref(false);
const mapZoom = ref(10);
const mapCenter = ref<[number, number]>([14.5995, 120.9842]); // Manila coordinates

// Computed properties
const activeDeliveries = computed(() => deliveryEndpoints.value.filter(e => e.status === 'active').length);
const pendingDeliveries = computed(() => deliveryEndpoints.value.filter(e => e.status === 'pending').length);
const totalRevenue = computed(() => deliveryEndpoints.value.reduce((sum, endpoint) => sum + endpoint.invoice.total_amount, 0));

// Methods
function selectEndpoint(endpoint: any) {
    selectedEndpoint.value = endpoint;
    showEndpointModal.value = true;
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
        case 'completed': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'active': return 'default';
        case 'pending': return 'secondary';
        case 'completed': return 'default';
        case 'cancelled': return 'destructive';
        default: return 'outline';
    }
}

function trackDelivery(endpoint: any) {
    // Demo tracking function
    alert(`Tracking delivery for ${endpoint.name} - Invoice #${endpoint.invoice.id}`);
    showEndpointModal.value = false;
}
</script> 