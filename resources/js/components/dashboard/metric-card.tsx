import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface MetricCardProps {
    title: string;
    value: string;
    description: string;
    accent?: string;
    icon?: ReactNode;
}

export function MetricCard({ title, value, description, accent = 'from-emerald-500/20 to-cyan-500/20', icon }: MetricCardProps) {
    return (
        <Card className="overflow-hidden rounded-3xl border-white/60 bg-white/80 shadow-[0_12px_40px_rgba(38,84,63,0.08)] backdrop-blur">
            <CardContent className="p-0">
                <div className={cn('h-1.5 bg-gradient-to-r', accent)} />
                <div className="flex items-start gap-4 p-5">
                    {icon && <div className="rounded-2xl bg-emerald-50 p-3 text-emerald-700">{icon}</div>}
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-sm font-medium">{title}</p>
                        <p className="mt-1 text-3xl font-bold tracking-tight text-[var(--foreground)]">{value}</p>
                        <p className="text-muted-foreground mt-2 text-sm leading-6">{description}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
