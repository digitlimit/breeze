<script setup lang="ts">
// ts-begin
defineProps<{
    value?: string;
}>();
// ts-end
// js-begin
defineProps({
    value: {
        type: String,
    },
});
// js-end
</script>

<template>
    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
