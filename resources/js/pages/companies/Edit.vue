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
  bg_light: null,
  bg_dark: null,
  logo_light: null,
  logo_dark: null,
  remove_logo_light: false,
  remove_logo_dark: false,
  remove_bg_light: false,
  remove_bg_dark: false,
  user_ids: [...props.assigned_user_ids],
})

function submit() {
  const originalTransform = form.transform;
  form.transform((data) => {
    const payload = { ...data, _method: 'put' }
    // Omit false remove flags to avoid accidental deletion
    if (!payload.remove_logo) delete payload.remove_logo
    if (!payload.remove_bg_light) delete payload.remove_bg_light
    if (!payload.remove_bg_dark) delete payload.remove_bg_dark
    // Omit files if not selected
    if (!payload.logo_light) delete payload.logo_light
    if (!payload.logo_dark) delete payload.logo_dark
    if (!payload.bg_light) delete payload.bg_light
    if (!payload.bg_dark) delete payload.bg_dark
    return payload
  });
  form.post(`/companies/${props.company.id}`, {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      form.transform = originalTransform;
    },
  })
}

function onLogoLightChange(e) {
  const file = e.target?.files?.[0] ?? null
  form.logo_light = file
  if (file) form.remove_logo_light = true
}

function onLogoDarkChange(e) {
  const file = e.target?.files?.[0] ?? null
  form.logo_dark = file
  if (file) form.remove_logo_dark = true
}

function onBgLightChange(e) {
  const file = e.target?.files?.[0] ?? null
  form.bg_light = file
  if (file) form.remove_bg_light = true
}

function onBgDarkChange(e) {
  const file = e.target?.files?.[0] ?? null
  form.bg_dark = file
  if (file) form.remove_bg_dark = true
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field>
            <FieldLabel for="logo_light">Logo (Light Mode)</FieldLabel>
            <input
              id="logo_light"
              name="logo_light"
              type="file"
              accept="image/*"
              @change="onLogoLightChange"
              class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200"
            />
            <div v-if="props.company?.logo_light && !form.remove_logo_light" class="mt-2">
              <img :src="props.company.logo_light" alt="Current light logo" class="h-16 w-24 object-contain bg-white" />
            </div>
            <div v-else-if="form.remove_logo_light" class="mt-2 text-sm text-neutral-500">Light logo will be removed.</div>
            <div class="mt-2">
              <UiButton v-if="props.company?.logo_light && !form.remove_logo_light" type="button" variant="outline" size="sm" @click="() => { form.remove_logo_light = true; form.logo_light = null }">Remove current light logo</UiButton>
            </div>
            <FieldError v-if="form.errors.logo_light">{{ form.errors.logo_light }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="logo_dark">Logo (Dark Mode)</FieldLabel>
            <input
              id="logo_dark"
              name="logo_dark"
              type="file"
              accept="image/*"
              @change="onLogoDarkChange"
              class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200"
            />
            <div v-if="props.company?.logo_dark && !form.remove_logo_dark" class="mt-2">
              <img :src="props.company.logo_dark" alt="Current dark logo" class="h-16 w-24 object-contain bg-white" />
            </div>
            <div v-else-if="form.remove_logo_dark" class="mt-2 text-sm text-neutral-500">Dark logo will be removed.</div>
            <div class="mt-2">
              <UiButton v-if="props.company?.logo_dark && !form.remove_logo_dark" type="button" variant="outline" size="sm" @click="() => { form.remove_logo_dark = true; form.logo_dark = null }">Remove current dark logo</UiButton>
            </div>
            <FieldError v-if="form.errors.logo_dark">{{ form.errors.logo_dark }}</FieldError>
          </Field>
        </div>

        <Field>
          <FieldLabel for="bg_light">Background (Light)</FieldLabel>
          <input
            id="bg_light"
            name="bg_light"
            type="file"
            accept="image/*"
            @change="onBgLightChange"
            class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200"
          />
          <div v-if="props.company?.bg_light && !form.remove_bg_light" class="mt-2">
            <img :src="props.company.bg_light" alt="Current light background" class="h-24 w-full rounded object-cover" />
          </div>
          <div v-else-if="form.remove_bg_light" class="mt-2 text-sm text-neutral-500">Light background will be removed.</div>
          <div class="mt-2">
            <UiButton v-if="props.company?.bg_light && !form.remove_bg_light" type="button" variant="outline" size="sm" @click="() => { form.remove_bg_light = true; form.bg_light = null }">Remove current light background</UiButton>
          </div>
          <FieldError v-if="form.errors.bg_light">{{ form.errors.bg_light }}</FieldError>
        </Field>

        <Field>
          <FieldLabel for="bg_dark">Background (Dark)</FieldLabel>
          <input
            id="bg_dark"
            name="bg_dark"
            type="file"
            accept="image/*"
            @change="onBgDarkChange"
            class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-neutral-700 hover:file:bg-neutral-200"
          />
          <div v-if="props.company?.bg_dark && !form.remove_bg_dark" class="mt-2">
            <img :src="props.company.bg_dark" alt="Current dark background" class="h-24 w-full rounded object-cover" />
          </div>
          <div v-else-if="form.remove_bg_dark" class="mt-2 text-sm text-neutral-500">Dark background will be removed.</div>
          <div class="mt-2">
            <UiButton v-if="props.company?.bg_dark && !form.remove_bg_dark" type="button" variant="outline" size="sm" @click="() => { form.remove_bg_dark = true; form.bg_dark = null }">Remove current dark background</UiButton>
          </div>
          <FieldError v-if="form.errors.bg_dark">{{ form.errors.bg_dark }}</FieldError>
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
