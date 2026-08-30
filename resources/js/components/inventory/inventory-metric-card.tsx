import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    label: string;
    value: string;
    description?: string;
    icon: LucideIcon;
};

export function InventoryMetricCard({
    label,
    value,
    description,
    icon: Icon,
}: Props) {
    return (
        <Card size="sm">
            <CardContent className="flex items-start justify-between gap-3">
                <div className="space-y-1">
                    <p className="text-muted-foreground text-sm">{label}</p>
                    <p className="text-2xl font-semibold tracking-tight">
                        {value}
                    </p>
                    {description ? (
                        <p className="text-muted-foreground text-xs">
                            {description}
                        </p>
                    ) : null}
                </div>
                <span className="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-xl">
                    <Icon className="size-4" />
                </span>
            </CardContent>
        </Card>
    );
}
