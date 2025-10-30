<script setup>

import SentryNotification from "../Components/Notifications/SentryNotification.vue";
</script>

<template>
    <main class="relative isolate flex min-h-screen max-h-screen w-full bg-white dark:text-white max-lg:flex-col lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950">
        <slot name="sidebar" />
        <div class="flex min-h-screen max-h-screen flex-col p-2 w-full">
            <div class="grow p-4 lg:rounded-lg max-h-[calc(100vh-16px)] lg:bg-white lg:p-8 lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10">
                <slot />
            </div>
            <div class="flex w-full justify-center mt-2.5 text-sm/4">
                <a href="https://aokb28.su" target="_blank">
                    ГОСУДАРСТВЕННОЕ АВТОНОМНОЕ УЧРЕЖДЕНИЕ ЗДРАВООХРАНЕНИЯ АМУРСКОЙ ОБЛАСТИ "АМУРСКАЯ ОБЛАСТНАЯ КЛИНИЧЕСКАЯ БОЛЬНИЦА", 2025 год
                </a>
            </div>
        </div>
        <SentryNotification />
    </main>
</template>

<style scoped>

</style>
