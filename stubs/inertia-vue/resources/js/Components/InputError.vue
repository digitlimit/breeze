<script setup lang="ts">
// ts-begin
defineProps<{
    message?: string;
}>();
// ts-end
// js-begin
defineProps({
    message: {
        type: String,
    },
});
// js-end
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-600 dark:text-red-400">
            {{ message }}
        </p>
    </div>
</template>
