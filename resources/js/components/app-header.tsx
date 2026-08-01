import { Breadcrumbs } from '@/components/breadcrumbs';
import { Icon } from '@/components/icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Menu, Settings } from 'lucide-react';
import AppLogo from './app-logo';
import AppLogoIcon from './app-logo-icon';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const getInitials = useInitials();
    const dashboardUrl = auth.user?.dashboard_route ?? route('dashboard');

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            url: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Settings',
            url: route('profile.edit'),
            icon: Settings,
        },
    ];

    return (
        <>
            <div className="border-sidebar-border/80 border-b bg-white/90 backdrop-blur">
                <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                    {/* Mobile Menu */}
                    <div className="lg:hidden">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="mr-2 h-[34px] w-[34px]">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="bg-sidebar flex h-full w-64 flex-col items-stretch justify-between">
                                <SheetTitle className="sr-only">Navigation Menu</SheetTitle>
                                <SheetHeader className="flex justify-start text-left">
                                    <AppLogoIcon className="h-6 w-6 fill-current text-black dark:text-white" />
                                </SheetHeader>
                                <div className="mt-6 flex h-full flex-1 flex-col space-y-4">
                                    <div className="flex flex-col space-y-3 text-sm">
                                        {mainNavItems.map((item) => (
                                            <Link
                                                key={item.title}
                                                href={item.url}
                                                className={cn(
                                                    'flex items-center gap-2 rounded-2xl px-3 py-3 font-medium transition-colors',
                                                    page.url === item.url ? 'bg-emerald-100 text-emerald-900' : 'hover:bg-white/70',
                                                )}
                                            >
                                                {item.icon && <Icon iconNode={item.icon} className="h-5 w-5" />}
                                                <span>{item.title}</span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <Link href={dashboardUrl} prefetch className="flex items-center space-x-2">
                        <AppLogo />
                    </Link>

                    <div className="ml-6 hidden items-center gap-2 lg:flex">
                        {mainNavItems.map((item) => (
                            <Link
                                key={item.title}
                                href={item.url}
                                className={cn(
                                    'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                                    page.url === item.url
                                        ? 'bg-emerald-100 text-emerald-900'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                {item.icon && <Icon iconNode={item.icon} className="h-4 w-4" />}
                                <span>{item.title}</span>
                            </Link>
                        ))}
                    </div>

                    <div className="ml-auto flex items-center space-x-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="size-10 rounded-full p-1">
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage src={auth.user?.avatar_url ?? undefined} alt={auth.user?.name ?? 'Learner'} />
                                        <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(auth.user?.name ?? 'Learner')}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                {auth.user && <UserMenuContent user={auth.user} />}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
            {breadcrumbs.length > 1 && (
                <div className="border-sidebar-border/70 flex w-full border-b">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
