<script setup>
import { computed } from 'vue';

const props = defineProps({
    parentElement: Object,
    element: Object,
    formData: Object,
    isInline: {
        type: Boolean,
        default: false
    }
});

const displayText = computed(() => {
    return props.element.content || '';
});

const textStyles = computed(() => {
    const styles = {};
    const formatting = props.element.formatting || {};

    if (formatting.fontSize) {
        styles.fontSize = formatting.fontSize;
    }

    if (formatting.fontFamily) {
        styles.fontFamily = formatting.fontFamily;
    }

    if (formatting.fontColor) {
        styles.color = formatting.fontColor;
    }

    return styles;
});

const textClasses = computed(() => {
    const classes = [];
    const formatting = props.element.formatting || {};

    if (formatting.bold) {
        classes.push('text-bold');
    }

    if (formatting.italic) {
        classes.push('text-italic');
    }

    if (formatting.underline !== 'none') {
        classes.push('text-underline');
    }

    return classes;
});
</script>

<template>
  <span
      :data-element-id="element.id"
      class="text-element"
      :style="textStyles"
      :class="textClasses"
  >
    {{ displayText }}
  </span>
</template>

<style scoped>
.text-element {
    display: inline;
    white-space: pre-wrap;
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

/* Стили для печати */
@media print {
    .text-element {
        color: black !important;
        font-family: 'Times New Roman', serif !important;
    }
}
</style>
