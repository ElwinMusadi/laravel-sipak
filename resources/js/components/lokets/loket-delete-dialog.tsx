import { Form } from '@inertiajs/react';
import { useState } from 'react';
import LoketController from '@/actions/App/Http/Controllers/LoketController';
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
    loket: { id: number; code: string; name: string };
};

export function LoketDeleteDialog({ loket }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive">Hapus Loket</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Loket yang belum digunakan?</DialogTitle>
                    <DialogDescription>
                        {loket.code} — {loket.name} hanya boleh dihapus bila
                        belum memiliki pengguna, alokasi, maupun BAP. Audit
                        penghapusan tetap dicatat.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Kembali</Button>
                    </DialogClose>
                    <Form action={LoketController.destroy(loket.id)}>
                        {({ processing }) => (
                            <Button variant="destructive" disabled={processing}>
                                Hapus Loket
                            </Button>
                        )}
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
