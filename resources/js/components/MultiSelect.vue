<script lang="ts" setup>
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { onClickOutside } from '@vueuse/core';
import { computed, nextTick, ref } from 'vue';

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
const searchQuery = ref('');
const placement = ref<'top' | 'bottom'>('bottom');
const selectedValues = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

// Compute dropdown placement (top or bottom) based on viewport space
const updatePlacement = () => {
    if (!dropdown.value) return;
    const rect = dropdown.value.getBoundingClientRect();
    const viewportHeight =
        window.innerHeight || document.documentElement.clientHeight;
    const spaceBelow = viewportHeight - rect.bottom;
    const estimatedDropdownHeight = 260; // px, roughly max-h + search
    placement.value = spaceBelow < estimatedDropdownHeight ? 'top' : 'bottom';
};

// Toggle open/close
const toggle = () => {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        // Recompute placement when opening
        nextTick(() => {
            updatePlacement();
        });
    }
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

// Options filtered by search and sorted: selected first, then by label
const filteredOptions = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();

    const byLabel = (label: string) => label.toLowerCase();

    const base = props.options.filter((o) =>
        query ? byLabel(o.label).includes(query) : true,
    );

    return base.slice().sort((a, b) => {
        const aSelected = selectedValues.value.includes(a.value);
        const bSelected = selectedValues.value.includes(b.value);
        if (aSelected !== bSelected) return aSelected ? -1 : 1;
        return byLabel(a.label).localeCompare(byLabel(b.label));
    });
});

onClickOutside(dropdown, () => (open.value = false));
</script>

<template>
    <Field>
        <FieldLabel v-if="label">{{ label }}</FieldLabel>

        <div ref="dropdown" class="relative">
            <!-- Display box -->
            <div
                @click="toggle"
                class="min-h-[42px] w-full cursor-pointer rounded-md border bg-background px-2 py-2 text-sm focus:ring-2 focus:ring-ring focus:ring-offset-2"
                :class="{ 'pointer-events-none opacity-50': disabled }"
            >
                <!-- Selected items as tags (scrollable area) -->
                <template v-if="selectedValues.length">
                    <div
                        class="max-h-[150px] overflow-y-auto rounded-md bg-background/60 p-1.5"
                    >
                        <div class="flex flex-wrap gap-1">
                            <div
                                v-for="value in selectedValues"
                                :key="value"
                                class="flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-primary"
                            >
                                <span>
                                    {{
                                        options.find((o) => o.value === value)
                                            ?.label ?? value
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
                        </div>
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
                    class="absolute z-10 w-full overflow-hidden rounded-md border bg-popover shadow-lg"
                    :class="
                        placement === 'top'
                            ? 'bottom-full mb-1 max-h-64'
                            : 'top-full mt-1 max-h-64'
                    "
                >
                    <!-- Search -->
                    <div class="border-b bg-popover px-3 py-2">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search users…"
                            class="w-full rounded-md border px-2 py-1 text-xs focus:ring-1 focus:ring-ring focus:outline-none"
                            @click.stop
                        />
                    </div>

                    <!-- Options -->
                    <div class="max-h-56 overflow-y-auto py-1">
                        <div
                            v-for="option in filteredOptions"
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
                        <div
                            v-if="!filteredOptions.length"
                            class="px-3 py-2 text-xs text-muted-foreground"
                        >
                            No results
                        </div>
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
