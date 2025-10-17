<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    posts: Array,
    stats: Object,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAuthenticated = computed(() => !!user.value);

const getPostTypeIcon = (type) => {
    // This function is used for reference but icons are inline in template
    return type;
};

const getPostAction = (post) => {
    if (post.type.value === 'video') return 'Watch →';
    if (post.type.value === 'podcast') return 'Listen →';
    return 'Read →';
};

const getPostTypeColors = (type) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-700',
        red: 'bg-red-100 text-red-700',
        purple: 'bg-purple-100 text-purple-700',
    };
    return colors[type] || colors.blue;
};
</script>

<template>
    <Head title="LaravelSense - Your Laravel Community Feed" />
    
    <div class="antialiased bg-gray-50 font-sans min-h-screen">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">LaravelSense</span>
                    </div>

                    <!-- Search Bar -->
                    <div class="flex-1 max-w-xl mx-8">
                        <div class="relative">
                            <input 
                                type="search" 
                                placeholder="Search Laravel content..." 
                                class="w-full pl-12 pr-4 py-3 bg-gray-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition-all text-sm"
                            >
                            <svg class="w-5 h-5 absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Right Side - Auth/Non-Auth -->
                    <div v-if="isAuthenticated" class="flex items-center space-x-4">
                        <!-- Authenticated User Menu -->
                        <div class="flex items-center space-x-3">
                            <div class="relative group">
                                <button class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center cursor-pointer">
                                    <span class="text-white font-semibold text-sm">{{ user.name.charAt(0).toUpperCase() }}</span>
                                </button>
                                <!-- Dropdown -->
                                <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                    <div class="py-2">
                                        <Link :href="route('profile.edit')" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 text-sm">
                                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Settings
                                        </Link>
                                        <div class="border-t border-gray-200 my-2"></div>
                                        <Link :href="route('logout')" method="post" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 text-sm w-full text-left">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            Sign Out
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Non-Auth Navigation -->
                    <div v-else class="flex items-center space-x-4">
                        <Link :href="route('login')" class="text-gray-600 hover:text-gray-900 font-medium transition-colors text-sm">Login</Link>
                        <Link :href="route('register')" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-5 py-2.5 rounded-lg font-medium hover:from-red-600 hover:to-red-700 transition-all text-sm">
                            Join Now
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Authenticated User Layout -->
            <div v-if="isAuthenticated" class="grid lg:grid-cols-4 gap-12">
                
                <!-- Left Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        
                        <!-- Quick Actions -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-900 mb-4 text-lg">Quick Actions</h3>
                            <div class="space-y-3">
                                <button class="w-full bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span>Add Source</span>
                                </button>
                                <Link href="#" class="block w-full bg-gray-100 text-gray-700 px-4 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                    Bookmarks
                                </Link>
                                <Link href="#" class="block w-full bg-gray-100 text-gray-700 px-4 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    Liked Posts
                                </Link>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-900 mb-4 text-lg">Content Stats</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Blog Posts</span>
                                    <span class="text-xs text-gray-500">{{ stats.posts_by_type.posts }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Videos</span>
                                    <span class="text-xs text-gray-500">{{ stats.posts_by_type.videos }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Podcasts</span>
                                    <span class="text-xs text-gray-500">{{ stats.posts_by_type.podcasts }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Trending -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-900 mb-4 text-lg">Trending</h3>
                            <div class="space-y-3">
                                <div v-for="tag in stats.trending_tags.slice(0, 5)" :key="tag.name" class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">#{{ tag.name }}</span>
                                    <span class="text-xs text-gray-500">{{ tag.count }} posts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Feed -->
                <div class="lg:col-span-3">
                    <div class="grid md:grid-cols-2 gap-8">
                        <article v-for="post in posts" :key="post.id" class="bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-200">
                            <!-- Video Thumbnail (if video) -->
                            <div v-if="post.type.value === 'video'" class="relative rounded-t-2xl overflow-hidden">
                                <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                    <img v-if="post.featured_image" :src="post.featured_image" :alt="post.title" class="w-full h-full object-cover">
                                    <div v-else class="w-16 h-16 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg">
                                        <svg class="w-6 h-6 text-red-600 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div v-if="post.duration" class="absolute bottom-3 right-3 bg-black/70 text-white px-2 py-1 rounded text-xs">
                                    {{ post.duration }}
                                </div>
                                <div class="absolute top-3 left-3 px-2 py-1 rounded text-xs font-medium text-white" :class="getPostTypeColors(post.type.color)">
                                    {{ post.type.label.toUpperCase() }}
                                </div>
                            </div>

                            <div class="p-6">
                                <!-- Source Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                                            <span v-if="post.author.name" class="text-white font-semibold text-sm">
                                                {{ post.author.name.charAt(0).toUpperCase() }}
                                            </span>
                                            <img v-else-if="post.author.avatar" :src="post.author.avatar" :alt="post.author.name" class="w-9 h-9 rounded-full">
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ post.author.name || 'Anonymous' }}</div>
                                            <div class="text-gray-500 text-sm">{{ post.published_at }}</div>
                                        </div>
                                    </div>
                                    <button v-if="isAuthenticated" class="text-gray-400 hover:text-red-600 p-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Content -->
                                <h3 class="text-xl font-bold text-gray-900 mb-4 leading-tight hover:text-red-600 cursor-pointer transition-colors">
                                    {{ post.title }}
                                </h3>
                                
                                <p v-if="post.excerpt" class="text-gray-600 mb-6 leading-relaxed line-clamp-3">
                                    {{ post.excerpt }}
                                </p>

                                <!-- Podcast Player (if podcast) -->
                                <div v-if="post.type.value === 'podcast'" class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-3 mb-4">
                                    <div class="flex items-center space-x-3">
                                        <button class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center shadow-sm">
                                            <svg class="w-4 h-4 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </button>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                                <span>0:00</span>
                                                <span>{{ post.duration || '0:00' }}</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-1">
                                                <div class="bg-indigo-600 h-1 rounded-full" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tags -->
                                <div v-if="post.tags && post.tags.length" class="flex items-center flex-wrap gap-2 mb-6">
                                    <span v-for="tag in post.tags.slice(0, 3)" :key="tag" class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded-full text-xs font-medium">
                                        {{ tag }}
                                    </span>
                                    <span v-if="post.duration" class="text-gray-500 text-sm ml-auto">{{ post.duration }}</span>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-6 text-sm text-gray-500">
                                        <button v-if="isAuthenticated" class="flex items-center space-x-2 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            <span>{{ post.likes_count }}</span>
                                        </button>
                                        <button v-if="isAuthenticated" class="flex items-center space-x-2 hover:text-blue-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                            <span>0</span>
                                        </button>
                                        <button class="text-gray-500 hover:text-green-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <button class="text-red-600 hover:text-red-700 font-medium">
                                        {{ getPostAction(post) }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <!-- Non-Authenticated User Layout -->
            <div v-else class="max-w-2xl mx-auto">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-2xl p-8 mb-8 text-white text-center">
                    <h1 class="text-2xl font-bold mb-2">Welcome to LaravelSense</h1>
                    <p class="text-red-100 mb-6">Discover the latest Laravel content from top creators in the community</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link :href="route('login')" class="bg-white text-red-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                            Sign In
                        </Link>
                        <Link :href="route('register')" class="bg-red-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-800 transition-colors">
                            Join Free
                        </Link>
                    </div>
                </div>

                <!-- Public Feed -->
                <div class="grid md:grid-cols-2 gap-8">
                    <article v-for="post in posts.slice(0, 6)" :key="post.id" class="bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition-all duration-200">
                        <div class="p-6">
                            <!-- Source Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                                        <span v-if="post.author.name" class="text-white font-semibold text-sm">
                                            {{ post.author.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ post.author.name || 'Anonymous' }}</div>
                                        <div class="text-gray-500 text-sm">{{ post.published_at }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <h3 class="text-xl font-bold text-gray-900 mb-4 leading-tight hover:text-red-600 cursor-pointer transition-colors">
                                {{ post.title }}
                            </h3>
                            
                            <p v-if="post.excerpt" class="text-gray-600 mb-6 leading-relaxed line-clamp-3">
                                {{ post.excerpt }}
                            </p>

                            <!-- Tags -->
                            <div v-if="post.tags && post.tags.length" class="flex items-center flex-wrap gap-2 mb-6">
                                <span v-for="tag in post.tags.slice(0, 2)" :key="tag" class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded-full text-xs font-medium">
                                    {{ tag }}
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-6 text-sm text-gray-500">
                                    <span class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <span>{{ post.likes_count }}</span>
                                    </span>
                                </div>
                                <button class="text-red-600 hover:text-red-700 font-medium">
                                    {{ getPostAction(post) }}
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>