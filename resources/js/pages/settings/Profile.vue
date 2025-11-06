<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { ref } from 'vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import ImageUpload from '@/components/ImageUpload.vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    profile?: Record<string, any> | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const photoFile = ref<File | null>(null);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Profile information"
                    description="Update your name, email, and profile details"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="photo">Profile photo</Label>
                        <ImageUpload
                            v-model="photoFile"
                            name="photo"
                            :initial-url="props.profile?.photo ?? null"
                            accept="image/*"
                            :preview-class="'h-16 w-16 rounded-full object-cover border'"
                        />
                        <InputError class="mt-2" :message="errors.photo" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="position">Position</Label>
                        <Input
                            id="position"
                            name="position"
                            class="mt-1 block w-full"
                            :default-value="props.profile?.position ?? ''"
                            placeholder="e.g. Product Manager"
                        />
                        <InputError class="mt-2" :message="errors.position" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            name="phone"
                            class="mt-1 block w-full"
                            :default-value="props.profile?.phone ?? ''"
                            placeholder="Phone number"
                        />
                        <InputError class="mt-2" :message="errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="bio">Bio</Label>
                        <Input
                            id="bio"
                            name="bio"
                            class="mt-1 block w-full"
                            :default-value="props.profile?.bio ?? ''"
                            placeholder="Short bio"
                        />
                        <InputError class="mt-2" :message="errors.bio" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="website">Website</Label>
                        <Input
                            id="website"
                            name="website"
                            class="mt-1 block w-full"
                            :default-value="props.profile?.website ?? ''"
                            placeholder="https://example.com"
                        />
                        <InputError class="mt-2" :message="errors.website" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="linkedin">LinkedIn</Label>
                            <Input id="linkedin" name="linkedin" :default-value="props.profile?.linkedin ?? ''" placeholder="LinkedIn URL" />
                            <InputError class="mt-2" :message="errors.linkedin" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="twitter">Twitter</Label>
                            <Input id="twitter" name="twitter" :default-value="props.profile?.twitter ?? ''" placeholder="Twitter URL" />
                            <InputError class="mt-2" :message="errors.twitter" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="facebook">Facebook</Label>
                            <Input id="facebook" name="facebook" :default-value="props.profile?.facebook ?? ''" placeholder="Facebook URL" />
                            <InputError class="mt-2" :message="errors.facebook" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="instagram">Instagram</Label>
                            <Input id="instagram" name="instagram" :default-value="props.profile?.instagram ?? ''" placeholder="Instagram URL" />
                            <InputError class="mt-2" :message="errors.instagram" />
                        </div>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            Save
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
