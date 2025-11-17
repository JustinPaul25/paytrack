<template>
  <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="name">Full name</Label>
        <Input id="name" v-model="form.name" placeholder="e.g., Aisha Bello" required />
        <p class="text-xs text-muted-foreground mt-1">This is the customer’s name.</p>
        <InputError :message="form.errors.name" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="company_name">Company (optional)</Label>
        <Input id="company_name" v-model="form.company_name" placeholder="e.g., Bright Ventures Ltd." />
        <p class="text-xs text-muted-foreground mt-1">Add if this customer represents a company.</p>
        <InputError :message="form.errors.company_name" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="email">Email address</Label>
        <Input id="email" v-model="form.email" type="email" placeholder="name@company.com" required />
        <p class="text-xs text-muted-foreground mt-1">We’ll use this to send receipts.</p>
        <InputError :message="form.errors.email" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="phone">Phone number (optional)</Label>
        <Input
          id="phone"
          v-model="form.phone"
          placeholder="09XXXXXXXXX or +639XXXXXXXXX"
          inputmode="tel"
          pattern="^(?:\+?63|0)9\d{9}$"
          maxlength="13"
          @input="onPhoneInput"
        />
        <p class="text-xs text-muted-foreground mt-1">Philippine mobile number format only (09XXXXXXXXX or +639XXXXXXXXX).</p>
        <InputError :message="form.errors.phone" />
      </div>
    </div>
    <div class="flex gap-4">
      <div class="flex-1">
        <Label for="address">Address</Label>
        <textarea id="address" v-model="form.address" placeholder="Street, city, state" class="w-full rounded border px-3 py-2 mt-1" rows="3" />
        <p class="text-xs text-muted-foreground mt-1">Helps with delivery and invoices.</p>
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
        <Label for="profile_image">Profile picture (optional)</Label>
        <Input id="profile_image" type="file" accept="image/jpeg,image/png" @change="onFileChange" />
        <p class="text-xs text-muted-foreground mt-1">Max 2MB, JPG or PNG.</p>
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
  } else {
    form.profile_image = null;
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