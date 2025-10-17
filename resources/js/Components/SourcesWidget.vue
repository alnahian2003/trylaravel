<template>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-lg">My Sources</h3>
            <Link :href="route('sources.index')" class="text-red-600 hover:text-red-700 text-sm font-medium">
                Manage
            </Link>
        </div>
        
        <div v-if="sources && sources.length > 0" class="space-y-3">
            <div 
                v-for="source in sources.slice(0, 10)" 
                :key="source.id"
                class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 transition-colors group"
            >
                <!-- Favicon -->
                <div class="flex-shrink-0">
                    <img 
                        :src="source.favicon_url || getDefaultFavicon(source.url)" 
                        :alt="source.name"
                        class="w-5 h-5 rounded"
                        @error="handleImageError"
                    >
                </div>
                
                <!-- Source Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ source.name }}</p>
                        <span class="text-xs text-gray-500 ml-2">{{ source.posts_count || 0 }}</span>
                    </div>
                    <p class="text-xs text-gray-500 truncate">{{ getDomainFromUrl(source.url) }}</p>
                </div>
                
                <!-- Status indicator -->
                <div class="flex-shrink-0">
                    <div 
                        :class="source.is_active ? 'bg-green-400' : 'bg-red-400'"
                        class="w-2 h-2 rounded-full"
                        :title="source.is_active ? 'Active' : 'Inactive'"
                    ></div>
                </div>
            </div>
        </div>
        
        <!-- Empty state -->
        <div v-else-if="sources && sources.length === 0" class="text-center py-6">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M3.429 14.286a1.71 1.71 0 011.715 1.714A1.71 1.71 0 013.43 17.714a1.71 1.71 0 01-1.715-1.714c0-.95.765-1.714 1.714-1.714zM3.429 9.143c1.903 0 3.652.753 4.967 2.125a1.714 1.714 0 01-2.446 2.398 3.43 3.43 0 00-2.521-1.095c-.947 0-1.715.767-1.715 1.715a1.714 1.714 0 01-3.428 0 5.14 5.14 0 015.143-5.143zM3.429 4c3.046 0 5.857 1.214 7.875 3.429a1.714 1.714 0 01-2.446 2.398A6.858 6.858 0 003.43 7.429c-1.894 0-3.429 1.535-3.429 3.428a1.714 1.714 0 01-3.428 0C-3.428 6.29.858 4 3.429 4z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-600 mb-3">No sources yet</p>
            <button 
                @click="$emit('add-source')"
                class="text-red-600 hover:text-red-700 text-sm font-medium"
            >
                Add your first source
            </button>
        </div>
        
        <!-- Loading state -->
        <div v-else class="space-y-3">
            <div v-for="i in 5" :key="i" class="flex items-center space-x-3 p-2">
                <div class="w-5 h-5 bg-gray-200 rounded animate-pulse"></div>
                <div class="flex-1">
                    <div class="h-4 bg-gray-200 rounded w-24 mb-1 animate-pulse"></div>
                    <div class="h-3 bg-gray-200 rounded w-16 animate-pulse"></div>
                </div>
                <div class="w-2 h-2 bg-gray-200 rounded-full animate-pulse"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    sources: {
        type: Array,
        default: null
    }
});

defineEmits(['add-source']);

// Helper functions
const getDomainFromUrl = (url) => {
    try {
        const domain = new URL(url).hostname.replace('www.', '');
        return domain;
    } catch {
        return 'Unknown';
    }
};

const getDefaultFavicon = (url) => {
    try {
        const domain = new URL(url).hostname;
        return `https://www.google.com/s2/favicons?domain=${domain}&sz=32`;
    } catch {
        return 'https://via.placeholder.com/20/e5e7eb/9ca3af?text=?';
    }
};

const handleImageError = (event) => {
    event.target.src = 'https://via.placeholder.com/20/e5e7eb/9ca3af?text=?';
};
</script>