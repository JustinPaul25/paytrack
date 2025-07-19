<template>
  <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="name">Name</Label>
        <Input id="name" v-model="form.name" required />
        <InputError :message="form.errors.name" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="company_name">Company Name</Label>
        <Input id="company_name" v-model="form.company_name" />
        <InputError :message="form.errors.company_name" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" type="email" required />
        <InputError :message="form.errors.email" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="phone">Phone</Label>
        <Input id="phone" v-model="form.phone" />
        <InputError :message="form.errors.phone" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="address">Address</Label>
        <textarea id="address" v-model="form.address" class="w-full rounded border px-3 py-2 mt-1" rows="3" />
        <InputError :message="form.errors.address" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="location">Location</Label>
        <LocationInput v-model="form.location" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="profile_image">Profile Image</Label>
        <Input id="profile_image" type="file" accept="image/*" @change="onFileChange" />
        <InputError :message="form.errors.profile_image" />
        <div v-if="profileImageUrl" class="mt-2">
          <img :src="profileImageUrl" alt="Profile Image" class="w-24 h-24 object-cover rounded-full" />
        </div>
      </div>
    </div>
    <CardFooter class="flex gap-2 justify-end">
      <Button type="submit" variant="default">{{ form.customerId ? 'Update Customer' : 'Create Customer' }}</Button>
      <slot name="footer" />
    </CardFooter>
  </form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import { Button } from '@/components/ui/button';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import LocationInput from '@/components/ui/input/LocationInput.vue';

const props = defineProps({
  customer: Object,
  profileImageUrl: String
});



// Ensure location data is properly formatted
const getLocationData = () => {
  if (props.customer?.location) {
    const loc = props.customer.location;
    // Handle both object and string formats
    if (typeof loc === 'string') {
      try {
        return JSON.parse(loc);
      } catch {
        return { lat: 0, lng: 0 };
      }
    } else if (typeof loc === 'object' && loc !== null) {
      // Ensure lat and lng are numbers
      const lat = parseFloat(loc.lat);
      const lng = parseFloat(loc.lng);
      if (!isNaN(lat) && !isNaN(lng)) {
        return { lat, lng };
      }
    }
  }
  return { lat: 0, lng: 0 };
};

const form = useForm({
  name: props.customer?.name || '',
  company_name: props.customer?.company_name || '',
  email: props.customer?.email || '',
  phone: props.customer?.phone || '',
  address: props.customer?.address || '',
  profile_image: null as File | null,
  customerId: props.customer?.id || null,
  location: getLocationData()
});



function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.profile_image = target.files[0];
  } else {
    form.profile_image = null;
  }
}

function submit() {
  if (form.customerId) {
    form.post(route('customers.update', form.customerId), {
      forceFormData: true,
      onSuccess: () => form.reset('profile_image')
    });
  } else {
    form.post(route('customers.store'), {
      forceFormData: true,
      onSuccess: () => form.reset('profile_image')
    });
  }
}
</script> 