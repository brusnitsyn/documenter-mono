<script setup>
import {computed, ref} from "vue";
import DocumentElement from "./DocumentElement.vue";
import DocumentTableRow from "./DocumentTableRow.vue";

const props = defineProps({
    content: String,
    element: Object,
})

const rows = computed(() => props.element.rows || [])
const cols = computed(() => props.element.cols || 0)

</script>

<template>
    <div>
        <table :data-element-id="element.id" class="table-element">
            <tbody>
                <DocumentTableRow @is-mounted="" :rows="rows" />
<!--                <tr-->
<!--                    v-for="(row, rowIndex) in rows"-->
<!--                    :key="`row-${rowIndex}`"-->
<!--                >-->
<!--                    <td-->
<!--                        v-for="(cell, colIndex) in row.cells"-->
<!--                        :key="`td-${rowIndex}-${colIndex}`"-->
<!--                        :style="`width: ${cell.width}px`"-->
<!--                    >-->
<!--                        <DocumentElement v-for="element in cell.elements" :element="element" :key="element.id" />-->
<!--                    </td>-->
<!--                </tr>-->
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
