<script setup lang="ts">
import { ref, watch, h, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import UiButton from '@/components/ui/button/Button.vue'
import DataTable from '@/components/DataTable.vue'
import { is } from 'laravel-permission-to-vuejs'

const props = defineProps({
  teams: { type: Object, required: true }, // Laravel paginator
  filters: { type: Object, required: true },
})

const search = ref(props.filters.search || '')
const perPage = ref(Number(props.filters.per_page || 10))
const pageIndex = ref(Number((props.teams?.current_page ?? 1) - 1))

const breadcrumbs = [
  { title: 'Teams', href: '/teams' },
]

const canManage = computed(() => Boolean(is('admin') || is('super-admin')))

// TanStack Table columns
const columns = [
  { accessorKey: 'name', header: () => 'Name', enableSorting: true, enableColumnFilter: true },
  { accessorKey: 'slug', header: () => 'Slug', enableSorting: true, enableColumnFilter: true },
  {
    id: 'company_name',
    header: () => 'Company',
    accessorFn: (row) => row?.company?.name ?? '',
    enableSorting: false,
  },
  {
    id: 'actions',
    header: () => 'Actions',
    cell: ({ row }) => {
      const team = row.original
      const children: any[] = []
      if (is('admin | super-admin')) {
        children.push(
          h(Link, { href: `/teams/${team.id}/edit`, onClick: (e) => e.stopPropagation() }, {
            default: () => h(UiButton, { size: 'sm', variant: 'outline' }, { default: () => 'Edit' })
          }),
          h(UiButton, {
            size: 'sm',
            variant: 'destructive',
            onClick: (e) => {
              e.stopPropagation()
              if (confirm('Delete this team?')) {
                router.delete(`/teams/${team.id}`, { preserveScroll: true })
              }
            }
          }, { default: () => 'Delete' })
        )
      } else if (is('manager')) {
        children.push(
          h(Link, { href: `/teams/${team.id}/edit`, onClick: (e) => e.stopPropagation() }, {
            default: () => h(UiButton, { size: 'sm', variant: 'outline' }, { default: () => 'Edit' })
          }),
        )
      } else {
        children.push(
          h(Link, { href: `/teams/${team.id}`, onClick: (e) => e.stopPropagation() }, {
            default: () => h(UiButton, { size: 'sm', variant: 'secondary' }, { default: () => 'Show' })
          }),
        )
      }
      return h('div', { class: 'flex gap-2' }, children)
    },
  },
]

function goto(params = {}) {
  router.get('/teams', {
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
  <Head title="Teams" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6 flex items-center gap-3">
        <div class="ml-auto flex items-center gap-2">
          <Link v-if="canManage" href="/teams/create">
            <UiButton>Create</UiButton>
          </Link>
        </div>
      </div>

      <DataTable
        :columns="columns"
        :data="teams.data || []"
        :manual-pagination="true"
        v-model:pageIndex="pageIndex"
        :page-count="Number(teams.last_page || 1)"
        v-model:globalFilter="search"
        v-model:pageSize="perPage"
        :total-count="Number(teams.total || 0)"
        @update:pageIndex="() => goto()"
        @update:pageSize="() => goto({ page: 1 })"
      />
    </div>
  </AppLayout>
</template>
