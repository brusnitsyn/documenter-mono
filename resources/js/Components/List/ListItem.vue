<script setup>
import {onMounted, ref} from "vue";

const componentRef = ref()
</script>

<template>
    <div ref="componentRef"
         @mouseenter="componentRef.setAttribute('data-hover', '')"
         @mouseleave="componentRef.removeAttribute('data-hover')"
         class="flex flex-col max-lg:flex-row gap-y-1 px-2 sm:py-2 py-2.5 rounded-md transition-all data-hover:bg-zinc-950/5 dark:data-hover:bg-white/5 active:scale-[.99]">
        <div v-if="$slots.header || $slots.actions" class="flex justify-between items-center">
            <div v-if="$slots.header" class="text-sm font-medium">
                <slot name="header" />
            </div>
            <div v-if="$slots.actions">
                <slot name="actions" />
            </div>
        </div>
        <div v-if="$slots.default">
            <slot />
        </div>
        <div v-if="$slots.footer">
            <slot name="footer" />
        </div>
    </div>
</template>

<style scoped>

</style>
