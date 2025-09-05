<script setup>
import {computed, ref} from "vue";

const props = defineProps({
    variant: {
        type: String,
        validator(value, props) {
            return ['success', 'warning', 'danger', 'info'].includes(value)
        }
    }
})

const baseClasses = [
    'inline-flex',
    'items-center',
    'gap-x-1.5',
    'rounded-md',
    'px-1.5',
    'py-0.5',
    'text-sm/5',
    'font-medium',
    'sm:text-xs/5',
    'forced-colors:outline',
]

const successColorClasses = [
    'bg-lime-400/20',
    'text-lime-700',
    'group-data-hover:bg-lime-400/30',
    'dark:bg-lime-400/10',
    'dark:text-lime-300',
    'dark:group-data-hover:bg-lime-400/15'
]
const warningColorClasses = [
    'bg-amber-400/20',
    'text-amber-700',
    'group-data-hover:bg-amber-400/25',
    'dark:bg-amber-400/10',
    'dark:text-amber-400',
    'dark:group-data-hover:bg-amber-400/20'
]
const dangerColorClasses = [
    'bg-rose-400/20',
    'text-rose-700',
    'group-data-hover:bg-rose-400/25',
    'dark:bg-rose-400/10',
    'dark:text-rose-400',
    'dark:group-data-hover:bg-rose-400/20'
]
const infoColorClasses = [
    'bg-sky-400/20',
    'text-sky-700',
    'group-data-hover:bg-sky-400/25',
    'dark:bg-sky-400/10',
    'dark:text-sky-400',
    'dark:group-data-hover:bg-sky-400/20'
]
const primaryColorClasses = [
    'bg-orange-400/20',
    'text-orange-700',
    'group-data-hover:bg-orange-400/25',
    'dark:bg-orange-400/10',
    'dark:text-orange-400',
    'dark:group-data-hover:bg-orange-400/20'
]

const colorClasses = {
    success: successColorClasses,
    warning: warningColorClasses,
    danger: dangerColorClasses,
    info: infoColorClasses,
    primary: primaryColorClasses
}

const computedClasses = computed(() => {
    return [
        ...baseClasses,
        ...(colorClasses[props.variant] || [])
    ]
})
</script>

<template>
    <div :data-variant="variant" :class="computedClasses">
        <slot />
    </div>
</template>

<style scoped>

</style>
