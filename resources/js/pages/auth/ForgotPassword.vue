<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot password" />
    
    <div class="flex min-h-screen">
        <!-- Left Section - Form -->
        <div class="flex w-full lg:w-3/5 items-center justify-center bg-background px-8 py-12">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="mb-8 flex justify-center">
                    <div class="flex h-16 w-auto items-center justify-center">
                        <img src="/img/paytracklogo.png" alt="PayTrack Logo" class="h-full w-auto max-w-full" />
                    </div>
                </div>

                <!-- Title and Description -->
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-foreground mb-2">Forgot password</h1>
                    <p class="text-muted-foreground">Enter your email to receive a password reset link</p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-6 text-center text-sm font-medium text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                    {{ status }}
                </div>

                <!-- Form -->
                <div class="space-y-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-4">
                            <div>
                                <Label for="email" class="text-sm font-medium text-foreground mb-2 block">Email address</Label>
                                <Input 
                                    id="email" 
                                    type="email" 
                                    name="email" 
                                    autocomplete="email" 
                                    v-model="form.email" 
                                    autofocus 
                                    placeholder="Enter your email address"
                                    class="w-full"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <Button class="w-full" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                                Email password reset link
                            </Button>
                        </div>
                    </form>

                    <div class="text-center text-sm text-muted-foreground">
                        <span>Or, return to </span>
                        <TextLink :href="route('login')" class="text-primary hover:text-primary/80 transition-colors">log in</TextLink>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section - Abstract Geometric Design -->
        <div class="hidden lg:flex lg:w-2/5 bg-gradient-to-br from-primary/20 via-primary/30 to-primary/40 dark:from-primary/40 dark:via-primary/50 dark:to-primary/60 relative overflow-hidden">
            <!-- Geometric Shapes -->
            <div class="absolute inset-0">
                <!-- Top Left - Light Purple U-shape -->
                <div class="absolute top-8 left-8 w-32 h-32 bg-primary/30 rounded-full opacity-60"></div>
                
                <!-- Top Middle - Dark Blue Rectangle with Dots -->
                <div class="absolute top-16 left-1/3 w-24 h-16 bg-primary/60 rounded-lg opacity-80">
                    <div class="grid grid-cols-6 gap-1 p-2">
                        <div v-for="i in 24" :key="i" class="w-1 h-1 bg-background rounded-full opacity-60"></div>
                    </div>
                </div>
                
                <!-- Orange and Red Diamonds -->
                <div class="absolute top-32 left-1/3 flex space-x-2">
                    <div class="w-6 h-6 bg-orange-400 transform rotate-45"></div>
                    <div class="w-6 h-6 bg-red-500 transform rotate-45"></div>
                </div>
                <div class="absolute top-40 left-1/3 flex space-x-2">
                    <div class="w-6 h-6 bg-red-500 transform rotate-45"></div>
                    <div class="w-6 h-6 bg-orange-400 transform rotate-45"></div>
                </div>
                
                <!-- Horizontal Bar with Lines -->
                <div class="absolute top-48 left-1/4 w-32 h-2 bg-primary/50 rounded">
                    <div class="flex space-x-1 p-1">
                        <div v-for="i in 20" :key="i" class="w-0.5 h-full bg-background opacity-40"></div>
                    </div>
                </div>
                
                <!-- Top Right - Purple Cubes -->
                <div class="absolute top-8 right-8">
                    <div class="flex space-x-1">
                        <div class="w-8 h-8 bg-primary/40 transform rotate-45"></div>
                        <div class="w-8 h-8 bg-primary/60 transform rotate-45"></div>
                        <div class="w-8 h-8 bg-primary/80 transform rotate-45"></div>
                    </div>
                </div>
                
                <!-- Concentric Circles -->
                <div class="absolute top-24 right-16">
                    <div class="w-16 h-16 border-4 border-primary/40 rounded-full opacity-60"></div>
                    <div class="absolute top-2 left-2 w-12 h-12 border-4 border-primary/30 rounded-full opacity-40"></div>
                </div>
                
                <!-- Mid-Left - Blue Triangles -->
                <div class="absolute top-1/3 left-8">
                    <div class="w-0 h-0 border-l-8 border-r-8 border-b-12 border-l-transparent border-r-transparent border-b-primary/40"></div>
                    <div class="w-0 h-0 border-l-8 border-r-8 border-b-12 border-l-transparent border-r-transparent border-b-primary/60 mt-2"></div>
                </div>
                
                <!-- Mid-Right - Purple Leaves -->
                <div class="absolute top-1/3 right-16">
                    <div class="flex flex-col space-y-2">
                        <div class="w-6 h-8 bg-primary/40 rounded-full transform rotate-45"></div>
                        <div class="w-6 h-8 bg-primary/60 rounded-full transform rotate-45"></div>
                        <div class="w-6 h-8 bg-primary/80 rounded-full transform rotate-45"></div>
                    </div>
                </div>
                
                <!-- White Star -->
                <div class="absolute top-1/2 right-8">
                    <svg class="w-6 h-6 text-background" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L15.09 8.26L22 9L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9L8.91 8.26L12 2Z"/>
                    </svg>
                </div>
                
                <!-- Grid Pattern -->
                <div class="absolute top-1/2 right-24 w-16 h-16 bg-primary/60 rounded">
                    <div class="grid grid-cols-4 gap-1 p-2">
                        <div v-for="i in 16" :key="i" class="w-2 h-2 bg-background rounded opacity-40"></div>
                    </div>
                </div>
                
                <!-- Bottom Left - Teal Rectangle -->
                <div class="absolute bottom-24 left-8 w-20 h-16 bg-teal-400 transform rotate-12"></div>
                
                <!-- Yellow Starburst -->
                <div class="absolute bottom-32 left-32">
                    <div class="w-12 h-12 bg-yellow-400 transform rotate-45"></div>
                </div>
                
                <!-- Grey Dots -->
                <div class="absolute bottom-16 left-1/3 flex space-x-2">
                    <div v-for="i in 4" :key="i" class="w-2 h-2 bg-muted-foreground rounded-full"></div>
                </div>
                
                <!-- White Spiky Shape -->
                <div class="absolute bottom-20 left-1/2">
                    <div class="w-8 h-8 bg-background opacity-60 transform rotate-45"></div>
                </div>
                
                <!-- Bottom Right - Dark Blue Semi-circle -->
                <div class="absolute bottom-8 right-8 w-24 h-24 bg-primary/60 rounded-full opacity-80"></div>
                
                <!-- Teal Square with Dots -->
                <div class="absolute bottom-16 right-24 w-12 h-12 bg-teal-500 rounded">
                    <div class="grid grid-cols-3 gap-1 p-2">
                        <div v-for="i in 9" :key="i" class="w-1 h-1 bg-background rounded-full opacity-60"></div>
                    </div>
                </div>
                
                <!-- Connecting Lines -->
                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M20 30 Q40 20 60 30 T100 30" stroke="currentColor" stroke-width="1" fill="none" class="text-primary/30"/>
                    <path d="M10 60 Q30 50 50 60 T90 60" stroke="currentColor" stroke-width="1" fill="none" class="text-primary/30"/>
                    <path d="M30 80 Q50 70 70 80 T110 80" stroke="currentColor" stroke-width="1" fill="none" class="text-primary/30"/>
                </svg>
            </div>
        </div>
    </div>
</template>
