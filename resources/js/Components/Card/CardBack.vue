<script setup>
import Button from "../Button/Button.vue"
const emits = defineEmits([
    'click'
])
const props = defineProps({
    tag: {
        type: [String, Object],
        default: 'button'
    }
})
</script>

<template>
    <Button :tag="tag" v-bind:href="$attrs.href" @click="emits('click')">
        <template #icon>
            <svg xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 24 24">
                <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"></path>
                    <path d="M5 12l6 6"></path>
                    <path d="M5 12l6-6"></path>
                </g>
            </svg>
        </template>
        Вернуться назад
    </Button>
</template>

<style scoped>

</style>
