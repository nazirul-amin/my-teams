<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, watch, computed } from 'vue';

interface Company { id: string | number; name: string }
interface Team { id: string | number; name: string }

interface Props {
  companies: Company[];
  teams: Team[];
  contactCard?: { slug: string; company_id: string | number; team_id: string | number; is_dark_mode?: boolean } | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Contact Card', href: '/settings/contact-card' },
];

const page = usePage();
const user = page.props.auth.user;

const makeSlug = (s: string) =>
  (s || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');

const initialSlug = props.contactCard?.slug ?? makeSlug(user.name);
const slugInput = ref(initialSlug);

watch(
  () => user.name,
  (name) => {
    if (!props.contactCard?.slug) {
      slugInput.value = makeSlug(name as string);
    }
  }
);

const form = useForm({
  slug: slugInput.value,
  company_id: String(props.contactCard?.company_id ?? ''),
  team_id: String(props.contactCard?.team_id ?? ''),
  is_dark_mode: Boolean(props.contactCard?.is_dark_mode ?? false),
});

watch(slugInput, (v) => (form.slug = v));
const submit = () => form.post('/settings/contact-card');

const publicUrl = computed(() => (props.contactCard?.slug ? `/c/${props.contactCard.slug}` : null));

const absoluteShareUrl = computed(() => {
  if (!publicUrl.value) return null;
  if (typeof window === 'undefined') return publicUrl.value;
  try {
    const origin = window.location.origin;
    return new URL(publicUrl.value, origin).toString();
  } catch {
    return publicUrl.value;
  }
});

const qrImageUrl = computed(() => {
  if (!absoluteShareUrl.value) return null;
  const data = encodeURIComponent(absoluteShareUrl.value);
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=1&data=${data}`;
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Contact Card" />

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <HeadingSmall
          title="Contact Card"
          description="Generate a public contact card that you can share"
        />

        <form class="space-y-6" @submit.prevent="submit">
          <div class="grid gap-2">
            <Label for="slug">Public slug</Label>
            <Input id="slug" name="slug" v-model="slugInput" placeholder="my-name" />
            <InputError class="mt-2" :message="form.errors.slug" />
          </div>

          <div class="grid gap-2">
            <Label for="company_id">Company</Label>
            <select id="company_id" name="company_id" class="border rounded p-2" v-model="form.company_id">
              <option value="" disabled>Select a company</option>
              <option v-for="c in props.companies" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </select>
            <InputError class="mt-2" :message="form.errors.company_id" />
          </div>

          <div class="grid gap-2">
            <Label for="team_id">Team</Label>
            <select id="team_id" name="team_id" class="border rounded p-2" v-model="form.team_id">
              <option value="" disabled>Select a team</option>
              <option v-for="t in props.teams" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
            </select>
            <InputError class="mt-2" :message="form.errors.team_id" />
          </div>

          <div class="flex items-center gap-3">
            <input id="is_dark_mode" name="is_dark_mode" type="checkbox" v-model="form.is_dark_mode" class="h-4 w-4" />
            <Label for="is_dark_mode">Use dark background</Label>
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="generate-contact-card" type="submit">Generate</Button>
            <p v-if="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
            <div v-if="publicUrl" class="text-sm">
              Share link:
              <Link :href="publicUrl" class="underline">{{ publicUrl }}</Link>
            </div>
          </div>

          <div v-if="qrImageUrl" class="flex items-center gap-6 pt-2">
            <img :src="qrImageUrl as string" alt="Contact card QR" class="h-36 w-36 rounded border" />
            <div class="flex flex-col gap-2">
              <p class="text-sm text-neutral-600">Scan or download the QR code to share your contact card.</p>
              <a :href="qrImageUrl as string" download="contact-card-qr.png" class="underline text-sm">Download QR</a>
            </div>
          </div>
        </form>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
