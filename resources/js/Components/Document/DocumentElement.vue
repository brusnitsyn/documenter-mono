<script setup>
import {computed} from "vue"
import DocumentWarning from "./DocumentWarning.vue"
import DocumentHtml from "./DocumentHtml.vue"
import DocumentHeading from "./DocumentHeading.vue"

const props = defineProps({
    element: Object
})

const emit = defineEmits(['variable-click'])

const componentMap = {
    'heading': DocumentHeading,
    'html': DocumentHtml,
    'warning': DocumentWarning
}

const componentType = computed(() => {
    return componentMap[props.element.type] || 'div'
})

const elementClass = computed(() => {
    return `element-${props.element.type}`
})

const compiledContent = computed(() => {
    return props.element.compiledContent || props.element.content
})

const handleVariableClick = (variableName) => {
    emit('variable-click', variableName)
}
</script>

<template>
    <div class="document-element" :class="elementClass">
        <component
            :is="componentType"
            :content="compiledContent"
            :element="element"
            @variable-click="handleVariableClick"
        />
    </div>
</template>

<style scoped>

</style>
