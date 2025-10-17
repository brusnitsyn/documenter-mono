<script setup>
import {computed, onMounted, onUnmounted, ref} from "vue";
import {normalizeOptions} from "../../Utils/optionsFormatter.js";

const props = defineProps({
    options: {
        type: Array,
        required: true,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Выберите вариант'
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const model = defineModel('value')

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const containerRef = ref(null)

// Формирование options
const normalizedOptions = computed(() => {
    return normalizeOptions(props.options)
})

// Находим выбранную опцию
const selectedOption = computed(() => {
    if (!model.value) return null

    return normalizedOptions.value.find(opt => opt.value === model.value) || null
})

// Проверяем, выбрана ли опция
const isOptionSelected = (option) => {
    return model.value === option.value
}

// Переключаем dropdown
const toggleDropdown = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value
    }
}

// Выбираем опцию
const selectOption = (option) => {
    model.value = option.value
    emit('change', option)
    isOpen.value = false
}

// Закрываем dropdown при клике вне компонента
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

// Закрываем dropdown при нажатии Escape
const handleEscapeKey = (event) => {
    if (event.key === 'Escape') {
        isOpen.value = false
    }
}

// Устанавливаем обработчики событий
onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside)
    document.addEventListener('keydown', handleEscapeKey)
})

// Убираем обработчики при размонтировании
onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside)
    document.removeEventListener('keydown', handleEscapeKey)
})
</script>

<template>
    <div class="relative" ref="containerRef">
        <!-- Кнопка-триггер -->
        <button
            type="button"
            class="group relative block w-full before:absolute before:inset-px before:rounded-[calc(var(--radius-lg)-1px)] before:bg-white before:shadow-sm dark:before:hidden focus:outline-hidden after:pointer-events-none after:absolute after:inset-0 after:rounded-lg after:ring-transparent after:ring-inset data-focus:after:ring-2 data-focus:after:ring-blue-500 data-disabled:opacity-50 data-disabled:before:bg-zinc-950/5 data-disabled:before:shadow-none"
            :class="{
              'opacity-50 cursor-not-allowed': disabled,
              'ring-2 ring-blue-500 rounded-[calc(var(--radius-lg)-1px)]': isOpen
            }"
            @click="toggleDropdown"
            :disabled="disabled"
        >
            <span class="relative block w-full appearance-none rounded-lg py-[calc(--spacing(2.5)-1px)] sm:py-[calc(--spacing(1.5)-1px)] min-h-11 sm:min-h-9 pr-[calc(--spacing(7)-1px)] pl-[calc(--spacing(3.5)-1px)] sm:pl-[calc(--spacing(3)-1px)] text-left text-base/6 text-zinc-950 placeholder:text-zinc-500 sm:text-sm/6 dark:text-white border border-zinc-950/10 group-hover:border-zinc-950/20 dark:border-white/10 dark:group-hover:border-white/20 bg-transparent dark:bg-white/5">
              <div class="flex min-w-0 items-center">
                <span class="ml-2.5 truncate first:ml-0 sm:ml-2 sm:first:ml-0">
                  {{ selectedOption?.label || placeholder }}
                </span>
              </div>
            </span>

            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg
                    class="size-5 stroke-zinc-500 group-disabled:stroke-zinc-600 sm:size-4 dark:stroke-zinc-400"
                    viewBox="0 0 16 16"
                    aria-hidden="true"
                    fill="none"
                    :class="{ 'transform rotate-180': isOpen }"
                >
                  <path d="M5.75 10.75L8 13L10.25 10.75" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path d="M10.25 5.25L8 3L5.75 5.25" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
        </button>

        <!-- Dropdown меню -->
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-1 w-full rounded-lg bg-white shadow-lg border border-zinc-200 dark:bg-zinc-900 dark:border-zinc-700 max-h-60 overflow-auto"
            >
                <ul class="py-1">
                    <li
                        v-for="option in normalizedOptions"
                        :key="option.value"
                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-zinc-600/10 hover:text-white text-zinc-900 dark:text-white dark:hover:bg-zinc-700"
                        @click="selectOption(option)"
                    >
                        <span class="block truncate text-sm">
                          {{ option.label }}
                        </span>

                        <span
                            v-if="isOptionSelected(option)"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-white/20 hover:text-white/20"
                        >
                          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                          </svg>
                        </span>
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>

<style scoped>

</style>
