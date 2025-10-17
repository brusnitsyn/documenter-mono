<script setup>
import Modal from "../../Components/Modal/Modal.vue";
import Input from "../../Components/Input/Input.vue"
import {ref, watch} from "vue";
import FormGroup from "../../Components/Form/FormGroup.vue";
import Select from "../../Components/Select/Select.vue";
import Button from "../../Components/Button/Button.vue";

const props = defineProps({
    variable: Object
})

const emit = defineEmits(['save']);

watch(() => props.variable, (newVal) => {
    if (newVal) {
        localVariable.value = { ...newVal }
    }
}, { immediate: true })

const addOption = () => {
    localVariable.value.options.push({ value: '', label: '' })
}

const removeOption = (index) => {
    localVariable.value.options.splice(index, 1)
}

const save = () => {
    emit('save', { ...localVariable.value });
};

const open = defineModel('open', {
    type: Boolean,
    default: false
})

const localVariable = ref({
    name: '',
    label: '',
    type: 'text',
    options: [],
    default: ''
})

const typeOptions = [
    {
        text: 'Текст'
    }
]
</script>

<template>
    <Modal v-model:open="open" @close="open = false" title="Добавить переменную">
        <div class="flex flex-col gap-y-2">
            <FormGroup label="Имя переменной:">
                <Input v-model:value="localVariable.name" placeholder="Например: your_name" />
            </FormGroup>

            <FormGroup label="Отображаемое имя:">
                <Input v-model:value="localVariable.label" placeholder="Например: Ваше имя" />
            </FormGroup>

            <FormGroup label="Тип:">
                <Select v-model="localVariable.type" :options="typeOptions" />
            </FormGroup>
        </div>

        <template #actions>
            <div class="flex flex-row justify-end gap-x-2">
                <Button variant="danger" @click="open = false">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">
                            <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6L6 18"></path>
                                <path d="M6 6l12 12"></path>
                            </g>
                        </svg>
                    </template>
                    Отмена
                </Button>
                <Button @click="save">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">
                            <path d="M5 12l5 5L20 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </template>
                    Сохранить
                </Button>
            </div>
        </template>
    </Modal>
</template>

<style scoped>

</style>
