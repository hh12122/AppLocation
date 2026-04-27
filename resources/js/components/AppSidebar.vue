<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { Car, LayoutGrid, Calendar, Heart, CreditCard, MessageSquare, Home, Building, Bike, Wrench, Ship, MapPin, ClipboardList, ShieldCheck, FileText, Globe } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage()
const user = computed(() => page.props.auth?.user)
const userRole = computed(() => user.value?.user_role || 'locataire')
const isOwner = computed(() => userRole.value === 'proprietaire' || userRole.value === 'both')
const isRenter = computed(() => userRole.value === 'locataire' || userRole.value === 'both')
const isAdmin = computed(() => !!user.value?.is_admin)
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
            },
            {
                title: 'Demandes véhicules',
                href: '/my-bookings',
                icon: ClipboardList,
            },
            {
                title: 'Demandes propriétés',
                href: '/property-bookings-management',
                icon: ClipboardList,
            },
            {
                title: 'Demandes matériel',
                href: '/equipment-bookings-management',
                icon: ClipboardList,
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
       /*  {
            title: 'Parrainage',
            href: '/referrals',
            icon: Users,
        }, */
        {
            title: 'Mes paiements',
            href: '/payments',
            icon: CreditCard,
        }
    )

    return items
})

const adminNavItems = computed<NavItem[]>(() => {
    if (!isAdmin.value) return []

    return [
        {
            title: 'Permis à vérifier',
            href: '/admin/license-verifications',
            icon: ShieldCheck,
        },
        {
            title: 'Traductions',
            href: '/admin/translations',
            icon: FileText,
        },
           /*{
            title: 'Geo-notifications',
            href: '/admin/geo-notifications',
            icon: Globe,
        }   */
    ]
})

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
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Administration" />
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
