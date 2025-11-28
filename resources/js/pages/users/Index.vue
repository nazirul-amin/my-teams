<script setup lang="ts">
import ResourceCard from '@/components/ResourceCard.vue';
import ResourceGrid from '@/components/ResourceGrid.vue';
import UiButton from '@/components/ui/button/Button.vue';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useQRCode } from '@vueuse/integrations/useQRCode';
import { is } from 'laravel-permission-to-vuejs';
import { LinkIcon, QrCodeIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    users: any;
    filters: Record<string, any>;
    auth_id: string;
}>();

const search = ref<string>(props.filters.search || '');
const pageIndex = ref<number>(Number((props.users?.current_page ?? 1) - 1));
const canAdminManage = computed(() =>
    Boolean(is('admin') || is('super-admin')),
);
const canEditUsers = computed(() =>
    Boolean(is('super-admin') || is('admin') || is('manager')),
);

const breadcrumbs = [{ title: 'Members', href: '/members' }];

const items = ref<any[]>(props.users?.data || []);
const isLoadingMore = ref(false);
const hasMore = computed(
    () =>
        Number(props.users?.current_page || 1) <
        Number(props.users?.last_page || 1),
);

function goto(params: Record<string, any> = {}) {
    router.get(
        '/members',
        {
            search: search.value || undefined,
            page: pageIndex.value + 1,
            ...params,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('page');
                const newSearch = url.searchParams.toString();
                const clean = `${url.pathname}${newSearch ? `?${newSearch}` : ''}${url.hash}`;
                window.history.replaceState({}, '', clean);
            },
        },
    );
}

function profilePhotoUrl(user: any): string {
    const photo = user?.profile?.photo;
    if (!photo || typeof photo !== 'string')
        return 'https://ui-avatars.com/api/?name=' + user.name;
    if (photo.startsWith('/storage/') || photo.startsWith('http')) return photo;
    return `/storage/${photo}`;
}

watch(
    () => props.users,
    (newVal, oldVal) => {
        const currentPage = Number(newVal?.current_page || 1);
        const previousPage = Number(oldVal?.current_page || 0);

        if (!oldVal || currentPage <= 1 || currentPage <= previousPage) {
            items.value = [...(newVal?.data || [])];
        } else if (currentPage > previousPage) {
            items.value = [...items.value, ...(newVal?.data || [])];
        }

        pageIndex.value = currentPage - 1;
        isLoadingMore.value = false;
    },
    { immediate: true },
);

watch(search, () => {
    pageIndex.value = 0;
    goto();
});

function loadMore() {
    if (isLoadingMore.value || !hasMore.value) return;
    isLoadingMore.value = true;
    pageIndex.value += 1;
    goto();
}

function contactCardUrl(user: any): string | null {
    const slug = user?.contact_card?.slug || user?.contactCard?.slug;
    if (!slug) return null;
    return `/contact-card/${slug}`;
}

function contactCardAbsoluteUrl(user: any): string | null {
    const relative = contactCardUrl(user);
    if (!relative) return null;
    return `${window.location.origin}${relative}`;
}

const userQrMap: Record<string, any> = {};

function contactCardQrDataUrl(user: any): string | null {
    const abs = contactCardAbsoluteUrl(user);
    if (!abs) return null;
    const key = String(
        user?.contact_card?.slug || user?.contactCard?.slug || user?.id,
    );
    if (!userQrMap[key]) {
        userQrMap[key] = useQRCode(abs, { width: 512, margin: 1 });
    }
    return userQrMap[key]?.value || null;
}

function canGenerate(user: any): boolean {
    // Super-admin OR admin who created this user OR manager (list is filtered to same team for managers)
    return Boolean(
        is('super-admin') ||
            (is('admin') && user.created_by === props.auth_id) ||
            is('manager'),
    );
}

// QR preview dialog state
const showQrDialog = ref(false);
const qrUser = ref<any | null>(null);
const currentQrDataUrl = computed<string | null>(() =>
    qrUser.value ? contactCardQrDataUrl(qrUser.value) : null,
);

function openQrDialog(user: any) {
    qrUser.value = user;
    // Precompute/cache QR if not already
    contactCardQrDataUrl(user);
    showQrDialog.value = true;
}

// Dialog state for generation with selection
const showDialog = ref(false);
const genUser = ref<any | null>(null);
const companies = ref<Array<{ id: string; name: string }>>([]);
const teams = ref<Array<{ id: string; name: string }>>([]);
const selectedCompanyId = ref<string | null>(null);
const selectedTeamId = ref<string | null>(null);
const loadingCompanies = ref(false);
const loadingTeams = ref(false);

async function fetchCompanies(userId: string) {
    loadingCompanies.value = true;
    try {
        const res = await fetch(`/members/${userId}/companies`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        companies.value = Array.isArray(data) ? data : [];
        selectedCompanyId.value = companies.value[0]?.id ?? null;
    } finally {
        loadingCompanies.value = false;
    }
}

async function fetchTeams(companyId: string, userId: string) {
    if (!companyId) {
        teams.value = [];
        selectedTeamId.value = null;
        return;
    }
    loadingTeams.value = true;
    try {
        const url = new URL(
            `/companies/${companyId}/teams`,
            window.location.origin,
        );
        url.searchParams.set('user_id', userId);
        const res = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        teams.value = Array.isArray(data) ? data : [];
        selectedTeamId.value = teams.value[0]?.id ?? null;
    } finally {
        loadingTeams.value = false;
    }
}

async function openGenerateDialog(user: any) {
    genUser.value = user;
    showDialog.value = true;
    await fetchCompanies(user.id);
    if (selectedCompanyId.value)
        await fetchTeams(selectedCompanyId.value, user.id);
}

watch(selectedCompanyId, async (cid) => {
    if (showDialog.value && genUser.value) {
        await fetchTeams(cid || '', genUser.value.id);
    }
});

function submitGenerate() {
    if (!genUser.value || !selectedCompanyId.value || !selectedTeamId.value)
        return;
    router.post(
        `/members/${genUser.value.id}/contact-card`,
        { company_id: selectedCompanyId.value, team_id: selectedTeamId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
                genUser.value = null;
                goto();
            },
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="min-w-[200px] flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search members..."
                        class="w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                    />
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <Link v-if="canAdminManage" href="/members/create">
                        <UiButton>Add Member</UiButton>
                    </Link>
                </div>
            </div>
            <ResourceGrid
                v-if="items.length"
                :items="items"
                :is-loading="isLoadingMore"
                :has-more="hasMore"
                @load-more="loadMore"
            >
                <template #default="{ item: user }">
                    <ResourceCard
                        :title="user.name"
                        :subtitle="user.profile?.position"
                        :photo="profilePhotoUrl(user)"
                        :is-avatar="true"
                        :lines="[
                            ...(user.role_label
                                ? [
                                      {
                                          label: 'Role',
                                          value: user.role_label,
                                      },
                                  ]
                                : []),
                            ...(user.email
                                ? [
                                      {
                                          label: 'Email',
                                          value: user.email,
                                          href: `mailto:${user.email}`,
                                      },
                                  ]
                                : []),
                            ...(user.profile?.phone
                                ? [
                                      {
                                          label: 'Phone',
                                          value: user.profile.phone,
                                          href: `tel:${user.profile.phone}`,
                                      },
                                  ]
                                : []),
                        ]"
                        :can-manage="canEditUsers"
                        :show-url="`/members/${user.id}`"
                        :edit-url="`/members/${user.id}/edit`"
                        :delete-url="
                            canAdminManage ? `/members/${user.id}` : undefined
                        "
                    >
                        <template #actions>
                            <div
                                v-if="contactCardUrl(user)"
                                class="grid grid-cols-3 gap-2"
                            >
                                <a
                                    :href="contactCardUrl(user)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="col-span-2 inline-flex h-11 flex-1 cursor-pointer items-center justify-center rounded-md border px-3 text-sm transition-transform duration-150 hover:scale-[1.02] hover:bg-neutral-50"
                                >
                                    <LinkIcon class="mr-2 h-4 w-4" /> Contact
                                    Card
                                </a>
                                <button
                                    type="button"
                                    @click.stop="openQrDialog(user)"
                                    class="hover:bg-neutral-5 inline-flex h-11 flex-1 cursor-pointer items-center justify-center rounded-md border px-3 text-sm transition-transform duration-150 hover:scale-[1.02]"
                                    aria-label="View contact card QR"
                                >
                                    <QrCodeIcon class="mr-2 h-4 w-4" /> QR
                                </button>
                            </div>
                            <div v-if="canGenerate(user)" class="flex w-full">
                                <button
                                    type="button"
                                    class="inline-flex h-11 flex-1 cursor-pointer items-center justify-center rounded-md bg-secondary px-3 text-sm font-medium text-black/80 transition-transform duration-150 hover:scale-[1.02] hover:opacity-90 disabled:opacity-60"
                                    @click.stop="openGenerateDialog(user)"
                                >
                                    {{
                                        contactCardUrl(user)
                                            ? 'Regenerate'
                                            : 'Generate'
                                    }}
                                    Card
                                </button>
                            </div>
                        </template>
                    </ResourceCard>
                </template>
            </ResourceGrid>
            <div v-else class="text-sm text-neutral-600">No users found.</div>
        </div>
        <!-- Generate Contact Card Dialog -->
        <Dialog :open="showDialog" @update:open="(v) => (showDialog = v)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Generate Contact Card</DialogTitle>
                </DialogHeader>

                <div class="space-y-3 py-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Company</label
                        >
                        <select
                            class="w-full rounded-md border px-3 py-2 text-sm"
                            v-model="selectedCompanyId"
                            :disabled="loadingCompanies"
                        >
                            <option :value="null" disabled>
                                Select company
                            </option>
                            <option
                                v-for="c in companies"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Team</label
                        >
                        <select
                            class="w-full rounded-md border px-3 py-2 text-sm"
                            v-model="selectedTeamId"
                            :disabled="loadingTeams || !selectedCompanyId"
                        >
                            <option :value="null" disabled>
                                {{
                                    selectedCompanyId
                                        ? 'Select team'
                                        : 'Select company first'
                                }}
                            </option>
                            <option
                                v-for="t in teams"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <DialogFooter>
                    <button
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-md border px-3 text-sm transition-transform duration-150 hover:scale-[1.02] hover:bg-neutral-50"
                        @click="showDialog = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-3 text-sm text-white transition-transform duration-150 hover:scale-[1.02] hover:opacity-90 disabled:opacity-50"
                        :disabled="!selectedCompanyId || !selectedTeamId"
                        @click="submitGenerate"
                    >
                        Generate
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <Dialog :open="showQrDialog" @update:open="(v) => (showQrDialog = v)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Contact Card QR</DialogTitle>
                </DialogHeader>

                <div class="flex flex-col items-center gap-3 py-2">
                    <div
                        class="aspect-square w-64 overflow-hidden rounded-md border"
                    >
                        <img
                            v-if="currentQrDataUrl"
                            :src="currentQrDataUrl"
                            alt="Contact Card QR"
                            class="h-full w-full object-contain"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-sm text-neutral-500"
                        >
                            Generating...
                        </div>
                    </div>

                    <div
                        class="mt-2 flex w-full items-center justify-end gap-2"
                    >
                        <button
                            type="button"
                            class="inline-flex h-11 items-center justify-center rounded-md border px-3 text-sm transition-transform duration-150 hover:scale-[1.02] hover:bg-neutral-50"
                            @click="showQrDialog = false"
                        >
                            Close
                        </button>
                        <a
                            v-if="currentQrDataUrl"
                            :href="currentQrDataUrl"
                            download="contact-card-qr.png"
                            class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-3 text-sm text-white transition-transform duration-150 hover:scale-[1.02] hover:opacity-90"
                        >
                            Download
                        </a>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
