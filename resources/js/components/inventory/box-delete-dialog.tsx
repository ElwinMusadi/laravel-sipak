import { Form } from '@inertiajs/react';
import { useState } from 'react';
import SkpdBoxController from '@/actions/App/Http/Controllers/SkpdBoxController';
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
    box: {
        id: number;
        box_number: string;
        numerator_start: number;
        numerator_end: number;
    };
};

export function BoxDeleteDialog({ box }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive">Hapus Box</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Box yang belum digunakan?</DialogTitle>
                    <DialogDescription>
                        {box.box_number} (
                        {formatRange(box.numerator_start, box.numerator_end)})
                        hanya dapat dihapus bila belum memiliki alokasi atau
                        riwayat pemakaian. Audit penghapusan tetap dicatat.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Kembali</Button>
                    </DialogClose>
                    <Form action={SkpdBoxController.destroy(box.id)}>
                        {({ processing }) => (
                            <Button variant="destructive" disabled={processing}>
                                Hapus Box
                            </Button>
                        )}
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
