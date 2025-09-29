<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Header with Sign In / Sign Up for guests only -->
        <header class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <!-- Logo -->
                <Link :href="route('home')" class="flex items-center gap-2 font-semibold">
                    <AppLogoIcon class="size-8 fill-current text-foreground" />
                    <span class="text-lg font-bold">AppLocation</span>
                </Link>

                <!-- Auth buttons for guests -->
                <div v-if="!$page.props.auth.user" class="flex items-center gap-3">
                    <Link :href="route('login')">
                        <Button variant="ghost" size="sm">
                            Se connecter
                        </Button>
                    </Link>
                    <Link :href="route('register')">
                        <Button size="sm">
                            S'inscrire
                        </Button>
                    </Link>
                </div>

                <!-- User info for authenticated users -->
                <div v-else class="flex items-center gap-3">
                    <span class="text-sm text-muted-foreground">
                        {{ $page.props.auth.user.name }}
                    </span>
                    <Link :href="route('dashboard')">
                        <Button size="sm">
                            Tableau de bord
                        </Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main>
            <slot />
        </main>
    </div>
</template>