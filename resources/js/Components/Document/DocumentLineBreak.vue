<script setup>
const props = defineProps({
    element: {
        type: Object,
        default: {}
    }
})
</script>


<template>
    <br :data-element-id="element.id" class="block content-[\'\'] m-0 p-0 h-0" />
</template>

<style scoped>

</style>
