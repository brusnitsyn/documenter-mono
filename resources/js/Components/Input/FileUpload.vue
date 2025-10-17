<script setup>
import Input from "./Input.vue"
import {ref} from "vue";

const props = defineProps({
    accept: {
        type: String,
        default: ''
    }
})

const fileRef = ref(null)
const file = defineModel('file')
const fileList = defineModel('fileList')

const onFileChanged = (e) => {
    const target = e.target
    if (target && target.files) {
        fileList.value = target.files
        file.value = target.files[0]
    }
}
</script>

<template>
    <Input ref="fileRef"
           type="file"
           @change="(e) => onFileChanged(e)"
           :accept="accept"
           />
</template>

<style scoped>

</style>
