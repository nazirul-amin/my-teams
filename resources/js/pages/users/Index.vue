<script setup lang="ts">
import { ref, watch, h, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import UiButton from '@/components/ui/button/Button.vue'
import DataTable from '@/components/DataTable.vue'
import { is } from 'laravel-permission-to-vuejs'

const props = defineProps<{
  users: any,
  filters: Record<string, any>,
}>()

const search = ref<string>(props.filters.search || '')
const perPage = ref<number>(Number(props.filters.per_page || 10))
const pageIndex = ref<number>(Number((props.users?.current_page ?? 1) - 1))
const canManage = computed(() => Boolean(is('admin') || is('super-admin')))

const breadcrumbs = [
  { title: 'Users', href: '/users' },
]

const columns = [
  { accessorKey: 'name', header: () => 'Name', enableSorting: true, enableColumnFilter: true },
  { accessorKey: 'email', header: () => 'Email', enableSorting: true, enableColumnFilter: true },
  {
    id: 'creator_name',
    header: () => 'Created By',
    accessorFn: (row: any) => row?.creator?.name ?? '',
    enableSorting: false,
  },
  {
    id: 'actions',
    header: () => 'Actions',
    cell: ({ row }: any) => {
      const user = row.original
      const children: any[] = []
      if (is('admin | super-admin')) {
        children.push(
          h(Link, { href: `/users/${user.id}/edit`, onClick: (e: Event) => e.stopPropagation() }, {
            default: () => h(UiButton, { size: 'sm', variant: 'outline' }, { default: () => 'Edit' })
          }),
          h(UiButton, {
            size: 'sm',
            variant: 'destructive',
            onClick: (e: Event) => {
              e.stopPropagation()
              if (confirm('Delete this user?')) {
                router.delete(`/users/${user.id}`, { preserveScroll: true })
              }
            }
          }, { default: () => 'Delete' })
        )
      } else {
        children.push(
          h(Link, { href: `/users/${user.id}`, onClick: (e: Event) => e.stopPropagation() }, {
            default: () => h(UiButton, { size: 'sm', variant: 'secondary' }, { default: () => 'Show' })
          }),
        )
      }
      return h('div', { class: 'flex gap-2' }, children)
    },
  },
]

function goto(params: Record<string, any> = {}) {
  router.get('/users', {
    search: search.value || undefined,
    per_page: perPage.value,
    page: pageIndex.value + 1,
    ...params,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

watch(search, () => {
  pageIndex.value = 0
  goto()
})

watch(perPage, () => {
  pageIndex.value = 0
  goto()
})
</script>

<template>
  <Head title="Users" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6 flex items-center gap-3">
        <div class="ml-auto flex items-center gap-2">
          <Link v-if="canManage" href="/users/create">
            <UiButton>Create</UiButton>
          </Link>
        </div>
      </div>

      <DataTable
        :columns="columns"
        :data="users.data || []"
        :manual-pagination="true"
        v-model:pageIndex="pageIndex"
        :page-count="Number(users.last_page || 1)"
        v-model:globalFilter="search"
        v-model:pageSize="perPage"
        :total-count="Number(users.total || 0)"
        @update:pageIndex="() => goto()"
        @update:pageSize="() => goto({ page: 1 })"
      />
    </div>
  </AppLayout>
</template>
