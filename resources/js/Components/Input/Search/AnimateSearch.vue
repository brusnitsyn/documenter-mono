<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {useDebounceFn} from "@vueuse/core";

const props = defineProps({
    placeholders: {
        type: Array,
        default: () => [
            'Поиск по шаблонам...',
        ]
    },
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])
const value = ref(props.modelValue)
const currentPlaceholderIndex = ref(0)
const isAnimating = ref(false)
const showPlaceholder = ref(true)

const debounceUpdate = useDebounceFn((value) => {
    emit('update:modelValue', value)
}, 800)

// Автоматическое обновление modelValue
watch(value, (newVal) => {
    debounceUpdate(newVal)
    showPlaceholder.value = newVal === ''
})

watch(() => props.modelValue, (newVal) => {
    value.value = newVal
})

// Анимация placeholder
const animatePlaceholder = () => {
    if (isAnimating.value) return

    isAnimating.value = true

    setTimeout(() => {
        currentPlaceholderIndex.value = (currentPlaceholderIndex.value + 1) % props.placeholders.length
        isAnimating.value = false
    }, 3000) // Меняем каждые 3 секунды
}

onMounted(() => {
    animatePlaceholder()
    setInterval(animatePlaceholder, 3500) // Интервал анимации
})

const currentPlaceholder = computed(() => props.placeholders[currentPlaceholderIndex.value])
</script>

<template>
    <div class="relative w-full">
        <input
            v-model="value"
            type="text"
            :placeholder="currentPlaceholder"
            class="relative block w-full appearance-none rounded-lg px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)] text-base/6 text-zinc-950 placeholder:text-zinc-500 sm:text-sm/6 dark:text-white border border-zinc-950/10 hover:border-zinc-950/20 focus:border-zinc-950/20 dark:border-white/10 dark:hover:border-white/20 dark:focus:border-white/20 bg-transparent dark:bg-white/5 focus:outline-hidden data-invalid:border-red-500 data-invalid:data-hover:border-red-500 dark:data-invalid:border-red-600 dark:data-invalid:data-hover:border-red-600 data-disabled:border-zinc-950/20 dark:data-disabled:border-white/15 dark:data-disabled:bg-white/2.5 dark:data-hover:data-disabled:border-white/15 dark:scheme-dark"
        />

        <div v-if="showPlaceholder" class="absolute left-1 inset-0 pointer-events-none overflow-hidden">
            <div
                v-for="(ph, index) in placeholders"
                :key="ph"
                :class="[
                  'absolute inset-0 flex items-center px-[calc(--spacing(3.5)-1px)] sm:px-[calc(--spacing(3)-1px)] text-zinc-500 transition-all duration-500',
                  index === currentPlaceholderIndex ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'
                ]"
            >
                <span class="text-base/6 sm:text-sm/6">{{ ph }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Дополнительные стили для плавной анимации */
.absolute > div {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1),
    opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

input::placeholder {
    opacity: 0; /* Скрываем стандартный placeholder */
}
</style>
