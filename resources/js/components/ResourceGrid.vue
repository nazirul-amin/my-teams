<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    items: any[];
    isLoading: boolean;
    hasMore: boolean;
}>();

const emit = defineEmits<{
    (e: 'load-more'): void;
}>();

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;
let hasUserScrolled = false;

function setupObserver() {
    if (!sentinel.value || !props.hasMore) return;

    observer = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !props.isLoading && props.hasMore) {
            emit('load-more');
        }
    });

    observer.observe(sentinel.value);
}

onMounted(() => {
    const onScroll = () => {
        if (window.scrollY > 0) {
            hasUserScrolled = true;
            setupObserver();
            window.removeEventListener('scroll', onScroll);
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    if (observer && sentinel.value) {
        observer.unobserve(sentinel.value);
    }
    observer = null;
    hasUserScrolled = false;
});

watch(
    () => props.hasMore,
    () => {
        if (observer && sentinel.value) {
            observer.unobserve(sentinel.value);
            observer = null;
        }
        if (hasUserScrolled) {
            setupObserver();
        }
    },
);
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <slot
                v-for="item in items"
                :key="item.id ?? item.key ?? JSON.stringify(item)"
                :item="item"
            />
        </div>

        <div
            v-if="hasMore"
            ref="sentinel"
            class="flex items-center justify-center py-2"
        >
            <span v-if="isLoading" class="text-xs text-neutral-500">
                Loading more...
            </span>
        </div>
    </div>
</template>
