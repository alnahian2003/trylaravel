<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, computed, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    sources: Array,
    stats: Object,
});

const page = usePage();
const { success, error } = useToast();

// Watch for flash messages and show toasts
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        success(flash.success);
    }
    if (flash?.error) {
        error(flash.error);
    }
}, { immediate: true });

// Add Source Modal
const showAddSourceModal = ref(false);

const openAddSourceModal = () => {
    showAddSourceModal.value = true;
};

const closeAddSourceModal = () => {
    showAddSourceModal.value = false;
    addSourceForm.reset();
};

const addSourceForm = useForm({
    url: '',
});

const submitAddSource = () => {
    addSourceForm.post(route('sources.store'), {
        onSuccess: () => {
            closeAddSourceModal();
            success('Source added successfully!');
        },
        onError: () => {
            error('Failed to add source. Please try again.');
        }
    });
};

// Delete Source
const deleteSource = (sourceId) => {
    if (confirm('Are you sure you want to delete this source?')) {
        const deleteForm = useForm({});
        deleteForm.delete(route('sources.destroy', sourceId), {
            onSuccess: () => {
                success('Source removed successfully!');
            },
            onError: () => {
                error('Failed to remove source. Please try again.');
            }
        });
    }
};

// Search functionality
const searchQuery = ref('');
const filteredSources = computed(() => {
    if (!searchQuery.value) {
        return props.sources;
    }
    return props.sources.filter(source => 
        source.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        source.url.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// Helper functions
const getStatusClass = (isActive) => {
    return isActive 
        ? 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700'
        : 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700';
};

const getStatusText = (isActive) => {
    return isActive ? 'Active' : 'Error';
};

const getStatusIcon = (isActive) => {
    return isActive ? 'check-circle' : 'exclamation-circle';
};

const formatLastUpdated = (lastFetchedAt) => {
    if (!lastFetchedAt) return 'Never';
    return new Date(lastFetchedAt).toLocaleString();
};

const getDomainFromUrl = (url) => {
    try {
        const domain = new URL(url).hostname.replace('www.', '');
        return domain;
    } catch {
        return 'Unknown';
    }
};
</script>

<template>
    <Head title="Manage Sources" />

    <AuthenticatedLayout>
        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-3 flex items-center">
                            <svg class="w-8 h-8 text-orange-600 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3.429 14.286a1.71 1.71 0 011.715 1.714A1.71 1.71 0 013.43 17.714a1.71 1.71 0 01-1.715-1.714c0-.95.765-1.714 1.714-1.714zM3.429 9.143c1.903 0 3.652.753 4.967 2.125a1.714 1.714 0 01-2.446 2.398 3.43 3.43 0 00-2.521-1.095c-.947 0-1.715.767-1.715 1.715a1.714 1.714 0 01-3.428 0 5.14 5.14 0 015.143-5.143zM3.429 4c3.046 0 5.857 1.214 7.875 3.429a1.714 1.714 0 01-2.446 2.398A6.858 6.858 0 003.43 7.429c-1.894 0-3.429 1.535-3.429 3.428a1.714 1.714 0 01-3.428 0C-3.428 6.29.858 4 3.429 4z"/>
                            </svg>
                            Manage Sources
                        </h1>
                        <p class="text-xl text-gray-600">Control which RSS feeds appear in your personalized digest</p>
                    </div>
                    <button 
                        @click="openAddSourceModal"
                        class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                        </svg>
                        Add New Source
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-3 gap-8 mb-8">
                <div class="bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-2 font-medium">Active Sources</p>
                            <p class="text-4xl font-bold text-gray-900">{{ stats.active_sources }}</p>
                        </div>
                        <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-2 font-medium">Total Articles</p>
                            <p class="text-4xl font-bold text-gray-900">{{ stats.total_articles }}</p>
                        </div>
                        <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-2 font-medium">Last Updated</p>
                            <p class="text-4xl font-bold text-gray-900">{{ stats.last_updated }}</p>
                        </div>
                        <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sources Table -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-lg">
                <div class="px-6 py-6 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900">Your Sources ({{ sources.length }})</h2>
                    <div class="flex items-center space-x-3">
                        <input 
                            v-model="searchQuery"
                            type="search" 
                            placeholder="Search sources..." 
                            class="border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" v-if="filteredSources.length > 0">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">URL</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Articles</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Updated</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr 
                                v-for="source in filteredSources" 
                                :key="source.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <img 
                                            :src="source.favicon_url || 'https://via.placeholder.com/40'" 
                                            :alt="source.name" 
                                            class="w-10 h-10 rounded-full"
                                        >
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ source.name }}</p>
                                            <p class="text-xs text-gray-500">{{ getDomainFromUrl(source.url) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <a 
                                        :href="source.feed_url" 
                                        target="_blank" 
                                        class="text-sm text-blue-600 hover:text-blue-700 hover:underline flex items-center"
                                    >
                                        {{ source.feed_url }}
                                        <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(source.is_active)" class="flex items-center">
                                        <svg v-if="source.is_active" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                                        </svg>
                                        {{ getStatusText(source.is_active) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900 font-medium">{{ source.posts_count || 0 }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">{{ formatLastUpdated(source.last_fetched_at) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="deleteSource(source.id)"
                                        class="text-red-600 hover:text-red-700 text-sm font-medium p-2 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Delete source"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3.429 14.286a1.71 1.71 0 011.715 1.714A1.71 1.71 0 013.43 17.714a1.71 1.71 0 01-1.715-1.714c0-.95.765-1.714 1.714-1.714zM3.429 9.143c1.903 0 3.652.753 4.967 2.125a1.714 1.714 0 01-2.446 2.398 3.43 3.43 0 00-2.521-1.095c-.947 0-1.715.767-1.715 1.715a1.714 1.714 0 01-3.428 0 5.14 5.14 0 015.143-5.143zM3.429 4c3.046 0 5.857 1.214 7.875 3.429a1.714 1.714 0 01-2.446 2.398A6.858 6.858 0 003.43 7.429c-1.894 0-3.429 1.535-3.429 3.428a1.714 1.714 0 01-3.428 0C-3.428 6.29.858 4 3.429 4z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No sources found</h3>
                        <p class="text-gray-600 mb-6">
                            {{ searchQuery ? 'No sources match your search.' : 'Get started by adding your first RSS feed.' }}
                        </p>
                        <button 
                            v-if="!searchQuery"
                            @click="openAddSourceModal"
                            class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                            </svg>
                            Add Your First Source
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Add Source Modal -->
        <Modal :show="showAddSourceModal" @close="closeAddSourceModal">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Add New Source</h2>
                    <button 
                        @click="closeAddSourceModal" 
                        class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-xl"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAddSource">
                    <div class="mb-6">
                        <InputLabel for="url" value="Website URL" class="block text-sm font-semibold text-gray-700 mb-3" />
                        <TextInput
                            id="url"
                            v-model="addSourceForm.url"
                            type="url"
                            placeholder="https://example.com/feed"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                            required
                        />
                        <InputError :message="addSourceForm.errors.url" class="mt-2" />
                        <p class="mt-3 text-sm text-gray-500">Paste the RSS feed URL from any website or blog</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-blue-600 mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-blue-900 mb-2">How to find RSS feeds</h4>
                                <p class="text-sm text-blue-800">Look for RSS icons or links labeled "Feed", "RSS", or "Subscribe". Usually found in website footers.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <SecondaryButton @click="closeAddSourceModal" class="flex-1">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton 
                            :disabled="addSourceForm.processing" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                            </svg>
                            Add Source
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>