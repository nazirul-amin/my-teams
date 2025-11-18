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
import { is } from 'laravel-permission-to-vuejs';
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

function contactCardQrUrl(user: any): string | null {
    const abs = contactCardAbsoluteUrl(user);
    if (!abs) return null;
    const size = '512x512';
    return `https://api.qrserver.com/v1/create-qr-code/?size=${size}&data=${encodeURIComponent(abs)}`;
}

function canGenerate(user: any): boolean {
    // Super-admin OR admin who created this user OR manager (list is filtered to same team for managers)
    return Boolean(
        is('super-admin') ||
            (is('admin') && user.created_by === props.auth_id) ||
            is('manager'),
    );
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
                        :photo="user.profile?.photo ?? null"
                        :is-avatar="true"
                        :lines="[
                            ...(user.role_label
                                ? [{ label: 'Role', value: user.role_label }]
                                : []),
                            ...(user.email
                                ? [{ label: 'Email', value: user.email }]
                                : []),
                            ...(user.profile?.phone
                                ? [
                                      {
                                          label: 'Phone',
                                          value: user.profile.phone,
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
                                class="flex w-full justify-between gap-2"
                            >
                                <a
                                    :href="contactCardUrl(user)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex flex-1 cursor-pointer items-center justify-center rounded-md border p-1 text-sm hover:bg-neutral-50"
                                >
                                    Contact Card
                                </a>
                                <a
                                    :href="contactCardQrUrl(user)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="hover:bg-neutral-5 inline-flex flex-1 cursor-pointer items-center justify-center rounded-md border p-1 text-sm"
                                >
                                    QR
                                </a>
                            </div>
                            <div v-if="canGenerate(user)" class="flex w-full">
                                <button
                                    type="button"
                                    class="inline-flex flex-1 cursor-pointer items-center justify-center rounded-md border p-1 text-sm hover:bg-neutral-50"
                                    @click.stop="openGenerateDialog(user)"
                                >
                                    {{
                                        contactCardUrl(user)
                                            ? 'Regenerate'
                                            : 'Generate'
                                    }}
                                    Contact Card
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
                        class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm hover:bg-neutral-50"
                        @click="showDialog = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md bg-primary px-3 py-2 text-sm text-white hover:opacity-90 disabled:opacity-50"
                        :disabled="!selectedCompanyId || !selectedTeamId"
                        @click="submitGenerate"
                    >
                        Generate
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
