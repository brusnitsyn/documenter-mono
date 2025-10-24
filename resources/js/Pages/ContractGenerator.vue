<script setup>
import {computed, nextTick, onMounted, ref, useTemplateRef, watch} from "vue"
import {useDateFormat, useDebounceFn} from "@vueuse/core"
import Sections from "../Layouts/Sections.vue";
import Input from '../Components/Input/Input.vue'
import Select from "../Components/Select/Select.vue";
import Card from "../Components/Card/Card.vue";
import Button from "../Components/Button/Button.vue";
import ListStrate from "../Components/List/ListStrate.vue";
import CardBack from "../Components/Card/CardBack.vue";
import {Link, router} from "@inertiajs/vue3";
import Editor from "../Components/Editor.vue";
import VuePdfEmbed, { useVuePdfEmbed } from 'vue-pdf-embed'
import {useFileDownload} from "../Composables/useFileDownload.js";
import PriceInput from "../Components/Document/InputVariable/PriceInput.vue";
import TextArea from "../Components/Input/TextArea.vue";

const { downloadFile } = useFileDownload()

const props = defineProps({
    template: Object,
})

const editorRef = ref(null)
const content = ref(props.template.content ?? [])
const formData = ref([])
const documentStructure = ref(props.template.content || [])

const prepareVariables = (variables) => {
    for (const variable of variables) {
        formData.value.push({
            label: variable.label,
            type: variable.type,
            name: variable.name,
            options: variable.options
        })
    }
}

// Форматируем ключ переменной в читаемый name
const formatLabel = (key) => {
    return key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

const viewer = useTemplateRef('viewer')

onMounted(async() => {
    await preview()
    prepareVariables(props.template.variables)
})

const previewLoading = ref(true)
const previewUrl = ref()
const preview = async () => {
    previewLoading.value = true
    await axios.post(`/contract-generator/${props.template.id}/preview`, {
        variables: formData.value
    }, {
        responseType: 'blob'
    }).then(res => {
        previewUrl.value = URL.createObjectURL(res.data)
    })
}

const updatePreview = async () => {
    await preview()
}

const onChangeVariableTextValue = (variableId, value) => {
    console.log(variableId, value)
    changeVariableValue(variableId, value)
}

const onChangeVariableSelectValue = (variableId, option) => {
    changeVariableValue(variableId, option.value)
}

const changeVariableValue = (variableId, value) => {
    if (content.value && Array.isArray(content.value)) {
        const updatedContent = content.value.map(htmlString => {
            return htmlString.replace(
                new RegExp(`(<span[^>]*brs-element-id="${variableId}"[^>]*>)[^<]*(</span>)`, 'g'),
                `$1${value}$2`
            )
        })

        content.value = updatedContent
    }
}

const onPrint = () => {
    if (viewer.value)
        viewer.value.print(200, props.template.name, true)
}

const onDownloadDocx = async () => {
    try {
        await downloadFile(
            `/contract-generator/${props.template.id}/download`,
            { variables: formData.value },
            `${props.template.name}.docx`
        )
    } catch (e) {
        console.error('Ошибка при скачивании docx файла: ', e.message)
    }
}

// function scrollToElementByText(text, options = {}) {
//     const {
//         behavior = 'smooth',
//         block = 'start',
//         inline = 'nearest',
//         partialMatch = false,
//         caseSensitive = false
//     } = options
//
//     // Ищем все элементы, содержащие текст
//     const elements = Array.from(document.querySelectorAll('*')).filter(element => {
//         const elementText = caseSensitive
//             ? element.textContent
//             : element.textContent.toLowerCase()
//         const searchText = caseSensitive
//             ? text
//             : text.toLowerCase()
//
//         return partialMatch
//             ? elementText.includes(searchText)
//             : elementText.trim() === searchText
//     })
//
//     if (elements.length > 0) {
//         elements[0].scrollIntoView({
//             behavior,
//             block,
//             inline
//         })
//         return elements[0]
//     }
//
//     return null
// }
const searchAndScroll = (targetText) => {
    if (!targetText.trim()) return

    const result = scrollToElementByText(targetText)

    if (result) {
        console.log(result)
        highlightElement(result.element)
    }
}

const scrollToElementByText = (targetText) => {
    const elementContainers = document.querySelectorAll('.textLayer')
    if (!elementContainers) return null

    const elementsOfContainers = []
    for (const container of elementContainers) {
        elementsOfContainers.push(...container.children)
    }

    const allElements = Array.from(elementsOfContainers)
        .filter(el => el.textContent && el.textContent.trim())
        .filter(el => {
            const style = window.getComputedStyle(el)
            return style.display !== 'none' && style.visibility !== 'hidden'
        })

    // Сначала ищем точное совпадение
    for (const el of allElements) {
        if (el.textContent.trim() === targetText) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            return { element: el, foundText: targetText, isComposite: false }
        }
    }

    // Ищем составной текст в соседних элементах
    for (let i = 0; i < allElements.length - 1; i++) {
        const current = allElements[i]
        const next = allElements[i + 1]

        const combined = current.textContent.trim() + next.textContent.trim()

        if (combined === targetText) {
            current.scrollIntoView({ behavior: 'smooth', block: 'center' })
            return {
                element: [current, next],
                foundText: targetText,
                isComposite: true,
                parts: [current.textContent.trim(), next.textContent.trim()]
            }
        }
    }

    return null
}

const highlightElement = (element) => {
    // Убираем предыдущую подсветку
    document.querySelectorAll('.search-highlight').forEach(el => {
        el.classList.remove('search-highlight')
    })

    if (Array.isArray(element)) {
        for (const el of element) {
            el.classList.add('search-highlight')

            setTimeout(() => {
                el.classList.remove('search-highlight')
            }, 3000)
        }
    } else {
        element.classList.add('search-highlight')

        setTimeout(() => {
            element.classList.remove('search-highlight')
        }, 3000)
    }
}
</script>

<template>
    <Sections>
        <template #leftbar>
            <Card header="Информация о документе">
                <div>
                    <ListStrate header="Наименование">
                        <span class="block text-sm">
                            {{ template.name }}
                        </span>
                    </ListStrate>
                    <ListStrate header="Дата обновления">
                        <span class="text-sm">
                            {{ useDateFormat(template.updated_at, 'DD.MM.YYYY HH:mm:ss') }}
                        </span>
                    </ListStrate>
                    <ListStrate header="Дата создания">
                        <span class="text-sm">
                            {{ useDateFormat(template.created_at, 'DD.MM.YYYY HH:mm:ss') }}
                        </span>
                    </ListStrate>
                </div>
                <template #footer>
                    <div class="flex flex-col gap-y-1">
                        <Button block @click="onPrint" :loading="previewLoading">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"></path><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"></path><rect x="7" y="13" width="10" height="8" rx="2"></rect></g></svg>
                            </template>
                            Печать документа
                        </Button>
                        <Button block @click="onDownloadDocx" :loading="previewLoading">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"></path><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"></path><path d="M12 11v6"></path><path d="M9 14l3 3l3-3"></path></g></svg>
                            </template>
                            Скачать docx
                        </Button>
                        <CardBack :tag="Link" href="/" class="mt-2" />
                    </div>
                </template>
            </Card>
        </template>

        <Card header="Предпросмотр" :content-scroll="!previewLoading" :content-relative>
            <div class="flex flex-col items-center justify-center">
                <VuePdfEmbed width="793.701" text-layer ref="viewer" :source="previewUrl" @rendered="previewLoading = false" />
                <div  v-if="previewLoading" class="absolute inset-0 backdrop-blur-xs h-full flex items-center justify-center z-10">
                    <div class="text-center space-y-4">
                        <div class="flex items-center justify-center">
                            <div class="relative">
                                <div class="w-8 h-8 border-3 border-gray-200 border-t-blue-500 rounded-full animate-spin"></div>
                                <div class="absolute inset-0 w-8 h-8 border-3 border-transparent border-r-blue-300 rounded-full animate-spin" style="animation-duration: 1.5s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <template #rightbar>
            <Card header="Свойства документа" :content-relative>
                <div v-if="previewLoading" class="absolute inset-0 flex items-center justify-center">
                    <div class="flex items-center justify-center">
                        <div class="relative">
                            <div class="w-8 h-8 border-3 border-gray-200 border-t-blue-500 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 w-8 h-8 border-3 border-transparent border-r-blue-300 rounded-full animate-spin" style="animation-duration: 1.5s"></div>
                        </div>
                    </div>
                </div>
                <ListStrate v-else v-for="(variable, key) in formData" :key="key" :header="variable.label">
                    <Input
                        v-if="variable.type === 'text'"
                        :id="key"
                        @focus="searchAndScroll(variable.name)"
                        v-model:value="variable.value"
                        @update:value="value => onChangeVariableTextValue(key, value)"
                        :placeholder="`Введите ${formatLabel(variable.label)}`"
                    />

                    <TextArea
                        v-if="variable.type === 'textarea'"
                        :rows="8"
                        :resize="false"
                        :id="key"
                        @focus="searchAndScroll(variable.name)"
                        v-model:value="variable.value"
                        @update:value="value => onChangeVariableTextValue(key, value)"
                        :placeholder="`Введите ${formatLabel(variable.label)}`"
                    />

                    <!-- Select поле -->
                    <Select
                        v-else-if="variable.type === 'select'"
                        :id="key"
                        @focus="searchAndScroll(variable.name)"
                        @change="value => onChangeVariableSelectValue(key, value)"
                        v-model:value="variable.value"
                        :options="variable.options"
                    />

                    <!-- Radio кнопки -->
                    <div v-else-if="variable.type === 'radio'" class="space-y-2">
                        <label
                            v-for="(optionLabel, optionValue) in variable.options"
                            :key="optionValue"
                            class="flex items-center"
                        >
                            <input
                                type="radio"
                                :name="key"
                                :value="optionValue"
                                v-model="formData[key]"
                                @change="updatePreview"
                                class="mr-2"
                            >
                            {{ optionLabel }}
                        </label>
                    </div>

                    <PriceInput v-else-if="variable.type === 'price-input'"
                                v-model:number="variable.number"
                                v-model:text="variable.value"
                                @focus="searchAndScroll(variable.name)"
                    />
                </ListStrate>
                <template #footer>
                    <Button :loading="previewLoading" block @click="updatePreview">
                        Обновить предпросмотр
                    </Button>
                </template>
            </Card>
        </template>
    </Sections>
</template>

<style scoped>
@reference "tailwindcss";

:deep(.search-highlight) {
    @apply border border-dashed border-yellow-500 bg-yellow-200 text-black;
}
:deep(.vue-pdf-embed) {
    margin: 0 auto;
}
:deep(.vue-pdf-embed .vue-pdf-embed__page) {
    margin-bottom: 20px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    overflow: hidden;
}
</style>
