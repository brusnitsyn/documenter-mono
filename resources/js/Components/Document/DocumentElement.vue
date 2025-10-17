<script setup>
import {computed, onMounted} from "vue"
import DocumentWarning from "./DocumentWarning.vue"
import DocumentHtml from "./DocumentHtml.vue"
import DocumentHeading from "./DocumentHeading.vue"
import DocumentParagraph from "./DocumentParagraph.vue";
import DocumentTextRun from "./DocumentTextRun.vue";
import DocumentLineBreak from "./DocumentLineBreak.vue";
import DocumentTable from "./DocumentTable.vue";

const props = defineProps({
    element: Object
})

const emit = defineEmits(['variable-click', 'is-mounted'])

const componentMap = {
    'heading': DocumentHeading,
    'html': DocumentHtml,
    // 'warning': DocumentWarning,
    'paragraph': DocumentParagraph,
    'text_run': DocumentTextRun,
    'line_break': DocumentLineBreak,
    'table': DocumentTable
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

onMounted(() => {
    emit('is-mounted', true)
})
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
