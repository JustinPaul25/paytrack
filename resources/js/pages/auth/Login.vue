<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />
    
    <div class="flex min-h-screen">
        <!-- Left Section - Login Form -->
        <div class="flex w-full lg:w-3/5 items-center justify-center bg-background px-8 py-12">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="mb-8 flex justify-center">
                    <div class="flex h-16 w-auto items-center justify-center">
                        <img src="/img/paytracklogo.png" alt="PayTrack Logo" class="h-full w-auto max-w-full" />
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-foreground mb-2">Welcome back!</h1>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-6 text-center text-sm font-medium text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <Label for="email" class="text-sm font-medium text-foreground mb-2 block">Email *</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="Enter your mail address"
                            class="w-full"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <Label for="password" class="text-sm font-medium text-foreground mb-2 block">Password *</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                v-model="form.password"
                                placeholder="Enter password"
                                class="w-full pr-12"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                            >
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Remember Me and Forgot Password -->
                    <div class="flex items-center justify-between">
                        <Label for="remember" class="flex items-center space-x-2 cursor-pointer">
                            <Checkbox 
                                id="remember" 
                                v-model="form.remember" 
                                class="text-primary focus:ring-primary"
                            />
                            <span class="text-sm text-foreground">Remember me</span>
                        </Label>
                        <TextLink 
                            v-if="canResetPassword" 
                            :href="route('password.request')" 
                            class="text-sm text-primary hover:text-primary/80 transition-colors"
                        >
                            Forgot your password?
                        </TextLink>
                    </div>

                    <!-- Login Button -->
                    <Button 
                        type="submit" 
                        class="w-full"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Log In
                    </Button>

                    <!-- Divider -->
                    <!-- <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-background px-2 text-muted-foreground">Or, Login with</span>
                        </div>
                    </div> -->

                    <!-- Google Login Button -->
                    <!-- <Button 
                        type="button" 
                        variant="outline"
                        class="w-full flex items-center justify-center space-x-2"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Sign up with google</span>
                    </Button> -->
                </form>
            </div>
        </div>

        <!-- Right Section - Login Image -->
        <div class="hidden lg:flex lg:w-2/5 relative overflow-hidden">
            <img 
                src="/img/login_img.jpg" 
                alt="Login" 
                class="w-full h-full object-cover"
            />
            <!-- Optional overlay for better text contrast if needed -->
            <div class="absolute inset-0 bg-black/10"></div>
        </div>
    </div>
</template>
