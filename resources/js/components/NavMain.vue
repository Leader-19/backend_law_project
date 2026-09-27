<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// import { ChevronRight } from '@lucide/vue';
import { ChevronRight } from '@lucide/vue';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();

const itemIsActive = (item: NavItem) =>
    urlIsActive(item.href, page.url) ||
    item.items?.some((child) => urlIsActive(child.href, page.url)) ||
    false;
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Navigations</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                v-slot="{ open }"
                :key="item.title"
                :default-open="itemIsActive(item)"
                class="group/collapsible"
            >
                <SidebarMenuItem v-if="item.items?.length">
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            :is-active="itemIsActive(item)"
                            :tooltip="item.title"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                :class="['ml-auto transition-transform', open && 'rotate-90']"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuItem v-for="child in item.items" :key="child.title">
                                <SidebarMenuSubButton as-child :is-active="urlIsActive(child.href, page.url)">
                                    <Link :href="child.href">
                                        <component :is="child.icon" v-if="child.icon" />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </SidebarMenuItem>
                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        as-child
                        :is-active="itemIsActive(item)"
                        :tooltip="item.title"
                    >
                        <a v-if="item.external" :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </a>
                        <Link v-else :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
