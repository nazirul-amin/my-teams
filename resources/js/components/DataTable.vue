<script lang="ts" setup>
import type { ColumnDef } from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';

import UiButton from '@/components/ui/button/Button.vue';
import UiInput from '@/components/ui/input/Input.vue';

const props = defineProps<{
    columns: ColumnDef<any, any>[];
    data: any[];
    manualPagination?: boolean;
    pageCount?: number;
    pageIndex?: number;
    pageSize?: number;
    pageSizes?: number[];
    enableGlobalFilter?: boolean;
    globalFilter?: string | null;
    totalCount?: number | null;
    tableClass?: string;
}>();

const emit = defineEmits([
    'update:pageIndex',
    'update:pageSize',
    'update:globalFilter',
    'row:click',
]);

const pageIndex = ref<number>(props.pageIndex ?? 0);
const pageSize = ref<number>(props.pageSize ?? 10);
const globalFilter = ref(props.globalFilter ?? '');

watch(
    () => props.pageIndex,
    (v) => {
        if (typeof v === 'number' && v !== pageIndex.value) {
            pageIndex.value = v;
        }
    },
);
watch(
    () => props.pageSize,
    (v) => {
        if (typeof v === 'number' && v !== pageSize.value) {
            pageSize.value = v;
        }
    },
);
watch(
    () => props.globalFilter,
    (v) => {
        if (v !== globalFilter.value) globalFilter.value = v || '';
    },
);

const pageCountValue = computed<number>(() => props.pageCount ?? -1);

const table = useVueTable<any>({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    state: {
        get pagination() {
            return { pageIndex: pageIndex.value, pageSize: pageSize.value };
        },
        get globalFilter() {
            return globalFilter.value;
        },
    },
    manualPagination: props.manualPagination ?? false,
    get pageCount() {
        return (props.manualPagination ?? false)
            ? pageCountValue.value
            : undefined;
    },
    onPaginationChange: (updater) => {
        const current = {
            pageIndex: pageIndex.value,
            pageSize: pageSize.value,
        };
        const nextState =
            typeof updater === 'function' ? updater(current) : (updater as any);
        if (
            typeof nextState.pageIndex === 'number' &&
            nextState.pageIndex !== pageIndex.value
        ) {
            pageIndex.value = nextState.pageIndex;
            emit('update:pageIndex', pageIndex.value);
        }
        if (
            typeof nextState.pageSize === 'number' &&
            nextState.pageSize !== pageSize.value
        ) {
            pageSize.value = nextState.pageSize;
            emit('update:pageSize', pageSize.value);
        }
    },
    onGlobalFilterChange: (val) => {
        globalFilter.value = val ?? '';
        emit('update:globalFilter', globalFilter.value);
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    enableSortingRemoval: false,
});

const pageInfo = computed(() => {
    const current = pageIndex.value + 1;
    const pageCount = props.manualPagination
        ? props.pageCount === -1
            ? current
            : props.pageCount
        : table.getPageCount();
    return { current, pageCount };
});

const totalRows = computed(() => {
    if (props.manualPagination && props.totalCount != null)
        return props.totalCount;
    // client-side: use filtered rows length
    return table.getFilteredRowModel().rows.length;
});

function onHeaderClick(header: any) {
    if (header.column.getCanSort()) {
        header.column.toggleSorting(undefined, { multi: false });
    }
}

function sortIndicator(column: any) {
    const dir = column.getIsSorted();
    if (dir === 'asc') return '▲';
    if (dir === 'desc') return '▼';
    return '';
}

const pageSizesTyped = computed<number[]>(() => props.pageSizes as number[]);
</script>

<template>
    <div class="space-y-3">
        <div v-if="enableGlobalFilter" class="flex items-center gap-2">
            <UiInput
                :model-value="globalFilter"
                @update:model-value="(v) => table.setGlobalFilter(v)"
                placeholder="Search..."
                class="max-w-xs"
            />
            <UiButton
                variant="outline"
                size="sm"
                @click="() => table.resetColumnFilters()"
                >Reset filters</UiButton
            >
            <div class="ml-auto flex items-center gap-2">
                <label for="per-page" class="text-sm text-muted-foreground"
                    >Rows per page</label
                >
                <select
                    id="per-page"
                    class="h-9 rounded-md border bg-background px-2 text-sm"
                    :value="pageSize"
                    @change="
                        (e) =>
                            table.setPageSize(
                                Number((e.target as HTMLSelectElement).value),
                            )
                    "
                >
                    <option
                        v-for="size in pageSizesTyped"
                        :key="Number(size)"
                        :value="Number(size)"
                    >
                        {{ size }}
                    </option>
                </select>
            </div>
        </div>

        <div :class="tableClass">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="String(headerGroup.id)"
                    >
                        <th
                            v-for="header in headerGroup.headers"
                            :key="String(header.id)"
                            class="border-b px-3 py-2 text-left font-medium select-none"
                            @click="onHeaderClick(header)"
                        >
                            <span class="inline-flex items-center gap-1">
                                <span>
                                    <template v-if="!header.isPlaceholder">
                                        <FlexRender
                                            :render="
                                                header.column.columnDef.header
                                            "
                                            v-bind="header.getContext()"
                                        />
                                    </template>
                                </span>
                                <span
                                    v-if="header.column.getCanSort()"
                                    class="text-xs text-muted-foreground"
                                    >{{ sortIndicator(header.column) }}</span
                                >
                            </span>
                        </th>
                    </tr>
                    <!-- Column filters row -->
                    <tr>
                        <th
                            v-for="column in table.getVisibleLeafColumns()"
                            :key="String(column.id)"
                            class="border-b px-3 py-2 text-left"
                        >
                            <div v-if="column.getCanFilter()">
                                <UiInput
                                    :model-value="
                                        String(column.getFilterValue() ?? '')
                                    "
                                    @update:model-value="
                                        (v) => column.setFilterValue(v)
                                    "
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="table.getRowModel().rows.length">
                        <tr
                            v-for="row in table.getRowModel().rows"
                            :key="String(row.id)"
                            class="cursor-pointer border-b hover:bg-muted/40"
                            @click="emit('row:click', row.original)"
                        >
                            <td
                                v-for="cell in row.getVisibleCells()"
                                :key="String(cell.id)"
                                class="px-3 py-2"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    v-bind="cell.getContext()"
                                />
                            </td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td
                            :colspan="table.getAllColumns().length"
                            class="px-3 py-6 text-center text-muted-foreground"
                        >
                            No results.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-3 text-sm">
            <div class="text-muted-foreground">
                Showing
                <span class="font-medium">{{
                    table.getRowModel().rows.length
                        ? pageIndex * pageSize + 1
                        : 0
                }}</span>
                to
                <span class="font-medium">{{
                    Math.min((pageIndex + 1) * pageSize, totalRows)
                }}</span>
                of
                <span class="font-medium">{{ totalRows }}</span>
                results
            </div>

            <div class="flex items-center gap-2">
                <UiButton
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.setPageIndex(0)"
                >
                    « First
                </UiButton>
                <UiButton
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    ‹ Prev
                </UiButton>
                <div class="px-2">
                    Page {{ pageInfo.current }} of {{ pageInfo.pageCount }}
                </div>
                <UiButton
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    Next ›
                </UiButton>
                <UiButton
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanNextPage()"
                    @click="table.setPageIndex(table.getPageCount() - 1)"
                >
                    Last »
                </UiButton>
            </div>
        </div>
    </div>
</template>
