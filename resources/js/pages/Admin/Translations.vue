<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import axios from 'axios'

interface Language {
    id: number
    code: string
    name: string
    native_name: string
    flag: string
    is_active: boolean
    is_default: boolean
    is_rtl: boolean
}

interface Props {
    languages: Language[]
    groups: string[]
    missingTranslations: Record<string, string[]>
}

const props = defineProps<Props>()

const selectedLocale = ref(props.languages[0]?.code || 'fr')
const selectedGroup = ref(props.groups[0] || '')
const translations = ref<Record<string, string>>({})
const isLoadingTranslations = ref(false)
const searchQuery = ref('')
const showAddLanguageForm = ref(false)

const addLanguageForm = useForm({
    code: '',
    name: '',
    native_name: '',
    flag: '',
    is_rtl: false,
    copy_from: 'fr',
})

const loadTranslations = async () => {
    if (!selectedLocale.value || !selectedGroup.value) return

    isLoadingTranslations.value = true
    try {
        const response = await axios.get(route('localization.translations'), {
            params: {
                locale: selectedLocale.value,
                group: selectedGroup.value,
            },
        })
        translations.value = response.data.translations || {}
    } catch (error) {
        console.error('Failed to load translations:', error)
        translations.value = {}
    } finally {
        isLoadingTranslations.value = false
    }
}

watch([selectedLocale, selectedGroup], () => {
    loadTranslations()
}, { immediate: true })

const filteredKeys = computed(() => {
    const keys = Object.keys(translations.value)
    if (!searchQuery.value) return keys
    return keys.filter(key =>
        key.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        translations.value[key]?.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const updateTranslation = async (key: string, value: string) => {
    try {
        await axios.post(route('admin.translations.update'), {
            locale: selectedLocale.value,
            group: selectedGroup.value,
            key,
            value,
        })
    } catch (error) {
        console.error('Failed to update translation:', error)
    }
}

const handleTranslationBlur = (key: string, event: FocusEvent) => {
    const target = event.target as HTMLInputElement
    if (target.value !== translations.value[key]) {
        translations.value[key] = target.value
        updateTranslation(key, target.value)
    }
}

const toggleLanguage = (language: Language) => {
    router.post(route('admin.languages.toggle', language.id), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}

const setDefaultLanguage = (language: Language) => {
    router.post(route('admin.languages.default', language.id), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}

const submitAddLanguage = () => {
    addLanguageForm.post(route('admin.languages.add'), {
        onSuccess: () => {
            showAddLanguageForm.value = false
            addLanguageForm.reset()
        },
        preserveScroll: true,
    })
}

const exportTranslations = (format: string) => {
    window.open(route('admin.translations.export', { locale: selectedLocale.value, format }), '_blank')
}

const missingCount = computed(() => {
    return Object.values(props.missingTranslations).reduce((sum, keys) => sum + keys.length, 0)
})
</script>

<template>
    <Head title="Gestion des traductions" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Gestion des traductions
                    </h2>
                    <div class="flex items-center gap-3">
                        <Button variant="outline" @click="showAddLanguageForm = !showAddLanguageForm">
                            Ajouter une langue
                        </Button>
                        <Button variant="outline" @click="exportTranslations('json')">
                            Exporter JSON
                        </Button>
                        <Button variant="outline" @click="exportTranslations('csv')">
                            Exporter CSV
                        </Button>
                    </div>
                </div>

                <!-- Add Language Form -->
                <Card v-if="showAddLanguageForm">
                    <CardHeader>
                        <CardTitle class="text-base">Ajouter une langue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitAddLanguage" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code</label>
                                <Input v-model="addLanguageForm.code" placeholder="ex: de" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                <Input v-model="addLanguageForm.name" placeholder="ex: German" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom natif</label>
                                <Input v-model="addLanguageForm.native_name" placeholder="ex: Deutsch" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Drapeau</label>
                                <Input v-model="addLanguageForm.flag" placeholder="🇩🇪" />
                            </div>
                            <div class="flex items-end gap-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="addLanguageForm.is_rtl" class="rounded border-gray-300" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">RTL</span>
                                </label>
                            </div>
                            <div class="flex items-end">
                                <Button type="submit" :disabled="addLanguageForm.processing">
                                    Ajouter
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Languages List -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Langues ({{ languages.length }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Langue</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Code</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Active</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Defaut</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">RTL</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="language in languages" :key="language.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-sm">
                                            <span class="mr-2">{{ language.flag }}</span>
                                            {{ language.native_name }} ({{ language.name }})
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ language.code }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="language.is_active ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                                {{ language.is_active ? 'Oui' : 'Non' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="language.is_default" class="text-blue-600 text-sm font-medium">Defaut</span>
                                            <Button v-else variant="ghost" size="sm" @click="setDefaultLanguage(language)">
                                                Definir
                                            </Button>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            {{ language.is_rtl ? 'Oui' : 'Non' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Button variant="outline" size="sm" @click="toggleLanguage(language)">
                                                {{ language.is_active ? 'Desactiver' : 'Activer' }}
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Missing Translations Alert -->
                <Card v-if="missingCount > 0">
                    <CardHeader>
                        <CardTitle class="text-base text-yellow-600">Traductions manquantes ({{ missingCount }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div v-for="(keys, locale) in missingTranslations" :key="locale" class="text-sm">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ locale }}:</span>
                                <span class="text-gray-500 dark:text-gray-400 ml-2">{{ keys.length }} cle(s) manquante(s)</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Translation Editor -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Editeur de traductions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col sm:flex-row gap-4 mb-6">
                            <div class="w-full sm:w-48">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Langue</label>
                                <select
                                    v-model="selectedLocale"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option v-for="language in languages" :key="language.code" :value="language.code">
                                        {{ language.flag }} {{ language.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-full sm:w-48">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Groupe</label>
                                <select
                                    v-model="selectedGroup"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option v-for="group in groups" :key="group" :value="group">
                                        {{ group }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rechercher</label>
                                <Input v-model="searchQuery" placeholder="Rechercher une cle..." />
                            </div>
                        </div>

                        <div v-if="isLoadingTranslations" class="text-center py-8 text-gray-500">
                            Chargement...
                        </div>

                        <div v-else-if="filteredKeys.length === 0" class="text-center py-8 text-gray-500">
                            Aucune traduction trouvee.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="key in filteredKeys"
                                :key="key"
                                class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                            >
                                <div class="w-1/3">
                                    <p class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ key }}</p>
                                </div>
                                <div class="flex-1">
                                    <Input
                                        :model-value="translations[key]"
                                        @blur="handleTranslationBlur(key, $event)"
                                        class="font-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
