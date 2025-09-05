<script setup>
import DocumentElement from "./DocumentElement.vue"

const props = defineProps({
    structure: Array
})

const emit = defineEmits(['variable-click'])

const isSectionVisible = (section) => {
    return section.enabled !== false
}

const handleVariableClick = (variableName) => {
    emit('variable-click', variableName)
}
</script>

<template>
    <div class="document-content">
        <template v-for="section in structure" :key="section.id">
            <div v-if="isSectionVisible(section)" class="section mb-6">
                <template v-for="element in section.elements" :key="element.id">
                    <DocumentElement
                        :element="element"
                        @variable-click="handleVariableClick"
                    />
                </template>
            </div>
        </template>
    </div>
</template>

<style scoped>
.document-content {
    line-height: 1.6;
    font-family: 'Times New Roman', serif;
    font-size: 14px;
}

.section {
    page-break-inside: avoid;
}
</style>
