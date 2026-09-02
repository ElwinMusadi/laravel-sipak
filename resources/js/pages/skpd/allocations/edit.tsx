import { Form, Head, Link } from "@inertiajs/react";
import { useState } from "react";
import SkpdAllocationController from "@/actions/App/Http/Controllers/SkpdAllocationController";
import InputError from "@/components/input-error";
import { formatRange } from "@/components/inventory/format";
import { RangeInputFields } from "@/components/inventory/range-input-fields";
import type { LoketOption } from "@/components/inventory/types";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import Heading from "@/components/heading";
import { index, show } from "@/routes/skpd/allocations";

type Props = {
  allocation: {
    id: number;
    box: {
      box_number: string;
      numerator_start: number;
      numerator_end: number;
    };
    loket_id: number;
    numerator_start: number;
    numerator_end: number;
  };
  lokets: LoketOption[];
};

export default function EditAllocation({ allocation, lokets }: Props) {
  const [loketId, setLoketId] = useState(allocation.loket_id.toString());
  const [numeratorStart, setNumeratorStart] = useState(
    allocation.numerator_start.toString().padStart(7, "0"),
  );
  const [numeratorEnd, setNumeratorEnd] = useState(
    allocation.numerator_end.toString().padStart(7, "0"),
  );

  return (
    <>
      <Head title={`Edit Alokasi ${allocation.box.box_number}`} />

      <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
        <Heading
          title="Edit Alokasi SKPD"
          description="Ubah Loket penerima atau rentang alokasi yang masih pending. Box asal tidak dapat diganti."
        />

        <Card className="max-w-3xl">
          <CardContent>
            <Form
              {...SkpdAllocationController.update.form(allocation.id)}
              className="space-y-6"
            >
              {({ errors, processing }) => (
                <>
                  <Card size="sm" className="bg-muted/50 shadow-none">
                    <CardHeader>
                      <CardTitle>Box asal</CardTitle>
                    </CardHeader>
                    <CardContent className="font-mono text-sm">
                      <p>{allocation.box.box_number}</p>
                      <p className="text-muted-foreground">
                        {formatRange(
                          allocation.box.numerator_start,
                          allocation.box.numerator_end,
                        )}
                      </p>
                    </CardContent>
                  </Card>

                  <div className="grid gap-2">
                    <Label htmlFor="loket_id">Loket penerima</Label>
                    <Select
                      name="loket_id"
                      value={loketId}
                      onValueChange={setLoketId}
                    >
                      <SelectTrigger
                        id="loket_id"
                        aria-invalid={Boolean(errors.loket_id)}
                      >
                        <SelectValue placeholder="Pilih Loket" />
                      </SelectTrigger>
                      <SelectContent>
                        {lokets.map((loket) => (
                          <SelectItem
                            key={loket.id}
                            value={loket.id.toString()}
                          >
                            {loket.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <InputError message={errors.loket_id} />
                  </div>

                  <RangeInputFields
                    numeratorStart={numeratorStart}
                    numeratorEnd={numeratorEnd}
                    onNumeratorStartChange={setNumeratorStart}
                    onNumeratorEndChange={setNumeratorEnd}
                    errors={errors}
                  />

                  <p className="text-muted-foreground rounded-xl border p-4 text-sm">
                    Perubahan hanya dapat dilakukan sebelum handover diterima.
                    Range tetap harus berada di dalam Box asal dan tidak boleh
                    bertumpang tindih dengan alokasi aktif lain.
                  </p>

                  <div className="flex flex-wrap justify-end gap-3">
                    <Button variant="outline" asChild>
                      <Link href={show(allocation.id)}>Batal</Link>
                    </Button>
                    <Button disabled={processing}>Simpan perubahan</Button>
                  </div>
                </>
              )}
            </Form>
          </CardContent>
        </Card>
      </main>
    </>
  );
}

EditAllocation.layout = {
  breadcrumbs: [
    { title: "Distribusi / Alokasi", href: index() },
    { title: "Edit alokasi", href: index() },
  ],
};
