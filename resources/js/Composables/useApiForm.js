// composables/useSmartApiForm.js
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

export function useApiForm(initialData = {}) {
    const loading = ref(false)
    const errors = ref({})
    const progress = ref(0)

    // Для обычных полей формы
    const formData = ref({ ...initialData })
    // Для файлов
    const files = ref({})

    const setFile = (fieldName, file) => {
        files.value[fieldName] = file
        // Автоматически очищаем ошибку для этого поля
        clearError(fieldName)
    }

    const clearError = (field) => {
        if (errors.value[field]) {
            const newErrors = { ...errors.value }
            delete newErrors[field]
            errors.value = newErrors
        }
    }

    const clearAllErrors = () => {
        errors.value = {}
    }

    const submit = async (url, method = 'post', config = {}) => {
        loading.value = true
        progress.value = 0
        clearAllErrors()

        try {
            // Создаем FormData
            const formDataToSend = new FormData()

            // Добавляем обычные поля формы
            Object.keys(formData.value).forEach(key => {
                if (formData.value[key] !== null && formData.value[key] !== undefined) {
                    formDataToSend.append(key, formData.value[key])
                }
            })

            // Добавляем файлы
            Object.keys(files.value).forEach(key => {
                if (files.value[key]) {
                    formDataToSend.append(key, files.value[key])
                }
            })

            const response = await axios({
                method,
                url,
                data: formDataToSend,
                headers: {
                    'Content-Type': 'multipart/form-data',
                    ...config.headers
                },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        progress.value = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        )
                    }
                },
                ...config
            })

            return response.data
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {}
            }
            throw error
        } finally {
            loading.value = false
        }
    }

    const reset = () => {
        // Сбрасываем обычные поля
        Object.keys(initialData).forEach(key => {
            formData.value[key] = initialData[key]
        })
        // Сбрасываем файлы
        files.value = {}
        clearAllErrors()
    }

    return {
        formData,
        files,
        errors,
        loading,
        progress,
        submit,
        setFile,
        clearError,
        clearAllErrors,
        reset
    }
}
