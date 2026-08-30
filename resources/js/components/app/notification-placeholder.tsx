import { Bell } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export function NotificationPlaceholder() {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className="inline-flex">
                    <Button
                        variant="ghost"
                        size="icon"
                        disabled
                        aria-label="Notifikasi belum tersedia"
                    >
                        <Bell />
                    </Button>
                </span>
            </TooltipTrigger>
            <TooltipContent>Notifikasi belum tersedia</TooltipContent>
        </Tooltip>
    );
}
