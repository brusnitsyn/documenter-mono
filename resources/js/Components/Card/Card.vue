<script setup>
import CardHeader from "./CardHeader.vue";
import CardBack from "./CardBack.vue";
import {computed} from "vue";
const props = defineProps({
    header: String,
    contentScroll: {
        type: Boolean,
        default: true
    },
    contentRelative: {
        type: Boolean,
        default: true
    },
    mergeContentClass: {
        type: String,
        default: ''
    }
})

const contentClass = computed(() => {
    const classes = ['p-3 h-full']

    props.contentRelative ? classes.push('relative') : delete classes.find(cls => cls === 'relative')

    if (props.contentScroll) {
        classes.push('overflow-y-auto')
        delete classes.find(cls => cls === 'overflow-y-clip')
    } else {
        classes.push('overflow-y-clip')
        delete classes.find(cls => cls === 'overflow-y-auto')
    }

    if (props.mergeContentClass) {
        const mergeClasses = props.mergeContentClass.split(' ')
        classes.push(...mergeClasses)
    }

    return classes
})

</script>

<template>
    <div class="h-full flex flex-col justify-between overflow-clip rounded-lg bg-white lg:shadow-xs ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
        <div class="p-3">
            <CardHeader>
                <slot v-if="$slots.header" name="header" />
                <span v-else>
                    {{ header }}
                </span>
            </CardHeader>
        </div>
        <div :class="contentClass">
            <slot />
        </div>
        <div v-if="$slots.footer" class="p-3">
            <slot name="footer" />
        </div>
    </div>
</template>

<style scoped>

</style>
