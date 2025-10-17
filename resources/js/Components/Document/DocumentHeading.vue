<script setup>
import {computed} from "vue";

const props = defineProps({
    content: String,
    element: Object
})

const styles = computed(() => {
    const styles = {}
    const style = props.element.style || {}

    // Выравнивание
    if (style.align) {
        styles.textAlign = style.align
    }

    // Междустрочный интервал
    if (style.lineHeight) {
        styles.lineHeight = style.lineHeight
    }

    // Отступы
    if (style.spaceBefore) {
        styles.marginTop = `${style.spaceBefore}pt`
    }

    if (style.spaceAfter) {
        styles.marginBottom = `${style.spaceAfter}pt`
    }

    if (style.indent) {
        if (style.indent.left) {
            styles.marginLeft = `${style.indent.left}pt`
        }
        if (style.indent.right) {
            styles.marginRight = `${style.indent.right}pt`
        }
        if (style.indent.firstLine) {
            styles.textIndent = `${style.indent.firstLine}pt`
        }
    }

    return styles
})
</script>

<template>
    <h2 :data-element-id="element.id" :style="styles" class="font-bold" v-html="content"></h2>
</template>

<style scoped>

</style>
