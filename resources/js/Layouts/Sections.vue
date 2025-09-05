<script setup>

</script>

<template>
    <main class="relative isolate h-screen w-full grid lg:grid-cols-[320px_1fr_280px] bg-white dark:text-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950 p-2 gap-2">
        <!-- Left Sidebar -->
        <div v-if="$slots.leftbar" class="h-full flex flex-col lg:rounded-lg lg:bg-white lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10 min-h-0 scrollbar-thin scrollbar- scrollbar-thumb-zinc-300 scrollbar-track-zinc-100 hover:scrollbar-thumb-zinc-400 dark:scrollbar-thumb-zinc-600 dark:scrollbar-track-zinc-800 dark:hover:scrollbar-thumb-zinc-500 scrollbar-rounded">
            <div class="p-3 h-full overflow-y-auto">
                <slot name="leftbar" />
            </div>
        </div>

        <!-- Main Content -->
        <div class="h-full flex flex-col min-h-0">
            <div class="flex-1 p-4 lg:rounded-lg lg:bg-white lg:p-8 lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10 overflow-y-auto">
                <slot />
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="h-full flex flex-col lg:rounded-lg lg:bg-white lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10 min-h-0 ">
            <div class="p-3 h-full overflow-y-auto">
                <slot name="rightbar" />
            </div>
        </div>
    </main>
</template>

<style scoped>

</style>
