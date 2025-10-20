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

const open = defineModel('open')
const stage = ref('upload')
const description = ref('')
const uploadedFile = ref(null)

const { formData: uploadForm, errors, loading, submit, setFile: setFileToForm } = useApiForm({
    name: '',
    description: '',
    file: null,
    variables: []
})

watch(() => stage.value, (value) => {
    if (value === 'variables') {
        description.value = 'Опишите найденные в документе переменные'
    }
})

const uploadFile = async () => {
    try {
        setFileToForm('doc_file', uploadedFile.value)
        await submit('/api/import/variables').then(res => {
            uploadForm.value.variables = res.variables
            if (uploadForm.value.variables.length > 0) stage.value = 'variables'
        })
    } catch (err) {
        console.log(err)
    }
}

const variableTypes = [
    {
        key: 'Текст',
        value: 'text'
    },
    {
        key: 'Выбор',
        value: 'select'
    }
]

const submitForm = () => {
    uploadForm.value.file = uploadedFile.value
    router.post('/templates/import', uploadForm.value, {
        onSuccess: () => {
            open.value = false
        }
    })
}
</script>

<template>
    <Modal v-model:open="open" title="Импорт документа" :description="description">
        <div v-if="stage === 'upload'" class="flex flex-col gap-y-1">
            <Input v-model:value="uploadForm.name" label="Наименование" />
            <Input v-model:value="uploadForm.description" label="Описание" />
            <FileUpload class="mt-2.5" v-model:file="uploadedFile" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
        </div>
        <ListStrate v-else v-for="variable in uploadForm.variables" :header="variable.label">
            <Select :options="variableTypes" v-model:value="variable.type" placeholder="Выберите тип" />
        </ListStrate>

        <div v-if="errors" class="absolute translate-x-full top-2 -right-2">
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
