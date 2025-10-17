import {nextTick} from "vue";

export function waitForAllComponentsMounted(vm, timeout = 5000) {
    return new Promise((resolve, reject) => {
        const startTime = Date.now()

        function checkComponents() {
            // Проверяем, смонтирован ли текущий компонент
            if (!vm.isMounted) {
                if (Date.now() - startTime > timeout) {
                    reject(new Error('Timeout waiting for component to mount'))
                    return
                }
                nextTick(checkComponents)
                return
            }

            // Рекурсивно проверяем дочерние компоненты
            const checkChildren = (component) => {
                const promises = []

                // Проверяем прямых потомков (в Vue 3 используем subTree)
                if (component.subTree && component.subTree.children) {
                    const processChildren = (children) => {
                        children.forEach(child => {
                            if (child.component) {
                                const childInstance = child.component.proxy
                                if (childInstance && !childInstance.isMounted) {
                                    promises.push(new Promise((resolveChild, rejectChild) => {
                                        const childStartTime = Date.now()

                                        function checkChild() {
                                            if (childInstance.isMounted) {
                                                resolveChild()
                                            } else if (Date.now() - childStartTime > timeout) {
                                                rejectChild(new Error('Timeout waiting for child component to mount'))
                                            } else {
                                                nextTick(checkChild)
                                            }
                                        }

                                        checkChild()
                                    }))
                                }

                                // Рекурсивно проверяем потомков
                                if (childInstance.subTree && childInstance.subTree.children) {
                                    promises.push(checkChildren(childInstance))
                                }
                            }
                        })
                    }

                    processChildren(component.subTree.children)
                }

                return Promise.all(promises)
            }

            checkChildren(vm)
                .then(resolve)
                .catch(reject)
        }

        checkComponents()
    })
}
export const generateElementHTML = (element, formData = {}) => {
    switch (element.type) {
        case 'heading':
            return `<h${element.level} data-element-id="${element.id}">${element.content}</h${element.level}>`
        case 'paragraph':
            return `<p data-element-id="${element.id}">${element.content}</p>`
        case 'variable':
            return `<span class="variable" data-element-id="${element.id}" data-variable="${element.variableName}">[${element.variableName}]</span>`
        case 'warning':
            return `<div class="warning" data-element-id="${element.id}">⚠️ ${element.content}</div>`
        case 'table':
            const tableHtml = element.content.replace('<table', `<table data-element-id="${element.id}"`)
            return tableHtml
        default:
            return element.content || ''
    }
}
