<script setup>
import {computed} from "vue";

const props = defineProps({
    label: {
        type: String,
        default: null
    },
    position: {
        type: String,
        default: 'top'
    }
})

const labelPositions = {
    left: [
        'flex flex-row gap-x-2 items-center'
    ],
    top: [
        'flex flex-col gap-y-1'
    ]
}

const labelPositionClass = computed(() => {
    if (props.label)
        return labelPositions[props.position]
})
</script>

<template>
    <div :class="labelPositionClass">
        <label v-if="label" class="text-base/6 text-zinc-950 select-none data-disabled:opacity-50 sm:text-sm/6 dark:text-white">
            {{ label }}
        </label>
        <slot />
    </div>
</template>

<style scoped>

</style>
