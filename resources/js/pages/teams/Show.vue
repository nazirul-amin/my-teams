<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    team: any;
}>();

const breadcrumbs = [
    { title: 'Teams', href: '/teams' },
    { title: props.team?.name, href: `/teams/${props.team?.id}` },
];
</script>

<template>
    <Head :title="`View ${props.team?.name || 'Team'}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="space-y-2">
                <div class="text-xl font-semibold">{{ props.team?.name }}</div>
                <div class="text-sm text-muted-foreground">
                    {{ props.team?.slug }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <div class="font-medium">Company</div>
                    <div class="text-sm">
                        {{ props.team?.company?.name || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Website</div>
                    <div class="text-sm break-all">
                        {{ props.team?.website || '-' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div class="mb-2 font-medium">Logos</div>
                    <div class="flex items-center gap-6">
                        <div class="space-y-1">
                            <div class="text-xs text-muted-foreground">
                                Light
                            </div>
                            <img
                                v-if="props.team?.logo_light"
                                :src="props.team?.logo_light"
                                alt="logo light"
                                class="h-16 w-32 rounded border object-contain"
                            />
                            <div v-else class="text-sm">-</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs text-muted-foreground">
                                Dark
                            </div>
                            <img
                                v-if="props.team?.logo_dark"
                                :src="props.team?.logo_dark"
                                alt="logo dark"
                                class="h-16 w-32 rounded border object-contain"
                            />
                            <div v-else class="text-sm">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
