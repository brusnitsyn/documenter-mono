<script setup>
import {computed, ref} from 'vue';
import DocumentText from './DocumentText.vue';
// import DocumentVariable from './DocumentVariable.vue';
import DocumentLineBreak from './DocumentLineBreak.vue';

const props = defineProps({
    element: Object,
    formData: Object
});

defineEmits(['variable-click']);

const elementComponents = {
    text: DocumentText,
    // variable: DocumentVariable,
    line_break: DocumentLineBreak
}

const textRunRef = ref()

const combinedStyles = computed(() => {
    const styles = {}
    const formatting = props.element.formatting || {}
    const paragraphStyle = props.element.style || {}

    // Стили шрифта
    if (formatting.fontSize) {
        styles.fontSize = formatting.fontSize;
    }

    if (formatting.fontFamily) {
        styles.fontFamily = formatting.fontFamily;
    }

    if (formatting.fontColor) {
        styles.color = formatting.fontColor;
    }

    if (formatting.backgroundColor) {
        styles.backgroundColor = formatting.backgroundColor;
    }

    // Стили параграфа (если есть)
    if (paragraphStyle.align) {
        styles.textAlign = paragraphStyle.align;
    }

    if (paragraphStyle.lineHeight) {
        styles.lineHeight = paragraphStyle.lineHeight;
    }

    if (paragraphStyle.indent) {
        // console.log(paragraphStyle.indent)
        if (paragraphStyle.indent.firstLine)
            styles['--first-line-indent'] = `${paragraphStyle.indent.firstLine}px`
    }

    return styles;
});

const textRunClasses = computed(() => {
    // const classes = []
    // const style = props.element.style
    // if (style && style.indent) {
    //     if (style.indent.firstLine)
    //         classes.push(`first:pl-[${style.indent.firstLine}px]`)
    // }
    //
    // return classes
    // const classes = ['text-run-element']
    //
    // const formatting = props.element.formatting || {}
    // if (formatting.bold) classes.push('text-bold')
    // if (formatting.italic) classes.push('text-italic')
    // if (formatting.underline !== 'none') classes.push('text-underline')
    // if (formatting.strikethrough) classes.push('text-strikethrough')
    //
    // return classes;
});

const getComponentForElement = (element) => {
    return elementComponents[element.type] || 'span';
};
</script>

<template>
    <div ref="textRunRef" :data-element-id="element.id" :style="combinedStyles" >
        <template v-for="children in element.elements" :key="children.id" >
            <DocumentText
                class="first:pl-[var(--first-line-indent)]"
                :element="children"
                :form-data="formData"
                @variable-click="$emit('variable-click', $event)"
            />
        </template>
    </div>
</template>


<style scoped>
.text-run {
    display: inline;
    line-height: inherit;
}

.text-run-element {
    display: inline;
}

.text-bold {
    font-weight: bold;
}

.text-italic {
    font-style: italic;
}

.text-underline {
    text-decoration: underline;
}

.text-strikethrough {
    text-decoration: line-through;
}

/* Наследование стилей для вложенных элементов */
.text-run ::v-deep(.text-element) {
    display: inline;
}

.text-run ::v-deep(.variable-element) {
    display: inline;
}

.text-run ::v-deep(.line-break) {
    display: block;
    height: 0;
    overflow: hidden;
}
</style>
