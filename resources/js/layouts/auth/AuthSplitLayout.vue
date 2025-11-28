<script setup lang="ts">
import { home } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-[#FFF7F3] p-10 text-black lg:flex"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-linear-to-br from-[#C599B6] via-[#FAD0C4] to-[#FFF7F3]"
            />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                {{ name }}
            </Link>
            <div v-if="quote" class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg text-black/60">
                        &ldquo;{{ quote.message }}&rdquo;
                    </p>
                    <footer class="text-sm text-black/50">
                        {{ quote.author }}
                    </footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
