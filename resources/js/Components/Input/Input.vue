<script setup>
const value = defineModel('value')
const props = defineProps({
    label: String,
    disabled: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div class="flex flex-col">
        <span v-if="label" class="text-sm mb-0.5">
            {{ label }}
        </span>
        <input v-model="value"
               v-bind="$attrs"
               :disabled="disabled"
               :data-disabled="disabled"
               class="relative block w-full appearance-none rounded-lg px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)] text-base/6 text-zinc-950 placeholder:text-zinc-500 sm:text-sm/6 dark:text-white border border-zinc-950/10 hover:border-zinc-950/20 focus:border-zinc-950/20 dark:border-white/10 dark:hover:border-white/20 dark:focus:border-white/20 bg-transparent dark:bg-white/5 focus:outline-hidden data-invalid:border-red-500 data-invalid:data-hover:border-red-500 dark:data-invalid:border-red-600 dark:data-invalid:data-hover:border-red-600 data-[disabled=true]:border-zinc-950/20 dark:data-[disabled=true]:border-white/15 data-[disabled=true]:text-zinc-950/35 dark:data-[disabled=true]:text-white/35 dark:data-[disabled=true]:bg-white/2.5 dark:data-hover:data-disabled:border-white/15 dark:scheme-dark" />
    </div>
</template>

<style scoped>

</style>
