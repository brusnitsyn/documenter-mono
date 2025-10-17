<script setup>
import DocumentElement from "./DocumentElement.vue";
import {onMounted} from "vue";

const props = defineProps({
    rows: Array
})

const emits = defineEmits(['is-mounted'])

onMounted(() => {
    emits('is-mounted', true)
})
</script>

<template>
    <tr
        v-for="(row, rowIndex) in rows"
        :key="`row-${rowIndex}`"
    >
        <td v-for="cell in row.cells" :style="`width: ${cell.width}px`">
            <DocumentElement v-for="element in cell.elements" :element="element" />
        </td>
    </tr>
</template>

<style scoped>

</style>
