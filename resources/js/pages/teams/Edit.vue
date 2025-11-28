<script setup lang="ts">
import ImageUpload from '@/components/ImageUpload.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue';
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue';
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue';
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue';
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue';
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue';
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue';
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue';
import AlertDialogTrigger from '@/components/ui/alert-dialog/AlertDialogTrigger.vue';
import UiButton from '@/components/ui/button/Button.vue';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import UiInput from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    team: { type: Object, required: true },
    companies: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    assigned_user_ids: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { title: 'Teams', href: '/teams' },
    { title: `${props.team?.name}` },
];

const form = useForm({
    company_id: props.team?.company_id ?? '',
    name: props.team?.name ?? '',
    slug: props.team?.slug ?? '',
    website: props.team?.website ?? '',
    logo_light: null,
    logo_dark: null,
    remove_logo_light: false,
    remove_logo_dark: false,
    user_ids: [...(props.assigned_user_ids ?? [])],
});

const assignableUsers = ref(props.users);

function submit() {
    const originalTransform = form.transform;
    form.transform((data) => {
        const payload = { ...data, _method: 'put' };
        // Prevent unintended deletion: do not send remove_logo if false
        if (!payload.remove_logo_light) delete payload.remove_logo_light;
        if (!payload.remove_logo_dark) delete payload.remove_logo_dark;
        // Don't send logo when no new file selected
        if (!payload.logo_light) delete payload.logo_light;
        if (!payload.logo_dark) delete payload.logo_dark;
        return payload;
    });
    form.post(`/teams/${props.team.id}`, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.transform = originalTransform;
        },
    });
}

function destroyTeam() {
    router.delete(`/teams/${props.team.id}`, { preserveScroll: true });
}

watch(
    () => form.company_id,
    async (companyId) => {
        // Reset selected users when company changes and fetch new assignable users
        form.user_ids = [];
        assignableUsers.value = [];
        if (!companyId) return;
        try {
            const res = await fetch(
                `/shared/assignable-users?company_id=${companyId}&team_id=${props.team.id}`,
                {
                    headers: { Accept: 'application/json' },
                },
            );
            if (res.ok) {
                assignableUsers.value = await res.json();
            }
        } catch {}
    },
);
</script>

<template>
    <Head :title="`Edit Team - ${props.team?.name ?? ''}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <form class="space-y-4" @submit.prevent="submit">
                <Field>
                    <FieldLabel for="company_id">Company</FieldLabel>
                    <select
                        id="company_id"
                        v-model="form.company_id"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option
                            v-for="c in props.companies"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                    <FieldError v-if="form.errors.company_id">{{
                        form.errors.company_id
                    }}</FieldError>
                </Field>

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

                <Field>
                    <FieldLabel for="website">Website</FieldLabel>
                    <UiInput
                        id="website"
                        v-model="form.website"
                        placeholder="https://example.com"
                    />
                    <FieldError v-if="form.errors.website">{{
                        form.errors.website
                    }}</FieldError>
                </Field>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Field>
                        <FieldLabel for="logo_light"
                            >Logo (Light Mode)</FieldLabel
                        >
                        <ImageUpload
                            v-model="form.logo_light"
                            v-model:removed="form.remove_logo_light"
                            :initial-url="props.team?.logo_light || null"
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
                            :initial-url="props.team?.logo_dark || null"
                            :preview-class="'w-full h-24'"
                        />
                        <FieldError v-if="form.errors.logo_dark">{{
                            form.errors.logo_dark
                        }}</FieldError>
                    </Field>
                </div>

                <Field>
                    <MultiSelect
                        v-model="form.user_ids"
                        :options="
                            assignableUsers.map((u) => ({
                                value: u.id,
                                label: `${u.name} (${u.email})`,
                            }))
                        "
                        label="Assign Users"
                        :error="form.errors.user_ids"
                        description="Select one or more users to add to this team"
                    />
                </Field>

                <div class="flex items-center gap-2">
                    <UiButton type="submit" :disabled="form.processing"
                        >Update</UiButton
                    >
                    <Link href="/teams">
                        <UiButton variant="outline" type="button"
                            >Cancel</UiButton
                        >
                    </Link>
                    <div class="ml-auto">
                        <AlertDialog>
                            <AlertDialogTrigger as-child>
                                <UiButton variant="destructive" type="button">
                                    Delete
                                </UiButton>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        Delete Team
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        This action cannot be undone. This will
                                        permanently delete this team.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel as-child>
                                        <UiButton
                                            variant="outline"
                                            type="button"
                                        >
                                            Cancel
                                        </UiButton>
                                    </AlertDialogCancel>
                                    <AlertDialogAction as-child>
                                        <UiButton
                                            variant="destructive"
                                            type="button"
                                            class="inline-flex items-center justify-center gap-2"
                                            @click="destroyTeam"
                                        >
                                            Delete
                                        </UiButton>
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
