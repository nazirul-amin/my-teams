<script setup lang="ts">
import MultiSelect from '@/components/MultiSelect.vue';
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

const props = defineProps({
    teams: { type: Object, required: true }, // Laravel paginator
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search || '');
const pageIndex = ref(Number((props.teams?.current_page ?? 1) - 1));

const breadcrumbs = [{ title: 'Teams', href: '/teams' }];

const canManage = computed(() => Boolean(is('admin') || is('super-admin')));
const canEditTeams = computed(() =>
    Boolean(is('admin') || is('super-admin') || is('manager')),
);
function canAssign(team: any) {
    if (is('super-admin') || is('admin')) return true;
    // Managers can assign only if they are members of the team
    return is('manager') && Number(team?.is_member || 0) > 0;
}

const items = ref<any[]>(props.teams?.data || []);
const isLoadingMore = ref(false);
const hasMore = computed(
    () =>
        Number(props.teams?.current_page || 1) <
        Number(props.teams?.last_page || 1),
);

function goto(params: Record<string, unknown> = {}) {
    router.get(
        '/teams',
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

async function fetchAssignedUsers(teamId: string) {
    try {
        const res = await fetch(`/shared/assigned-users?team_id=${teamId}`, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('Failed to load assigned users');
        const data: Array<{ id: string }> = await res.json();
        selectedUserIds.value = data.map((u) => u.id);
    } catch (e) {
        // no-op
    }
}

watch(
    () => props.teams,
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

function prevPage() {
    if (pageIndex.value <= 0) return;
    pageIndex.value -= 1;
    goto();
}

function nextPage() {
    const lastPageIndex = Number(props.teams.last_page || 1) - 1;
    if (pageIndex.value >= lastPageIndex) return;
    pageIndex.value += 1;
    goto();
}

function loadMore() {
    if (isLoadingMore.value || !hasMore.value) return;
    isLoadingMore.value = true;
    pageIndex.value += 1;
    goto();
}

// Assign members dialog state
const showAssign = ref(false);
const targetTeam = ref<any | null>(null);
const assignableUsers = ref<Array<{ id: string; name: string; email: string }>>(
    [],
);
const selectedUserIds = ref<string[]>([]);
const loadingAssignable = ref(false);
const submittingAssign = ref(false);

async function fetchAssignableUsers(teamId: string) {
    loadingAssignable.value = true;
    try {
        const res = await fetch(`/shared/assignable-users?team_id=${teamId}`, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('Failed to load users');
        assignableUsers.value = await res.json();
    } catch (e) {
        // no-op
    } finally {
        loadingAssignable.value = false;
    }
}

async function openAssignDialog(team: any) {
    targetTeam.value = team;
    selectedUserIds.value = [];
    showAssign.value = true;
    await Promise.all([
        fetchAssignableUsers(team.id),
        fetchAssignedUsers(team.id),
    ]);
}

function submitAssign() {
    if (!targetTeam.value || !selectedUserIds.value.length) return;
    submittingAssign.value = true;
    router.post(
        `/teams/${targetTeam.value.id}/assign-users`,
        { user_ids: selectedUserIds.value },
        {
            preserveScroll: true,
            onFinish: () => {
                submittingAssign.value = false;
            },
            onSuccess: () => {
                showAssign.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Teams" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="min-w-[200px] flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search teams..."
                        class="w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                    />
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <Link v-if="canManage" href="/teams/create">
                        <UiButton>Add Team</UiButton>
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
                <template #default="{ item }">
                    <ResourceCard
                        :title="item.name"
                        :photo="item.logo_light ?? null"
                        :lines="[
                            ...(item.website
                                ? [
                                      {
                                          label: 'Website',
                                          value: item.website,
                                      },
                                  ]
                                : []),
                            ...(item.manager_names && item.manager_names.length
                                ? [
                                      {
                                          label:
                                              item.manager_names.length > 1
                                                  ? 'Managers'
                                                  : 'Manager',
                                          value: item.manager_names.join(', '),
                                      },
                                  ]
                                : []),
                        ]"
                        :can-manage="canEditTeams"
                        :show-url="`/teams/${item.id}`"
                        :edit-url="
                            canManage || canAssign(item)
                                ? `/teams/${item.id}/edit`
                                : undefined
                        "
                        :delete-url="
                            canManage ? `/teams/${item.id}` : undefined
                        "
                    >
                        <template #actions>
                            <div v-if="canAssign(item)" class="flex w-full">
                                <button
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center rounded-md border px-3 py-2 text-sm hover:bg-neutral-50"
                                    @click.stop="openAssignDialog(item)"
                                >
                                    Assign Members
                                </button>
                            </div>
                        </template>
                    </ResourceCard>
                </template>
            </ResourceGrid>
            <div v-else class="text-sm text-neutral-600">No teams found.</div>

            <!-- Assign Members Dialog -->
            <Dialog :open="showAssign" @update:open="(v) => (showAssign = v)">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Assign Members</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-3 py-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Team</label
                            >
                            <div class="text-sm">{{ targetTeam?.name }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Users</label
                            >
                            <MultiSelect
                                v-model="selectedUserIds"
                                :options="
                                    assignableUsers.map((u) => ({
                                        label: `${u.name} <${u.email}>`,
                                        value: u.id,
                                    }))
                                "
                                :loading="loadingAssignable"
                                placeholder="Select users to assign"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm hover:bg-neutral-50"
                            @click="showAssign = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md bg-primary px-3 py-2 text-sm text-white hover:opacity-90 disabled:opacity-50"
                            :disabled="
                                !selectedUserIds.length || submittingAssign
                            "
                            @click="submitAssign"
                        >
                            Assign
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
