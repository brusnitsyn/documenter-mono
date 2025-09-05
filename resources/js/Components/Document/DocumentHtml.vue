<script setup>
defineProps({
    content: String
})
</script>

<template>
    <div class="html-content mb-4" v-html="content"></div>
</template>

<style scoped>
.html-content {
    text-align: justify;
}

.html-content ::v-deep(.placeholder) {
    background-color: #fffacd;
    border-bottom: 1px dashed #ccc;
    cursor: pointer;
    padding: 0 2px;
    border-radius: 2px;
}

.html-content ::v-deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.html-content ::v-deep(table, th, td) {
    border: 1px solid black;
    padding: 8px;
    text-align: left;
}
</style>
