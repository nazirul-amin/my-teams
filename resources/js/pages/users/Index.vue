<script setup lang="ts">
import ResourceCard from '@/components/ResourceCard.vue';
import ResourceGrid from '@/components/ResourceGrid.vue';
import UiButton from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { is } from 'laravel-permission-to-vuejs';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    users: any;
    filters: Record<string, any>;
}>();

const search = ref<string>(props.filters.search || '');
const pageIndex = ref<number>(Number((props.users?.current_page ?? 1) - 1));
const canManage = computed(() => Boolean(is('admin') || is('super-admin')));

const breadcrumbs = [{ title: 'Users', href: '/users' }];

const items = ref<any[]>(props.users?.data || []);
const isLoadingMore = ref(false);
const hasMore = computed(
    () =>
        Number(props.users?.current_page || 1) <
        Number(props.users?.last_page || 1),
);

function goto(params: Record<string, any> = {}) {
    router.get(
        '/users',
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
                        placeholder="Search users..."
                        class="w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                    />
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <Link v-if="canManage" href="/users/create">
                        <UiButton>Create</UiButton>
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
                        :photo="user.profile?.photo ?? null"
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
                        :can-manage="canManage"
                        :show-url="`/users/${user.id}`"
                        :edit-url="`/users/${user.id}/edit`"
                        :delete-url="`/users/${user.id}`"
                    />
                </template>
            </ResourceGrid>
            <div v-else class="text-sm text-neutral-600">No users found.</div>
        </div>
    </AppLayout>
</template>
