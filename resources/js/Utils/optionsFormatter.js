// utils/optionsFormatter.js

/**
 * Преобразует массив [key: value] в массив объектов {value: key, label: value}
 */
export const formatOptions = (optionsArray) => {
    if (!optionsArray || !Array.isArray(optionsArray)) return []

    return optionsArray.map((item, index) => {
        // Если это объект с ключами key и value
        if (item && typeof item === 'object' && item.key !== undefined && item.value !== undefined) {
            return {
                value: item.value,
                label: item.key
            }
        }

        // Если это массив [key, value]
        if (Array.isArray(item) && item.length >= 2) {
            return {
                value: item[0],
                label: item[1]
            }
        }

        // Если это простой массив значений
        if (typeof item === 'string' || typeof item === 'number') {
            return {
                value: item,
                label: String(item)
            }
        }

        // Если это объект с произвольными ключами
        if (item && typeof item === 'object') {
            const keys = Object.keys(item)
            if (keys.length > 0) {
                return {
                    value: keys[0],
                    label: item[keys[0]]
                }
            }
        }

        // Fallback для непредвиденных форматов
        return {
            value: index,
            label: String(item)
        }
    })
}

/**
 * Преобразует объект {key: value, key: value} в массив {value: key, label: value}
 */
export const formatObjectOptions = (optionsObject) => {
    if (!optionsObject || typeof optionsObject !== 'object') return []

    return Object.entries(optionsObject).map(([key, value]) => ({
        value: key,
        label: value
    }))
}

/**
 * Универсальная функция, которая определяет тип данных и форматирует соответствующим образом
 */
export const normalizeOptions = (input) => {
    if (Array.isArray(input)) {
        return formatOptions(input)
    }

    if (typeof input === 'object' && input !== null) {
        return formatObjectOptions(input)
    }

    return []
}
