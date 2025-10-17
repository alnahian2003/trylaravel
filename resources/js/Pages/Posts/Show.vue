<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    post: Object,
    relatedPosts: Array,
    navigation: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAuthenticated = computed(() => !!user.value);

const contextPanelVisible = ref(false);
const readingProgress = ref(0);

const getPostTypeIcon = (type) => {
    // SVG icons are now used inline in template instead
    return type;
};

const getPostTypeColors = (type) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-700',
        red: 'bg-red-100 text-red-700',
        purple: 'bg-purple-100 text-purple-700',
        green: 'bg-green-100 text-green-700',
        orange: 'bg-orange-100 text-orange-700',
    };
    return colors[type] || colors.blue;
};

const getReadingTime = () => {
    if (!props.post.content) return '1 min read';
    const words = props.post.content.replace(/<[^>]*>/g, '').split(/\s+/).length;
    const minutes = Math.ceil(words / 200);
    return `${minutes} min read`;
};

const updateReadingProgress = () => {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    readingProgress.value = scrolled;
};

const toggleContextPanel = () => {
    contextPanelVisible.value = !contextPanelVisible.value;
};

onMounted(() => {
    window.addEventListener('scroll', updateReadingProgress);
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateReadingProgress);
});
</script>

<template>
    <Head :title="post.title" />
    
    <div class="antialiased bg-white font-sans">
        <!-- Top Navigation -->
        <nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-6">
                        <Link :href="route('home')" class="text-gray-600 hover:text-gray-900 transition-colors font-medium">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to Feed
                        </Link>
                        <div class="text-gray-300">|</div>
                        <Link :href="route('home')" class="text-gray-600 hover:text-gray-900 transition-colors font-medium">Explore</Link>
                        <Link :href="route('home')" class="text-gray-600 hover:text-gray-900 transition-colors font-medium">Library</Link>
                        <Link :href="route('home')" class="text-gray-600 hover:text-gray-900 transition-colors font-medium">Sources</Link>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Link v-if="navigation?.previous" :href="route('posts.show', navigation.previous.slug)" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-xl hover:bg-gray-100 transition-colors" :title="`Previous: ${navigation.previous.title} (K)`">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <button v-else class="text-gray-300 px-3 py-2 rounded-xl cursor-not-allowed" title="Previous (K)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        
                        <Link v-if="navigation?.next" :href="route('posts.show', navigation.next.slug)" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-xl hover:bg-gray-100 transition-colors" :title="`Next: ${navigation.next.title} (J)`">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                        <button v-else class="text-gray-300 px-3 py-2 rounded-xl cursor-not-allowed" title="Next (J)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        
                        <div class="w-px h-6 bg-gray-300 mx-2"></div>
                        
                        <button v-if="isAuthenticated" class="text-gray-600 hover:text-red-600 px-4 py-2 rounded-xl hover:bg-red-50 transition-colors font-medium">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            Save
                        </button>
                        <button class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors font-medium">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                            </svg>
                            Share
                        </button>
                        <button @click="toggleContextPanel" class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors font-medium lg:hidden">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Context
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Layout -->
        <div class="flex">
            <!-- Article Content -->
            <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <!-- Article Header -->
                <header class="mb-12">
                    <div class="flex items-center space-x-3 mb-6">
                        <span class="text-sm px-4 py-1.5 rounded-full font-semibold" :class="getPostTypeColors(post.type.color)">
                            <i :class="getPostTypeIcon(post.type.value)" class="mr-1"></i>
                            {{ post.type.label.toUpperCase() }}
                        </span>
                        <span class="text-gray-500">•</span>
                        <span class="text-gray-600 font-medium">{{ getReadingTime() }}</span>
                        <span v-if="post.duration" class="text-gray-500">•</span>
                        <span v-if="post.duration" class="text-gray-600 font-medium">{{ post.duration }}</span>
                    </div>

                    <h1 class="text-5xl font-bold text-gray-900 mb-8 leading-tight">
                        {{ post.title }}
                    </h1>

                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                            <span v-if="post.author.name" class="text-white font-semibold text-lg">
                                {{ post.author.name.charAt(0).toUpperCase() }}
                            </span>
                            <img v-else-if="post.author.avatar" :src="post.author.avatar" :alt="post.author.name" class="w-14 h-14 rounded-full">
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 text-lg">{{ post.author.name || 'Anonymous' }}</p>
                            <p class="text-gray-600">Published {{ post.published_at }}</p>
                        </div>
                    </div>
                </header>

                <!-- Featured Image for Videos -->
                <div v-if="post.type.value === 'video' && post.featured_image" class="mb-12">
                    <div class="relative rounded-xl overflow-hidden">
                        <img :src="post.featured_image" :alt="post.title" class="w-full h-auto">
                        <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                            <div class="w-20 h-20 bg-white bg-opacity-90 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div v-if="post.duration" class="absolute bottom-4 right-4 bg-black bg-opacity-70 text-white px-3 py-1 rounded text-sm">
                            {{ post.duration }}
                        </div>
                    </div>
                </div>

                <!-- Podcast Player -->
                <div v-if="post.type.value === 'podcast'" class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 mb-12">
                    <div class="flex items-center space-x-4">
                        <button class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center shadow-lg hover:bg-indigo-700 transition-colors">
                            <svg class="w-5 h-5 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 mb-2">{{ post.title }}</h3>
                            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                                <span>0:00</span>
                                <span>{{ post.duration || '0:00' }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Article Body -->
                <article class="article-content text-gray-800 text-lg mb-16">
                    <div v-if="post.excerpt" class="text-xl text-gray-600 mb-8 leading-relaxed font-medium">
                        {{ post.excerpt }}
                    </div>
                    <div class="prose prose-lg max-w-none" v-html="post.content"></div>
                </article>

                <!-- Article Footer -->
                <footer class="mt-16 pt-8 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center space-x-4">
                            <button v-if="isAuthenticated" class="flex items-center space-x-3 text-gray-600 hover:text-red-600 px-6 py-3 rounded-xl hover:bg-red-50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="font-semibold">{{ post.likes_count }}</span>
                            </button>
                            <button v-if="isAuthenticated" class="text-gray-600 hover:text-red-600 px-6 py-3 rounded-xl hover:bg-red-50 transition-colors font-semibold">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                Save for Later
                            </button>
                        </div>
                        <button v-if="isAuthenticated" class="text-gray-600 hover:text-gray-900 px-6 py-3 rounded-xl hover:bg-gray-100 transition-colors font-semibold">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                            Report Issue
                        </button>
                    </div>

                    <!-- Related Posts -->
                    <div v-if="relatedPosts && relatedPosts.length" class="bg-gray-50 rounded-2xl p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Read Next</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <Link v-for="relatedPost in relatedPosts.slice(0, 2)" :key="relatedPost.id" :href="route('posts.show', relatedPost.slug)" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-lg transition-shadow">
                                <span class="text-sm px-3 py-1.5 rounded-full font-semibold" :class="getPostTypeColors(relatedPost.type.color)">
                                    {{ relatedPost.type.label.toUpperCase() }}
                                </span>
                                <h4 class="font-bold text-gray-900 mt-4 mb-2 text-lg">{{ relatedPost.title }}</h4>
                                <p class="text-gray-600">{{ getReadingTime() }}</p>
                            </Link>
                        </div>
                    </div>
                </footer>
            </main>

            <!-- Context Panel (Right Sidebar) -->
            <aside :class="['w-96 bg-gray-50 border-l border-gray-200 sticky top-16 h-screen overflow-y-auto', contextPanelVisible ? 'block' : 'hidden lg:block']">
                <div class="p-8 space-y-8">
                    <!-- Context Header -->
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900">
                            <svg class="w-5 h-5 text-blue-600 mr-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Context
                        </h3>
                        <button @click="toggleContextPanel" class="text-gray-600 hover:text-gray-900 p-2 hover:bg-gray-200 rounded-xl transition-colors lg:hidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Post Stats -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-lg">
                        <h4 class="font-semibold text-gray-900 mb-4">
                            <svg class="w-4 h-4 text-green-600 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Post Stats
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Views</span>
                                <span class="font-semibold text-gray-900">{{ post.views_count.toLocaleString() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Likes</span>
                                <span class="font-semibold text-gray-900">{{ post.likes_count.toLocaleString() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Published</span>
                                <span class="font-semibold text-gray-900">{{ post.formatted_published_at }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Source Information -->
                    <div v-if="post.source_url" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-lg">
                            <h4 class="font-semibold text-gray-900 mb-4">
                                <svg class="w-4 h-4 text-blue-600 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Source
                            </h4>
                        <a :href="post.source_url" target="_blank" rel="noopener noreferrer" class="block text-sm text-gray-700 hover:text-red-600 py-2">
                                <svg class="w-3 h-3 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            View Original Article
                        </a>
                    </div>

                    <!-- Topics -->
                    <div v-if="post.tags && post.tags.length" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-lg">
                            <h4 class="font-semibold text-gray-900 mb-4">
                                <svg class="w-4 h-4 text-green-600 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                Tags
                            </h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="tag in post.tags" :key="tag" class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                                {{ tag }}
                            </span>
                        </div>
                    </div>

                    <!-- File Information -->
                    <div v-if="post.file_url" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-lg">
                            <h4 class="font-semibold text-gray-900 mb-4">
                                <svg class="w-4 h-4 text-purple-600 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                File Info
                            </h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Type</span>
                                <span class="font-semibold text-gray-900">{{ post.file_type }}</span>
                            </div>
                            <div v-if="post.file_size" class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Size</span>
                                <span class="font-semibold text-gray-900">{{ post.file_size }}</span>
                            </div>
                            <a :href="post.file_url" target="_blank" class="text-xs text-red-600 hover:text-red-700 font-medium mt-2 inline-block">
                                Download File 
                                <svg class="w-3 h-3 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Reading Progress Bar -->
        <div class="fixed bottom-0 left-0 right-0 h-1 bg-gray-200 z-50">
            <div class="h-full bg-gradient-to-r from-red-600 to-red-700 transition-all duration-150" :style="`width: ${readingProgress}%`"></div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.prose {
    color: #374151;
    line-height: 1.75;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    color: #111827;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose h1 {
    font-size: 2.25rem;
    line-height: 2.5rem;
}

.prose h2 {
    font-size: 1.875rem;
    line-height: 2.25rem;
}

.prose h3 {
    font-size: 1.5rem;
    line-height: 2rem;
}

.prose p {
    margin-bottom: 1.5rem;
}

.prose ul, .prose ol {
    margin-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.prose li {
    margin-bottom: 0.5rem;
}

.prose a {
    color: #dc2626;
    text-decoration: underline;
}

.prose a:hover {
    color: #b91c1c;
}

.prose code {
    background-color: #f3f4f6;
    color: #1f2937;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.prose pre {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1.5rem;
}

.prose blockquote {
    border-left: 4px solid #dc2626;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6b7280;
}

.prose img {
    border-radius: 0.5rem;
    margin: 1.5rem 0;
    max-width: 100%;
    height: auto;
}
</style>