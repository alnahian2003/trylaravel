<template>
    <AuthenticatedLayout>
        <Head title="Library" />

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-3">
                            <i class="fas fa-bookmark text-purple-600 mr-3"></i>
                            Your Library
                        </h1>
                        <p class="text-xl text-gray-600">{{ totalBookmarks }} bookmarked articles</p>
                    </div>
                    <button class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-download mr-2"></i>
                        Export All
                    </button>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <!-- Filter Tabs -->
                    <div class="flex items-center space-x-3 overflow-x-auto pb-2">
                        <button 
                            @click="filterByCategory(null)"
                            :class="selectedCategory === null 
                                ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors"
                        >
                            All ({{ totalBookmarks }})
                        </button>
                        <button 
                            v-for="(count, category) in categoryCounts" 
                            :key="category"
                            @click="filterByCategory(category)"
                            :class="selectedCategory === category 
                                ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-colors whitespace-nowrap"
                        >
                            <i :class="getCategoryIcon(category)" class="mr-2"></i>
                            {{ category }} ({{ count }})
                        </button>
                    </div>

                    <!-- Sort & View Options -->
                    <div class="flex items-center space-x-3">
                        <select class="border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                            <option>Newest First</option>
                            <option>Oldest First</option>
                            <option>Most Read</option>
                            <option>Alphabetical</option>
                        </select>
                        <div class="flex items-center space-x-1 bg-gray-100 rounded-xl p-1">
                            <button class="bg-white text-gray-700 px-3 py-2 rounded-lg text-sm shadow-sm">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="text-gray-500 px-3 py-2 rounded-lg text-sm hover:bg-white hover:shadow-sm transition-all">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookmarked Articles Grid -->
            <InfiniteScroll data="bookmarks" :buffer="500">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="post in bookmarks.data" 
                        :key="post.id"
                        class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl transition-all group cursor-pointer"
                        @click="navigateToPost(post.slug, $event)"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <span 
                                    v-if="post.categories && post.categories.length > 0"
                                    :class="getCategoryClasses(post.categories[0])"
                                    class="text-xs px-3 py-1.5 rounded-full font-semibold"
                                >
                                    <i :class="getCategoryIcon(post.categories[0])" class="mr-1"></i>
                                    {{ post.categories[0].toUpperCase() }}
                                </span>
                                <button 
                                    @click.stop="removeBookmark(post.id)"
                                    class="text-gray-400 hover:text-red-600 transition-colors p-1 hover:bg-gray-100 rounded-lg"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>

                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-red-600 transition-colors">
                                {{ post.title }}
                            </h3>
                            <p class="text-gray-600 mb-4 line-clamp-2 leading-relaxed">
                                {{ post.excerpt }}
                            </p>

                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <span class="flex items-center">
                                    <i class="fas fa-newspaper mr-2"></i>
                                    {{ post.author_name || 'Unknown Author' }}
                                </span>
                                <span v-if="post.duration">{{ formatDuration(post.duration) }} read</span>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-clock mr-1"></i>
                                    Saved {{ formatDate(post.created_at) }}
                                </span>
                                <button 
                                    @click.stop="navigateToPost(post.slug)"
                                    class="bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl"
                                >
                                    Read
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </InfiniteScroll>

            <!-- Empty State -->
            <div v-if="!bookmarks.data.length" class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-bookmark text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">No bookmarks yet</h3>
                    <p class="text-gray-600 mb-6">Start bookmarking articles from your feed to build your personal library.</p>
                    <Link 
                        :href="route('home')"
                        class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl"
                    >
                        Browse Feed
                    </Link>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router, InfiniteScroll } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed, ref } from 'vue'

const props = defineProps({
    bookmarks: Object,
    categoryCounts: Object,
    totalBookmarks: Number,
    selectedCategory: String,
})

const selectedCategory = ref(props.selectedCategory || null)

const getCategoryIcon = (category) => {
    const icons = {
        'testing': 'fas fa-flask text-blue-600',
        'performance': 'fas fa-tachometer-alt text-green-600',
        'apis': 'fas fa-plug text-purple-600',
        'security': 'fas fa-shield-alt text-red-600',
        'frontend': 'fas fa-palette text-pink-600',
        'architecture': 'fas fa-sitemap text-indigo-600',
    }
    return icons[category.toLowerCase()] || 'fas fa-tag text-gray-600'
}

const getCategoryClasses = (category) => {
    const classes = {
        'testing': 'bg-blue-100 text-blue-700',
        'performance': 'bg-green-100 text-green-700',
        'apis': 'bg-purple-100 text-purple-700',
        'security': 'bg-red-100 text-red-700',
        'frontend': 'bg-pink-100 text-pink-700',
        'architecture': 'bg-indigo-100 text-indigo-700',
    }
    return classes[category.toLowerCase()] || 'bg-gray-100 text-gray-700'
}

const formatDuration = (seconds) => {
    if (!seconds) return '5 min'
    const minutes = Math.ceil(seconds / 60)
    return `${minutes} min`
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = Math.abs(now - date)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays === 1) return '1 day ago'
    if (diffDays < 7) return `${diffDays} days ago`
    if (diffDays < 30) return `${Math.ceil(diffDays / 7)} week${Math.ceil(diffDays / 7) > 1 ? 's' : ''} ago`
    return `${Math.ceil(diffDays / 30)} month${Math.ceil(diffDays / 30) > 1 ? 's' : ''} ago`
}

const filterByCategory = (category) => {
    selectedCategory.value = category
    const params = category ? { category } : {}
    router.get(route('library'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}

const navigateToPost = (slug, event = null) => {
    router.visit(route('posts.show', slug))
}

const removeBookmark = (postId) => {
    console.log('Remove bookmark for post:', postId)
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>