<script setup>
import { computed } from 'vue';
import DocumentText from './DocumentText.vue';
// import DocumentVariable from './DocumentVariable.vue';
import DocumentTextRun from './DocumentTextRun.vue';
import DocumentLineBreak from './DocumentLineBreak.vue';

const props = defineProps({
    element: Object,
    formData: Object
});

defineEmits(['variable-click']);

const elementComponents = {
    text: DocumentText,
    // variable: DocumentVariable,
    text_run: DocumentTextRun,
    line_break: DocumentLineBreak
};

const paragraphStyles = computed(() => {
    const styles = {};
    const paragraphStyle = props.element.style || {};

    // Выравнивание
    if (paragraphStyle.align) {
        styles.textAlign = paragraphStyle.align;
    }

    // Междустрочный интервал
    if (paragraphStyle.lineHeight) {
        styles.lineHeight = paragraphStyle.lineHeight;
    }

    // Отступы
    if (paragraphStyle.spaceBefore) {
        styles.marginTop = `${paragraphStyle.spaceBefore}pt`;
    }

    if (paragraphStyle.spaceAfter) {
        styles.marginBottom = `${paragraphStyle.spaceAfter}pt`;
    }

    if (paragraphStyle.indent) {
        if (paragraphStyle.indent.left) {
            styles.marginLeft = `${paragraphStyle.indent.left}pt`;
        }
        if (paragraphStyle.indent.right) {
            styles.marginRight = `${paragraphStyle.indent.right}pt`;
        }
        if (paragraphStyle.indent.firstLine) {
            styles.textIndent = `${paragraphStyle.indent.firstLine}pt`;
        }
    }

    return styles;
});

const paragraphClasses = computed(() => {
    const classes = ['paragraph-element'];

    if (props.element.style?.align) {
        classes.push(`align-${props.element.style.align}`);
    }

    return classes;
});

const getComponentForElement = (element) => {
    return elementComponents[element.type] || 'span';
};
</script>

<template>
    <p
        class="document-paragraph"
        :style="paragraphStyles"
        :class="paragraphClasses"
    >
        <template v-if="element.elements" v-for="element in element.elements" :key="element.id">
            <component
                :is="getComponentForElement(element)"
                :element="element"
                :form-data="formData"
                :is-inline="true"
                @variable-click="$emit('variable-click', $event)"
            />
        </template>
        <template v-else>
            {{ element.content }}
        </template>
    </p>
</template>

<style scoped>
.document-paragraph {
    margin: 0.5em 0;
    text-align: left;
}

.document-paragraph.align-center {
    text-align: center;
}

.document-paragraph.align-right {
    text-align: right;
}

.document-paragraph.align-justify {
    text-align: justify;
}

.document-paragraph.align-distribute {
    text-align: justify;
    text-justify: distribute;
}

/* Наследование стилей для вложенных элементов */
.document-paragraph ::v-deep(.text-element) {
    display: inline;
}

.document-paragraph ::v-deep(.variable-element) {
    display: inline;
}

.document-paragraph ::v-deep(.text-run) {
    display: inline;
}

/* Стили для печати */
@media print {
    .document-paragraph {
        text-align: justify;
        margin: 0.75em 0;
    }
}
</style>
