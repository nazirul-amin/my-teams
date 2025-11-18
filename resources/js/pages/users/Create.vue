<script setup lang="ts">
import MultiSelect from '@/components/MultiSelect.vue';
import UiButton from '@/components/ui/button/Button.vue';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import UiInput from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const breadcrumbs = [
    { title: 'Members', href: '/members' },
    { title: 'Add Member', href: '/members/create' },
];

const props = defineProps<{
    companies: Array<{ id: string; name: string }>;
    roles: Array<{ value: string; label: string }>;
}>();

const form = useForm({
    name: '',
    email: '',
    role: '',
    company_ids: [],
});

function submit() {
    form.post('/members', { preserveScroll: true });
}
</script>

<template>
    <Head title="Create User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <form class="space-y-4" @submit.prevent="submit">
                <Field>
                    <FieldLabel for="name">Name</FieldLabel>
                    <UiInput id="name" v-model="form.name" />
                    <FieldError v-if="form.errors.name">{{
                        form.errors.name
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="email">Email</FieldLabel>
                    <UiInput id="email" type="email" v-model="form.email" />
                    <FieldError v-if="form.errors.email">{{
                        form.errors.email
                    }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="role">Role</FieldLabel>
                    <select
                        id="role"
                        v-model="form.role"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option value="" disabled>Select a role</option>
                        <option
                            v-for="r in props.roles"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
                        </option>
                    </select>
                    <FieldError v-if="form.errors.role">{{
                        form.errors.role
                    }}</FieldError>
                </Field>

                <Field>
                    <MultiSelect
                        v-model="form.company_ids"
                        :options="
                            props.companies.map((c) => ({
                                value: c.id,
                                label: c.name,
                            }))
                        "
                        label="Assign Companies"
                        :error="form.errors.company_ids"
                        description="Select one or more companies for this user"
                    />
                </Field>

                <div class="flex items-center gap-2">
                    <UiButton type="submit" :disabled="form.processing"
                        >Create</UiButton
                    >
                    <Link href="/members">
                        <UiButton variant="outline" type="button"
                            >Cancel</UiButton
                        >
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
