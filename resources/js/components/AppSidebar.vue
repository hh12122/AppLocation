<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { BookOpen, Car, Folder, LayoutGrid, Search, Calendar, Heart, CreditCard, MessageSquare, Users, Home, Building, Bike, Wrench, Ship, MapPin } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage()
const user = computed(() => page.props.auth?.user)
const userRole = computed(() => user.value?.user_role || 'locataire')
const isOwner = computed(() => userRole.value === 'proprietaire' || userRole.value === 'both')
const isRenter = computed(() => userRole.value === 'locataire' || userRole.value === 'both')
const unreadMessageCount = ref(0)

const fetchUnreadCount = async () => {
    try {
        const response = await fetch(route('api.chat.unread-count'))
        if (response.ok) {
            const data = await response.json()
            unreadMessageCount.value = data.unread_count
        }
    } catch (error) {
        console.error('Error fetching unread count:', error)
    }
}

onMounted(() => {
    fetchUnreadCount()

    // Set up real-time updates for unread count via Echo
    const echo = (window as any).Echo
    if (echo && user.value) {
        echo.private(`App.Models.User.${user.value.id}`)
            .notification(() => {
                fetchUnreadCount()
            })
    }
})

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
    ]

    // Public browsing items (all users)
    items.push(
        {
            title: 'Véhicules',
            href: '/vehicles',
            icon: Car,
        },
        {
            title: 'Propriétés',
            href: '/properties',
            icon: Home,
        },
        {
            title: 'Sport',
            href: '/equipment/category/sports_equipment',
            icon: Bike,
        },
        {
            title: 'Outils',
            href: '/equipment/category/tools_material',
            icon: Wrench,
        },
        {
            title: 'Bateaux',
            href: '/equipment/category/boats',
            icon: Ship,
        },
        {
            title: 'Espaces',
            href: '/equipment/category/spaces',
            icon: MapPin,
        }
    )

    // Owner items
    if (isOwner.value) {
        items.push(
            {
                title: 'Mes véhicules',
                href: '/my-vehicles',
                icon: Car,
            },
            {
                title: 'Mes propriétés',
                href: '/my-properties',
                icon: Building,
            },
            {
                title: 'Mon matériel',
                href: '/my-equipment',
                icon: Wrench,
            }
        )
    }

    // Renter items
    if (isRenter.value) {
        items.push(
            {
                title: 'Mes réservations',
                href: '/my-rentals',
                icon: Calendar,
            },
            {
                title: 'Mes séjours',
                href: '/my-property-bookings',
                icon: Calendar,
            },
            {
                title: 'Mes locations matériel',
                href: '/my-equipment-bookings',
                icon: Calendar,
            }
        )
    }

    // Common items (all users)
    items.push(
        {
            title: 'Messages',
            href: '/chat',
            icon: MessageSquare,
            badge: unreadMessageCount.value > 0 ? unreadMessageCount.value : undefined,
            badgeVariant: 'destructive',
        },
        {
            title: 'Mes favoris',
            href: '/favorites',
            icon: Heart,
        },
        {
            title: 'Parrainage',
            href: '/referrals',
            icon: Users,
        },
        {
            title: 'Mes paiements',
            href: '/payments',
            icon: CreditCard,
        }
    )

    return items
})

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
