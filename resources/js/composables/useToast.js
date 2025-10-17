import { ref, reactive } from 'vue'

// Global toast state
const toasts = ref([])
let toastId = 0

// Default options
const defaultOptions = {
    duration: 5000, // 5 seconds
    position: 'top-right',
    type: 'info'
}

// Toast types
export const TOAST_TYPES = {
    SUCCESS: 'success',
    ERROR: 'error',
    WARNING: 'warning',
    INFO: 'info'
}

export function useToast() {
    /**
     * Add a new toast notification
     */
    const addToast = (options) => {
        if (typeof options === 'string') {
            options = { message: options }
        }

        const toast = {
            id: ++toastId,
            ...defaultOptions,
            ...options,
            timestamp: Date.now()
        }

        toasts.value.push(toast)

        // Auto remove if duration is set
        if (toast.duration > 0) {
            setTimeout(() => {
                removeToast(toast.id)
            }, toast.duration)
        }

        return toast.id
    }

    /**
     * Remove a toast by ID
     */
    const removeToast = (id) => {
        const index = toasts.value.findIndex(toast => toast.id === id)
        if (index > -1) {
            toasts.value.splice(index, 1)
        }
    }

    /**
     * Clear all toasts
     */
    const clearToasts = () => {
        toasts.value = []
    }

    /**
     * Success toast
     */
    const success = (message, options = {}) => {
        return addToast({
            ...options,
            message,
            type: TOAST_TYPES.SUCCESS,
            duration: options.duration ?? 4000
        })
    }

    /**
     * Error toast
     */
    const error = (message, options = {}) => {
        return addToast({
            ...options,
            message,
            type: TOAST_TYPES.ERROR,
            duration: options.duration ?? 6000 // Errors stay longer
        })
    }

    /**
     * Warning toast
     */
    const warning = (message, options = {}) => {
        return addToast({
            ...options,
            message,
            type: TOAST_TYPES.WARNING,
            duration: options.duration ?? 5000
        })
    }

    /**
     * Info toast
     */
    const info = (message, options = {}) => {
        return addToast({
            ...options,
            message,
            type: TOAST_TYPES.INFO,
            duration: options.duration ?? 4000
        })
    }

    /**
     * Show validation errors as toasts
     */
    const showValidationErrors = (errors) => {
        if (typeof errors === 'object' && errors !== null) {
            Object.entries(errors).forEach(([field, messages]) => {
                const errorMessages = Array.isArray(messages) ? messages : [messages]
                errorMessages.forEach(message => {
                    error(message, { 
                        title: `${field.charAt(0).toUpperCase() + field.slice(1)} Error`,
                        duration: 6000 
                    })
                })
            })
        } else if (typeof errors === 'string') {
            error(errors)
        }
    }

    /**
     * Show network error with retry option
     */
    const showNetworkError = (retryCallback = null) => {
        const actions = retryCallback ? [{
            label: 'Retry',
            handler: retryCallback,
            closesOnClick: true
        }] : []

        return error('Network error occurred. Please check your connection.', {
            title: 'Connection Error',
            duration: 0, // Don't auto-dismiss
            actions
        })
    }

    /**
     * Show confirmation toast with actions
     */
    const confirm = (message, onConfirm, onCancel = null, options = {}) => {
        const actions = [
            {
                label: 'Confirm',
                handler: onConfirm,
                closesOnClick: true
            }
        ]

        if (onCancel) {
            actions.push({
                label: 'Cancel',
                handler: onCancel,
                closesOnClick: true
            })
        }

        return warning(message, {
            ...options,
            actions,
            duration: 0 // Don't auto-dismiss confirmations
        })
    }

    /**
     * Handle form submission feedback
     */
    const handleFormResponse = (response, successMessage = 'Operation completed successfully') => {
        if (response.status >= 200 && response.status < 300) {
            success(successMessage)
        } else {
            error('An error occurred. Please try again.')
        }
    }

    /**
     * Handle API errors with detailed messages
     */
    const handleApiError = (error) => {
        if (error.response) {
            const status = error.response.status
            const data = error.response.data

            switch (status) {
                case 422:
                    if (data.errors) {
                        showValidationErrors(data.errors)
                    } else {
                        error(data.message || 'Validation failed')
                    }
                    break
                case 401:
                    error('Please log in to continue', { title: 'Authentication Required' })
                    break
                case 403:
                    error('You do not have permission to perform this action', { title: 'Access Denied' })
                    break
                case 404:
                    error('The requested resource was not found', { title: 'Not Found' })
                    break
                case 429:
                    error('Too many requests. Please try again later.', { title: 'Rate Limited' })
                    break
                case 500:
                    error('Server error occurred. Please try again later.', { title: 'Server Error' })
                    break
                default:
                    error(data.message || 'An unexpected error occurred')
            }
        } else if (error.request) {
            showNetworkError()
        } else {
            error('An unexpected error occurred')
        }
    }

    /**
     * Handle Laravel flash messages from Inertia props
     */
    const handleFlashMessages = (flash) => {
        if (!flash) return

        if (flash.success) {
            success(flash.success)
        }
        if (flash.error) {
            error(flash.error)
        }
        if (flash.warning) {
            warning(flash.warning)
        }
        if (flash.info) {
            info(flash.info)
        }
    }

    return {
        toasts,
        addToast,
        removeToast,
        clearToasts,
        success,
        error,
        warning,
        info,
        showValidationErrors,
        showNetworkError,
        confirm,
        handleFormResponse,
        handleApiError,
        handleFlashMessages,
        TOAST_TYPES
    }
}

// Global instance for use outside of Vue components
export const toast = useToast()