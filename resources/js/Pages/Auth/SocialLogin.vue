<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const redirectToProvider = (provider) => {
    window.location.href = route('auth.redirect', provider);
};
</script>

<template>
    <Head title="Sign In - Laravel Pulse" />
    
    <div class="antialiased bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
        <div class="min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
            <!-- Logo -->
            <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-8">
                <div class="flex items-center justify-center space-x-3 mb-6">
                    <div class="w-16 h-16 bg-red-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="text-3xl font-bold text-gray-900">Laravel Pulse</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome back</h2>
                <p class="text-gray-600">Sign in to access your personalized Laravel digest</p>
            </div>

            <!-- Status Message -->
            <div v-if="status" class="sm:mx-auto sm:w-full sm:max-w-md mb-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600">{{ status }}</div>
                </div>
            </div>

            <!-- Login Card -->
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-10 px-8 shadow-xl rounded-2xl border border-gray-200">
                    <!-- Social Login Buttons -->
                    <div class="space-y-4">
                        <!-- GitHub Login -->
                        <button 
                            @click="redirectToProvider('github')"
                            class="w-full flex items-center justify-center space-x-3 px-6 py-4 border-2 border-gray-900 rounded-xl font-semibold text-gray-900 hover:bg-gray-900 hover:text-white transition-all duration-200 group"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            <span>Continue with GitHub</span>
                        </button>

                        <!-- Google Login -->
                        <button 
                            @click="redirectToProvider('google')"
                            class="w-full flex items-center justify-center space-x-3 px-6 py-4 border-2 border-gray-300 rounded-xl font-semibold text-gray-700 hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 group"
                        >
                            <svg class="w-6 h-6" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span>Continue with Google</span>
                        </button>
                    </div>

                    <!-- Why These Methods -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 1l3.09 6.26L22 9l-5 4.87L18.18 21 12 17.77 5.82 21 7 13.87 2 9l6.91-1.74L12 1z"/>
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-blue-900 text-sm mb-1">Secure & Simple</h4>
                                    <p class="text-xs text-blue-800">We use OAuth for secure authentication. No passwords to remember, no data to manage.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Privacy -->
                <p class="mt-6 text-center text-xs text-gray-500">
                    By continuing, you agree to our 
                    <a href="/terms" class="text-red-600 hover:text-red-700 font-medium">Terms of Service</a> and 
                    <a href="/privacy" class="text-red-600 hover:text-red-700 font-medium">Privacy Policy</a>
                </p>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <Link 
                        href="/" 
                        class="text-gray-600 hover:text-gray-900 text-sm font-medium transition-colors inline-flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Home
                    </Link>
                </div>
            </div>

            <!-- Developer Friendly Note -->
            <div class="mt-12 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-gray-900 rounded-xl p-6 text-center">
                    <div class="flex items-center justify-center space-x-2 mb-3">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0L19.2 12l-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
                        </svg>
                        <h3 class="text-white font-bold">Built for Developers</h3>
                    </div>
                    <p class="text-gray-400 text-sm mb-4">
                        GitHub login recommended - we'll use your profile to personalize content based on your Laravel projects.
                    </p>
                    <div class="flex items-center justify-center space-x-4 text-xs text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            No spam
                        </span>
                        <span class="flex items-center">
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Cancel anytime
                        </span>
                        <span class="flex items-center">
                            <svg class="w-3 h-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Free trial
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>