import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, Code2, Gamepad2, Gift, GraduationCap, Keyboard, LayoutGrid, Settings, Users } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth, featureFlags } = usePage<SharedData & { featureFlags?: Record<string, boolean> }>().props;
    const dashboardUrl = auth.user?.dashboard_route ?? route('dashboard');
    const htmlEnabled = featureFlags?.html_learning_engine ?? true;
    const parentHtmlEnabled = htmlEnabled && (featureFlags?.html_parent_preview ?? true);

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            url: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(auth.user?.primary_role === 'administrator'
            ? [
                  { title: 'School Management', url: route('admin.school.index'), icon: Users },
                  { title: 'Curriculum', url: route('admin.curriculum.index'), icon: BookOpen },
                  { title: 'Assignments', url: route('admin.assignments.index'), icon: ClipboardList },
                  { title: 'Games', url: route('admin.games.index'), icon: Gamepad2 },
                  { title: 'Rewards', url: route('admin.rewards.index'), icon: Gift },
                  { title: 'Typing Engine', url: route('admin.typing.index'), icon: Keyboard },
                  ...(htmlEnabled ? [{ title: 'HTML Engine', url: route('admin.html.index'), icon: Code2 }] : []),
              ]
            : []),
        ...(auth.user?.primary_role === 'teacher'
            ? [
                  { title: 'My Classes', url: route('teacher.progress.index'), icon: GraduationCap },
                  { title: 'Curriculum', url: route('teacher.curriculum.index'), icon: BookOpen },
                  { title: 'Assignments', url: route('teacher.assignments.index'), icon: ClipboardList },
                  { title: 'Games', url: route('teacher.games.index'), icon: Gamepad2 },
                  { title: 'Progress', url: route('teacher.progress.index'), icon: Gift },
                  { title: 'Typing', url: route('teacher.typing.index'), icon: Keyboard },
                  ...(htmlEnabled ? [{ title: 'HTML Learning', url: route('teacher.html.index'), icon: Code2 }] : []),
              ]
            : []),
        ...(auth.user?.primary_role === 'parent'
            ? [
                  { title: 'Assignments', url: route('parent.assignments.index'), icon: ClipboardList },
                  { title: 'Games', url: route('parent.games.index'), icon: Gamepad2 },
                  { title: 'Progress', url: route('parent.progress.index'), icon: Gift },
                  { title: 'Typing', url: route('parent.typing.index'), icon: Keyboard },
                  ...(parentHtmlEnabled ? [{ title: 'HTML Progress', url: route('parent.html.index'), icon: Code2 }] : []),
              ]
            : []),
        ...(auth.user?.primary_role === 'child'
            ? [
                  { title: 'My Missions', url: route('child.missions.index'), icon: ClipboardList },
                  { title: 'My Journey', url: route('child.journey'), icon: BookOpen },
                  { title: 'Rewards', url: route('child.rewards.index'), icon: Gift },
                  { title: 'Typing', url: route('child.typing.index'), icon: Keyboard },
                  ...(htmlEnabled ? [{ title: 'HTML Adventure', url: route('child.html.index'), icon: Code2 }] : []),
              ]
            : []),
        {
            title: 'Settings',
            url: route('profile.edit'),
            icon: Settings,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
