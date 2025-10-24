<script setup>
import Input from "../../Input/Input.vue";
import {computed, watch} from "vue";

const numberModel = defineModel('number')
const textModel = defineModel('text')

// Функция для преобразования числа в текст
const numberToWords = (num) => {
    if (num === 0) return 'ноль рублей'
    if (num < 0) return 'минус ' + numberToWords(Math.abs(num))

    const units = ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять']
    const teens = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать']
    const tens = ['', '', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто']
    const hundreds = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот']

    const thousands = ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять']
    const millions = ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять']
    const billions = ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять']

    // Функция для преобразования трехзначного числа
    const convertThreeDigit = (n, isFemale = false) => {
        if (n === 0) return ''

        let result = ''
        const hundred = Math.floor(n / 100)
        const remainder = n % 100

        if (hundred > 0) {
            result += hundreds[hundred] + ' '
        }

        if (remainder >= 20) {
            const ten = Math.floor(remainder / 10)
            const unit = remainder % 10
            result += tens[ten] + ' '
            if (unit > 0) {
                if (isFemale && unit <= 2) {
                    result += (unit === 1 ? 'одна' : 'две') + ' '
                } else {
                    result += units[unit] + ' '
                }
            }
        } else if (remainder >= 10) {
            result += teens[remainder - 10] + ' '
        } else if (remainder > 0) {
            if (isFemale && remainder <= 2) {
                result += (remainder === 1 ? 'одна' : 'две') + ' '
            } else {
                result += units[remainder] + ' '
            }
        }

        return result.trim()
    }

    // Функция для получения правильного окончания
    const getEnding = (num, forms) => {
        const lastDigit = num % 10
        const lastTwoDigits = num % 100

        if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
            return forms[2]
        }
        if (lastDigit === 1) {
            return forms[0]
        }
        if (lastDigit >= 2 && lastDigit <= 4) {
            return forms[1]
        }
        return forms[2]
    }

    let result = ''
    let remaining = num

    // Миллиарды
    const billionsPart = Math.floor(remaining / 1000000000)
    if (billionsPart > 0) {
        result += convertThreeDigit(billionsPart) + ' '
        result += getEnding(billionsPart, ['миллиард', 'миллиарда', 'миллиардов']) + ' '
        remaining %= 1000000000
    }

    // Миллионы
    const millionsPart = Math.floor(remaining / 1000000)
    if (millionsPart > 0) {
        result += convertThreeDigit(millionsPart) + ' '
        result += getEnding(millionsPart, ['миллион', 'миллиона', 'миллионов']) + ' '
        remaining %= 1000000
    }

    // Тысячи
    const thousandsPart = Math.floor(remaining / 1000)
    if (thousandsPart > 0) {
        result += convertThreeDigit(thousandsPart, true) + ' '
        result += getEnding(thousandsPart, ['тысяча', 'тысячи', 'тысяч']) + ' '
        remaining %= 1000
    }

    // Сотни, десятки, единицы
    if (remaining > 0) {
        result += convertThreeDigit(remaining) + ' '
    }

    // Добавляем рубли с правильным окончанием
    result += getEnding(num, ['рубль', 'рубля', 'рублей'])

    return result.trim().replace(/\s+/g, ' ')
}

// Вычисляемое свойство для текстового представления
const amountInWords = computed(() => {
    const num = Number(numberModel.value) || 0
    return numberToWords(num)
})

// Следим за изменениями и обновляем модель
watch(amountInWords, (newValue) => {
    textModel.value = `${numberModel.value} руб. (${newValue})`
}, { immediate: true })

// Обработчик изменения ввода
const handleInput = (value) => {
    numberModel.value = value
}
</script>

<template>
    <Input v-model:value="numberModel" @update:value="handleInput" placeholder="Введите сумму" type="number" />
</template>

<style scoped>

</style>
