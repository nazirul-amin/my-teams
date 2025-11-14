<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    company: any;
}>();

const breadcrumbs = [
    { title: 'Companies', href: '/companies' },
    { title: props.company?.name, href: `/companies/${props.company?.id}` },
];
</script>

<template>
    <Head :title="`View ${props.company?.name || 'Company'}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="space-y-2">
                <div class="text-xl font-semibold">
                    {{ props.company?.name }}
                </div>
                <div class="text-sm text-muted-foreground">
                    {{ props.company?.slug }}
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <div class="font-medium">Website</div>
                    <div class="text-sm break-all">
                        {{ props.company?.website || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Email</div>
                    <div class="text-sm break-all">
                        {{ props.company?.email || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Phone</div>
                    <div class="text-sm">{{ props.company?.phone || '-' }}</div>
                </div>
                <div>
                    <div class="font-medium">LinkedIn</div>
                    <div class="text-sm break-all">
                        {{ props.company?.linkedin || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Twitter</div>
                    <div class="text-sm break-all">
                        {{ props.company?.twitter || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Facebook</div>
                    <div class="text-sm break-all">
                        {{ props.company?.facebook || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Instagram</div>
                    <div class="text-sm break-all">
                        {{ props.company?.instagram || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">Address</div>
                    <div class="text-sm break-words">
                        {{ props.company?.address || '-' }}
                    </div>
                </div>
                <div>
                    <div class="font-medium">City</div>
                    <div class="text-sm">{{ props.company?.city || '-' }}</div>
                </div>
                <div>
                    <div class="font-medium">State</div>
                    <div class="text-sm">{{ props.company?.state || '-' }}</div>
                </div>
                <div>
                    <div class="font-medium">Country</div>
                    <div class="text-sm">
                        {{ props.company?.country || '-' }}
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
                                v-if="props.company?.logo_light"
                                :src="props.company?.logo_light"
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
                                v-if="props.company?.logo_dark"
                                :src="props.company?.logo_dark"
                                alt="logo dark"
                                class="h-16 w-32 rounded border object-contain"
                            />
                            <div v-else class="text-sm">-</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-2 font-medium">Backgrounds</div>
                    <div class="flex items-center gap-6">
                        <div class="space-y-1">
                            <div class="text-xs text-muted-foreground">
                                Light
                            </div>
                            <img
                                v-if="props.company?.bg_light"
                                :src="props.company?.bg_light"
                                alt="background light"
                                class="h-16 w-32 rounded border object-cover"
                            />
                            <div v-else class="text-sm">-</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs text-muted-foreground">
                                Dark
                            </div>
                            <img
                                v-if="props.company?.bg_dark"
                                :src="props.company?.bg_dark"
                                alt="background dark"
                                class="h-16 w-32 rounded border object-cover"
                            />
                            <div v-else class="text-sm">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
