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
                        <Button block @click="onPrint">
                            <template #icon>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"></path><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"></path><rect x="7" y="13" width="10" height="8" rx="2"></rect></g></svg>
                            </template>
                            Печать документа
                        </Button>
                        <Button block @click="onDownloadDocx">
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

        <Card header="Предпросмотр">
            <div class="flex flex-col items-center justify-center relative">
                <VuePdfEmbed width="793.701" text-layer ref="viewer" :source="previewUrl" @rendered="previewLoading = false" />
                <div v-if="previewLoading" class="absolute inset-y-0 backdrop-blur-xs h-[calc(100vh-100px)] w-[793px] rounded-md">
                    <div  class="relative text-black text-center top-1/2">
                        Loading...
                    </div>
                </div>
            </div>
        </Card>

        <template #rightbar>
            <Card header="Свойства документа">
                <ListStrate v-for="(variable, key) in formData" :key="key" :header="variable.label">
                    <Input
                        v-if="variable.type === 'text'"
                        :id="key"
                        v-model:value="variable.value"
                        @update:value="value => onChangeVariableTextValue(key, value)"
                        :placeholder="`Введите ${formatLabel(variable.label)}`"
                    />

                    <!-- Select поле -->
                    <Select
                        v-else-if="variable.type === 'select'"
                        :id="key"
                        @change="value => onChangeVariableSelectValue(key, value)"
                        v-model:value="variable.value"
                        :options="variable.values"
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
                </ListStrate>
                <template #footer>
                    <Button block @click="updatePreview">
                        Обновить предпросмотр
                    </Button>
                </template>
            </Card>
        </template>
    </Sections>
</template>

<style scoped>
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
