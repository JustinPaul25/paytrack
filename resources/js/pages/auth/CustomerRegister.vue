<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff, ChevronRight, ChevronLeft, Check } from 'lucide-vue-next';
import { ref } from 'vue';
import LocationInput from '@/components/ui/input/LocationInput.vue';

const form = useForm({
    // Step 1: Basic Information
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    
    // Step 2: Contact Information
    company_name: '',
    phone: '',
    
    // Step 3: Location & Address
    address: '',
    location: { lat: 0, lng: 0 },
    
    // Step 4: Profile Picture
    profile_image: null as File | null,
});

const currentStep = ref(1);
const totalSteps = 4;
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const profileImageUrl = ref<string | null>(null);

const steps = [
    { number: 1, title: 'Basic Information' },
    { number: 2, title: 'Contact Information' },
    { number: 3, title: 'Location & Address' },
    { number: 4, title: 'Profile Picture' },
];

const canGoNext = () => {
    if (currentStep.value === 1) {
        return form.name && form.email && form.password && form.password_confirmation;
    }
    if (currentStep.value === 2) {
        return true; // All fields optional in step 2
    }
    if (currentStep.value === 3) {
        return true; // All fields optional in step 3
    }
    if (currentStep.value === 4) {
        return true; // Profile picture is optional
    }
    return false;
};

const nextStep = () => {
    if (currentStep.value < totalSteps && canGoNext()) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        const isValidType = ['image/jpeg', 'image/png'].includes(file.type);
        const isValidSize = file.size <= 2 * 1024 * 1024; // 2MB
        if (!isValidType || !isValidSize) {
            form.profile_image = null;
            target.value = '';
            const reason = !isValidType ? 'Please select a JPG or PNG image.' : 'File is too large. Max size is 2MB.';
            window.alert(reason);
            return;
        }
        form.profile_image = file;
        profileImageUrl.value = URL.createObjectURL(file);
    } else {
        form.profile_image = null;
        profileImageUrl.value = null;
    }
}

function onPhoneInput(e: Event) {
    const target = e.target as HTMLInputElement;
    let value = target.value;
    
    // Remove all non-digit and non-plus characters
    value = value.replace(/[^0-9+]/g, '');
    
    // If starts with +, ensure it's +63
    if (value.startsWith('+')) {
        if (value.length > 1 && !value.startsWith('+63')) {
            value = '+63' + value.substring(1).replace(/[^0-9]/g, '');
        }
        // Limit to +639XXXXXXXXX (13 chars: +639 + 9 digits)
        if (value.length > 13) {
            value = value.substring(0, 13);
        }
    } else {
        // If starts with 0, ensure it's 09
        if (value.length > 0 && value[0] === '0' && value.length > 1 && value[1] !== '9') {
            value = '09' + value.substring(2).replace(/[^0-9]/g, '');
        }
        // If starts with 63, convert to +63
        if (value.startsWith('63')) {
            value = '+' + value;
        }
        // Limit to 11 digits for 09XXXXXXXXX format
        if (value.length > 11 && !value.startsWith('+')) {
            value = value.substring(0, 11);
        }
    }
    
    // Update the input and form
    if (value !== target.value) {
        target.value = value;
    }
    form.phone = value;
}

const submit = () => {
    form.post(route('customer.register'), {
        forceFormData: true,
        onFinish: () => form.reset('password', 'password_confirmation', 'profile_image'),
    });
};
</script>

<template>
    <Head title="Register as Customer" />
    
    <div class="flex min-h-screen">
        <!-- Left Section - Registration Form -->
        <div class="flex w-full lg:w-3/5 items-center justify-center bg-background px-8 py-12 overflow-y-auto">
            <div class="w-full max-w-2xl">
                <!-- Logo -->
                <div class="mb-8 flex justify-center">
                    <div class="flex h-16 w-auto items-center justify-center">
                        <img src="/img/paytracklogo.png" alt="PayTrack Logo" class="h-full w-auto max-w-full" />
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-foreground mb-2">Create Your Account</h1>
                    <p class="text-sm text-muted-foreground">Fill in your details to get started</p>
                </div>

                <!-- Stepper Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            v-for="(step, index) in steps"
                            :key="step.number"
                            class="flex items-center flex-1"
                        >
                            <div class="flex items-center">
                                <div
                                    :class="[
                                        'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors',
                                        currentStep > step.number
                                            ? 'bg-primary border-primary text-primary-foreground'
                                            : currentStep === step.number
                                            ? 'bg-primary border-primary text-primary-foreground'
                                            : 'bg-background border-muted text-muted-foreground'
                                    ]"
                                >
                                    <Check v-if="currentStep > step.number" class="h-5 w-5" />
                                    <span v-else class="text-sm font-medium">{{ step.number }}</span>
                                </div>
                                <div class="ml-3 hidden sm:block">
                                    <div
                                        :class="[
                                            'text-sm font-medium',
                                            currentStep >= step.number ? 'text-foreground' : 'text-muted-foreground'
                                        ]"
                                    >
                                        {{ step.title }}
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                :class="[
                                    'flex-1 h-0.5 mx-4 transition-colors',
                                    currentStep > step.number ? 'bg-primary' : 'bg-muted'
                                ]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Registration Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Step 1: Basic Information -->
                    <div v-show="currentStep === 1" class="space-y-6">
                        <div>
                            <Label for="name" class="text-sm font-medium text-foreground mb-2 block">Full Name *</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                v-model="form.name"
                                placeholder="Enter your full name"
                                class="w-full"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div>
                            <Label for="email" class="text-sm font-medium text-foreground mb-2 block">Email Address *</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autocomplete="email"
                                v-model="form.email"
                                placeholder="Enter your email address"
                                class="w-full"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div>
                            <Label for="password" class="text-sm font-medium text-foreground mb-2 block">Password *</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    v-model="form.password"
                                    placeholder="Enter your password"
                                    class="w-full pr-12"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <Eye v-if="showPassword" class="h-5 w-5" />
                                    <EyeOff v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <div>
                            <Label for="password_confirmation" class="text-sm font-medium text-foreground mb-2 block">Confirm Password *</Label>
                            <div class="relative">
                                <Input
                                    id="password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    v-model="form.password_confirmation"
                                    placeholder="Confirm your password"
                                    class="w-full pr-12"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <Eye v-if="showPasswordConfirmation" class="h-5 w-5" />
                                    <EyeOff v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <InputError :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <!-- Step 2: Contact Information -->
                    <div v-show="currentStep === 2" class="space-y-6">
                        <div>
                            <Label for="company_name" class="text-sm font-medium text-foreground mb-2 block">Company Name (Optional)</Label>
                            <Input
                                id="company_name"
                                type="text"
                                autocomplete="organization"
                                v-model="form.company_name"
                                placeholder="Enter your company name"
                                class="w-full"
                            />
                            <p class="text-xs text-muted-foreground mt-1">Add if you represent a company.</p>
                            <InputError :message="form.errors.company_name" />
                        </div>

                        <div>
                            <Label for="phone" class="text-sm font-medium text-foreground mb-2 block">Phone Number (Optional)</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                                inputmode="tel"
                                pattern="^(?:\+?63|0)9\d{9}$"
                                maxlength="13"
                                @input="onPhoneInput"
                                class="w-full"
                            />
                            <p class="text-xs text-muted-foreground mt-1">Philippine mobile number format only (09XXXXXXXXX or +639XXXXXXXXX).</p>
                            <InputError :message="form.errors.phone" />
                        </div>
                    </div>

                    <!-- Step 3: Location & Address -->
                    <div v-show="currentStep === 3" class="space-y-6">
                        <div>
                            <Label for="address" class="text-sm font-medium text-foreground mb-2 block">Address (Optional)</Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                placeholder="Street, city, state"
                                class="w-full rounded border px-3 py-2 mt-1"
                                rows="3"
                            />
                            <p class="text-xs text-muted-foreground mt-1">Helps with delivery and invoices.</p>
                            <InputError :message="form.errors.address" />
                        </div>

                        <div>
                            <Label for="location" class="text-sm font-medium text-foreground mb-2 block">Location (Optional)</Label>
                            <LocationInput v-model="form.location" />
                            <InputError :message="form.errors.location" />
                        </div>
                    </div>

                    <!-- Step 4: Profile Picture -->
                    <div v-show="currentStep === 4" class="space-y-6">
                        <div>
                            <Label for="profile_image" class="text-sm font-medium text-foreground mb-2 block">Profile Picture (Optional)</Label>
                            <Input
                                id="profile_image"
                                type="file"
                                accept="image/jpeg,image/png"
                                @change="onFileChange"
                                class="w-full"
                            />
                            <p class="text-xs text-muted-foreground mt-1">Max 2MB, JPG or PNG.</p>
                            <InputError :message="form.errors.profile_image" />
                            <div v-if="profileImageUrl" class="mt-4">
                                <img :src="profileImageUrl" alt="Profile Preview" class="w-32 h-32 object-cover rounded-full border-2 border-border" />
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center pt-6">
                        <Button
                            v-if="currentStep > 1"
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            class="flex items-center gap-2"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Previous
                        </Button>
                        <div v-else></div>

                        <div class="flex gap-2">
                            <Button
                                v-if="currentStep < totalSteps"
                                type="button"
                                @click="nextStep"
                                :disabled="!canGoNext()"
                                class="flex items-center gap-2"
                            >
                                Next
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                            <Button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="flex items-center gap-2"
                            >
                                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                Create Account
                            </Button>
                        </div>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center text-sm text-muted-foreground pt-4">
                        Already have an account?
                        <TextLink :href="route('login')" class="underline underline-offset-4">Log in</TextLink>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Section - Registration Image -->
        <div class="hidden lg:flex lg:w-2/5 relative overflow-hidden">
            <img 
                src="/img/login_img.jpg" 
                alt="Registration" 
                class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-black/10"></div>
        </div>
    </div>
</template>

