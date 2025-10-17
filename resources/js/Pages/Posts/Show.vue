<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

const { success, error, handleApiError } = useToast();

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

// Reactive state for user interactions
const isLiked = ref(props.post.is_liked);
const isBookmarked = ref(props.post.is_bookmarked);
const isSeen = ref(props.post.is_seen);
const likesCount = ref(props.post.likes_count);
const isLiking = ref(false);
const isBookmarking = ref(false);
const isMarkingSeen = ref(false);
const showShareModal = ref(false);
const showReportModal = ref(false);
const isReporting = ref(false);

const currentUrl = computed(() => window.location.href);

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

// User interaction functions
const toggleLike = async () => {
    if (!isAuthenticated.value || isLiking.value) return;
    
    isLiking.value = true;
    
    try {
        const response = await fetch(route('posts.like', props.post.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            },
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        isLiked.value = data.is_liked;
        likesCount.value = data.likes_count;
        
        success(data.is_liked ? 'Post liked!' : 'Like removed');
    } catch (err) {
        console.error('Error toggling like:', err);
        handleApiError(err, 'Failed to update like status');
    } finally {
        isLiking.value = false;
    }
};

const toggleBookmark = async () => {
    if (!isAuthenticated.value || isBookmarking.value) return;
    
    isBookmarking.value = true;
    
    try {
        const response = await fetch(route('posts.bookmark', props.post.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        isBookmarked.value = data.is_bookmarked;
        
        success(data.is_bookmarked ? 'Post saved to your library!' : 'Post removed from library');
    } catch (err) {
        console.error('Error toggling bookmark:', err);
        handleApiError(err, 'Failed to update bookmark status');
    } finally {
        isBookmarking.value = false;
    }
};

const markAsSeen = async () => {
    if (!isAuthenticated.value || isMarkingSeen.value || isSeen.value) return;
    
    isMarkingSeen.value = true;
    
    try {
        const response = await fetch(route('posts.mark-seen', props.post.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        isSeen.value = data.is_seen;
        
        success('Post marked as seen!');
    } catch (err) {
        console.error('Error marking as seen:', err);
        handleApiError(err, 'Failed to mark post as seen');
    } finally {
        isMarkingSeen.value = false;
    }
};

const markAsUnseen = async () => {
    if (!isAuthenticated.value || isMarkingSeen.value || !isSeen.value) return;
    
    isMarkingSeen.value = true;
    
    try {
        const response = await fetch(route('posts.mark-unseen', props.post.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        isSeen.value = data.is_seen;
        
        success('Post marked as unseen!');
    } catch (err) {
        console.error('Error marking as unseen:', err);
        handleApiError(err, 'Failed to mark post as unseen');
    } finally {
        isMarkingSeen.value = false;
    }
};

const sharePost = () => {
    showShareModal.value = true;
};

const closeShareModal = () => {
    showShareModal.value = false;
};

const shareToSocial = (platform) => {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(props.post.title);
    const text = encodeURIComponent(props.post.excerpt || `Check out this ${props.post.type.label.toLowerCase()}: ${props.post.title}`);
    
    let shareUrl = '';
    
    switch (platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${text}&url=${url}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
            break;
        case 'reddit':
            shareUrl = `https://reddit.com/submit?url=${url}&title=${title}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${text}%20${url}`;
            break;
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${url}&text=${title}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        success(`Shared to ${platform.charAt(0).toUpperCase() + platform.slice(1)}!`);
        closeShareModal();
    }
};

const copyToClipboard = async () => {
    const url = window.location.href;
    
    try {
        await navigator.clipboard.writeText(url);
        success('Link copied to clipboard!');
        closeShareModal();
    } catch (error) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            success('Link copied to clipboard!');
            closeShareModal();
        } catch (err) {
            error('Could not copy link');
        }
        document.body.removeChild(textArea);
    }
};

const openReportModal = () => {
    showReportModal.value = true;
};

const closeReportModal = () => {
    showReportModal.value = false;
};

const submitReport = async (reportData) => {
    if (!isAuthenticated.value || isReporting.value) return;
    
    isReporting.value = true;
    
    try {
        const response = await fetch(route('posts.report', props.post.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(reportData)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            success('Report submitted successfully. Thank you for helping us improve the platform.');
            closeReportModal();
        } else {
            // Handle validation errors or other API errors
            if (data.errors) {
                const firstError = Object.values(data.errors)[0][0];
                error(firstError);
            } else {
                error(data.message || 'Failed to submit report. Please try again.');
            }
        }
    } catch (err) {
        console.error('Error submitting report:', err);
        handleApiError(err, 'Failed to submit report');
    } finally {
        isReporting.value = false;
    }
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
                        
                        <button v-if="isAuthenticated" @click="toggleBookmark" :disabled="isBookmarking" :class="[
                            'px-4 py-2 rounded-xl transition-colors font-medium',
                            isBookmarked ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-gray-600 hover:text-red-600 hover:bg-red-50'
                        ]">
                            <svg class="w-4 h-4 mr-2 inline" :fill="isBookmarked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            {{ isBookmarked ? 'Saved' : 'Save' }}
                        </button>
                        
                        <!-- Mark as Seen Button -->
                        <button 
                            v-if="isAuthenticated && !isSeen" 
                            @click="markAsSeen" 
                            :disabled="isMarkingSeen" 
                            class="px-4 py-2 rounded-xl transition-colors font-medium text-gray-600 hover:text-green-600 hover:bg-green-50"
                            title="Mark as seen"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Mark as Seen
                        </button>
                        
                        <!-- Mark as Unseen Button -->
                        <button 
                            v-if="isAuthenticated && isSeen" 
                            @click="markAsUnseen" 
                            :disabled="isMarkingSeen" 
                            class="px-4 py-2 rounded-xl transition-colors font-medium bg-green-50 text-green-600 hover:text-gray-600 hover:bg-gray-50"
                            title="Mark as unseen"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            Seen
                        </button>
                        <button @click="sharePost" class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors font-medium">
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
                            <button v-if="isAuthenticated" @click="toggleLike" :disabled="isLiking" :class="[
                                'flex items-center space-x-3 px-6 py-3 rounded-xl transition-colors',
                                isLiked ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-gray-600 hover:text-red-600 hover:bg-red-50'
                            ]">
                                <svg class="w-5 h-5" :fill="isLiked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="font-semibold">{{ likesCount }}</span>
                            </button>
                            <button v-if="isAuthenticated" @click="toggleBookmark" :disabled="isBookmarking" :class="[
                                'px-6 py-3 rounded-xl transition-colors font-semibold',
                                isBookmarked ? 'text-red-600 bg-red-50 hover:bg-red-100' : 'text-gray-600 hover:text-red-600 hover:bg-red-50'
                            ]">
                                <svg class="w-4 h-4 mr-2 inline" :fill="isBookmarked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                {{ isBookmarked ? 'Saved' : 'Save for Later' }}
                            </button>
                        </div>
                        <button v-if="isAuthenticated" @click="openReportModal" class="text-gray-600 hover:text-gray-900 px-6 py-3 rounded-xl hover:bg-gray-100 transition-colors font-semibold">
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
                                <span class="font-semibold text-gray-900">{{ likesCount.toLocaleString() }}</span>
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

        <!-- Share Modal -->
        <div v-if="showShareModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click="closeShareModal">
            <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full" @click.stop>
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Share</h3>
                    <button @click="closeShareModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Social Media Options -->
                <div class="p-6">
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <!-- Twitter -->
                        <button @click="shareToSocial('twitter')" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">Twitter</span>
                        </button>
                        
                        <!-- Facebook -->
                        <button @click="shareToSocial('facebook')" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">Facebook</span>
                        </button>
                        
                        <!-- LinkedIn -->
                        <button @click="shareToSocial('linkedin')" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-blue-700 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">LinkedIn</span>
                        </button>
                    
                        <!-- Reddit -->
                        <button @click="shareToSocial('reddit')" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">Reddit</span>
                        </button>
                        
                        <!-- WhatsApp -->
                        <button @click="shareToSocial('whatsapp')" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">WhatsApp</span>
                        </button>
                        
                        <!-- Telegram -->
                        <button @click="shareToSocial('telegram')" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-blue-400 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">Telegram</span>
                        </button>
                </div>
                
                    <!-- Copy link section -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Copy Link</h4>
                        <div class="flex items-center space-x-3">
                            <div class="flex-1 bg-gray-50 rounded-lg px-3 py-2.5 border border-gray-200">
                                <span class="text-sm text-gray-600 break-all font-mono">{{ currentUrl }}</span>
                            </div>
                            <button @click="copyToClipboard" class="px-4 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors font-medium text-sm flex-shrink-0">
                                <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Report Issue Modal -->
        <div v-if="showReportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click="closeReportModal">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" @click.stop>
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Report Issue</h3>
                    <button @click="closeReportModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="submitReport({ 
                    type: $event.target.type.value, 
                    description: $event.target.description.value 
                })" class="p-6">
                    <div class="mb-4">
                        <label for="reportType" class="block text-sm font-medium text-gray-700 mb-2">Issue Type</label>
                        <select name="type" id="reportType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="">Select an issue type</option>
                            <option value="spam">Spam or unwanted content</option>
                            <option value="inappropriate">Inappropriate content</option>
                            <option value="copyright">Copyright violation</option>
                            <option value="misinformation">Misinformation</option>
                            <option value="broken">Broken link or technical issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="reportDescription" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea 
                            name="description" 
                            id="reportDescription" 
                            rows="4" 
                            required
                            placeholder="Please provide more details about the issue..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                        ></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" @click="closeReportModal" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isReporting" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ isReporting ? 'Submitting...' : 'Submit Report' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <ToastContainer />
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