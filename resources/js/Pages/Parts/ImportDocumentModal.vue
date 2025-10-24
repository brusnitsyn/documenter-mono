<script setup>
import Modal from "../../Components/Modal/Modal.vue";
import {computed, ref, watch} from "vue";
import FileUpload from "../../Components/Input/FileUpload.vue";
import Button from "../../Components/Button/Button.vue";
import {router} from "@inertiajs/vue3";
import Select from "../../Components/Select/Select.vue";
import ListStrate from "../../Components/List/ListStrate.vue";
import Input from "../../Components/Input/Input.vue";
import {useApiForm} from "../../Composables/useApiForm.js";
import Card from "../../Components/Card/Card.vue";
import FormGroup from "../../Components/Form/FormGroup.vue";
import Calendar from "../../Components/Calendar/Calendar.vue";

const open = defineModel('open')
const stage = ref('upload')
const description = ref('')
const uploadedFile = ref(null)
const templateVariables = ref(null)

const { formData: uploadForm, errors, reset, loading, submit, setFile: setFileToForm } = useApiForm({
    name: '',
    description: '',
    file: null,
    variables: []
})

watch(() => stage.value, (value) => {
    if (value === 'variables') description.value = 'Опишите найденные в документе переменные'
    else description.value = ''
})

const uploadFile = async () => {
    try {
        setFileToForm('doc_file', uploadedFile.value)
        await submit('/api/import/variables').then(res => {
            uploadForm.value.variables = res.variables.map(itm => ({
                label: itm.label,
                name: itm.name,
                type: 'text'
            }))
            templateVariables.value = res.variables
            if (templateVariables.value.length > 0) {
                stage.value = 'variables'
                selectedVariable.value = uploadForm.value.variables[0]
            }
            else errors.value = {
                file: [
                    'В документе отсутствуют переменные'
                ]
            }
        })
    } catch (err) {
        console.log(err)
    }
}

const variableTypes = [
    {
        key: 'Однострочное поле',
        value: 'text'
    },
    {
        key: 'Многострочное поле',
        value: 'textarea'
    },
    {
        key: 'Поле выбора',
        value: 'select'
    },
    {
        key: 'Поле ввода стоимости',
        value: 'price-input'
    },
    {
        key: 'Календарь',
        value: 'calendar'
    },
]

const submitForm = () => {
    uploadForm.value.file = uploadedFile.value
    router.post('/templates/import', uploadForm.value, {
        onSuccess: () => {
            open.value = false
        }
    })
}

const afterCloseModal = () => {
    stage.value = 'upload'
    uploadedFile.value = null
    reset()
}

const widthOfStage = computed(() => {
    if (stage.value === 'upload')
        return 0
    else if (stage.value === 'variables')
        return 980
})
const calendarNowDate = ref(new Date())

const selectedVariable = ref()
const activeVariable = computed(() => {
    return uploadForm.value.variables.find(itm => itm.name === selectedVariable.value?.name)
})

const inputVariableOptions = (value) => {
    activeVariable.value.options = value.split(',').map(item => item.trim())
}

const changeTypeValue = (type) => {
    if (type !== 'select') {
        delete activeVariable.value.options
        delete activeVariable.value.textOptions
    }
}

const clickToVariable = (variable) => {
    selectedVariable.value = variable
    calendarNowDate.value = new Date()
}
</script>

<template>
    <Modal v-model:open="open" title="Импорт документа" :description="description" @after-close="afterCloseModal" :width="widthOfStage">
        <div v-if="stage === 'upload'" class="flex flex-col gap-y-1">
            <Input v-model:value="uploadForm.name" label="Наименование" />
            <Input v-model:value="uploadForm.description" label="Описание" />
            <FileUpload class="mt-2.5" v-model:file="uploadedFile" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
        </div>
        <div v-else class="grid grid-cols-[280px_1fr] gap-x-2 ">
            <Card header="Переменные документа">
                <div class="flex flex-col gap-y-0.5 max-h-[420px] pr-2 overflow-y-auto">
                    <Button v-for="variable in templateVariables" block @click="clickToVariable(variable)">
                        {{ variable.label }}
                    </Button>
                </div>
<!--                <ListStrate v-for="variable in uploadForm.variables" :header="variable.label">-->
<!--                    <Select :options="variableTypes" v-model:value="variable.type" placeholder="Выберите тип" />-->
<!--                </ListStrate>-->
            </Card>
            <Card :header="selectedVariable.label">
                <div class="pr-2">
                    <div class="flex flex-col gap-y-1">
                        <FormGroup label="Наименование переменной" position="top">
                            <Input v-model:value="activeVariable.name" disabled />
                        </FormGroup>

                        <FormGroup label="Отображаемое наименование" position="top">
                            <Input v-model:value="activeVariable.label" />
                        </FormGroup>

                        <FormGroup label="Тип ввода" position="top">
                            <Select :options="variableTypes" v-model:value="activeVariable.type" @update:value="changeTypeValue(value)" placeholder="Выберите тип" />
                        </FormGroup>

                        <FormGroup v-if="activeVariable.type === 'select'" label="Значения для выбора" position="top">
                            <Input v-model:value="activeVariable.textOptions" @update:value="value => inputVariableOptions(value)" />
                        </FormGroup>

                        <FormGroup v-if="activeVariable.type === 'calendar'" label="Формат выводимой даты" position="top">
                            <Input v-model:value="activeVariable.format" placeholder="К примеру dd.MM.yyyy" />
                        </FormGroup>

                        <FormGroup v-if="activeVariable.type === 'calendar'" label="Предпросмотр даты" position="top">
                            <Calendar v-model="calendarNowDate" :format="activeVariable.format" block disabled />
                        </FormGroup>
                    </div>
                </div>
            </Card>
        </div>

        <div v-if="errors" class="absolute translate-x-full top-2 -right-2 flex flex-col gap-y-1">
            <template v-for="errorContainer in errors">
                <template v-for="error in errorContainer">
                    <div class="flex flex-row items-center bg-rose-300 rounded-md gap-x-1.5 py-2 px-3">
                        <div class="h-5 w-5 text-red-500">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M9 10h.01"></path><path d="M15 10h.01"></path><path d="M9.5 15.25a3.5 3.5 0 0 1 5 0"></path></g></svg>
                        </div>
                        <span class="text-red-500 text-sm">
                            {{ error }}
                        </span>
                    </div>
                </template>
            </template>
        </div>

        <template #actions>
            <Button v-if="stage === 'upload'" @click="uploadFile">
                Далее
            </Button>
            <Button v-else @click="submitForm">
                Завершить
            </Button>
        </template>
    </Modal>
</template>

<style scoped>

</style>
