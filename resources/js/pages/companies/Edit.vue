<script setup>
import { watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import UiButton from '@/components/ui/button/Button.vue'
import UiInput from '@/components/ui/input/Input.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Field, FieldLabel, FieldError, FieldDescription } from '@/components/ui/field'
import MultiSelect from '@/components/MultiSelect.vue'

const props = defineProps({
  company: { type: Object, required: true },
  users: { type: Array, default: () => [] },
  assigned_user_ids: { type: Array, default: () => [] },
})

const form = useForm({
  name: props.company.name || '',
  slug: props.company.slug || '',
  website: props.company.website || '',
  linkedin: props.company.linkedin || '',
  twitter: props.company.twitter || '',
  facebook: props.company.facebook || '',
  instagram: props.company.instagram || '',
  phone: props.company.phone || '',
  email: props.company.email || '',
  address: props.company.address || '',
  city: props.company.city || '',
  state: props.company.state || '',
  country: props.company.country || '',
  cover_photo: null,
  user_ids: [...props.assigned_user_ids],
})

function submit() {
  const originalTransform = form.transform;
  form.transform((data) => ({ ...data, _method: 'put' }));
  form.post(`/companies/${props.company.id}`, {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      form.transform = originalTransform;
    },
  })
}

const breadcrumbs = [
  { title: 'Companies', href: '/companies' },
  { title: 'Edit Company', href: `/companies/${props.company.id}/edit` },
]

function slugify(v) {
  return (v || '')
    .toString()
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)+/g, '')
}

watch(() => form.name, (v) => {
  form.slug = slugify(v)
})
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Edit ${form.name || 'Company'}`" />
    <div class="p-6 space-y-6">
      <form class="space-y-4" @submit.prevent="submit">
        <Field>
          <FieldLabel for="name">Name</FieldLabel>
          <UiInput id="name" v-model="form.name" />
          <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
        </Field>

        <Field>
          <FieldLabel for="slug">Slug</FieldLabel>
          <UiInput id="slug" v-model="form.slug" />
          <FieldError v-if="form.errors.slug">{{ form.errors.slug }}</FieldError>
        </Field>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field>
            <FieldLabel for="website">Website</FieldLabel>
            <UiInput id="website" v-model="form.website" />
            <FieldError v-if="form.errors.website">{{ form.errors.website }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="email">Email</FieldLabel>
            <UiInput id="email" type="email" v-model="form.email" />
            <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
          </Field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field>
            <FieldLabel for="phone">Phone</FieldLabel>
            <UiInput id="phone" v-model="form.phone" />
            <FieldError v-if="form.errors.phone">{{ form.errors.phone }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="linkedin">LinkedIn</FieldLabel>
            <UiInput id="linkedin" v-model="form.linkedin" />
            <FieldError v-if="form.errors.linkedin">{{ form.errors.linkedin }}</FieldError>
          </Field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field>
            <FieldLabel for="twitter">Twitter</FieldLabel>
            <UiInput id="twitter" v-model="form.twitter" />
            <FieldError v-if="form.errors.twitter">{{ form.errors.twitter }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="facebook">Facebook</FieldLabel>
            <UiInput id="facebook" v-model="form.facebook" />
            <FieldError v-if="form.errors.facebook">{{ form.errors.facebook }}</FieldError>
          </Field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field>
            <FieldLabel for="instagram">Instagram</FieldLabel>
            <UiInput id="instagram" v-model="form.instagram" />
            <FieldError v-if="form.errors.instagram">{{ form.errors.instagram }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="address">Address</FieldLabel>
            <UiInput id="address" v-model="form.address" />
            <FieldError v-if="form.errors.address">{{ form.errors.address }}</FieldError>
          </Field>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Field>
            <FieldLabel for="city">City</FieldLabel>
            <UiInput id="city" v-model="form.city" />
            <FieldError v-if="form.errors.city">{{ form.errors.city }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="state">State</FieldLabel>
            <UiInput id="state" v-model="form.state" />
            <FieldError v-if="form.errors.state">{{ form.errors.state }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="country">Country</FieldLabel>
            <UiInput id="country" v-model="form.country" />
            <FieldError v-if="form.errors.country">{{ form.errors.country }}</FieldError>
          </Field>
        </div>

        <Field>
          <FieldLabel for="cover_photo">Cover photo</FieldLabel>
          <input
            id="cover_photo"
            name="cover_photo"
            type="file"
            accept="image/*"
            @change="(e) => (form.cover_photo = e.target?.files?.[0] ?? null)"
            class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200"
          />
          <div v-if="props.company?.cover_photo" class="mt-2">
            <img :src="props.company.cover_photo" alt="Current cover" class="h-24 w-full rounded object-cover" />
          </div>
          <FieldError v-if="form.errors.cover_photo">{{ form.errors.cover_photo }}</FieldError>
        </Field>

        <Field>
          <MultiSelect
            v-model="form.user_ids"
            :options="props.users.map(u => ({ value: u.id, label: `${u.name} (${u.email})` }))"
            label="Assign Users"
            :error="form.errors.user_ids"
            description="Select one or more users to add to this company"
          />
        </Field>

        <div class="flex items-center gap-2">
          <UiButton type="submit" :disabled="form.processing">Save</UiButton>
          <Link href="/companies">
            <UiButton variant="outline" type="button">Cancel</UiButton>
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
