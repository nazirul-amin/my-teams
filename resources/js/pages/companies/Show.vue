<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
  company: any,
}>()

const breadcrumbs = [
  { title: 'Companies', href: '/companies' },
  { title: props.company?.name, href: `/companies/${props.company?.id}` },
]
</script>

<template>
  <Head :title="`View ${props.company?.name || 'Company'}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
      <div class="space-y-2">
        <div class="text-xl font-semibold">{{ props.company?.name }}</div>
        <div class="text-sm text-muted-foreground">Slug: {{ props.company?.slug }}</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <div class="font-medium">Website</div>
          <div class="text-sm break-all">{{ props.company?.website || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Email</div>
          <div class="text-sm break-all">{{ props.company?.email || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Phone</div>
          <div class="text-sm">{{ props.company?.phone || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">LinkedIn</div>
          <div class="text-sm break-all">{{ props.company?.linkedin || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Twitter</div>
          <div class="text-sm break-all">{{ props.company?.twitter || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Facebook</div>
          <div class="text-sm break-all">{{ props.company?.facebook || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Instagram</div>
          <div class="text-sm break-all">{{ props.company?.instagram || '-' }}</div>
        </div>
        <div>
          <div class="font-medium">Address</div>
          <div class="text-sm break-words">{{ props.company?.address || '-' }}</div>
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
          <div class="text-sm">{{ props.company?.country || '-' }}</div>
        </div>
      </div>

      <div class="pt-4">
        <Link href="/companies" class="inline-flex items-center rounded-md border px-4 py-2 text-sm">Back</Link>
      </div>
    </div>
  </AppLayout>
</template>
