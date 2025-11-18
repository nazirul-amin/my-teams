<script setup lang="ts">
import UiButton from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Link, router } from '@inertiajs/vue3';

interface LineItem {
    label?: string | null;
    value: string;
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
    <Card>
        <CardHeader :class="isAvatar ? 'flex items-center gap-2' : ''">
            <img
                :src="photo ?? 'https://placehold.co/600x200'"
                alt="photo"
                :class="{
                    'h-20 w-20 rounded-full': isAvatar,
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

        <CardContent class="flex-1 space-y-1">
            <div
                v-for="(line, index) in lines || []"
                :key="index"
                class="text-xs text-neutral-700"
            >
                <template v-if="line.label">
                    <span class="font-medium">{{ line.label }}:</span>
                    <span class="ml-1">{{ line.value }}</span>
                </template>
                <template v-else>
                    <span>{{ line.value }}</span>
                </template>
            </div>
        </CardContent>

        <CardFooter>
            <div class="flex w-full flex-col gap-2">
                <slot name="actions" />
                <template v-if="canManage">
                    <div class="flex w-full justify-between gap-2">
                        <Link v-if="editUrl" :href="editUrl" class="flex-1">
                            <UiButton
                                class="w-full"
                                size="sm"
                                variant="outline"
                            >
                                Edit
                            </UiButton>
                        </Link>
                        <UiButton
                            v-if="deleteUrl"
                            class="w-full flex-1"
                            size="sm"
                            variant="destructive"
                            type="button"
                            @click.stop="onDelete"
                        >
                            Delete
                        </UiButton>
                    </div>
                </template>
            </div>
        </CardFooter>
    </Card>
</template>
