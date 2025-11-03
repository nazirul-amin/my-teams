<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import UiButton from '@/components/ui/button/Button.vue'
import UiInput from '@/components/ui/input/Input.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Field, FieldLabel, FieldError } from '@/components/ui/field'
import MultiSelect from '@/components/MultiSelect.vue'

const props = defineProps({
  team: { type: Object, required: true },
  companies: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  assigned_user_ids: { type: Array, default: () => [] },
})

const breadcrumbs = [
  { title: 'Teams', href: '/teams' },
  { title: 'Edit Team', href: `/teams/${props.team?.id}/edit` },
]

const form = useForm({
  company_id: props.team?.company_id ?? '',
  name: props.team?.name ?? '',
  slug: props.team?.slug ?? '',
  logo: props.team?.logo ?? '',
  user_ids: [...(props.assigned_user_ids ?? [])],
})

const assignableUsers = ref(props.users)

function submit() {
  form.put(`/teams/${props.team.id}`, { preserveScroll: true })
}

function destroyTeam() {
  if (confirm('Delete this team?')) {
    router.delete(`/teams/${props.team.id}`, { preserveScroll: true })
  }
}

watch(() => form.company_id, async (companyId) => {
  // Reset selected users when company changes and fetch new assignable users
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
  <Head :title="`Edit Team - ${props.team?.name ?? ''}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <form class="space-y-4" @submit.prevent="submit">
        <Field>
          <FieldLabel for="company_id">Company</FieldLabel>
          <select id="company_id" v-model="form.company_id" class="w-full border rounded px-3 py-2">
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
            <FieldLabel for="logo">Logo URL</FieldLabel>
            <UiInput id="logo" v-model="form.logo" />
            <FieldError v-if="form.errors.logo">{{ form.errors.logo }}</FieldError>
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
          <UiButton type="submit" :disabled="form.processing">Update</UiButton>
          <Link href="/teams">
            <UiButton variant="outline" type="button">Cancel</UiButton>
          </Link>
          <div class="ml-auto">
            <UiButton variant="destructive" type="button" @click="destroyTeam">Delete</UiButton>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
