<script setup>
import {computed, ref, watch} from "vue";

const emits = defineEmits([
    'click'
])

const props = defineProps({
    tag: {
        type: [String, Object],
        default: 'button'
    },
    href: {
        type: String,
        default: null
    },
    icon: {
        type: Boolean,
        default: false
    },
    block: {
        type: Boolean,
        default: false
    },
    variant: {
        type: String,
        default: 'default'
    },
    loading: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    textAlign: {
        type: String,
        default: 'left'
    },
    iconLeft: {
        type: Boolean,
        default: false
    },
    iconRight: {
        type: Boolean,
        default: false
    },
    maxWidth: {
        type: [String, Number],
        default: 'none'
    },
})

const baseClasses = [
    'group', 'cursor-pointer', 'relative', 'block', 'appearance-none', 'rounded-lg', 'text-left', 'text-base/6',
    'sm:text-sm/6', 'border', 'active:scale-[.99]',
    'transition-all'
]
const paddingClasses = [
    'py-[calc(--spacing(2.5)-1px)]', 'sm:py-[calc(--spacing(1.5)-1px)]',
    'px-[calc(--spacing(3.5)-1px)]', 'sm:px-[calc(--spacing(2.5)-1px)]',
]
const paddingClassesIcon = [
    'py-[calc(--spacing(2.5)-1px)]', 'sm:py-[calc(--spacing(2.5)-1px)]',
    'px-[calc(--spacing(2.5)-1px)]', 'sm:px-[calc(--spacing(2.5)-1px)]',
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
    ]
}

const textContainerClasses = computed(() => {
    let base = ['flex', 'flex-row', 'gap-x-2', 'relative']

    if (props.textAlign) {
        const align = {
            center: 'justify-center',
            left: 'justify-start',
            right: 'justify-end',
        }

        const textAlign = align[props.textAlign]

        base = base.concat([textAlign])
    }

    return base
})

const classes = computed(() => {
    let base = [...baseClasses]

    if (props.icon) {
        base = base.concat(paddingClassesIcon)
    } else {
        base = base.concat(paddingClasses)
    }

    if (props.block) {
        base = base.concat(['w-full'])
    }

    if (props.variant) {
        base = base.concat(variants[props.variant])
    }

    return base
})

const textClasses = computed(() => {
    let base = ['min-w-0', 'w-full']

    // Автоматически рассчитываем максимальную ширину если есть иконки
    if (props.maxWidth === 'none') {
        if (props.iconLeft && props.iconRight) {
            base = base.concat(['max-w-[calc(100%-8rem)]']) // минус 2 иконки
        } else if (props.iconLeft || props.iconRight) {
            base = base.concat(['max-w-[calc(100%-1.5rem)]']) // минус 1 иконка
        }
    } else if (props.maxWidth) {
        base = base.concat([`max-w-[${props.maxWidth}]`])
    }

    base = base.concat(['truncate'])

    return base
})

const handleClick = (event) => {
    if (props.loading || props.disabled) {
        event.preventDefault()
        return
    }
    emits('click', event)
}
</script>

<template>
    <component :is="tag"
               :href="href"
               @click="handleClick"
               :class="classes"
               :disabled="loading || disabled"
               v-bind="$attrs">
        <div :class="textContainerClasses">
            <!-- Спиннер загрузки -->
            <div
                v-if="loading"
                class="absolute inset-0 flex items-center justify-center"
            >
                <div class="flex items-center gap-x-2">
                    <!-- Анимированный спиннер -->
                    <div class="flex space-x-1">
                        <div class="w-1.5 h-1.5 bg-current rounded-full animate-bounce" style="animation-delay: 0s"></div>
                        <div class="w-1.5 h-1.5 bg-current rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-1.5 h-1.5 bg-current rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>

            <!-- Основной контент кнопки (скрывается при loading) -->
            <div
                :class="[
                    'flex flex-row gap-x-2 items-center transition-opacity duration-200 w-full',
                    loading ? 'opacity-0' : 'opacity-100'
                ]"
            >
                <div
                    v-if="($slots.iconLeft || (iconLeft && $slots.icon))"
                    class="shrink-0 size-6 stroke-zinc-500 group-disabled:stroke-zinc-600 sm:size-4 dark:stroke-zinc-400"
                >
                    <slot name="iconLeft">
                        <slot name="icon" />
                    </slot>
                </div>
                <div :class="textClasses">
                    <slot />
                </div>
                <div
                    v-if="$slots.iconRight || (iconRight && $slots.icon)"
                    class="shrink-0 size-6 stroke-zinc-500 group-disabled:stroke-zinc-600 sm:size-4 dark:stroke-zinc-400"
                >
                    <slot name="iconRight">
                        <slot name="icon" />
                    </slot>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>

</style>
