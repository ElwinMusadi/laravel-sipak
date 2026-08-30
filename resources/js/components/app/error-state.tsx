import { CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    title?: string;
    description: string;
};

export function ErrorState({
    title = 'Data tidak dapat dimuat',
    description,
}: Props) {
    return (
        <Alert variant="destructive">
            <CircleAlert />
            <AlertTitle>{title}</AlertTitle>
            <AlertDescription>{description}</AlertDescription>
        </Alert>
    );
}
