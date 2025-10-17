<script setup>
import CardHeader from "./CardHeader.vue";
import CardBack from "./CardBack.vue";
const props = defineProps({
    header: String,
})
</script>

<template>
    <div class="h-full flex flex-col justify-between overflow-clip lg:rounded-lg lg:bg-white lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10">
        <div class="p-3">
            <CardHeader>
                <slot v-if="$slots.header" name="header" />
                <span v-else>
                    {{ header }}
                </span>
            </CardHeader>
        </div>
        <div class="p-3 h-full overflow-y-auto">
            <slot />
        </div>
        <div v-if="$slots.footer" class="p-3">
            <slot name="footer" />
        </div>
    </div>
</template>

<style scoped>

</style>
