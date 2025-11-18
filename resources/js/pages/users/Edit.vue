<script setup lang="ts">
import ImageUpload from '@/components/ImageUpload.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import UiButton from '@/components/ui/button/Button.vue';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import UiInput from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const breadcrumbs = [
    { title: 'Members', href: '/members' },
    { title: 'Edit Member', href: '' },
];

const props = defineProps<{
    user: {
        id: string;
        name: string;
        email: string;
        profile?: {
            bio?: string | null;
            position?: string | null;
            phone?: string | null;
            website?: string | null;
            linkedin?: string | null;
            twitter?: string | null;
            facebook?: string | null;
            instagram?: string | null;
            photo?: string | null;
            cover_photo?: string | null;
        } | null;
    };
    companies: Array<{ id: string; name: string }>;
    assigned_company_ids: string[];
    roles: Array<{ value: string; label: string }>;
    current_role: string | null;
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.current_role || '',
    company_ids: [...props.assigned_company_ids],
    profile: {
        bio: props.user.profile?.bio ?? '',
        position: props.user.profile?.position ?? '',
        phone: props.user.profile?.phone ?? '',
        website: props.user.profile?.website ?? '',
        linkedin: props.user.profile?.linkedin ?? '',
        twitter: props.user.profile?.twitter ?? '',
        facebook: props.user.profile?.facebook ?? '',
        instagram: props.user.profile?.instagram ?? '',
        photo: props.user.profile?.photo ?? '',
        cover_photo: props.user.profile?.cover_photo ?? '',
    },
    photo_file: null as File | null,
    cover_photo_file: null as File | null,
    photo_removed: false,
    cover_photo_removed: false,
});

function submit() {
    const originalTransform = form.transform;
    form.transform((data) => {
        const payload: any = { ...data, _method: 'put' };
        // Omit false remove flags to avoid accidental deletion
        if (!payload.photo_removed) delete payload.photo_removed;
        if (!payload.cover_photo_removed) delete payload.cover_photo_removed;
        // Omit files if not selected
        if (!payload.photo_file) delete payload.photo_file;
        if (!payload.cover_photo_file) delete payload.cover_photo_file;
        return payload;
    });
    form.post(`/members/${props.user.id}`, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.transform = originalTransform;
        },
    });
}
</script>

<template>
    <Head :title="`Edit User - ${props.user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel>Photo</FieldLabel>
                        <ImageUpload
                            v-model="form.photo_file"
                            v-model:removed="form.photo_removed"
                            :initial-url="form.profile.photo || null"
                            name="photo"
                            preview-class="h-32 w-full"
                        />
                        <FieldError v-if="form.errors.photo_file">{{
                            form.errors.photo_file
                        }}</FieldError>
                    </Field>

                    <!-- <Field>
                        <FieldLabel>Cover Photo</FieldLabel>
                        <ImageUpload
                            v-model="form.cover_photo_file"
                            v-model:removed="form.cover_photo_removed"
                            :initial-url="form.profile.cover_photo || null"
                            name="cover_photo"
                            preview-class="h-32 w-full"
                        />
                        <FieldError v-if="form.errors.cover_photo_file">{{
                            form.errors.cover_photo_file
                        }}</FieldError>
                    </Field> -->
                </div>

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
                    <FieldLabel for="password"
                        >Password (leave blank to keep)</FieldLabel
                    >
                    <UiInput
                        id="password"
                        type="password"
                        v-model="form.password"
                    />
                    <FieldError v-if="form.errors.password">{{
                        form.errors.password
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

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="bio">Bio</FieldLabel>
                        <UiInput id="bio" v-model="form.profile.bio" />
                        <FieldError v-if="form.errors['profile.bio']">{{
                            form.errors['profile.bio']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="position">Position</FieldLabel>
                        <UiInput
                            id="position"
                            v-model="form.profile.position"
                        />
                        <FieldError v-if="form.errors['profile.position']">{{
                            form.errors['profile.position']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="phone">Phone</FieldLabel>
                        <UiInput
                            id="phone"
                            type="tel"
                            required
                            v-model="form.profile.phone"
                        />
                        <FieldError v-if="form.errors['profile.phone']">{{
                            form.errors['profile.phone']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="website">Website</FieldLabel>
                        <UiInput id="website" v-model="form.profile.website" />
                        <FieldError v-if="form.errors['profile.website']">{{
                            form.errors['profile.website']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="linkedin">LinkedIn</FieldLabel>
                        <UiInput
                            id="linkedin"
                            v-model="form.profile.linkedin"
                        />
                        <FieldError v-if="form.errors['profile.linkedin']">{{
                            form.errors['profile.linkedin']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="twitter">Twitter</FieldLabel>
                        <UiInput id="twitter" v-model="form.profile.twitter" />
                        <FieldError v-if="form.errors['profile.twitter']">{{
                            form.errors['profile.twitter']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="facebook">Facebook</FieldLabel>
                        <UiInput
                            id="facebook"
                            v-model="form.profile.facebook"
                        />
                        <FieldError v-if="form.errors['profile.facebook']">{{
                            form.errors['profile.facebook']
                        }}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel for="instagram">Instagram</FieldLabel>
                        <UiInput
                            id="instagram"
                            v-model="form.profile.instagram"
                        />
                        <FieldError v-if="form.errors['profile.instagram']">{{
                            form.errors['profile.instagram']
                        }}</FieldError>
                    </Field>
                </div>

                <div class="flex items-center gap-2">
                    <UiButton type="submit" :disabled="form.processing"
                        >Update</UiButton
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
