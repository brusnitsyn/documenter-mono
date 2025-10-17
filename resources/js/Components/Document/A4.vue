<script setup>
import {computed} from "vue";

const props = defineProps({
    cover: {
        type: Boolean,
        default: false
    }
})

const defaultClasses = [
    'w-[21cm] h-[29.7cm] bg-white text-black rounded-sm outline-none'
]

const defaultCoverClasses = [
    'p-[2.5cm]'
]

const coverClasses = [
    'pt-[2cm] pl-[3cm] pb-[2cm] pr-[1.5cm]'
]

const classes = computed(() => {
    let base = [...defaultClasses]

    if (props.cover)
        base = base.concat(coverClasses)
    else
        base = base.concat(defaultCoverClasses)

    return base
})
</script>

<template>
    <div :class="classes">
        <div class="min-h-[calc(29.7cm-5cm)] relative">
            <slot />
        </div>
    </div>
</template>

<style scoped>

</style>
