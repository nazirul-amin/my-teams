<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ appName }}</span>
    </div>
</template>
