<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    modelValue: File | null;
    initialUrl?: string | null;
    accept?: string;
    disabled?: boolean;
    previewClass?: string;
    buttonText?: string;
    removed?: boolean;
    name?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', v: File | null): void;
    (e: 'update:removed', v: boolean): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const objectUrl = ref<string | null>(null);

const hasPreview = computed(() => !!previewUrl.value);

const previewUrl = computed(() => {
    if (objectUrl.value) return objectUrl.value;
    if (!props.removed && props.initialUrl) return props.initialUrl;
    return null;
});

function onChooseFile() {
    fileInput.value?.click();
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = file ? URL.createObjectURL(file) : null;
    emit('update:modelValue', file);
    if (file) emit('update:removed', true);
}

function onRemove() {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = null;
    emit('update:modelValue', null);
    emit('update:removed', true);
}

onBeforeUnmount(() => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
});

watch(
    () => props.modelValue,
    (f) => {
        if (!f && objectUrl.value) {
            URL.revokeObjectURL(objectUrl.value);
            objectUrl.value = null;
        }
    },
);
</script>

<template>
    <div class="space-y-2">
        <input
            ref="fileInput"
            type="file"
            :accept="accept ?? 'image/*'"
            :name="name"
            class="hidden"
            :disabled="disabled"
            @change="onFileChange"
        />

        <div class="group relative" v-if="hasPreview">
            <img
                :src="previewUrl as string"
                :class="[
                    'rounded-md border bg-white object-contain',
                    previewClass ?? 'h-32 w-full',
                ]"
            />
            <button
                type="button"
                class="absolute inset-0 hidden cursor-pointer place-items-center rounded-md bg-black/50 text-white group-hover:grid"
                @click="onRemove"
            >
                Remove
            </button>
        </div>

        <button
            type="button"
            class="inline-flex items-center rounded-md border bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100 disabled:opacity-50"
            :disabled="disabled"
            @click="onChooseFile"
        >
            {{ buttonText ?? (hasPreview ? 'Replace Image' : 'Upload Image') }}
        </button>
    </div>
</template>
