import {
    ref,
    reactive,
    onMounted,
    onUpdated,
    nextTick
} from "vue"
import {useElementSize} from "@vueuse/core";

export function useDynamicA4Layout() {
    const A4_HEIGHT = 933
    const A4_WIDTH = 623
    const a4Pages = reactive([])
    const isCalculating = ref(false)
    const componentHeights = ref(new Map())

    const waitForComponentRender = (componentRef, itemType) => {
        return new Promise((resolve) => {
            if (!componentRef) {
                resolve(0)
                return
            }

            const checkRender = () => {
                requestAnimationFrame(() => {
                    if (componentRef.offsetHeight > 0) {
                        resolve(getComponentHeight(componentRef))
                    } else {
                        resolve(0)
                        // setTimeout(checkRender, 1000)
                    }
                })
            }

            checkRender()
        })
    }

    const getComponentHeight = (element) => {
        if (!element) return 0


        const styles = getComputedStyle(element)
        return element.offsetHeight
            + parseInt(styles.marginTop)
            + parseInt(styles.marginBottom)
            + parseInt(styles.borderTopWidth)
            + parseInt(styles.borderBottomWidth)
    }

    const calculateDynamicLayout = async(items, getComponentRefs) => {
        if (isCalculating.value) return
        isCalculating.value = true

        try {
            await nextTick()

            const componentRefs = getComponentRefs()

            a4Pages.splice(0, a4Pages.length)
            const currentPage = {items: [], totalHeight: 0, id: Date.now()}
            a4Pages.push(currentPage)

            const heightPromises = items.map(async (item, index) => {
                const componentRef = componentRefs.get(item.id)
                const height = await waitForComponentRender(componentRef, item.type)
                componentHeights.value.set(item.id || index, height)
                return height
            })

            const heights = await Promise.all(heightPromises)

            let currentHeight = 0
            const pageMargin = 0

            items.forEach((item, index) => {
                // console.log(item)
                const itemHeight = heights[index]
                const availableHeight = A4_HEIGHT - (pageMargin * 2) - currentHeight

                console.log(currentHeight > 0 && availableHeight < itemHeight)

                if (currentHeight > 0 && availableHeight < itemHeight) {
                    const newPage = {
                        items: [item],
                        totalHeight: itemHeight,
                        id: Date.now() + index
                    }
                    a4Pages.push(newPage)
                    currentHeight = itemHeight
                } else {
                    const page = a4Pages[a4Pages.length - 1]
                    page.items.push(item)
                    page.totalHeight += itemHeight
                    currentHeight += itemHeight
                }
            })
        } catch(error) {
            console.error('Ошибка вычисления размера')
        } finally {
            isCalculating.value = false
        }
    }

    return {
        a4Pages,
        A4_HEIGHT,
        A4_WIDTH,
        calculateDynamicLayout,
        isCalculating,
        componentHeights
    }
}
