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
    }
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
        'hover:dark:bg-white/5'
    ]
}

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
</script>

<template>
    <component :is="tag" :href="href" @click="emits('click')"
               :class="classes" v-bind="$attrs">
        <div class="flex flex-row gap-x-2 items-center">
            <div v-if="$slots.icon" class="size-6 stroke-zinc-500 group-disabled:stroke-zinc-600 sm:size-4 dark:stroke-zinc-400">
                <slot name="icon" />
            </div>
            <slot />
        </div>
    </component>
</template>

<style scoped>

</style>
