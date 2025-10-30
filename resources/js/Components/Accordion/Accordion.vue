<script setup>
import { ref, provide, onUnmounted } from 'vue'

const props = defineProps({
    // ID элемента, который открыт по умолчанию
    opened: {
        type: [String, Number],
        default: null
    }
})

const activeItem = ref(props.opened)
const registeredItems = ref(new Set())

const accordionManager = {
    activeItem,
    open: (id) => {
        activeItem.value = id
    },
    close: () => {
        activeItem.value = null
    },
    registerItem: (id) => {
        registeredItems.value.add(id)
    },
    unregisterItem: (id) => {
        registeredItems.value.delete(id)
    }
};

provide('accordionManager', accordionManager)
</script>

<template>
    <div class="space-y-4">
        <slot />
    </div>
</template>
