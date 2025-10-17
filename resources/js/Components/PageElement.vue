<script setup>
import {computed, nextTick, ref} from 'vue';

const props = defineProps({
    element: Object,
    pageIndex: Number,
    elementIndex: Number
});

const emit = defineEmits(['update', 'remove', 'split']);

const isEditing = ref(false);
const elementRef = ref()

const isTextElement = computed(() =>
    ['paragraph', 'heading'].includes(props.element.type)
);

const elementClasses = computed(() => [
    `element-${props.element.type}`,
    { 'editing': isEditing.value }
]);

const elementStyles = computed(() => {
    const styles = {};

    if (props.element.type === 'heading') {
        styles.fontSize = `${props.element.level * 4 + 12}px`;
        styles.fontWeight = 'bold';
    }

    return styles;
});

const displayContent = computed(() => {
    if (typeof props.element.content === 'string') {
        return props.element.content;
    }
    return '';
});

const tableRows = computed(() => {
    if (props.element.type === 'table' && props.element.content.rows) {
        return props.element.content.rows;
    }
    return [];
});

// Обработчики текстовых элементов
const onTextInput = (event) => {
    isEditing.value = true;
    console.log(event.target.textContent)
    emit('update', {
        ...props.element,
        content: event.target.textContent
    });
};

const onTextBlur = () => {
    isEditing.value = false;
};

// Обработчики таблиц
const onTableCellInput = (rowIndex, cellIndex, event) => {
    const newRows = [...tableRows.value];
    newRows[rowIndex][cellIndex] = event.target.textContent;

    emit('update', {
        ...props.element,
        content: {
            ...props.element.content,
            rows: newRows
        }
    });
};

const onTableBlur = () => {
    // Автосохранение таблицы
};

const addTableRow = () => {
    const cols = props.element.content.cols || 2;
    const newRow = Array(cols).fill('Новая ячейка');
    const newRows = [...tableRows.value, newRow];

    emit('update', {
        ...props.element,
        content: {
            ...props.element.content,
            rows: newRows
        }
    });
};

const addTableColumn = () => {
    const newRows = tableRows.value.map(row => [...row, 'Новая ячейка']);

    emit('update', {
        ...props.element,
        content: {
            rows: newRows,
            cols: (props.element.content.cols || 2) + 1
        }
    });
};

const removeTableRow = () => {
    if (tableRows.value.length > 1) {
        const newRows = tableRows.value.slice(0, -1);
        emit('update', {
            ...props.element,
            content: {
                ...props.element.content,
                rows: newRows
            }
        });
    }
};

const removeTableColumn = () => {
    if (props.element.content.cols > 1) {
        const newRows = tableRows.value.map(row => row.slice(0, -1));
        emit('update', {
            ...props.element,
            content: {
                rows: newRows,
                cols: props.element.content.cols - 1
            }
        });
    }
};

// Обработчики переменных
const onVariableUpdate = () => {
    emit('update', {
        ...props.element,
        content: `{{${props.element.variableName}}}`
    });
};
</script>

<template>
    <div
        class="page-element"
        :class="elementClasses"
        :style="elementStyles"
        @dblclick="$emit('split')"
    >
        <div class="element-content">
            <!-- Текстовые элементы -->
            <div
                v-if="isTextElement"
                ref="elementRef"
                class="text-element"
                contenteditable="true"
                @input="onTextInput"
                @blur="onTextBlur"
                v-html="displayContent"
            ></div>

            <!-- Таблица -->
            <div v-else-if="element.type === 'table'" class="table-element">
                <table class="element-table">
                    <tbody>
                        <tr v-for="(row, rowIndex) in tableRows" :key="rowIndex">
                            <td
                                v-for="(cell, cellIndex) in row.cells"
                                ref="elementRef"
                                :key="cellIndex"
                                contenteditable="true"
                                @input="onTableCellInput(rowIndex, cellIndex, $event)"
                                @blur="onTableBlur"
                            >
                                {{ cell }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="table-controls">
                    <button @click="addTableRow" class="btn btn-sm">➕ Строка</button>
                    <button @click="addTableColumn" class="btn btn-sm">➕ Столбец</button>
                    <button @click="removeTableRow" class="btn btn-sm btn-danger">➖ Строка</button>
                    <button @click="removeTableColumn" class="btn btn-sm btn-danger">➖ Столбец</button>
                </div>
            </div>

            <!-- Переменная -->
            <div v-else-if="element.type === 'variable'" class="variable-element">
                <span class="variable-tag">{{ element.variableName }}</span>
                <input
                    v-model="element.variableName"
                    @blur="onVariableUpdate"
                    class="variable-input"
                    placeholder="имя_переменной"
                />
            </div>
        </div>

        <div class="element-controls">
            <button @click="$emit('remove')" class="control-btn" title="Удалить">🗑️</button>
            <button @click="$emit('split')" class="control-btn" title="Разорвать">✂️</button>
        </div>
    </div>
</template>

<style scoped>
.page-element {
    position: relative;
    margin-bottom: 1rem;
    padding: 0.5rem;
    border: 1px solid transparent;
    border-radius: 4px;
    transition: all 0.2s;
}

.page-element:hover {
    border-color: #e1e5e9;
    background: #f8f9fa;
}

.page-element.editing {
    border-color: #007bff;
    background: #e3f2fd;
}

.element-content {
    min-height: 1.5em;
}

.text-element {
    outline: none;
    min-height: 1.5em;
}

.text-element:focus {
    background: white;
}

.table-element {
    margin: 0.5rem 0;
}

.element-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0.5rem;
}

.element-table td {
    border: 1px solid #dee2e6;
    padding: 0.5rem;
    min-width: 100px;
    outline: none;
}

.element-table td:focus {
    background: #f8f9fa;
}

.table-controls {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.variable-element {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: #fffacd;
    border: 1px dashed #c4a657;
    border-radius: 4px;
}

.variable-tag {
    font-weight: bold;
    color: #8b6f1d;
}

.variable-input {
    border: 1px solid #c4a657;
    border-radius: 3px;
    padding: 0.25rem;
    background: white;
}

.element-controls {
    position: absolute;
    top: -10px;
    right: -10px;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.page-element:hover .element-controls {
    opacity: 1;
}

.control-btn {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 3px;
    background: white;
    cursor: pointer;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.control-btn:hover {
    background: #f8f9fa;
}

.btn {
    padding: 0.25rem 0.5rem;
    border: 1px solid #e1e5e9;
    border-radius: 3px;
    background: white;
    cursor: pointer;
    font-size: 0.8rem;
}

.btn:hover {
    background: #f8f9fa;
}

.btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
}

.btn-danger {
    border-color: #dc3545;
    color: #dc3545;
}

.btn-danger:hover {
    background: #fee;
}
</style>
