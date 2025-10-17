<script setup>
import Modal from "../../Components/Modal/Modal.vue";
import {computed, ref, watch} from "vue";
import FileUpload from "../../Components/Input/FileUpload.vue";
import Button from "../../Components/Button/Button.vue";
import {router} from "@inertiajs/vue3";
import Select from "../../Components/Select/Select.vue";
import ListStrate from "../../Components/List/ListStrate.vue";
import Input from "../../Components/Input/Input.vue";

const open = defineModel('open')
const file = ref(null)
const stage = ref('upload')
const description = ref('')
const variables = ref([])
const uploadForm = ref({
    name: '',
    description: '',
    file: '',
    variables: []
})

watch(() => stage.value, (value) => {
    console.log(value)
    if (value === 'variables') {
        description.value = 'Опишите найденные в документе переменные'
    }
})

const uploadFile = async () => {
    // router.post('/templates/import', {
    //     doc_file: file.value
    // })
    const formData = new FormData()
    formData.append('doc_file', uploadForm.value.file)
    await axios.post('api/import/variables', formData).then(res => {
        uploadForm.value.variables = res.data.variables
        if (uploadForm.value.variables.length > 0) stage.value = 'variables'
    })
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

const submit = () => {
    router.post('/templates/import', uploadForm.value)
}
</script>

<template>
    <Modal v-model:open="open" title="Импорт документа" :description="description">
        <div v-if="stage === 'upload'">
            <Input v-model:value="uploadForm.name" label="Наименование" />
            <Input v-model:value="uploadForm.description" label="Описание" />
            <FileUpload class="mt-2.5" v-model:file="uploadForm.file" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
        </div>
        <ListStrate v-else v-for="variable in uploadForm.variables" :header="variable.label">
            <Select :options="variableTypes" v-model:value="variable.type" placeholder="Выберите тип" />
        </ListStrate>
        <template #actions>
            <Button v-if="stage === 'upload'" @click="uploadFile">
                Далее
            </Button>
            <Button v-else @click="submit">
                Завершить
            </Button>
        </template>
    </Modal>
</template>

<style scoped>

</style>
