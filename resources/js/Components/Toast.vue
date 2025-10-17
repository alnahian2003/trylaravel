<template>
    <div 
        v-if="toast" 
        class="fixed inset-0 z-50 flex items-start justify-center px-4 py-6 pointer-events-none sm:items-start sm:justify-end sm:p-6"
    >
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="visible"
                class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg pointer-events-auto ring-1 ring-black/5 dark:ring-white/10"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="shrink-0">
                            <!-- Success Icon -->
                            <svg 
                                v-if="toast.type === 'success'" 
                                class="w-6 h-6 text-green-400" 
                                aria-hidden="true"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <!-- Error Icon -->
                            <svg 
                                v-else-if="toast.type === 'error'" 
                                class="w-6 h-6 text-red-400" 
                                aria-hidden="true"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <!-- Warning Icon -->
                            <svg 
                                v-else-if="toast.type === 'warning'" 
                                class="w-6 h-6 text-yellow-400" 
                                aria-hidden="true"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <!-- Info Icon -->
                            <svg 
                                v-else-if="toast.type === 'info'" 
                                class="w-6 h-6 text-blue-400" 
                                aria-hidden="true"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p 
                                v-if="toast.title" 
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {{ toast.title }}
                            </p>
                            <p 
                                class="text-sm text-gray-500 dark:text-gray-300"
                                :class="{ 'mt-1': toast.title }"
                            >
                                {{ toast.message }}
                            </p>
                            <div v-if="toast.actions && toast.actions.length > 0" class="mt-4">
                                <div class="flex space-x-2">
                                    <button
                                        v-for="action in toast.actions"
                                        :key="action.label"
                                        type="button"
                                        class="bg-white dark:bg-gray-700 rounded-md text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                        @click="handleAction(action)"
                                    >
                                        {{ action.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 shrink-0 flex">
                            <button 
                                type="button"
                                class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                @click="close"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Progress bar for auto-dismiss -->
                <div 
                    v-if="toast.duration && toast.duration > 0"
                    class="h-1 bg-gray-200 dark:bg-gray-600 rounded-b-lg overflow-hidden"
                >
                    <div 
                        class="h-full transition-all ease-linear"
                        :class="{
                            'bg-green-500': toast.type === 'success',
                            'bg-red-500': toast.type === 'error',
                            'bg-yellow-500': toast.type === 'warning',
                            'bg-blue-500': toast.type === 'info',
                        }"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
    toast: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'action'])

const visible = ref(false)
const progress = ref(100)
let timer = null
let progressTimer = null

const close = () => {
    visible.value = false
    setTimeout(() => {
        emit('close')
    }, 100)
}

const handleAction = (action) => {
    if (action.handler) {
        action.handler()
    }
    emit('action', action)
    if (action.closesOnClick !== false) {
        close()
    }
}

const startTimer = () => {
    if (!props.toast?.duration || props.toast.duration <= 0) return

    // Start progress animation
    progress.value = 100
    progressTimer = setInterval(() => {
        progress.value -= (100 / (props.toast.duration / 100))
        if (progress.value <= 0) {
            progress.value = 0
            clearInterval(progressTimer)
        }
    }, 100)

    // Auto dismiss after duration
    timer = setTimeout(() => {
        close()
    }, props.toast.duration)
}

const pauseTimer = () => {
    if (timer) {
        clearTimeout(timer)
        timer = null
    }
    if (progressTimer) {
        clearInterval(progressTimer)
        progressTimer = null
    }
}

const resumeTimer = () => {
    if (props.toast?.duration && progress.value > 0) {
        const remainingTime = (progress.value / 100) * props.toast.duration
        
        progressTimer = setInterval(() => {
            progress.value -= (100 / (props.toast.duration / 100))
            if (progress.value <= 0) {
                progress.value = 0
                clearInterval(progressTimer)
            }
        }, 100)

        timer = setTimeout(() => {
            close()
        }, remainingTime)
    }
}

watch(() => props.toast, (newToast) => {
    if (newToast) {
        visible.value = true
        startTimer()
    } else {
        visible.value = false
    }
}, { immediate: true })

onMounted(() => {
    if (props.toast) {
        visible.value = true
        startTimer()
    }
})

onUnmounted(() => {
    if (timer) clearTimeout(timer)
    if (progressTimer) clearInterval(progressTimer)
})
</script>