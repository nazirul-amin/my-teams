<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import UiButton from '@/components/ui/button/Button.vue'
import UiInput from '@/components/ui/input/Input.vue'
import { Field, FieldLabel, FieldError } from '@/components/ui/field'
import MultiSelect from '@/components/MultiSelect.vue'

const breadcrumbs = [
  { title: 'Users', href: '/users' },
  { title: 'Edit User', href: '' },
]

const props = defineProps<{
  user: { id: string; name: string; email: string }
  companies: Array<{ id: string; name: string }>
  assigned_company_ids: string[]
  roles: Array<{ value: string; label: string }>
  current_role: string | null
}>()

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  role: props.current_role || '',
  company_ids: [...props.assigned_company_ids],
})

function submit() {
  form.put(`/users/${props.user.id}`, { preserveScroll: true })
}
</script>

<template>
  <Head :title="`Edit User - ${props.user.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <form class="space-y-4" @submit.prevent="submit">
        <Field>
          <FieldLabel for="name">Name</FieldLabel>
          <UiInput id="name" v-model="form.name" />
          <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
        </Field>

        <Field>
          <FieldLabel for="email">Email</FieldLabel>
          <UiInput id="email" type="email" v-model="form.email" />
          <FieldError v-if="form.errors.email">{{ form.errors.email }}</FieldError>
        </Field>

        <Field>
          <FieldLabel for="password">Password (leave blank to keep)</FieldLabel>
          <UiInput id="password" type="password" v-model="form.password" />
          <FieldError v-if="form.errors.password">{{ form.errors.password }}</FieldError>
        </Field>

        <Field>
          <FieldLabel for="role">Role</FieldLabel>
          <select id="role" v-model="form.role" class="w-full border rounded px-3 py-2">
            <option value="" disabled>Select a role</option>
            <option v-for="r in props.roles" :key="r.value" :value="r.value">{{ r.label }}</option>
          </select>
          <FieldError v-if="form.errors.role">{{ form.errors.role }}</FieldError>
        </Field>

        <Field>
          <MultiSelect
            v-model="form.company_ids"
            :options="props.companies.map(c => ({ value: c.id, label: c.name }))"
            label="Assign Companies"
            :error="form.errors.company_ids"
            description="Select one or more companies for this user"
          />
        </Field>

        <div class="flex items-center gap-2">
          <UiButton type="submit" :disabled="form.processing">Update</UiButton>
          <Link href="/users">
            <UiButton variant="outline" type="button">Cancel</UiButton>
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
