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
interface Team {
    id: string | number;
    name: string;
    logo?: string | null;
    website?: string | null;
}
interface User {
    id: string | number;
    name: string;
    email: string;
    profile?: Profile | null;
}
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
const phoneDisplay = computed(() => {
    const raw = phoneText.value ?? '';
    const digits = String(raw).replace(/\D/g, '');
    if (!digits) return raw;
    if (digits.length >= 11) {
        const first = digits.slice(0, 3);
        const mid = digits.slice(3, 7);
        const last = digits.slice(7, 11);
        const rest = digits.slice(11);
        return [first, mid, last, rest].filter(Boolean).join(' ').trim();
    }
    if (digits.length === 10) {
        return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6, 10)}`;
    }
    return digits.replace(/(\d{3})(?=\d)/g, '$1 ').trim();
});
const emailText = computed(() => props.user?.email ?? '');
const photo = computed(() => props.user?.profile?.photo ?? '');
const position = computed(() => props.user?.profile?.position ?? '');
const website = computed(() => props.user?.profile?.website ?? '');
const companyAddressLine2 = computed(() => {
    const parts = [
        props.company.city,
        props.company.state,
        props.company.country,
    ].filter((p) => !!p && String(p).trim().length > 0);
    return parts.join(', ');
});

const selectedBg = computed(() =>
    props.is_dark_mode ? props.company?.bg_dark : props.company?.bg_light,
);
const selectedCompanyLogo = computed(() =>
    props.is_dark_mode ? props.company?.logo_dark : props.company?.logo_light,
);
const selectedTeamLogo = computed(() =>
    props.is_dark_mode
        ? (props.team as any)?.logo_dark
        : (props.team as any)?.logo_light,
);
const textColorClass = computed(() =>
    props.is_dark_mode ? 'text-white' : 'text-black',
);

async function saveVCard() {
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

    const blob = new Blob([lines.join('\n')], { type: 'text/x-vcard' });
    const file = new File([blob], `${props.slug || 'contact'}.vcf`, {
        type: blob.type,
    });

    try {
        if ((navigator as any).canShare?.({ files: [file] })) {
            await (navigator as any).share({
                files: [file],
                title: `${props.user.name} contact`,
            });
            return;
        }
    } catch {}

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

    <div
        class="relative mx-auto flex h-screen max-w-md flex-col items-center px-6 pt-10 pb-32 text-center sm:pb-28 md:pb-24"
    >
        <img
            v-if="selectedBg"
            :src="selectedBg"
            alt="cover"
            class="pointer-events-none absolute inset-0 h-full w-full object-cover"
        />
        <div class="absolute inset-0"></div>
        <div class="my-2"></div>
        <div
            class="relative z-1 mt-6 h-38 w-38 shrink-0 sm:h-42 sm:w-42 md:h-46 md:w-46"
        >
            <div
                class="absolute inset-0 rounded-full bg-linear-to-tr from-[#9b6dad] to-[#f38456] p-[6px] outline-1 outline-white"
            >
                <div
                    class="h-full w-full overflow-hidden rounded-full bg-neutral-50"
                >
                    <img
                        v-if="photo"
                        :src="photo"
                        alt="avatar"
                        class="h-full w-full object-cover"
                    />
                </div>
            </div>
        </div>

        <!-- position and name -->
        <div class="z-1 mt-8 space-y-2">
            <h1
                class="bg-linear-to-tr from-[#ee5b71] to-[#f38456] bg-clip-text text-3xl font-extrabold tracking-tight text-transparent md:text-4xl"
            >
                {{ props.user.name }}
            </h1>
            <p
                v-if="position"
                :class="['text-sm md:text-base', textColorClass]"
            >
                {{ position }}
            </p>
        </div>

        <!-- phone and email -->
        <div class="z-1 mt-8 space-y-2">
            <a
                v-if="phoneText"
                :href="`tel:${phoneText}`"
                class="block bg-linear-to-tr from-[#ee5b71] to-[#f38456] bg-clip-text text-xl font-semibold text-transparent md:text-2xl"
            >
                {{ phoneDisplay }}
            </a>
            <a
                v-if="emailText"
                :href="`mailto:${emailText}`"
                :class="['block text-sm break-all', textColorClass]"
                >{{ emailText }}</a
            >
        </div>

        <div class="z-1 mt-8 max-w-sm space-y-2">
            <p :class="['font-semibold', textColorClass]">{{ company.name }}</p>
            <p
                v-if="company.address && companyAddressLine2"
                :class="['text-center text-xs break-all', textColorClass]"
            >
                {{ company.address }}<br />{{ companyAddressLine2 }}
            </p>
        </div>

        <!-- company/team logo and website -->
        <div class="z-1 mt-8 flex w-full max-w-sm items-center justify-between">
            <div class="min-w-0 flex-1">
                <img
                    v-if="selectedCompanyLogo"
                    :src="selectedCompanyLogo"
                    alt="company logo"
                    class="ml-4 h-12 w-24 object-contain"
                />
                <a
                    v-if="company.website"
                    :href="company.website"
                    target="_blank"
                    class="-ml-8 block cursor-pointer text-xs break-all"
                    :class="textColorClass"
                    >{{ company.website }}</a
                >
            </div>
            <div class="min-w-0 flex-1 text-right">
                <img
                    v-if="selectedTeamLogo"
                    :src="selectedTeamLogo"
                    alt="team logo"
                    class="ml-auto h-12 w-24 object-contain"
                />
                <a
                    v-if="team.website"
                    :href="team.website"
                    target="_blank"
                    class="block cursor-pointer text-xs break-all"
                    :class="textColorClass"
                    >{{ team.website }}</a
                >
            </div>
        </div>

        <!-- social media -->
        <div
            class="z-1 mt-8 flex flex-wrap items-center justify-center gap-4 text-sm"
            :class="textColorClass"
        >
            <a
                v-if="user.profile?.website"
                :href="user.profile.website"
                target="_blank"
                rel="noreferrer"
                class="underline"
                >{{ user.profile.website }}</a
            >
            <a
                v-if="user.profile?.linkedin"
                :href="user.profile.linkedin"
                target="_blank"
                class="underline"
                >LinkedIn</a
            >
            <a
                v-if="user.profile?.twitter"
                :href="user.profile.twitter"
                target="_blank"
                class="underline"
                >Twitter</a
            >
            <a
                v-if="user.profile?.facebook"
                :href="user.profile.facebook"
                target="_blank"
                class="underline"
                >Facebook</a
            >
            <a
                v-if="user.profile?.instagram"
                :href="user.profile.instagram"
                target="_blank"
                class="underline"
                >Instagram</a
            >
        </div>

        <div class="z-1 mx-auto mt-4 w-full max-w-md px-6">
            <button
                type="button"
                @click="saveVCard"
                class="w-full rounded-full bg-linear-to-tr from-[#ee5b71] to-[#f38456] px-6 py-4 text-center text-base font-semibold text-white shadow-md outline-1 outline-white transition-opacity hover:opacity-95"
            >
                Save Contact
            </button>
        </div>
    </div>
</template>
