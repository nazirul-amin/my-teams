<script setup lang="ts">
import UiButton from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Link, router } from '@inertiajs/vue3';
import { DeleteIcon, EditIcon } from 'lucide-vue-next';

interface LineItem {
    label?: string | null;
    value: string;
    href?: string | null;
}

const props = defineProps<{
    title: string;
    subtitle?: string | null;
    photo?: string | null;
    isAvatar?: boolean;
    lines?: LineItem[];
    canManage: boolean;
    showUrl?: string;
    editUrl?: string;
    deleteUrl?: string;
}>();

function onDelete() {
    if (!props.canManage || !props.deleteUrl) return;
    if (confirm('Delete this item?')) {
        router.delete(props.deleteUrl, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Card
        class="relative overflow-hidden rounded-3xl border-2 border-transparent bg-white/80 shadow-[0_10px_30px_-10px_rgba(108,92,231,0.18)] transition-all duration-300 hover:-translate-y-2 hover:border-violet-200/80 hover:shadow-[0_18px_45px_-18px_rgba(108,92,231,0.4)]"
    >
        <div
            class="pointer-events-none absolute -left-28 -top-28 h-72 w-72 rounded-e-full bg-violet-100/70"
        />
        <CardHeader
            class="relative z-10 flex flex-col items-center gap-2 pb-2 pt-4 text-center"
        >
            <img
                :src="photo ?? 'https://placehold.co/160x160'"
                alt="photo"
                :class="{
                    'h-20 w-20 rounded-[24px] bg-orange-100/80 shadow-md ring-4 ring-white/80':
                        isAvatar,
                    'h-20 rounded-2xl bg-slate-50 shadow-md ring-4 ring-white/80':
                        !isAvatar,
                    'object-cover': !photo,
                    'object-contain': photo,
                }"
            />
            <div class="flex flex-col gap-0.5">
                <CardTitle
                    class="text-base font-extrabold tracking-tight text-slate-700 capitalize"
                >
                    {{ title }}
                </CardTitle>
                <CardDescription
                    v-if="subtitle"
                    class="text-[13px] font-medium text-slate-500"
                >
                    {{ subtitle }}
                </CardDescription>
            </div>
        </CardHeader>

        <CardContent class="relative z-10 flex-1 space-y-1.5 px-3 text-left">
            <div
                v-for="(line, index) in lines || []"
                :key="index"
                class="flex items-center text-xs text-slate-700"
            >
                <template v-if="line.label">
                    <span
                        class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                        :title="`${line.label}: ${line.value}`"
                    >
                        {{ line.label }}
                    </span>
                    <span class="ml-2 flex-1 truncate" :title="line.value">
                        <a
                            v-if="line.href"
                            :href="line.href"
                            class="inline-flex max-w-full items-center text-xs font-semibold text-slate-800 underline-offset-2 hover:text-violet-600 hover:underline"
                        >
                            {{ line.value }}
                        </a>
                        <span
                            v-else
                            class="inline-flex max-w-full items-center text-xs font-semibold text-slate-800"
                        >
                            {{ line.value }}
                        </span>
                    </span>
                </template>
                <template v-else>
                    <span class="flex-1 truncate" :title="line.value">
                        <a
                            v-if="line.href"
                            :href="line.href"
                            class="inline-flex max-w-full items-center text-xs font-semibold text-slate-800 underline-offset-2 hover:text-violet-600 hover:underline"
                        >
                            {{ line.value }}
                        </a>
                        <span
                            v-else
                            class="inline-flex max-w-full items-center text-xs font-semibold text-slate-800"
                        >
                            {{ line.value }}
                        </span>
                    </span>
                </template>
            </div>
        </CardContent>

        <CardFooter>
            <div
                class="relative z-10 mt-0.5 flex w-full flex-col gap-2 border-t-2 border-dashed border-slate-100 pt-2"
            >
                <slot name="actions" />
                <template v-if="canManage">
                    <div class="flex w-full justify-between gap-2">
                        <Link v-if="editUrl" :href="editUrl" class="flex-1">
                            <UiButton
                                class="inline-flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border-amber-100 bg-amber-50/70 text-xs font-semibold text-slate-800 shadow-sm transition-transform duration-150 hover:scale-[1.03] hover:bg-amber-100"
                                size="sm"
                                variant="outline"
                            >
                                <EditIcon class="h-4 w-4" />
                            </UiButton>
                        </Link>
                        <UiButton
                            v-if="deleteUrl"
                            class="inline-flex h-10 flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border-red-200 bg-red-50/70 text-xs font-semibold text-red-600 shadow-sm transition-transform duration-150 hover:scale-[1.03] hover:bg-red-100"
                            size="sm"
                            variant="outline"
                            type="button"
                            aria-label="Delete resource"
                            @click.stop="onDelete"
                        >
                            <DeleteIcon class="h-4 w-4" />
                        </UiButton>
                    </div>
                </template>
            </div>
        </CardFooter>
        <div
            class="pointer-events-none absolute -right-10 -bottom-10 h-26 w-26 rounded-s-full bg-emerald-100/70"
        />
    </Card>
</template>
