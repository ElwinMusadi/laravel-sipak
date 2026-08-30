import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';

type Props = {
    title: string;
    description: string;
    icon?: LucideIcon;
    action?: ReactNode;
};

export function EmptyState({
    title,
    description,
    icon: Icon = Inbox,
    action,
}: Props) {
    return (
        <div className="flex min-h-48 flex-col items-center justify-center gap-3 px-6 py-8 text-center">
            <span className="bg-muted text-muted-foreground flex size-10 items-center justify-center rounded-xl">
                <Icon className="size-5" />
            </span>
            <div className="max-w-sm space-y-1">
                <h3 className="font-medium">{title}</h3>
                <p className="text-muted-foreground text-sm text-pretty">
                    {description}
                </p>
            </div>
            {action}
        </div>
    );
}
