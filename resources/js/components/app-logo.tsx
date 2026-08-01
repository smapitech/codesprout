import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="bg-primary/10 text-primary flex aspect-square size-9 items-center justify-center rounded-2xl shadow-sm">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold text-[var(--foreground)]">CodeSprout</span>
                <span className="text-muted-foreground truncate text-xs">ChildsBridge Academy</span>
            </div>
        </>
    );
}
