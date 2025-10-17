<script setup>
import {computed, watch} from 'vue'

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    titleId: {
        type: String,
        default: () => `modal-title-${Math.random().toString(36).substr(2, 9)}`
    },
    descriptionId: {
        type: String,
        default: () => `modal-description-${Math.random().toString(36).substr(2, 9)}`
    },
    panelId: {
        type: String,
        default: () => `modal-panel-${Math.random().toString(36).substr(2, 9)}`
    }
})

const emit = defineEmits(['close'])

// Блокировка скролла при открытии модального окна
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

// Очистка при размонтировании
import { onUnmounted } from 'vue'
onUnmounted(() => {
    document.body.style.overflow = ''
})
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            role="dialog"
            tabindex="-1"
            aria-modal="true"
            :data-headlessui-state="open ? 'open' : 'closed'"
            :aria-labelledby="titleId"
            :aria-describedby="descriptionId"
            class="fixed inset-0 z-50"
        >
            <!-- Backdrop -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 flex w-screen justify-center overflow-y-auto bg-zinc-950/25 px-2 py-2 sm:px-6 sm:py-8 lg:px-8 lg:py-16 dark:bg-zinc-950/50"
                    aria-hidden="true"
                    :data-headlessui-state="open ? 'open' : 'closed'"
                    @click="$emit('close')"
                />
            </transition>

            <!-- Modal content -->
            <div class="fixed inset-0 w-screen overflow-y-auto pt-6 sm:pt-0">
                <div class="grid min-h-full grid-rows-[1fr_auto] justify-items-center sm:grid-rows-[1fr_auto_3fr] sm:p-4">
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-12 opacity-0 sm:translate-y-0 sm:scale-95"
                        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-to-class="translate-y-12 opacity-0 sm:translate-y-0 sm:scale-95"
                    >
                        <div
                            v-if="open"
                            class="sm:max-w-lg row-start-2 w-full min-w-0 rounded-t-3xl bg-white p-8 shadow-lg ring-1 ring-zinc-950/10 sm:mb-auto sm:rounded-2xl dark:bg-zinc-900 dark:ring-white/10 forced-colors:outline will-change-transform"
                            :id="panelId"
                            :data-headlessui-state="open ? 'open' : 'closed'"
                        >
                            <!-- Title slot -->
                            <slot name="title" :titleId="titleId">
                                <h2
                                    v-if="title"
                                    class="text-lg/6 font-semibold text-balance text-zinc-950 sm:text-base/6 dark:text-white"
                                    :id="titleId"
                                >
                                    {{ title }}
                                </h2>
                            </slot>

                            <!-- Description slot -->
                            <slot name="description" :descriptionId="descriptionId">
                                <p
                                    v-if="description"
                                    class="mt-2 text-pretty text-base/6 text-zinc-500 sm:text-sm/6 dark:text-zinc-400"
                                    :id="descriptionId"
                                >
                                    {{ description }}
                                </p>
                            </slot>

                            <!-- Default content slot -->
                            <div class="mt-6 max-h-[520px] overflow-y-auto p-0.5 pr-2">
                                <slot></slot>
                            </div>

                            <!-- Actions slot -->
                            <div class="mt-8 flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                                <slot name="actions"></slot>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>

</style>
