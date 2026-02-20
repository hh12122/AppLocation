<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const avatarPreview = ref<string | null>(
    user.avatar ? `/storage/${user.avatar}` : null
);
const avatarFile = ref<File | null>(null);

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    bio: user.bio || '',
    avatar: null as File | null,
    address: user.address || '',
    city: user.city || '',
    postal_code: user.postal_code || '',
    country: user.country || '',
    date_of_birth: user.date_of_birth || '',
});

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        avatarFile.value = file;
        form.avatar = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    avatarPreview.value = null;
    avatarFile.value = null;
    form.avatar = null;

    // Reset file input
    const fileInput = document.getElementById('avatar') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
};

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Optionally reset avatar file input after success
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Personal Information Section -->
                    <div class="space-y-4">
                        <HeadingSmall title="Informations personnelles" description="Mettez à jour vos informations de base" />

                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label for="name">Nom complet *</Label>
                                <Input id="name" v-model="form.name" required autocomplete="name" placeholder="Votre nom complet" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Adresse email *</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    placeholder="votre.email@exemple.com"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div v-if="mustVerifyEmail && !user.email_verified_at">
                                <p class="text-sm text-muted-foreground">
                                    Votre adresse email n'est pas vérifiée.
                                    <Link
                                        :href="route('verification.send')"
                                        method="post"
                                        as="button"
                                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    >
                                        Cliquez ici pour renvoyer l'email de vérification.
                                    </Link>
                                </p>

                                <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                                    Un nouveau lien de vérification a été envoyé à votre adresse email.
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="phone">Téléphone</Label>
                                <Input id="phone" type="tel" v-model="form.phone" autocomplete="tel" placeholder="+33 6 12 34 56 78" />
                                <InputError :message="form.errors.phone" />
                                <p class="text-xs text-muted-foreground">Votre numéro sera visible par les propriétaires/locataires lors des réservations.</p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="date_of_birth">Date de naissance</Label>
                                <Input id="date_of_birth" type="date" v-model="form.date_of_birth" autocomplete="bday" />
                                <InputError :message="form.errors.date_of_birth" />
                                <p class="text-xs text-muted-foreground">Vous devez avoir au moins 18 ans.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Photo Section -->
                    <div class="space-y-4">
                        <HeadingSmall title="Photo de profil" description="Ajoutez une photo pour personnaliser votre profil" />

                        <div class="flex items-start gap-6">
                            <div class="flex-shrink-0">
                                <div class="h-24 w-24 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        alt="Avatar preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="h-full w-full flex items-center justify-center text-gray-400 text-3xl">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 space-y-2">
                                <Input
                                    id="avatar"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif"
                                    @change="handleAvatarChange"
                                    class="block w-full"
                                />
                                <InputError :message="form.errors.avatar" />
                                <p class="text-xs text-muted-foreground">JPG, PNG ou GIF (max. 2 Mo)</p>
                                <Button
                                    v-if="avatarPreview"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="removeAvatar"
                                >
                                    Supprimer la photo
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- About Section -->
                    <div class="space-y-4">
                        <HeadingSmall title="À propos" description="Parlez-nous de vous" />

                        <div class="grid gap-2">
                            <Label for="bio">Biographie</Label>
                            <Textarea
                                id="bio"
                                v-model="form.bio"
                                placeholder="Parlez un peu de vous, de vos passions, de votre expérience..."
                                rows="4"
                                maxlength="500"
                            />
                            <InputError :message="form.errors.bio" />
                            <p class="text-xs text-muted-foreground text-right">{{ form.bio.length }}/500 caractères</p>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="space-y-4">
                        <HeadingSmall title="Adresse" description="Informations de localisation (optionnel)" />

                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label for="address">Rue et numéro</Label>
                                <Input id="address" v-model="form.address" autocomplete="street-address" placeholder="123 rue de la République" />
                                <InputError :message="form.errors.address" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="city">Ville</Label>
                                    <Input id="city" v-model="form.city" autocomplete="address-level2" placeholder="Paris" />
                                    <InputError :message="form.errors.city" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="postal_code">Code postal</Label>
                                    <Input id="postal_code" v-model="form.postal_code" autocomplete="postal-code" placeholder="75001" />
                                    <InputError :message="form.errors.postal_code" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="country">Pays</Label>
                                <Input id="country" v-model="form.country" autocomplete="country-name" placeholder="France" />
                                <InputError :message="form.errors.country" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center gap-4 pt-4 border-t">
                        <Button :disabled="form.processing">
                            Enregistrer les modifications
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-green-600 font-medium">
                                ✓ Profil mis à jour avec succès !
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
