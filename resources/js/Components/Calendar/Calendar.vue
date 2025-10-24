<script setup>
import { computed, ref, watch } from 'vue'
import {
    format,
    parse,
    startOfMonth,
    endOfMonth,
    eachDayOfInterval,
    startOfWeek,
    endOfWeek,
    isSameDay,
    isSameMonth,
    isToday,
    addMonths,
    subMonths,
    isValid
} from 'date-fns'
import { ru } from 'date-fns/locale'

const props = defineProps({
    modelValue: {
        type: [Date, String, null],
        default: null
    },
    placeholder: {
        type: String,
        default: 'Выберите дату'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    variant: {
        type: String,
        default: 'default'
    },
    format: { // Пользователь вводит любой формат date-fns
        type: String,
        default: 'dd.MM.yyyy'
    },
    returnFormatted: {
        type: Boolean,
        default: true
    },
    locale: {
        type: Object,
        default: () => ru
    },
    block: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const baseClasses = [
    'group', 'cursor-pointer', 'relative', 'block', 'appearance-none', 'rounded-lg', 'text-left', 'text-base/6',
    'sm:text-sm/6', 'border', 'transition-all', 'disabled:cursor-not-allowed', 'disabled:opacity-50'
]

const paddingClasses = [
    'py-[calc(--spacing(2.5)-1px)]', 'sm:py-[calc(--spacing(1.5)-1px)]',
    'px-[calc(--spacing(3.5)-1px)]', 'sm:px-[calc(--spacing(2.5)-1px)]',
]

const variants = {
    default: [
        'text-zinc-950', 'placeholder:text-zinc-500', 'dark:text-white', 'border-zinc-950/10',
        'hover:border-zinc-950/20', 'dark:border-white/10', 'dark:hover:border-white/20', 'bg-transparent',
        'dark:bg-white/5'
    ],
    warning: [
        'text-white', 'placeholder:text-amber-500', 'border-amber-900',
        'hover:border-amber-400', 'dark:border-amber-700', 'dark:hover:border-amber-600', 'bg-transparent',
        'dark:bg-amber-800'
    ],
    danger: [
        'placeholder:text-rose-500', 'border-rose-900',
        'hover:border-rose-400', 'dark:border-rose-600', 'dark:hover:border-rose-400', 'bg-transparent',
        'dark:bg-rose-800'
    ],
    ghost: [
        'bg-transparent', 'border-transparent', 'hover:border-zinc-950/20', 'dark:hover:border-white/20',
        'hover:dark:bg-white/5'
    ]
}

const classes = computed(() => {
    let base = [...baseClasses, ...paddingClasses]

    if (props.variant) {
        base = base.concat(variants[props.variant])
    }

    if (props.block) base.push('w-full')

    return base
})

// Парсинг входящего значения
const parseInputValue = (value) => {
    if (!value) return null

    if (value instanceof Date) {
        return isValid(value) ? value : null
    }

    if (typeof value === 'string') {
        try {
            // Пробуем распарсить с текущим форматом
            const parsed = parse(value, props.format, new Date(), { locale: props.locale })
            if (isValid(parsed)) return parsed

            // Пробуем стандартные форматы как fallback
            const standardFormats = [
                'dd.MM.yyyy',
                'yyyy-MM-dd',
                'd MMMM yyyy',
                'd MMM yyyy',
                'dd/MM/yyyy',
                'MM/dd/yyyy'
            ]

            for (const fmt of standardFormats) {
                const parsed = parse(value, fmt, new Date(), { locale: props.locale })
                if (isValid(parsed)) return parsed
            }

            // Последняя попытка - стандартный парсинг
            const parsedDate = new Date(value)
            if (isValid(parsedDate)) return parsedDate
        } catch (e) {
            console.warn('Не удалось распарсить дату:', value)
        }
    }

    return null
}

// Состояния
const isOpen = ref(false)
const selectedDate = ref(props.modelValue ? parseInputValue(props.modelValue) : null)
const currentMonth = ref(selectedDate.value || new Date())
const today = new Date()

// Дни недели
const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

// Функция для экранирования текста в формате
const escapeFormat = (formatString) => {
    // Если формат уже содержит экранированный текст, оставляем как есть
    if (formatString.includes("'")) {
        return formatString
    }

    // Ищем текстовые части и экранируем их
    const tokens = formatString.split(/(\s+)/)
    let result = ''
    let inText = false

    for (const token of tokens) {
        if (token.trim() === '') {
            result += token
            continue
        }

        // Проверяем, является ли токен текстом (не паттерном date-fns)
        const isPattern = /^(dd?|MM?M?M?|yy?yy?|EEE?E?|QQ?Q?|ww?|HH?|hh?|mm?|ss?|SSS?|aaaa?|xxxx?|XXXX?|ZZZ?)$/.test(token)

        if (!isPattern && !inText) {
            result += `'${token}`
            inText = true
        } else if (!isPattern && inText) {
            result += ` ${token}`
        } else if (isPattern && inText) {
            result += `' ${token}`
            inText = false
        } else {
            result += token
        }
    }

    // Закрываем последнюю текстовую часть
    if (inText) {
        result += "'"
    }

    return result
}

// Форматирование даты с помощью date-fns
const formatDate = (date) => {
    if (!date || !isValid(date)) return ''

    try {
        // Пробуем форматировать как есть
        return format(date, props.format, { locale: props.locale })
    } catch (error) {
        console.warn('Ошибка форматирования даты:', error)

        // Fallback: пробуем заменить проблемные символы
        try {
            // Экранируем весь текст, который не является паттерном date-fns
            const safeFormat = props.format
                .replace(/([^dMyYwWDEHhmsSaZXx']+|'.*?')/g, "'$1'")
                .replace(/''/g, "'")

            return format(date, safeFormat, { locale: props.locale })
        } catch (e) {
            // Ultimate fallback
            return format(date, 'dd.MM.yyyy', { locale: props.locale })
        }
    }
}

// Получить дни для календаря
const calendarDays = computed(() => {
    const start = startOfWeek(startOfMonth(currentMonth.value), { weekStartsOn: 1 })
    const end = endOfWeek(endOfMonth(currentMonth.value), { weekStartsOn: 1 })

    return eachDayOfInterval({ start, end })
})

// Текущий месяц и год
const currentMonthYear = computed(() => {
    return format(currentMonth.value, 'LLLL yyyy', { locale: props.locale })
})

// Форматированная дата для отображения
const formattedDate = computed(() => {
    if (!selectedDate.value) return props.placeholder
    return formatDate(selectedDate.value)
})

// Методы
const selectDate = (date) => {
    if (!date || props.disabled) return

    selectedDate.value = date

    if (props.returnFormatted) {
        const formattedValue = formatDate(date)
        emit('update:modelValue', formattedValue)
    } else {
        emit('update:modelValue', date)
    }

    isOpen.value = false
}

const prevMonth = () => {
    currentMonth.value = subMonths(currentMonth.value, 1)
}

const nextMonth = () => {
    currentMonth.value = addMonths(currentMonth.value, 1)
}

// Вспомогательные методы для проверок
const isDateToday = (date) => isToday(date)
const isDateSelected = (date) => selectedDate.value && isSameDay(date, selectedDate.value)
const isDateCurrentMonth = (date) => isSameMonth(date, currentMonth.value)


// Методы для внешнего использования
const getFormattedDate = (date = null, customFormat = null) => {
    const targetDate = date || selectedDate.value
    if (!targetDate || !isValid(targetDate)) return null

    const formatToUse = customFormat || props.format

    try {
        return format(targetDate, formatToUse, { locale: props.locale })
    } catch (error) {
        console.warn('Ошибка форматирования:', error)
        return format(targetDate, 'dd.MM.yyyy', { locale: props.locale })
    }
}

const getDateObject = () => selectedDate.value

defineExpose({
    getFormattedDate,
    getDateObject
})

// Закрытие по клику вне компонента
const closeOnClickOutside = (event) => {
    if (!event.target.closest('.calendar-container')) {
        isOpen.value = false
    }
}

import { onMounted, onUnmounted } from 'vue'

onMounted(() => {
    document.addEventListener('click', closeOnClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', closeOnClickOutside)
})

// Следим за изменением modelValue
watch(() => props.modelValue, (newValue) => {
    const parsed = parseInputValue(newValue)
    if (parsed && (!selectedDate.value || !isSameDay(parsed, selectedDate.value))) {
        selectedDate.value = parsed
        if (parsed) {
            currentMonth.value = startOfMonth(parsed)
        }
    }
})

// Следим за изменением формата
watch(() => props.format, (newFormat) => {
    // Если дата уже выбрана, переформатируем её
    if (selectedDate.value && props.returnFormatted) {
        const formattedValue = formatDate(selectedDate.value)
        emit('update:modelValue', formattedValue)
    }
})
</script>

<template>
    <div class="calendar-container relative">
        <!-- Триггер -->
        <button
            :class="classes"
            @click="isOpen = !isOpen"
            :disabled="disabled"
            type="button"
        >
            <div class="flex flex-row gap-x-2 items-center justify-between w-full">
        <span :class="!selectedDate ? 'text-zinc-500 dark:text-zinc-400' : ''">
          {{ formattedDate }}
        </span>
                <svg
                    class="size-4 stroke-zinc-500 group-disabled:stroke-zinc-600 dark:stroke-zinc-400 transition-transform duration-200 flex-shrink-0"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <!-- Выпадающий календарь -->
        <div
            v-if="isOpen"
            class="absolute top-full left-0 mt-1 z-50 bg-white dark:bg-zinc-900 border border-zinc-950/10 dark:border-white/10 rounded-lg shadow-lg p-4 min-w-64"
        >
            <!-- Заголовок с навигацией -->
            <div class="flex items-center justify-between mb-4">
                <button
                    @click="prevMonth"
                    class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                >
                    <svg class="size-4 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <span class="text-sm font-medium text-zinc-900 dark:text-white">
          {{ currentMonthYear }}
        </span>

                <button
                    @click="nextMonth"
                    class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                >
                    <svg class="size-4 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Дни недели -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <div
                    v-for="day in weekDays"
                    :key="day"
                    class="text-xs text-center text-zinc-500 dark:text-zinc-400 py-1"
                >
                    {{ day }}
                </div>
            </div>

            <!-- Дни месяца -->
            <div class="grid grid-cols-7 gap-1">
                <button
                    v-for="date in calendarDays"
                    :key="date.getTime()"
                    @click="selectDate(date)"
                    :disabled="disabled"
                    class="aspect-square p-1 text-sm rounded transition-all duration-200"
                    :class="[
            !isDateCurrentMonth(date) ? 'text-zinc-400 dark:text-zinc-600' : '',
            isDateSelected(date)
              ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
              : isDateToday(date)
                ? 'border border-zinc-900 dark:border-white'
                : 'hover:bg-zinc-100 dark:hover:bg-zinc-800',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
                >
                    {{ format(date, 'd', { locale }) }}
                </button>
            </div>

            <!-- Быстрый выбор -->
            <div class="flex justify-between mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                <button
                    @click="selectDate(today)"
                    class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors"
                    :disabled="disabled"
                >
                    Сегодня
                </button>
                <button
                    @click="selectDate(null)"
                    class="text-xs text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 transition-colors"
                    :disabled="disabled"
                >
                    Очистить
                </button>
            </div>
        </div>
    </div>
</template>
