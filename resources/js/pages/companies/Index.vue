<script setup>
import { ref, watch, h } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import UiButton from '@/components/ui/button/Button.vue'
import UiInput from '@/components/ui/input/Input.vue'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  companies: { type: Object, required: true }, // Laravel paginator
  filters: { type: Object, required: true },
})

const search = ref(props.filters.search || '')
const perPage = ref(Number(props.filters.per_page || 10))
const pageIndex = ref(Number((props.companies?.current_page ?? 1) - 1))

const breadcrumbs = [
    {
        title: 'Companies',
        href: '/companies',
    },
];

// TanStack Table columns
const columns = [
  { accessorKey: 'name', header: 'Name', enableSorting: true, enableColumnFilter: true },
  { accessorKey: 'slug', header: 'Slug', enableSorting: true, enableColumnFilter: true },
  { accessorKey: 'phone', header: 'Phone', enableSorting: true, enableColumnFilter: true },
  {
    id: 'creator_name',
    header: 'Created By',
    accessorFn: (row) => row?.creator?.name ?? '',
    enableSorting: false,
  },
  {
    id: 'actions',
    header: 'Actions',
    cell: ({ row }) => {
      const company = row.original
      return h('div', { class: 'flex gap-2' }, [
        h(Link, { href: `/companies/${company.id}/edit`, onClick: (e) => e.stopPropagation() }, {
          default: () => h(UiButton, { size: 'sm', variant: 'outline' }, { default: () => 'Edit' })
        }),
        h(UiButton, {
          size: 'sm',
          variant: 'destructive',
          onClick: (e) => {
            e.stopPropagation()
            if (confirm('Delete this company?')) {
              router.delete(`/companies/${company.id}`, { preserveScroll: true })
            }
          }
        }, { default: () => 'Delete' })
      ])
    },
  },
]

function goto(params = {}) {
  router.get('/companies', {
    search: search.value || undefined,
    per_page: perPage.value,
    page: pageIndex.value + 1,
    ...params,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

watch(search, (v) => {
  // debounce could be added; keep immediate for now
  pageIndex.value = 0
  goto()
})

watch(perPage, () => {
  pageIndex.value = 0
  goto()
})
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
      <div class="p-6">
        <div class="mb-6 flex items-center gap-3">
          <div class="ml-auto flex items-center gap-2">
            <Link href="/companies/create">
              <UiButton>Create</UiButton>
            </Link>
          </div>
        </div>

        <DataTable
          :columns="columns"
          :data="companies.data || []"
          :manual-pagination="true"
          v-model:pageIndex="pageIndex"
          :page-count="Number(companies.last_page || 1)"
          v-model:globalFilter="search"
          v-model:pageSize="perPage"
          :total-count="Number(companies.total || 0)"
          @update:pageIndex="() => goto()"
          @update:pageSize="() => goto({ page: 1 })"
        />
      </div>
    </AppLayout>
</template>
