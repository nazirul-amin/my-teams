<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Company {
  id: string | number;
  name: string;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  website?: string | null;
  phone?: string | null;
  email?: string | null;
  cover_photo?: string | null;
}
interface Team { id: string | number; name: string }
interface User { id: string | number; name: string; email: string; profile?: Profile | null }
interface Profile {
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
}

interface Props {
  user: User;
  company: Company;
  team: Team;
  slug: string;
}

const props = defineProps<Props>();

const phoneText = computed(() => props.user?.profile?.phone ?? '');
const emailText = computed(() => props.user?.email ?? '');
const photo = computed(() => props.user?.profile?.photo ?? '');
const position = computed(() => props.user?.profile?.position ?? '');
const website = computed(() => props.user?.profile?.website ?? '');
const companyAddress = computed(() => {
  const parts = [props.company.address, props.company.city, props.company.state, props.company.country]
    .filter((p) => !!p && String(p).trim().length > 0);
  return parts.join(', ');
});

function saveVCard() {
  const lines: string[] = [];
  lines.push('BEGIN:VCARD');
  lines.push('VERSION:3.0');
  lines.push(`N:;${props.user.name};;;`);
  lines.push(`FN:${props.user.name}`);
  lines.push(`ORG:${props.company.name}`);
  if (position.value) lines.push(`TITLE:${position.value}`);
  if (phoneText.value) lines.push(`TEL;TYPE=CELL:${phoneText.value}`);
  if (emailText.value) lines.push(`EMAIL:${emailText.value}`);
  if (website.value) lines.push(`URL:${website.value}`);
  lines.push('END:VCARD');

  const blob = new Blob([lines.join('\n')], { type: 'text/vcard;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `${props.slug || 'contact'}.vcf`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
</script>

<template>
  <Head :title="`${props.user.name} · Contact Card`" />

  <div class="relative min-h-screen">
    <div class="relative mx-auto flex max-w-md h-screen flex-col items-center px-6 pb-24 pt-10 text-center">
      <img
        v-if="props.company?.cover_photo"
        :src="props.company.cover_photo"
        alt="cover"
        class="pointer-events-none absolute inset-0 h-full w-full object-cover"
      />
      <div class="absolute inset-0 bg-white/60"></div>
      <div class="relative mb-6 z-1 h-40 w-40">
        <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-pink-400 via-orange-400 to-amber-400 p-[6px]">
          <div class="h-full w-full rounded-full bg-neutral-50" />
        </div>
        <img v-if="photo" :src="photo" alt="avatar" class="absolute inset-1.5 h-[148px] w-[148px] rounded-full object-cover" />
      </div>

      <h1 class="bg-gradient-to-tr z-1 from-pink-500 via-orange-500 to-amber-500 bg-clip-text text-3xl font-extrabold tracking-tight text-transparent md:text-4xl">
        {{ props.user.name }}
      </h1>
      <p v-if="position" class="mt-2 z-1 text-sm text-neutral-500 md:text-base">{{ position }}</p>

      <div class="mt-8 space-y-2 z-1">
        <a v-if="phoneText" :href="`tel:${phoneText}`" class="block text-xl font-semibold text-pink-500 md:text-2xl">
          {{ phoneText }}
        </a>
        <a v-if="emailText" :href="`mailto:${emailText}`" class="block text-neutral-600">{{ emailText }}</a>
      </div>

      <div class="mt-10 max-w-sm space-y-2 z-1">
        <p class="text-base font-semibold text-neutral-900">{{ company.name }}</p>
        <p v-if="companyAddress" class="text-sm text-neutral-500">{{ companyAddress }}</p>
      </div>

      <div class="mt-6 flex flex-wrap items-center justify-center gap-4 text-sm text-neutral-600 z-1">
        <a v-if="user.profile?.website" :href="user.profile.website" target="_blank" rel="noreferrer" class="underline">{{ user.profile.website }}</a>
        <a v-if="user.profile?.linkedin" :href="user.profile.linkedin" target="_blank" class="underline">LinkedIn</a>
        <a v-if="user.profile?.twitter" :href="user.profile.twitter" target="_blank" class="underline">Twitter</a>
        <a v-if="user.profile?.facebook" :href="user.profile.facebook" target="_blank" class="underline">Facebook</a>
        <a v-if="user.profile?.instagram" :href="user.profile.instagram" target="_blank" class="underline">Instagram</a>
      </div>

      <div class="fixed inset-x-0 bottom-0 z-10 mx-auto max-w-md px-6 pb-8">
        <button
          type="button"
          @click="saveVCard"
          class="w-full rounded-full bg-gradient-to-tr from-pink-500 via-orange-500 to-amber-500 px-6 py-4 text-center text-base font-semibold text-white shadow-md transition-opacity hover:opacity-95"
        >
          Save Contact
        </button>
      </div>
    </div>
  </div>
</template>
