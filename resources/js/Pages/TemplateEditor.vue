<script setup>
import Editor from "../Components/Editor.vue";
import Sections from "../Layouts/Sections.vue";
import Card from "../Components/Card/Card.vue";
import {computed, ref, watch} from "vue";
import Button from "../Components/Button/Button.vue";
import ListStrate from "../Components/List/ListStrate.vue";
import Input from "../Components/Input/Input.vue";
import {now} from "@vueuse/core";
import Select from "../Components/Select/Select.vue";
import {Link, router} from "@inertiajs/vue3";
import CardBack from "../Components/Card/CardBack.vue";

const props = defineProps({
    template: Object
})

const editor = ref()

const content = ref(props.template?.content ?? [])
const zoom = 1
const zoom_min = 0.10
const zoom_max = 5.0
const page_format_mm = [210, 297]
const page_margins = "2cm 1.5cm 2cm 3cm"
const display = "grid" // ["grid", "vertical", "horizontal"]
const mounted = false // will be true after this component is mounted
const undo_count = -1 // contains the number of times user can undo (= current position in content_history)
const content_history = [] // contains the content states for undo/redo operations
const variables = ref(props.template?.variables_config ?? {})
const variablesItems = [
    {
        key: 'Поле ввода',
        value: 'text'
    },
    {
        key: 'Поле выбора',
        value: 'select'
    }
]

const elementInfo = ref({})
const activeElement = ref()
const activeElements = ref()
const currentTextStyle = ref('')

const getCurrentTextStyle = (style) => {
    currentTextStyle.value = style
}

const mappedTags = {
    'P': 'Текст',
    'H1': 'Заголовок 1',
    'H2': 'Заголовок 2',
    'VAR': 'Переменная'
}

const formatAlignLeft = () => {
    document.execCommand("justifyLeft")
}

const formatAlignCenter = () => {
    document.execCommand("justifyCenter")
}

const formatAlignRight = () => {
    document.execCommand("justifyRight")
}

const formatAlignJustify = () => {
    document.execCommand("justifyFull")
}

const formatTextBold = () => {
    document.execCommand("bold")
}
const formatTextItalic = () => {
    document.execCommand("italic")
}
const formatTextUnderline = () => {
    document.execCommand("underline")
}
const formatTextStrikethrough = () => {
    document.execCommand("strikethrough")
}

const formatFirstLine = () => {
    if (activeElement.value.style.textIndent) {
        activeElement.value.style.textIndent = ''
        return
    } else if (activeElement.value.parentElement.tagName === 'P' &&
        activeElement.value.parentElement.style.textIndent) {
        activeElement.value.parentElement.style.textIndent = ''
        return
    }

    if (activeElement.value.tagName === 'P') {
        activeElement.value.style.textIndent = '1.25cm'
    } else if (activeElement.value.parentElement.tagName === 'P') {
        activeElement.value.parentElement.style.textIndent = '1.25cm'
    }
}
const clearBackground = () => {
    activeElement.value.style.background = ''
}

const isVariable = computed(() => {
    return activeElement.value.getAttribute('brs-variable')
})
const createVariable = () => {
    const selection = window.getSelection();

    // Проверяем, есть ли выделение
    if (selection.rangeCount === 0) return;

    const range = selection.getRangeAt(0);
    const selectedText = range.toString();

    // Проверяем, что текст действительно выделен
    if (!selectedText) return;

    if (isVariable.value === 'true') {
        const elementId = activeElement.value.getAttribute('brs-element-id')
        delete variables.value[elementId]
        activeElement.value.removeAttribute('brs-variable')
        activeElement.value.removeAttribute('brs-type')
        activeElement.value.removeAttribute('brs-element-id')
    } else {
        // Создаем span элемент
        const span = document.createElement('span');
        const spanId = now().toString()
        span.textContent = selectedText;
        span.setAttribute('brs-variable', 'true');
        span.setAttribute('brs-type', 'text');
        span.setAttribute('brs-element-id', spanId);

        // Удаляем выделенный текст и вставляем span
        range.deleteContents();
        range.insertNode(span);

        variables.value = {
            ...variables.value,
            [spanId]: {
                name: selectedText,
                type: 'text',
                label: selectedText
            }
        }
    }

    // Очищаем выделение
    selection.removeAllRanges();

    console.log('Создан span для текста:', selectedText);
}

watch(activeElement, (element) => {
    if (!element) return

    elementInfo.value = {}

    if (element.getAttribute('brs-variable') === 'true') {
        const elementId = element.getAttribute('brs-element-id')
        elementInfo.value.id = elementId
        elementInfo.value.element = 'VAR'
        elementInfo.value.name = mappedTags[elementInfo.value.element]
        elementInfo.value.type = element.getAttribute('brs-type')
        elementInfo.value.value = ''
        return
    }

    elementInfo.value.name = mappedTags[element.tagName]
    elementInfo.value.element = element.tagName
})

const updateVariableType = (elementId, type) => {
    const variableKeys = Object.keys(variables.value[elementId])
    if (variableKeys.includes('values')) {
        if (type !== 'select') {
            delete variables.value[elementId].values
            delete variables.value[elementId].value
        }
    } else if (type === 'select'){
        variables.value[elementId].values = []
    }
}

const updateVariableValue = (elementId, value) => {
    const variableType = variables.value[elementId].type
    if (variableType === 'select') {
        variables.value[elementId].value = value
        variables.value[elementId].values = value.split(',').map(item => item.trim());
    }
}

const saveTemplate = () => {
    const data = {
        ...props.template,
        content: content.value,
        variables_config: variables.value
    }

    router.post('/editor', data)
}

const documentPrint = () => {
    window.print()
}
</script>

<template>
    <Sections>
        <template #leftbar>
            <Card>
                <template #footer>
                    <CardBack :tag="Link" href="/" />
                </template>
            </Card>
        </template>
        <Card>
            <template #header>
                <div class="flex flex-row gap-x-3">
                    <div class="flex flex-row gap-x-1">
                        <Button icon @click="formatAlignLeft">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M4 6h16"></path>
                                        <path d="M4 12h10"></path>
                                        <path d="M4 18h14"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                        <Button icon @click="formatAlignCenter">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M4 6h16"></path>
                                        <path d="M8 12h8"></path>
                                        <path d="M6 18h12"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                        <Button icon @click="formatAlignRight">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M4 6h16"></path>
                                        <path d="M10 12h10"></path>
                                        <path d="M6 18h14"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                        <Button icon @click="formatAlignJustify">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M4 6h16"></path>
                                        <path d="M4 12h16"></path>
                                        <path d="M4 18h12"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                    </div>
                    <div class="flex flex-row gap-x-1">
                        <Button icon @click="formatTextBold">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M7 5h6a3.5 3.5 0 0 1 0 7H7z"></path>
                                        <path d="M13 12h1a3.5 3.5 0 0 1 0 7H7v-7"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                        <Button icon @click="formatTextItalic">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M11 5h6"></path>
                                        <path d="M7 19h6"></path>
                                        <path d="M14 5l-4 14"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                        <Button icon @click="formatTextUnderline">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M7 5v5a5 5 0 0 0 10 0V5"></path>
                                        <path d="M5 19h14"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                        <Button icon @click="formatTextStrikethrough">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M5 12h14"></path>
                                        <path
                                            d="M16 6.5A4 2 0 0 0 12 5h-1a3.5 3.5 0 0 0 0 7h2a3.5 3.5 0 0 1 0 7h-1.5a4 2 0 0 1-4-1.5"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                    </div>
                    <div class="flex flex-row gap-x-1">
                        <Button icon @click="formatFirstLine">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6H9"></path><path d="M20 12h-7"></path><path d="M20 18H9"></path><path d="M4 8l4 4l-4 4"></path></g></svg>
                            </template>
                        </Button>
                        <Button icon @click="documentPrint">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"></path><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"></path><rect x="7" y="13" width="10" height="8" rx="2"></rect></g></svg>
                            </template>
                        </Button>
                        <Button icon @click="createVariable">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4C2.5 9 2.5 14 5 20M19 4c2.5 5 2.5 10 0 16M9 9h1c1 0 1 1 2.016 3.527C13 15 13 16 14 16h1"></path><path d="M8 16c1.5 0 3-2 4-3.5S14.5 9 16 9"></path></g></svg>
                            </template>
                        </Button>
                        <Button icon @click="clearBackground">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                     viewBox="0 0 24 24">
                                    <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                       stroke-linejoin="round">
                                        <path d="M5 12h14"></path>
                                        <path
                                            d="M16 6.5A4 2 0 0 0 12 5h-1a3.5 3.5 0 0 0 0 7h2a3.5 3.5 0 0 1 0 7h-1.5a4 2 0 0 1-4-1.5"></path>
                                    </g>
                                </svg>
                            </template>
                        </Button>
                    </div>
                </div>
            </template>
            <Editor id="editor"
                    ref="editor"
                    v-model="content"
                    :zoom="zoom"
                    :page_format_mm="page_format_mm"
                    :page_margins="page_margins"
                    :display="display"
                    @update:current-style="getCurrentTextStyle"
                    v-model:active-element="activeElement"
                    v-model:active-elements="activeElements"
            />
        </Card>
        <template #rightbar>
            <Card>
                <template #header>
                    <span>
                        {{ elementInfo.name ?? 'Нет активного элемента' }}
                    </span>
                </template>
                <template v-if="elementInfo.name">
                    <ListStrate header="Параметры">
                        <div class="flex flex-col gap-y-1">
                            <Input v-if="elementInfo?.id" label="Идентификатор" v-model:value="elementInfo.id" disabled />
<!--                            <Input v-if="elementInfo?.type" label="Тип" v-model:value="elementInfo.type" disabled />-->
                            <Input v-if="variables[elementInfo.id].label" label="Наименование" v-model:value="variables[elementInfo.id].label" />
                        </div>
                    </ListStrate>
                    <ListStrate v-if="elementInfo.element === 'VAR'" header="Заполнение">
                        <div class="flex flex-col gap-y-1">
                            <Select label="Тип" v-model:value="variables[elementInfo.id].type" @update:value="value => updateVariableType(elementInfo.id, value)" :options="variablesItems" />
                            <Input label="Значения" v-if="variables[elementInfo.id].type === 'select'" v-model:value="variables[elementInfo.id].value" @update:value="value => updateVariableValue(elementInfo.id, value)" />
                        </div>
                    </ListStrate>
                    <Button v-if="elementInfo.element === 'VAR'" block>
                        Создать раздел
                    </Button>
                </template>
                <template #footer>
                    <Button block @click="saveTemplate">
                        Сохранить
                    </Button>
                </template>
            </Card>
        </template>
    </Sections>
</template>
