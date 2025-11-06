<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
  team: any,
}>()

const breadcrumbs = [
  { title: 'Teams', href: '/teams' },
  { title: props.team?.name, href: `/teams/${props.team?.id}` },
]
</script>

<template>
  <Head :title="`View ${props.team?.name || 'Team'}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <div class="space-y-2">
        <div class="text-xl font-semibold">{{ props.team?.name }}</div>
        <div class="text-sm text-muted-foreground">Slug: {{ props.team?.slug }}</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <div class="font-medium">Company</div>
          <div class="text-sm">{{ props.team?.company?.name || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Website</div>
          <div class="text-sm break-all">{{ props.team?.website || '-' }}</div>
        </div>
      </div>

      <div class="pt-4">
        <Link href="/teams" class="inline-flex items-center rounded-md border px-4 py-2 text-sm">Back</Link>
      </div>
    </div>
  </AppLayout>
</template>
