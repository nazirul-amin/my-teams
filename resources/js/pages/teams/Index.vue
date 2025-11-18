<script setup lang="ts">
import ResourceCard from '@/components/ResourceCard.vue';
import ResourceGrid from '@/components/ResourceGrid.vue';
import UiButton from '@/components/ui/button/Button.vue';
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
                            ...(item.manager?.name
                                ? [
                                      {
                                          label: 'Manager',
                                          value: item.manager.name,
                                      },
                                  ]
                                : []),
                        ]"
                        :can-manage="canEditTeams"
                        :show-url="`/teams/${item.id}`"
                        :edit-url="`/teams/${item.id}/edit`"
                        :delete-url="
                            canManage ? `/teams/${item.id}` : undefined
                        "
                    />
                </template>
            </ResourceGrid>
            <div v-else class="text-sm text-neutral-600">No teams found.</div>
        </div>
    </AppLayout>
</template>
