import { Form } from '@inertiajs/react';
import { useState } from 'react';
import SkpdBapController from '@/actions/App/Http/Controllers/SkpdBapController';
import { formatRange } from '@/components/inventory/format';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    bap: {
        id: number;
        document_number: string;
        loket: { name: string };
        numerator_start: number;
        numerator_end: number;
    };
};

export function BapDeleteDialog({ bap }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive">Hapus draft</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus draft BAP SKPD?</DialogTitle>
                    <DialogDescription>
                        {bap.document_number} dari {bap.loket.name} dengan range{' '}
                        {formatRange(bap.numerator_start, bap.numerator_end)}{' '}
                        akan dihapus. Tindakan ini hanya tersedia sebelum ada
                        pembatalan, verifikasi, atau klarifikasi.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Kembali</Button>
                    </DialogClose>
                    <Form action={SkpdBapController.destroy(bap.id)}>
                        {({ processing }) => (
                            <Button variant="destructive" disabled={processing}>
                                Hapus draft
                            </Button>
                        )}
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
