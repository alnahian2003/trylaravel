<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 pointer-events-none">
            <!-- Top Right (default) -->
            <div class="absolute top-0 right-0 p-6 space-y-4">
                <TransitionGroup
                    name="toast"
                    tag="div"
                    class="space-y-4"
                >
                    <Toast
                        v-for="toast in toasts"
                        :key="toast.id"
                        :toast="toast"
                        @close="removeToast(toast.id)"
                        @action="handleToastAction"
                    />
                </TransitionGroup>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'
import Toast from '@/Components/Toast.vue'

const { toasts, removeToast } = useToast()

const handleToastAction = (action) => {
    if (action.handler) {
        action.handler()
    }
}
</script>

<style scoped>
/* Toast transition animations */
.toast-enter-active {
    transition: all 0.3s ease-out;
}

.toast-leave-active {
    transition: all 0.2s ease-in;
}

.toast-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateX(100%);
    opacity: 0;
}

.toast-move {
    transition: transform 0.3s ease;
}
</style>