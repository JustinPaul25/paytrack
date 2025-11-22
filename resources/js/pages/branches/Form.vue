<template>
  <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <Label for="name">Branch Name *</Label>
        <Input id="name" v-model="form.name" required />
        <InputError :message="form.errors.name" />
      </div>
      <div>
        <Label for="code">Branch Code *</Label>
        <Input id="code" v-model="form.code" placeholder="e.g., BR001" required />
        <InputError :message="form.errors.code" />
      </div>
    </div>

    <div>
      <Label for="address">Address *</Label>
      <textarea id="address" v-model="form.address" class="w-full rounded border px-3 py-2 mt-1" rows="3" required />
      <InputError :message="form.errors.address" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <Label for="phone">Phone</Label>
        <PhoneInput 
          id="phone" 
          v-model="form.phone" 
          placeholder="XXXXXXXXXX"
        />
        <p class="text-xs text-muted-foreground mt-1">Enter 10-digit Philippine mobile number.</p>
        <InputError :message="form.errors.phone" />
      </div>
      <div>
        <Label for="email">Email</Label>
        <Input id="email" v-model="form.email" type="email" />
        <InputError :message="form.errors.email" />
      </div>
    </div>

    <div>
      <Label for="description">Description</Label>
      <textarea id="description" v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" rows="3" />
      <InputError :message="form.errors.description" />
    </div>

    <div>
      <Label for="status">Status *</Label>
      <Select v-model="form.status" :options="statusOptions" required />
      <InputError :message="form.errors.status" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <Label for="opening_time">Opening Time</Label>
        <Input id="opening_time" v-model="form.opening_time" type="time" />
        <InputError :message="form.errors.opening_time" />
      </div>
      <div>
        <Label for="closing_time">Closing Time</Label>
        <Input id="closing_time" v-model="form.closing_time" type="time" />
        <InputError :message="form.errors.closing_time" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <Label for="manager_name">Manager Name</Label>
        <Input id="manager_name" v-model="form.manager_name" />
        <InputError :message="form.errors.manager_name" />
      </div>
      <div>
        <Label for="manager_phone">Manager Phone</Label>
        <PhoneInput 
          id="manager_phone" 
          v-model="form.manager_phone" 
          placeholder="XXXXXXXXXX"
        />
        <p class="text-xs text-muted-foreground mt-1">Enter 10-digit Philippine mobile number.</p>
        <InputError :message="form.errors.manager_phone" />
      </div>
      <div>
        <Label for="manager_email">Manager Email</Label>
        <Input id="manager_email" v-model="form.manager_email" type="email" />
        <InputError :message="form.errors.manager_email" />
      </div>
    </div>

    <div>
      <Label for="location">Location</Label>
      <LocationInput v-model="form.location" />
      <InputError :message="form.errors.location" />
    </div>

    <div>
      <Label for="branch_image">Branch Image</Label>
      <Input id="branch_image" type="file" accept="image/*" @change="onFileChange" />
      <InputError :message="form.errors.branch_image" />
      <div v-if="branchImageUrl" class="mt-2">
        <img :src="branchImageUrl" alt="Branch Image" class="w-24 h-24 object-cover rounded" />
      </div>
    </div>

    <div class="flex gap-2 justify-end">
      <Button type="submit" variant="default">{{ form.branchId ? 'Update Branch' : 'Create Branch' }}</Button>
      <slot name="footer" />
    </div>
  </form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import PhoneInput from '@/components/ui/input/PhoneInput.vue';
import Select from '@/components/ui/select/Select.vue';
import { Button } from '@/components/ui/button';
import LocationInput from '@/components/ui/input/LocationInput.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  branch: Object,
  branchImageUrl: String
});

const statusOptions = [
  { value: '', label: 'Select Status' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'maintenance', label: 'Maintenance' }
];

// Ensure location data is properly formatted
const getLocationData = () => {
  if (props.branch?.location) {
    const loc = props.branch.location;
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
  name: props.branch?.name || '',
  code: props.branch?.code || '',
  address: props.branch?.address || '',
  phone: props.branch?.phone || '',
  email: props.branch?.email || '',
  description: props.branch?.description || '',
  status: props.branch?.status || 'active',
  manager_name: props.branch?.manager_name || '',
  manager_phone: props.branch?.manager_phone || '',
  manager_email: props.branch?.manager_email || '',
  opening_time: props.branch?.opening_time || '',
  closing_time: props.branch?.closing_time || '',
  branch_image: null as File | null,
  branchId: props.branch?.id || null,
  location: getLocationData()
});

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.branch_image = target.files[0];
  } else {
    form.branch_image = null;
  }
}


function submit() {
  if (form.branchId) {
    form.post(route('branches.update', form.branchId), {
      forceFormData: true,
      onSuccess: () => {
        form.reset('branch_image');
        Swal.fire({
          title: 'Success!',
          text: 'Branch updated successfully.',
          icon: 'success',
          confirmButtonColor: '#8f5be8',
        });
      },
      onError: () => {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to update branch. Please check the form and try again.',
          icon: 'error',
          confirmButtonColor: '#8f5be8',
        });
      }
    });
  } else {
    form.post(route('branches.store'), {
      forceFormData: true,
      onSuccess: () => {
        form.reset('branch_image');
        Swal.fire({
          title: 'Success!',
          text: 'Branch created successfully.',
          icon: 'success',
          confirmButtonColor: '#8f5be8',
        });
      },
      onError: () => {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to create branch. Please check the form and try again.',
          icon: 'error',
          confirmButtonColor: '#8f5be8',
        });
      }
    });
  }
}
</script> 