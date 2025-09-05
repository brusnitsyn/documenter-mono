<script setup>
defineProps({
    content: String
})
</script>

<template>
    <h2 class="text-xl font-bold mb-4 mt-6" v-html="content"></h2>
</template>

<style scoped>

</style>
