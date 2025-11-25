<script lang="ts" setup>
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { onClickOutside } from '@vueuse/core';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array as () => (string | number)[],
        default: () => [],
    },
    options: {
        type: Array as () => { value: string | number; label: string }[],
        required: true,
    },
    label: String,
    description: String,
    error: String,
    placeholder: {
        type: String,
        default: 'Select options',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const dropdown = ref<HTMLElement | null>(null);
const selectedValues = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

// Toggle open/close
const toggle = () => {
    if (!props.disabled) open.value = !open.value;
};

// Select/unselect logic
const toggleOption = (value: string | number) => {
    const exists = selectedValues.value.includes(value);
    const newValues = exists
        ? selectedValues.value.filter((v) => v !== value)
        : [...selectedValues.value, value];
    emit('update:modelValue', newValues);
};

// Remove selected tag
const remove = (value: string | number) => {
    emit(
        'update:modelValue',
        selectedValues.value.filter((v) => v !== value),
    );
};

onClickOutside(dropdown, () => (open.value = false));
</script>

<template>
    <Field>
        <FieldLabel v-if="label">{{ label }}</FieldLabel>

        <div ref="dropdown" class="relative">
            <!-- Display box -->
            <div
                @click="toggle"
                class="flex min-h-[42px] w-full cursor-pointer flex-wrap items-center gap-1 rounded-md border bg-background px-2 py-2 text-sm focus:ring-2 focus:ring-ring focus:ring-offset-2"
                :class="{ 'pointer-events-none opacity-50': disabled }"
            >
                <!-- Selected items as tags -->
                <template v-if="selectedValues.length">
                    <div
                        v-for="value in selectedValues"
                        :key="value"
                        class="flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-primary"
                    >
                        <span>
                            {{
                                options.find((o) => o.value === value)?.label ??
                                value
                            }}
                        </span>
                        <button
                            type="button"
                            class="text-xs hover:text-red-500"
                            @click.stop="remove(value)"
                        >
                            ✕
                        </button>
                    </div>
                </template>
                <!-- Placeholder -->
                <span v-else class="text-muted-foreground">{{
                    placeholder
                }}</span>
            </div>

            <!-- Dropdown -->
            <transition name="fade">
                <div
                    v-if="open"
                    class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border bg-popover shadow-lg"
                >
                    <div
                        v-for="option in options"
                        :key="option.value"
                        @click.stop="toggleOption(option.value)"
                        class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-accent"
                    >
                        <input
                            type="checkbox"
                            class="form-checkbox"
                            :checked="selectedValues.includes(option.value)"
                            readonly
                        />
                        <span>{{ option.label }}</span>
                    </div>
                </div>
            </transition>
        </div>

        <FieldDescription v-if="description">{{
            description
        }}</FieldDescription>
        <FieldError v-if="error">{{ error }}</FieldError>
    </Field>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
