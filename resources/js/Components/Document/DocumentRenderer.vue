<script setup>
import DocumentElement from "./DocumentElement.vue"
import A4 from "./A4.vue";
import {computed, getCurrentInstance, nextTick, onMounted, ref, watch, watchEffect} from "vue";
import {useDynamicA4Layout} from "../../Composables/useDynamicA4Layout.js";
import {waitForAllComponentsMounted} from "../../Utils/heightCalculator.js";

const props = defineProps({
    structure: Array,
    editable: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['variable-click'])

const {calculateDynamicLayout, componentHeights, a4Pages} = useDynamicA4Layout()

const allElements = computed(() => props.structure.elements)
const componentRefs = ref(new Map())

const handleVariableClick = (variableName) => {
    emit('variable-click', variableName)
}

const elementRefs = ref(new Map())
const addToRefs = (el, id) => {
    if (el)
        elementRefs.value.set(id, el.$el)
}

const hasAllMounted = computed(() => {
    // Array.from(elementRefs.value.values())
    //     .every(ref => ref.value?.$.vnode.el?.isConnected)


})

// watch(() => hasAllMounted, async (newSize, oldSize) => {
//     const expectedCount = props.structure?.elements?.length || 0
//
//     if (newSize === expectedCount && newSize > 0 && newSize !== oldSize) {
//         // Даём время на полное монтирование
//         await nextTick()
//         await nextTick() // Двойной nextTick для надёжности
//
//         console.log(hasAllMounted.value)
//
//         // try {
//         //     await waitForAllComponentsMounted(getCurrentInstance())
//         //     console.log('Все компоненты смонтированы!')
//         //     await calculateDynamicLayout(props.structure.elements, () => elementRefs.value)
//         // } catch (error) {
//         //     console.error('Ошибка при ожидании монтирования:', error)
//         // }
//     }
// })
onMounted(() => {
    // console.log(Array.from(elementRefs.value.values())[0].)
})
watch(() => elementRefs.value.size, async () => {
    const expectedCount = props.structure?.elements?.length || 0
    const currentSize = elementRefs.value.size

    if (currentSize > 0 && currentSize === expectedCount) {
        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 5000))
        // console.log('Все компоненты смонтированы!')
        await calculateDynamicLayout(props.structure.elements, () => elementRefs.value)
    }
}, {
    flush: 'post'
})
</script>

<template>
    <A4 class="absolute -left-[9999px] -top-[9999px] section collapse document-content overflow-hidden">
        <DocumentElement
            v-for="element in allElements"
            :ref="el => addToRefs(el, element.id)"
            :element="element"
        />
    </A4>
    <A4 v-for="paginatedElement in a4Pages" cover class="section document-content my-2 overflow-hidden">
        <template v-for="item in paginatedElement.items" :key="item.id">
            <DocumentElement
                :contenteditable="editable"
                :class="editable ? 'overflow-hidden outline-none' : ''"
                :element="item"
                @variable-click="handleVariableClick"
            />
        </template>
    </A4>
</template>

<style scoped>
.document-content {
    line-height: 1.2;
    font-family: 'Times New Roman', serif;
    font-size: 14px;
}

.section {
    page-break-inside: avoid;
}
</style>
