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
  logo?: string | null;
  logo_light?: string | null;
  logo_dark?: string | null;
  bg_light?: string | null;
  bg_dark?: string | null;
}
interface Team { id: string | number; name: string; logo?: string | null; website?: string | null }
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
}

interface Props {
  user: User;
  company: Company;
  team: Team;
  slug: string;
  is_dark_mode: boolean;
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

const selectedBg = computed(() => (props.is_dark_mode ? props.company?.bg_dark : props.company?.bg_light));
const selectedCompanyLogo = computed(() => (props.is_dark_mode ? props.company?.logo_dark : props.company?.logo_light));
const selectedTeamLogo = computed(() => (props.is_dark_mode ? (props.team as any)?.logo_dark : (props.team as any)?.logo_light));
const textColorClass = computed(() => (props.is_dark_mode ? 'text-white' : 'text-black'));

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
        v-if="selectedBg"
        :src="selectedBg as string"
        alt="cover"
        class="pointer-events-none absolute inset-0 h-full w-full object-cover"
      />
      <div class="absolute inset-0"></div>
      <div class="relative mt-12 z-1 h-[160px] w-[160px]">
        <div class="absolute inset-0 rounded-full bg-linear-to-tr from-[#9b6dad] to-[#f38456] p-[6px] outline-1 outline-white">
          <div class="h-full w-full rounded-full bg-neutral-50" />
        </div>
        <img v-if="photo" :src="photo" alt="avatar" class="absolute inset-1.5 h-[148px] w-[148px] rounded-full object-cover" />
      </div>

      <!-- position and name -->
      <h1 class="bg-linear-to-tr z-1 mt-2 from-[#ee5b71] to-[#f38456] bg-clip-text text-3xl font-extrabold tracking-tight text-transparent md:text-4xl">
        {{ props.user.name }}
      </h1>
      <p v-if="position" :class="['mt-2 z-1 text-sm md:text-base', textColorClass]">{{ position }}</p>

      <!-- phone and email -->
      <div class="mt-8 space-y-2 z-1">
        <a v-if="phoneText" :href="`tel:${phoneText}`" class="block text-xl font-semibold bg-linear-to-tr from-[#ee5b71] to-[#f38456] bg-clip-text text-transparent md:text-2xl">
          {{ phoneText }}
        </a>
        <a v-if="emailText" :href="`mailto:${emailText}`" :class="['block', textColorClass]">{{ emailText }}</a>
      </div>

      <div class="mt-8 max-w-sm space-y-2 z-1">
        <p :class="['font-semibold', textColorClass]">{{ company.name }}</p>
        <p v-if="companyAddress" :class="['text-xs', textColorClass]">{{ companyAddress }}</p>
      </div>

      <!-- company/team logo and website -->
      <div class="mt-8 flex w-full max-w-sm items-center justify-between z-1">
        <div class="flex-1">
          <img
            v-if="selectedCompanyLogo"
            :src="selectedCompanyLogo as string"
            alt="company logo"
            class="h-12 w-24 object-contain"
          />
          <span class="-ml-7 text-xs" :class="textColorClass">{{ company.website }}</span>
        </div>
        <div class="flex-1 text-right">
          <img
            v-if="selectedTeamLogo"
            :src="selectedTeamLogo as string"
            alt="team logo"
            class="ml-auto h-12 w-24 object-contain"
          />
          <span class="text-xs" :class="textColorClass">{{ team.website }}</span>
        </div>
      </div>

      <!-- social media -->
      <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-sm z-1" :class="textColorClass">
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
          class="w-full rounded-full bg-linear-to-tr from-[#ee5b71] to-[#f38456] px-6 py-4 text-center text-base font-semibold text-white shadow-md transition-opacity hover:opacity-95 outline-1 outline-white"
        >
          Save Contact
        </button>
      </div>
    </div>
  </div>
</template>
