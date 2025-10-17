<script setup>
const props = defineProps({
    header: {
        type: String,
    }
})
</script>

<template>
    <div class="px-3 pt-2 pb-4 first:rounded-t-lg first:rounded-b-sm lg:ring-1 lg:ring-zinc-950/10 dark:lg:ring-white/10 last:rounded-b-lg last:rounded-t-sm not-last:not-first:rounded-sm not-last:not-first:my-1 bg-white dark:text-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-800">
        <slot v-if="$slots.header" name="header" />
            <span v-else class="block text-sm font-medium mb-2">
                {{ header }}
            </span>
        <slot />
    </div>
</template>

<style scoped>

</style>
