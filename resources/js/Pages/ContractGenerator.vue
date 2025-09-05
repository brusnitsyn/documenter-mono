<script setup>
import {nextTick, onMounted, ref} from "vue"
import {useDebounceFn} from "@vueuse/core"
import DocumentRenderer from "../Components/Document/DocumentRenderer.vue";
import Sections from "../Layouts/Sections.vue";
import Input from '../Components/Input/Input.vue'
import PageHeader from "../Components/Page/PageHeader.vue";
import Page from "../Components/Page/Page.vue";
import PageBody from "../Components/Page/PageBody.vue";
import A4 from "../Components/Document/A4.vue";
import Select from "../Components/Select/Select.vue";

const props = defineProps({
    template: Object,
    initialFormData: Object,
})

const formData = ref({...props.initialFormData})
const documentStructure = ref(props.template.content.structure || [])
const compiledText = ref('')

// Форматируем ключ переменной в читаемый label
const formatLabel = (key) => {
    return key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

// Обновляем превью с дебаунсом
const updatePreview = async () => {
    try {
        const response = await axios.post('/contract-generator/update-preview', {
            template_id: props.template.id,
            form_data: formData.value
        })

        if (response.data.success) {
            documentStructure.value = response.data.updatedStructure
            compiledText.value = response.data.compiledText
        }
    } catch (error) {
        console.error('Error updating preview:', error)
    }
}

const debouncedUpdatePreview = useDebounceFn(updatePreview, 500)

const downloadContract = () => {
    if (!compiledText.value) return

    const blob = new Blob([compiledText.value], { type: 'text/plain' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${props.template.name}.txt`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
}

// Инициализируем первое обновление при загрузке
onMounted(() => {
    nextTick(() => {
        updatePreview()
    })
})
</script>

<template>
    <Sections>
        <template #leftbar>
            <div class="">
                <h2 class="font-semibold mb-4">Заполните данные</h2>

                <div v-for="(field, key) in initialFormData" :key="key" class="mb-4 ">
                    <label :for="key" class="block text-sm font-medium mb-1">
                        {{ field.label }}
                    </label>
                    <Input
                        v-if="field.type === 'text'"
                        :id="key"
                        v-model="formData[key]"
                        @input="debouncedUpdatePreview"
                        :placeholder="`Введите ${formatLabel(key)}`"
                    />

                    <!-- Select поле -->
                    <Select
                        v-else-if="field.type === 'select'"
                        :id="key"
                        v-model="formData[key]"
                        @change="updatePreview"
                        :options="field.options"
                    />

                    <!-- Radio кнопки -->
                    <div v-else-if="field.type === 'radio'" class="space-y-2">
                        <label
                            v-for="(optionLabel, optionValue) in field.options"
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
                </div>
            </div>
        </template>
        <Page>
<!--            <template #header>-->
<!--                <PageHeader>-->
<!--                    Предпросмотр договора-->
<!--                </PageHeader>-->
<!--            </template>-->

            <PageBody>
                <div class="flex items-center justify-center">
                    <A4>
                        <DocumentRenderer
                            :structure="documentStructure"
                            v-if="documentStructure.length > 0"
                        />
                    </A4>
                </div>

                <div class="mt-4">
                    <button
                        @click="downloadContract"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
                        :disabled="!compiledText"
                    >
                        Скачать договор
                    </button>
                </div>
            </PageBody>
        </Page>
    </Sections>
</template>

<style scoped>

</style>
