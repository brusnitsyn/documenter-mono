
<script setup>
import {ref, computed, inject, onUnmounted} from "vue";

const props = defineProps({
    header: String,
    defaultOpen: {
        type: Boolean,
        default: false
    },
    contentScroll: {
        type: Boolean,
        default: true
    },
    contentRelative: {
        type: Boolean,
        default: true
    },
    mergeContentClass: {
        type: String,
        default: ''
    },
    id: {
        type: [String, Number],
        default: null
    }
});

// Инжектим управление аккордеоном из родителя
const accordionManager = inject('accordionManager', null);

const localOpen = ref(props.defaultOpen)
const isOpen = computed(() => {
    if (accordionManager) {
        return accordionManager.activeItem.value === props.id;
    }
    return localOpen.value;
});

const toggle = () => {
    if (accordionManager) {
        if (isOpen.value) {
            accordionManager.close();
        } else {
            accordionManager.open(props.id);
        }
    } else {
        localOpen.value = !localOpen.value
    }
};

// Регистрируем элемент в аккордеоне при монтировании
if (accordionManager && props.id) {
    accordionManager.registerItem(props.id);

    onUnmounted(() => {
        accordionManager.unregisterItem(props.id);
    });
}

const contentClass = computed(() => {
    const classes = ['h-full transition-all duration-300 ease-in-out'];

    if (isOpen.value) {
        classes.push('opacity-100 max-h-[1000px]'); // max-h достаточно большой для контента
    } else {
        classes.push('opacity-0 max-h-0');
    }

    props.contentRelative ? classes.push('relative') : null;

    if (props.contentScroll && isOpen.value) {
        classes.push('overflow-y-auto');
    } else {
        classes.push('overflow-y-clip');
    }

    if (props.mergeContentClass) {
        const mergeClasses = props.mergeContentClass.split(' ');
        classes.push(...mergeClasses);
    }

    return classes;
});

const containerClass = computed(() => {
    return [
        'h-full flex flex-col justify-between overflow-clip lg:rounded-lg lg:bg-white lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10',
        isOpen.value ? 'min-h-[100px]' : ''
    ];
});
</script>

<template>
    <div :class="containerClass" :data-br-id="props.id">
        <div class="p-1.5 px-2 pr-2">
            <div class="flex items-center justify-between">
                <div class="inline-flex gap-x-2 items-center">
                    <slot v-if="$slots.icon" name="icon" />
                    <div class="flex-1 cursor-pointer" @click="toggle">
                        <slot v-if="$slots.header" name="header" />
                        <span v-else class="block text-sm font-medium">
                            {{ header }}
                        </span>
                    </div>
                </div>
                <div class="inline-flex gap-x-2">
                    <slot v-if="$slots['header-extra']" name="header-extra" />
                    <div class="flex items-center justify-center w-6 h-6 transition-transform duration-300 cursor-pointer"
                         @click="toggle"
                         :class="{ 'rotate-180': isOpen }">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div :class="contentClass">
            <div v-if="isOpen" class="p-3 pt-0">
                <slot />
            </div>
        </div>

        <div v-if="$slots.footer && isOpen" class="p-3">
            <slot name="footer" />
        </div>
    </div>
</template>
