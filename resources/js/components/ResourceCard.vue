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
        class="rounded-xl shadow-sm transition-transform duration-150 hover:-translate-y-0.5 hover:shadow-md hover:shadow-neutral-200/80"
    >
        <CardHeader :class="isAvatar ? 'flex items-center gap-3' : ''">
            <img
                :src="photo ?? 'https://placehold.co/600x200'"
                alt="photo"
                :class="{
                    'h-20 w-20 rounded-full shadow-sm ring-2 ring-neutral-100 hover:ring-primary':
                        isAvatar,
                    'h-20 w-full': !isAvatar,
                    'object-cover': !photo,
                    'object-contain': photo,
                }"
            />
            <div class="flex flex-col gap-1">
                <CardTitle class="text-sm capitalize">{{ title }}</CardTitle>
                <CardDescription v-if="subtitle">
                    {{ subtitle }}
                </CardDescription>
            </div>
        </CardHeader>

        <CardContent class="flex-1 space-y-2">
            <div
                v-for="(line, index) in lines || []"
                :key="index"
                class="flex items-center text-xs text-neutral-800"
            >
                <template v-if="line.label">
                    <span
                        class="text-[11px] font-medium whitespace-nowrap text-neutral-500"
                        :title="`${line.label}: ${line.value}`"
                    >
                        {{ line.label }}:
                    </span>
                    <span class="ml-2 flex-1 truncate" :title="line.value">
                        <a
                            v-if="line.href"
                            :href="line.href"
                            class="inline-flex max-w-full items-center text-xs font-medium text-neutral-900 underline-offset-2 hover:underline"
                        >
                            {{ line.value }}
                        </a>
                        <span
                            v-else
                            class="inline-flex max-w-full items-center text-xs font-medium text-neutral-900"
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
                            class="inline-flex max-w-full items-center text-xs font-medium text-neutral-900 underline-offset-2 hover:underline"
                        >
                            {{ line.value }}
                        </a>
                        <span
                            v-else
                            class="inline-flex max-w-full items-center text-xs font-medium text-neutral-900"
                        >
                            {{ line.value }}
                        </span>
                    </span>
                </template>
            </div>
        </CardContent>

        <CardFooter>
            <div class="flex w-full flex-col gap-3 border-t pt-3">
                <slot name="actions" />
                <template v-if="canManage">
                    <div class="flex w-full justify-between gap-2">
                        <Link v-if="editUrl" :href="editUrl" class="flex-1">
                            <UiButton
                                class="inline-flex h-11 w-full cursor-pointer items-center justify-center gap-2 transition-transform duration-150 hover:scale-[1.02]"
                                size="sm"
                                variant="outline"
                            >
                                <EditIcon class="h-4 w-4" />
                            </UiButton>
                        </Link>
                        <UiButton
                            v-if="deleteUrl"
                            class="inline-flex h-11 flex-1 cursor-pointer items-center justify-center gap-2 border-red-500 text-red-600 transition-transform duration-150 hover:scale-[1.02] hover:bg-red-50"
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
    </Card>
</template>
