import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { ApplicationNavigationGroup } from './navigation';

type Props = {
    groups: readonly ApplicationNavigationGroup[];
};

export function ApplicationNavigation({ groups }: Props) {
    const { isCurrentUrl } = useCurrentUrl();

    return groups.map((group) => (
        <SidebarGroup key={group.title} className="px-2 py-0">
            <SidebarGroupLabel>{group.title}</SidebarGroupLabel>
            <SidebarGroupContent>
                <SidebarMenu>
                    {group.items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            {item.href ? (
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={item.title}
                                >
                                    <Link href={item.href} prefetch>
                                        <item.icon />
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            ) : (
                                <SidebarMenuButton
                                    disabled
                                    aria-label={`${item.title} belum tersedia`}
                                    title={`${item.title} belum tersedia`}
                                >
                                    <item.icon />
                                    <span>{item.title}</span>
                                </SidebarMenuButton>
                            )}
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    ));
}
