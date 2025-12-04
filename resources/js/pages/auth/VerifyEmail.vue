<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, CheckCircle2 } from 'lucide-vue-next';
import { ref, onMounted, watch } from 'vue';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});
const showVerificationDialog = ref(false);

// Show dialog if user just registered (status is verification-link-sent)
onMounted(() => {
    if (props.status === 'verification-link-sent') {
        showVerificationDialog.value = true;
    }
});

// Watch for status changes (e.g., when resending email)
watch(() => props.status, (newStatus) => {
    if (newStatus === 'verification-link-sent') {
        showVerificationDialog.value = true;
    }
});

const submit = () => {
    form.post(route('verification.send'), {
        onSuccess: () => {
            showVerificationDialog.value = true;
        }
    });
};

const closeDialog = () => {
    showVerificationDialog.value = false;
};
</script>

<template>
    <AuthLayout title="Verify email" description="Please verify your email address by clicking on the link we just emailed to you.">
        <Head title="Email verification" />

        <!-- Verification Success Dialog -->
        <Dialog :open="showVerificationDialog" @update:open="showVerificationDialog = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center justify-center mb-4">
                        <div class="rounded-full bg-green-100 p-3">
                            <Mail class="h-8 w-8 text-green-600" />
                        </div>
                    </div>
                    <DialogTitle class="text-center text-xl">Check Your Email</DialogTitle>
                    <DialogDescription class="text-center pt-2">
                        We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                <p class="font-medium mb-1">What to do next:</p>
                                <ol class="list-decimal list-inside space-y-1 text-blue-700 dark:text-blue-300">
                                    <li>Check your email inbox (and spam folder)</li>
                                    <li>Click the verification link in the email</li>
                                    <li>You'll be automatically redirected to your dashboard</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex-col sm:flex-row gap-2">
                    <Button @click="closeDialog" variant="default" class="w-full sm:w-auto">
                        Got it!
                    </Button>
                    <Button @click="submit" variant="outline" :disabled="form.processing" class="w-full sm:w-auto">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Resend Email
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <div v-if="status === 'verification-link-sent' && !showVerificationDialog" class="mb-4 text-center text-sm font-medium text-green-600">
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form @submit.prevent="submit" class="space-y-6 text-center">
            <Button :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Resend verification email
            </Button>

            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm"> Log out </TextLink>
        </form>
    </AuthLayout>
</template>
