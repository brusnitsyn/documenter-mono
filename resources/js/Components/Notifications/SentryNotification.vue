<script setup>
import { ref, computed, onMounted } from 'vue'
import Card from '../Card/Card.vue'
import Button from "../Button/Button.vue";
import Collapsible from "../Collapsible/Collapsible.vue";

// Пропсы
const props = defineProps({
    customTitle: {
        type: String,
        default: ''
    },
    customDescription: {
        type: String,
        default: ''
    },
    autoShow: {
        type: Boolean,
        default: true
    }
})

// Реактивные данные
const isVisible = ref(false)
const headerText = 'Уведомление о сборе данных'

const title = computed(() => props.customTitle || 'Мониторинг ошибок')
const description = computed(() => props.customDescription || 'Мы используем Sentry для отслеживания и исправления ошибок на сайте. Это помогает нам улучшать качество сервиса.')

// Собираемые данные
const collectedData = ref([
    'Текст ошибки и стектрейс',
    'Тип браузера и версия',
    'Операционная система',
    'URL страницы где произошла ошибка',
    'Временная метка ошибки',
    'Действия пользователя перед ошибкой',
    'Размер экрана устройства',
    'Анонимизированный идентификатор сессии'
])

// События
const emit = defineEmits(['accept', 'learnMore', 'close'])

const handleAccept = () => {
    localStorage.setItem('sentry-notification-accepted', 'true')
    isVisible.value = false
    emit('accept')
}

const handleLearnMore = () => {
    emit('learnMore')
    window.open('https://sentry.io/features/error-monitoring/', '_blank')
}

// Показывать уведомление только если пользователь еще не соглашался
onMounted(() => {
    if (props.autoShow && !localStorage.getItem('sentry-notification-accepted')) {
        isVisible.value = true
    }
})

// Экспортируем методы для управления видимостью
defineExpose({
    show: () => isVisible.value = true,
    hide: () => isVisible.value = false
})
</script>

<template>
    <div v-if="isVisible" class="fixed bottom-12 right-5 z-50 w-96">
        <Card
            :header="headerText"
            :content-scroll="false"
        >
            <!-- Компактный заголовок -->
            <div class="flex items-center space-x-3 p-3">
                <div class="flex-shrink-0 w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-slate-900">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 18a1.93 1.93 0 0 0 .306 1.076a2 2 0 0 0 1.584 .924c.646 .033 -.537 0 .11 0h3a4.992 4.992 0 0 0 -3.66 -4.81c.558 -.973 1.24 -2.149 2.04 -3.531a9 9 0 0 1 5.62 8.341h4c.663 0 2.337 0 3 0a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-1.84 3.176c4.482 2.05 7.6 6.571 7.6 11.824" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                        {{ title }}
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 truncate">
                        Мы используем Sentry для анализа ошибок
                    </p>
                </div>
            </div>

            <!-- Собираемые данные в аккордеоне -->
            <div class="px-3 pb-3">
                <Collapsible header="Какие данные собираем">
                    <div class="space-y-1 max-h-32 overflow-y-auto">
                        <div
                            v-for="(item, index) in collectedData"
                            :key="index"
                            class="flex items-start space-x-2 text-xs"
                        >
                            <div class="flex-shrink-0 w-3 h-3 text-emerald-500 mt-0.5">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-slate-600 dark:text-slate-300 leading-relaxed">
                              {{ item }}
                            </span>
                        </div>
                    </div>
                </Collapsible>
            </div>

            <!-- Футер с действиями -->
            <template #footer>
                <div class="flex space-x-2">
<!--                    <Button-->
<!--                        variant="ghost"-->
<!--                        text-align="center"-->
<!--                        block-->
<!--                        @click="handleLearnMore"-->
<!--                    >-->
<!--                        Подробнее-->
<!--                    </Button>-->
                    <Button
                        text-align="center"
                        block
                        @click="handleAccept"
                    >
                        Понятно
                    </Button>
                </div>
            </template>
        </Card>
    </div>
</template>

<style scoped>
/* Дополнительные кастомные стили если нужно */
</style>
