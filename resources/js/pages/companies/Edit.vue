<script setup lang="ts">
import ImageUpload from '@/components/ImageUpload.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import UiButton from '@/components/ui/button/Button.vue';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import UiInput from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    company: { type: Object, required: true },
    users: { type: Array, default: () => [] },
    assigned_user_ids: { type: Array, default: () => [] },
});

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
});

function submit() {
    const originalTransform = form.transform;
    form.transform((data) => {
        const payload = { ...data, _method: 'put' };
        // Omit false remove flags to avoid accidental deletion
        if (!payload.remove_bg_light) delete payload.remove_bg_light;
        if (!payload.remove_bg_dark) delete payload.remove_bg_dark;
        // Omit files if not selected
        if (!payload.logo_light) delete payload.logo_light;
        if (!payload.logo_dark) delete payload.logo_dark;
        if (!payload.bg_light) delete payload.bg_light;
        if (!payload.bg_dark) delete payload.bg_dark;
        return payload;
    });
    form.post(`/companies/${props.company.id}`, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.transform = originalTransform;
        },
    });
}

// File inputs handled by ImageUpload via v-model and v-model:removed

const breadcrumbs = [
    { title: 'Companies', href: '/companies' },
    { title: 'Edit Company', href: `/companies/${props.company.id}/edit` },
];

function slugify(v) {
    return (v || '')
        .toString()
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
}

watch(
    () => form.name,
    (v) => {
        form.slug = slugify(v);
    },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${form.name || 'Company'}`" />
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
                    <FieldLabel for="slug">Slug</FieldLabel>
                    <UiInput id="slug" v-model="form.slug" />
                    <FieldError v-if="form.errors.slug">{{
                        form.errors.slug
                    }}</FieldError>
                </Field>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="website">Website</FieldLabel>
                        <UiInput id="website" v-model="form.website" />
                        <FieldError v-if="form.errors.website">{{
                            form.errors.website
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="email">Email</FieldLabel>
                        <UiInput id="email" type="email" v-model="form.email" />
                        <FieldError v-if="form.errors.email">{{
                            form.errors.email
                        }}</FieldError>
                    </Field>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="phone">Phone</FieldLabel>
                        <UiInput id="phone" v-model="form.phone" />
                        <FieldError v-if="form.errors.phone">{{
                            form.errors.phone
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="linkedin">LinkedIn</FieldLabel>
                        <UiInput id="linkedin" v-model="form.linkedin" />
                        <FieldError v-if="form.errors.linkedin">{{
                            form.errors.linkedin
                        }}</FieldError>
                    </Field>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="twitter">Twitter</FieldLabel>
                        <UiInput id="twitter" v-model="form.twitter" />
                        <FieldError v-if="form.errors.twitter">{{
                            form.errors.twitter
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="facebook">Facebook</FieldLabel>
                        <UiInput id="facebook" v-model="form.facebook" />
                        <FieldError v-if="form.errors.facebook">{{
                            form.errors.facebook
                        }}</FieldError>
                    </Field>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="instagram">Instagram</FieldLabel>
                        <UiInput id="instagram" v-model="form.instagram" />
                        <FieldError v-if="form.errors.instagram">{{
                            form.errors.instagram
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="address">Address</FieldLabel>
                        <UiInput id="address" v-model="form.address" />
                        <FieldError v-if="form.errors.address">{{
                            form.errors.address
                        }}</FieldError>
                    </Field>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Field>
                        <FieldLabel for="city">City</FieldLabel>
                        <UiInput id="city" v-model="form.city" />
                        <FieldError v-if="form.errors.city">{{
                            form.errors.city
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="state">State</FieldLabel>
                        <UiInput id="state" v-model="form.state" />
                        <FieldError v-if="form.errors.state">{{
                            form.errors.state
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="country">Country</FieldLabel>
                        <UiInput id="country" v-model="form.country" />
                        <FieldError v-if="form.errors.country">{{
                            form.errors.country
                        }}</FieldError>
                    </Field>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="logo_light"
                            >Logo (Light Mode)</FieldLabel
                        >
                        <ImageUpload
                            v-model="form.logo_light"
                            v-model:removed="form.remove_logo_light"
                            :initial-url="props.company?.logo_light || null"
                            :preview-class="'w-full h-24'"
                        />
                        <FieldError v-if="form.errors.logo_light">{{
                            form.errors.logo_light
                        }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="logo_dark"
                            >Logo (Dark Mode)</FieldLabel
                        >
                        <ImageUpload
                            v-model="form.logo_dark"
                            v-model:removed="form.remove_logo_dark"
                            :initial-url="props.company?.logo_dark || null"
                            :preview-class="'w-full h-24'"
                        />
                        <FieldError v-if="form.errors.logo_dark">{{
                            form.errors.logo_dark
                        }}</FieldError>
                    </Field>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="bg_light"
                            >Background (Light)</FieldLabel
                        >
                        <ImageUpload
                            v-model="form.bg_light"
                            v-model:removed="form.remove_bg_light"
                            :initial-url="props.company?.bg_light || null"
                            :preview-class="'w-full h-24'"
                        />
                        <FieldError v-if="form.errors.bg_light">{{
                            form.errors.bg_light
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="bg_dark">Background (Dark)</FieldLabel>
                        <ImageUpload
                            v-model="form.bg_dark"
                            v-model:removed="form.remove_bg_dark"
                            :initial-url="props.company?.bg_dark || null"
                            :preview-class="'w-full h-24'"
                        />
                        <FieldError v-if="form.errors.bg_dark">{{
                            form.errors.bg_dark
                        }}</FieldError>
                    </Field>
                </div>

                <Field>
                    <MultiSelect
                        v-model="form.user_ids"
                        :options="
                            props.users.map((u) => ({
                                value: u.id,
                                label: `${u.name} (${u.email})`,
                            }))
                        "
                        label="Assign Users"
                        :error="form.errors.user_ids"
                        description="Select one or more users to add to this company"
                    />
                </Field>

                <div class="flex items-center gap-2">
                    <UiButton type="submit" :disabled="form.processing"
                        >Save</UiButton
                    >
                    <Link href="/companies">
                        <UiButton variant="outline" type="button"
                            >Cancel</UiButton
                        >
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
