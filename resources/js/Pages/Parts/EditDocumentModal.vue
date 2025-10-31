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
import Collapsible from "../../Components/Collapsible/Collapsible.vue";

const props = defineProps({
    templateId: {
        type: [String, Number],
        default: null
    }
})
const open = defineModel('open')
const stage = ref('upload')
const description = ref('')
const uploadedFile = ref(null)
const templateVariables = ref(null)
const formTitle = ref(null)
const isTemplateLoaded = ref(false)
const isUpdateFile = ref(false)

// Drag and drop состояния
const dragItem = ref(null)
const dragOverItem = ref(null)
const dragOverGroup = ref(null)
const dragSource = ref(null)
const lastDropPosition = ref({ targetIndex: null, group: null }) // Добавляем

watch(() => props.templateId,  async (newTemplateId) => {
    if (newTemplateId) {
        await loadTemplateData(newTemplateId)
    }
}, { immediate: true })

const loadTemplateData = async (templateId) => {
    await axios.get(`/api/templates/${templateId}`)
        .then(async res => {
            const template = res.data
            formTitle.value = template.name
            description.value = template.description || ''
            uploadForm.value.id = template.id
            uploadForm.value.name = template.name
            uploadForm.value.description = template.description || ''
            uploadForm.value.variables = template.variables || []
            isTemplateLoaded.value = true
        })
}

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
    if (isUpdateFile.value) {
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
    } else {
        templateVariables.value = uploadForm.value.variables
        if (templateVariables.value.length > 0) {
            stage.value = 'variables'
            selectedVariable.value = uploadForm.value.variables[0]
        }
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
    router.post(`/templates/update`, uploadForm.value, {
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
    for (const item of uploadForm.value.variables) {
        if (item.name === selectedVariable.value?.name) {
            return item;
        }
        else if (Array.isArray(item.children)) {
            const foundChild = item.children.find(child => child.name === selectedVariable.value?.name);
            if (foundChild) {
                return foundChild;
            }
        }
    }
    return undefined;
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

const createVariableGroup = () => {
    const groupCount = templateVariables.value.filter(itm => itm.isGroup === true)
    const group = {
        label: `Группа ${groupCount.length + 1}`,
        children: [],
        isGroup: true,
        type: 'group',
        name: `group-${Date.now()}`
    }
    templateVariables.value.push(group)
    uploadForm.value.variables = [...templateVariables.value]
}

// Drag and Drop функции
const dragStart = (event, variable, index, sourceGroup = null) => {
    dragSource.value = sourceGroup ? 'group' : 'root'
    dragItem.value = { variable, index, sourceGroup }

    // Делаем оригинальный элемент полупрозрачным
    event.currentTarget.style.opacity = '0.4'

    event.dataTransfer.effectAllowed = 'move'
}

const dragEnd = () => {
    // Восстанавливаем прозрачность всех элементов
    document.querySelectorAll('.drag-handle').forEach(el => {
        el.style.opacity = '1'
    })

    dragItem.value = null
    dragOverItem.value = null
    dragOverGroup.value = null
    dragSource.value = null
    lastDropPosition.value = { targetIndex: null, group: null }
}

const dragOver = (event, targetIndex, group = null) => {
    event.preventDefault()
    event.stopPropagation()

    console.log('dragOver:', { targetIndex, group, dragItem: dragItem.value })

    // Сохраняем последнюю позицию
    lastDropPosition.value = { targetIndex, group }

    // Не показывать индикатор если перетаскиваемый элемент тот же самый
    if (dragItem.value) {
        const isSameElement = group ?
            (dragItem.value.sourceGroup === group && dragItem.value.index === targetIndex) :
            (dragItem.value.index === targetIndex)

        if (isSameElement) {
            dragOverItem.value = null
            dragOverGroup.value = null
            return
        }
    }

    dragOverItem.value = targetIndex
    dragOverGroup.value = group
}

const handleGlobalDrop = (event) => {
    event.preventDefault()
    event.stopPropagation()

    if (!dragItem.value) return

    // Используем последнюю сохраненную позицию для глобального дропа
    const { targetIndex, group } = lastDropPosition.value

    if (targetIndex !== null) {
        drop(event, targetIndex, group)
    } else {
        // Если позиция не определена, сбрасываем
        dragEnd()
    }
}

const drop = (event, targetIndex, group = null) => {
    event.preventDefault()
    event.stopPropagation()

    if (!dragItem.value) return

    // Используем последнюю сохраненную позицию, если текущие параметры null
    const finalTargetIndex = targetIndex ?? lastDropPosition.value.targetIndex
    const finalGroup = group ?? lastDropPosition.value.group

    console.log('drop:', {
        targetIndex,
        group,
        finalTargetIndex,
        finalGroup,
        lastDropPosition: lastDropPosition.value,
        dragItem: dragItem.value
    })

    const sourceVariable = dragItem.value.variable
    const sourceGroup = dragItem.value.sourceGroup

    // Обработка для targetIndex = -2 (начало группы)
    const actualTargetIndex = targetIndex === -2 ? 0 : targetIndex

    // Если перетаскиваем ГРУППУ (изменение порядка групп)
    if (sourceVariable.isGroup && !group) {
        const sourceIndex = dragItem.value.index
        if (sourceIndex !== actualTargetIndex) {
            const [removed] = templateVariables.value.splice(sourceIndex, 1)

            // Корректируем targetIndex если удалили элемент перед целевой позицией
            const adjustedTargetIndex = sourceIndex < actualTargetIndex
                ? actualTargetIndex - 1
                : actualTargetIndex

            templateVariables.value.splice(adjustedTargetIndex, 0, removed)
        }
    }
    // Если перетаскиваем из группы в корень
    else if (sourceGroup && !group) {
        // Удаляем из группы
        const sourceIndex = sourceGroup.children.findIndex(v => v.name === sourceVariable.name)
        if (sourceIndex > -1) {
            sourceGroup.children.splice(sourceIndex, 1)
            // Добавляем в корень
            if (actualTargetIndex === -1) {
                templateVariables.value.push(sourceVariable)
            } else {
                templateVariables.value.splice(actualTargetIndex, 0, sourceVariable)
            }
        }
    }
    // Если перетаскиваем из корня в группу
    else if (!sourceGroup && group && !sourceVariable.isGroup) {
        // Удаляем из корня
        const sourceIndex = templateVariables.value.findIndex(v => v.name === sourceVariable.name)
        if (sourceIndex > -1) {
            templateVariables.value.splice(sourceIndex, 1)
            // Добавляем в группу
            if (!group.children) group.children = []
            if (actualTargetIndex === -1) {
                group.children.push(sourceVariable)
            } else {
                group.children.splice(actualTargetIndex, 0, sourceVariable)
            }
        }
    }
    // Если перетаскиваем из группы в другую группу
    else if (sourceGroup && group && sourceGroup !== group && !sourceVariable.isGroup) {
        // Удаляем из исходной группы
        const sourceIndex = sourceGroup.children.findIndex(v => v.name === sourceVariable.name)
        if (sourceIndex > -1) {
            sourceGroup.children.splice(sourceIndex, 1)
            // Добавляем в целевую группу
            if (!group.children) group.children = []
            if (actualTargetIndex === -1) {
                group.children.push(sourceVariable)
            } else {
                group.children.splice(actualTargetIndex, 0, sourceVariable)
            }
        }
    }
    // Если перетаскиваем между элементами в корне
    else if (!sourceGroup && !group && !sourceVariable.isGroup) {
        const sourceIndex = dragItem.value.index
        const [removed] = templateVariables.value.splice(sourceIndex, 1)
        templateVariables.value.splice(actualTargetIndex, 0, removed)
    }
    // Если перетаскиваем внутри одной группы
    else if (sourceGroup && group && sourceGroup === group && !sourceVariable.isGroup) {
        const sourceIndex = dragItem.value.index
        const [removed] = group.children.splice(sourceIndex, 1)
        group.children.splice(actualTargetIndex, 0, removed)
    }

    // Сбрасываем состояния
    dragItem.value = null
    dragOverItem.value = null
    dragOverGroup.value = null
    dragSource.value = null
}

// Удаление группы
const removeGroup = (group) => {
    const index = templateVariables.value.findIndex(v => v === group)
    if (index > -1) {
        // Перемещаем детей группы в корень
        if (group.children && group.children.length) {
            templateVariables.value.splice(index, 1, ...group.children)
        } else {
            templateVariables.value.splice(index, 1)
        }
    }
}

// Редактирование группы
const editGroup = (group) => {
    selectedVariable.value = group
}

// Получение индекса переменной в корневом списке
const getRootIndex = (variable) => {
    return templateVariables.value.findIndex(v => v.name === variable.name)
}
</script>

<template>
    <Modal v-model:open="open" :title="formTitle" :description="description" @after-close="afterCloseModal" :width="widthOfStage">
        <div v-if="!isTemplateLoaded" class="h-[376px] relative">
            <div class="absolute inset-1/2">
                <div class="flex items-center gap-x-2">
                    <div class="flex space-x-1">
                        <div class="w-1.5 h-1.5 bg-current rounded-full animate-bounce" style="animation-delay: 0s"></div>
                        <div class="w-1.5 h-1.5 bg-current rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-1.5 h-1.5 bg-current rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else>
            <div v-if="stage === 'upload'" class="flex flex-col gap-y-1">
                <Input v-model:value="uploadForm.name" label="Наименование" />
                <Input v-model:value="uploadForm.description" label="Описание" />
                <div class="mt-2.5">
                    <Button block text-align="center" v-if="!isUpdateFile" @click="isUpdateFile = true">
                        Загрузить обновленный шаблон
                    </Button>
                    <FileUpload v-else v-model:file="uploadedFile" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
                </div>
            </div>
            <div v-else class="grid grid-cols-[280px_1fr] gap-x-2 ">
                <Card header="Переменные документа">
                    <Button class="mb-2 -mt-2" block @click="createVariableGroup" icon-left>
                        <template #icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M4 4h6v6h-6zm10 0h6v6h-6zm-10 10h6v6h-6zm10 3h6m-3 -3v6" />
                            </svg>
                        </template>
                        Добавить группу
                    </Button>
                    <div class="flex flex-col gap-y-0.5 max-h-[396px] overflow-y-auto">
                        <template v-for="(variable, index) in templateVariables" :key="variable.name || variable.label">

                            <!-- Визуальный дубликат перетаскиваемого элемента -->
                            <div v-if="dragItem && dragOverItem === index && !dragOverGroup && dragItem.variable !== variable"
                                 @dragover="dragOver($event, index)"
                                 @drop="drop($event, index)"
                                 class="opacity-60 transform">
                                <Button
                                    icon-left
                                    block
                                    class="cursor-grabbing bg-blue-50 border-blue-200"
                                    disabled>
                                    <template #icon>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        </svg>
                                    </template>
                                    {{ dragItem.variable.label }}
                                </Button>
                            </div>

                            <div v-if="variable.isGroup"
                                 class="pl-px pt-px pb-px pr-px"
                                 :class="{ 'bg-blue-50/10 rounded': dragOverGroup === variable }"
                                 @dragover="dragOver($event, index)"
                                 @drop="drop($event, index)">
                                <Collapsible class="drag-handle"
                                             draggable="true"
                                             @dragstart="dragStart($event, variable, index)"
                                             @dragend="dragEnd">
                                    <template #icon>
                                        <div class="cursor-grab active:cursor-grabbing">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            </svg>
                                        </div>
                                    </template>
                                    <template #header>
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center flex-1 drag-handle">
                                                <span class="block text-sm font-medium truncate">{{variable.label}}</span>
                                            </div>
                                        </div>
                                    </template>
                                    <template #header-extra>
                                        <button @click="editGroup(variable)" class="text-white hover:text-zinc-300 flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                <path d="M13.5 6.5l4 4" />
                                            </svg>
                                        </button>
                                        <button @click="removeGroup(variable)" class="text-red-500 hover:text-red-700 flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18"></path>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </template>
                                    <div class="min-h-2 flex flex-col gap-y-0.5"
                                         @dragover="dragOver($event, -2, variable)"
                                         @drop="drop($event, -2, variable)">

                                        <template v-if="variable.children && variable.children.length > 0">
                                            <div v-for="(child, childIndex) in variable.children"
                                                 :key="child.name"
                                                 class="relative"
                                                 @dragover="dragOver($event, childIndex, variable)"
                                                 @drop="drop($event, childIndex, variable)"
                                            >

                                                <!-- Визуальный дубликат между элементами в группе -->
                                                <div v-if="dragItem && dragOverItem === childIndex && dragOverGroup === variable"
                                                     class="opacity-60 transform">
                                                    <Button
                                                        icon-left
                                                        block
                                                        class="cursor-grabbing bg-blue-50 border-blue-200"
                                                        disabled>
                                                        <template #icon>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                                <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                                <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                                <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                                <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                                <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                            </svg>
                                                        </template>
                                                        {{ dragItem.variable.label }}
                                                    </Button>
                                                </div>

                                                <Button
                                                    icon-left
                                                    block
                                                    @click="clickToVariable(child)"
                                                    draggable="true"
                                                    @dragstart="dragStart($event, child, childIndex, variable)"
                                                    @dragend="dragEnd"
                                                    class="drag-handle cursor-grab active:cursor-grabbing">
                                                    <template #icon>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                            <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                            <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                            <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                            <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                            <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                        </svg>
                                                    </template>
                                                    <span class="truncate">{{ child.label }}</span>
                                                </Button>
                                            </div>

                                        </template>
                                        <div v-else
                                             class="text-center text-gray-400 py-2 text-sm border-2 border-dashed border-gray-300 rounded mx-2"
                                             @dragover="dragOver($event, -2, variable)"
                                             @drop="drop($event, -2, variable)">
                                            Перетащите переменные сюда
                                        </div>
                                    </div>
                                </Collapsible>
                            </div>
                            <div v-else
                                 class="relative"
                                 :class="{ 'bg-blue-50/10': dragOverItem === index && !dragOverGroup }"
                                 @dragover="dragOver($event, index)"
                                 @drop="drop($event, index)">
                                <Button
                                    icon-left
                                    block
                                    @click="clickToVariable(variable)"
                                    draggable="true"
                                    @dragstart="dragStart($event, variable, index)"
                                    @dragend="dragEnd"
                                    class="drag-handle cursor-grab active:cursor-grabbing">
                                    <template #icon>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                            <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        </svg>
                                    </template>
                                    {{ variable.label }}
                                </Button>
                            </div>
                        </template>

                        <!-- Визуальный дубликат в конце корневого списка -->
                        <div v-if="dragItem && dragOverItem === -1 && !dragOverGroup"
                             class="opacity-60 transform scale-105">
                            <Button
                                icon-left
                                block
                                class="cursor-grabbing bg-blue-50 border-blue-200"
                                disabled>
                                <template #icon>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                        <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                    </svg>
                                </template>
                                {{ dragItem.variable.label }}
                            </Button>
                        </div>

                    </div>
                </Card>
                <Card :header="selectedVariable?.label || 'Выберите переменную'">
                    <div class="pr-2" v-if="selectedVariable">
                        <div class="flex flex-col gap-y-1">
                            <FormGroup label="Наименование переменной" position="top">
                                <Input v-model:value="activeVariable.name" disabled />
                            </FormGroup>

                            <FormGroup label="Отображаемое наименование" position="top">
                                <Input v-model:value="activeVariable.label" />
                            </FormGroup>

                            <FormGroup v-if="activeVariable.type !== 'group'" label="Тип ввода" position="top">
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
                    <div v-else class="text-center text-gray-400 py-8">
                        Выберите переменную для редактирования
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
