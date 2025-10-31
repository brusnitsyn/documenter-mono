<script setup>
import {computed} from "vue";

const props = defineProps({
    vertical: {
        type: String,
        default: 'vertical'
    }
})

const verticalClasses = [
    'flex', 'flex-col', 'gap-y-2'
]

const horizontalClasses = [
    'grid', 'grid-cols-3', 'gap-y-2', 'gap-x-2', 'grow', 'items-start'
]

const classes = computed(() => {
    if (props.vertical)
        return verticalClasses

    return horizontalClasses
})
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>

<style scoped>

</style>
