<script setup>

</script>

<template>
    <main class="relative isolate h-screen flex flex-col w-full bg-white dark:text-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950 p-2 ">
        <div class="grid lg:grid-cols-[320px_1fr_380px] gap-2 h-[calc(100vh-42px)]">
            <!-- Left Sidebar -->
            <div v-if="$slots.leftbar" class="min-h-0">
                <slot name="leftbar" />
            </div>

            <!-- Main Content -->
            <div class="h-full flex flex-col min-h-0">
                <slot />
<!--                <div class="flex-1 p-4 lg:rounded-lg lg:bg-white lg:p-8 lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10 overflow-y-auto">-->
<!--                    -->
<!--                </div>-->
            </div>

            <!-- Right Sidebar -->
            <div v-if="$slots.rightbar" class="min-h-0">
                <slot name="rightbar" />
            </div>
        </div>
        <div class="flex w-full justify-center mt-2.5 text-sm/4">
            <a href="https://aokb28.su" target="_blank">
                ГОСУДАРСТВЕННОЕ АВТОНОМНОЕ УЧРЕЖДЕНИЕ ЗДРАВООХРАНЕНИЯ АМУРСКОЙ ОБЛАСТИ "АМУРСКАЯ ОБЛАСТНАЯ КЛИНИЧЕСКАЯ БОЛЬНИЦА", 2025 год
            </a>
        </div>
    </main>
</template>

<style scoped>

</style>
