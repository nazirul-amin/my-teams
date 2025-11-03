<script setup>
import { ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import UiButton from '@/components/ui/button/Button.vue'
import UiInput from '@/components/ui/input/Input.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Field, FieldLabel, FieldError } from '@/components/ui/field'
import MultiSelect from '@/components/MultiSelect.vue'

const breadcrumbs = [
  { title: 'Teams', href: '/teams' },
  { title: 'Create Team', href: '/teams/create' },
]

const props = defineProps({
  companies: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
})

const form = useForm({
  company_id: '',
  name: '',
  slug: '',
  photo: '',
  cover_photo: '',
  user_ids: [],
})

const assignableUsers = ref(props.users)

function submit() {
  form.post('/teams', { preserveScroll: true })
}

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

watch(() => form.company_id, async (companyId) => {
  form.user_ids = []
  assignableUsers.value = []
  if (!companyId) return
  try {
    const res = await fetch(`/companies/${companyId}/users`, { headers: { 'Accept': 'application/json' } })
    if (res.ok) {
      assignableUsers.value = await res.json()
    }
  } catch (_) {}
})
</script>

<template>
  <Head title="Create Team" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <form class="space-y-4" @submit.prevent="submit">
        <Field>
          <FieldLabel for="company_id">Company</FieldLabel>
          <select id="company_id" v-model="form.company_id" class="w-full border rounded px-3 py-2">
            <option value="" disabled>Select a company</option>
            <option v-for="c in props.companies" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <FieldError v-if="form.errors.company_id">{{ form.errors.company_id }}</FieldError>
        </Field>

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
            <FieldLabel for="photo">Photo URL</FieldLabel>
            <UiInput id="photo" v-model="form.photo" />
            <FieldError v-if="form.errors.photo">{{ form.errors.photo }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="cover_photo">Cover Photo URL</FieldLabel>
            <UiInput id="cover_photo" v-model="form.cover_photo" />
            <FieldError v-if="form.errors.cover_photo">{{ form.errors.cover_photo }}</FieldError>
          </Field>
        </div>

        <Field>
          <MultiSelect
            v-model="form.user_ids"
            :options="assignableUsers.map(u => ({ value: u.id, label: `${u.name} (${u.email})` }))"
            label="Assign Users"
            :error="form.errors.user_ids"
            description="Select one or more users to add to this team"
          />
        </Field>

        <div class="flex items-center gap-2">
          <UiButton type="submit" :disabled="form.processing">Create</UiButton>
          <Link href="/teams">
            <UiButton variant="outline" type="button">Cancel</UiButton>
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
