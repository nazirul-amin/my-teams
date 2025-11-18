<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    user: { id: string; name: string; email: string };
}>();

const breadcrumbs = [
    { title: 'Members', href: '/members' },
    { title: props.user?.name, href: `/members/${props.user?.id}` },
];
</script>

<template>
    <Head :title="`View ${props.user?.name || 'User'}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="space-y-2">
                <div class="text-xl font-semibold">{{ props.user?.name }}</div>
                <div class="text-sm break-all text-muted-foreground">
                    {{ props.user?.email }}
                </div>
            </div>

            <div class="pt-4">
                <Link
                    href="/members"
                    class="inline-flex items-center rounded-md border px-4 py-2 text-sm"
                    >Back</Link
                >
            </div>
        </div>
    </AppLayout>
</template>
