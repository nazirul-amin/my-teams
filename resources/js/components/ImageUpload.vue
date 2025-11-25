<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import VuePictureCropper, { cropper } from 'vue-picture-cropper';

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

const isShowModal = ref(false);
const pic = ref<string>('');
const pendingFile = ref<File | null>(null);

function onChooseFile() {
    fileInput.value?.click();
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    if (!file) {
        pendingFile.value = null;
        if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = null;
        emit('update:modelValue', null);
        return;
    }

    pendingFile.value = file;
    emit('update:removed', false);

    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => {
        pic.value = String(reader.result || '');
        isShowModal.value = true;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    };
}

async function onConfirmCrop() {
    if (!cropper || !pendingFile.value) {
        isShowModal.value = false;
        return;
    }

    const options: Parameters<typeof cropper.getFile>[0] = {
        fileName: pendingFile.value.name || 'image.png',
    };

    const croppedFile = await cropper.getFile(options);
    if (!croppedFile) {
        return;
    }

    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = URL.createObjectURL(croppedFile);

    if (fileInput.value) {
        const dt = new DataTransfer();
        dt.items.add(croppedFile);
        fileInput.value.files = dt.files;
    }

    emit('update:modelValue', croppedFile);
    emit('update:removed', false);

    isShowModal.value = false;
    pic.value = '';
    pendingFile.value = null;
}

function onCancelCrop() {
    isShowModal.value = false;
    pic.value = '';
    pendingFile.value = null;
}

function onRemove() {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = null;
    pic.value = '';
    pendingFile.value = null;
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
                :src="previewUrl ?? undefined"
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

        <div
            v-if="isShowModal && pic"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        >
            <div class="w-full max-w-xl rounded-md bg-white p-4 shadow-lg">
                <div class="mb-3 text-sm font-medium">Crop image</div>
                <div class="max-h-[60vh] w-full overflow-hidden">
                    <VuePictureCropper
                        :box-style="{
                            width: '100%',
                            height: '100%',
                            backgroundColor: '#f8f8f8',
                            margin: 'auto',
                        }"
                        :img="pic"
                        :options="{
                            viewMode: 1,
                            dragMode: 'crop',
                            aspectRatio: NaN,
                        }"
                    />
                </div>
                <div class="mt-4 flex justify-end gap-2 text-sm">
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1.5 hover:bg-neutral-50"
                        @click="onCancelCrop"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-primary px-3 py-1.5 text-white hover:opacity-90"
                        @click="onConfirmCrop"
                    >
                        Apply
                    </button>
                </div>
            </div>
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
