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
        <CardHeader>
            <img
                :src="photo ?? 'https://placehold.co/400'"
                alt="photo"
                class="h-24 w-full object-contain"
            />
            <div class="flex flex-col gap-1">
                <CardTitle class="text-sm">{{ title }}</CardTitle>
                <CardDescription v-if="subtitle">
                    {{ subtitle }}
                </CardDescription>
            </div>
        </CardHeader>

        <CardContent class="flex-1 space-y-2">
            <div
                v-for="(line, index) in lines || []"
                :key="index"
                class="text-sm text-neutral-700"
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
            <template v-if="canManage">
                <div class="flex w-full justify-end gap-2">
                    <Link v-if="editUrl" :href="editUrl">
                        <UiButton size="default" variant="outline"
                            >Edit</UiButton
                        >
                    </Link>
                    <UiButton
                        v-if="deleteUrl"
                        size="default"
                        variant="destructive"
                        type="button"
                        @click.stop="onDelete"
                    >
                        Delete
                    </UiButton>
                </div>
            </template>
        </CardFooter>
    </Card>
</template>
