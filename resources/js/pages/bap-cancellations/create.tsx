import { Form, Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import { useState } from "react";
import SkpdBapCancellationController from "@/actions/App/Http/Controllers/SkpdBapCancellationController";
import InputError from "@/components/input-error";
import {
  formatDate,
  formatNomeratur,
  formatQuantity,
  formatRange,
} from "@/components/inventory/format";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { index as bapsIndex, show as showBap } from "@/routes/baps";
import { index } from "@/routes/bap-cancellations";

type Reason = { value: "cancelled" | "damaged"; label: string };

type Props = {
  bap: {
    id: number;
    service_date: string;
    loket: { id: number; name: string };
    numerator_start: number;
    numerator_end: number;
    total_usage: number;
    cancellation_quantity: number;
    normal_usage_quantity: number;
    status: "draft";
    created_by: string;
  };
  reasons: Reason[];
};

function numeratorDigits(value: string): string {
  return value.replace(/\D/g, "").slice(0, 7);
}

export default function CreateBapCancellation({ bap, reasons }: Props) {
  const [numerator, setNumerator] = useState("");
  const [reason, setReason] = useState<Reason["value"]>("cancelled");
  const selectedReason = reasons.find((item) => item.value === reason);

  return (
    <>
      <Head title={`Catat batal/rusak BAP #${bap.id}`} />

      <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6">
        <div className="grid gap-2">
          <Button
            variant="link"
            size="sm"
            className="pl-0! hover:no-underline w-fit"
            asChild
          >
            <Link href={showBap(bap.id)}>
              <ArrowLeft data-icon="inline-start" />
              Kembali ke BAP
            </Link>
          </Button>
          <div className="grid gap-1.5">
            <h1 className="text-2xl font-semibold tracking-tight">
              Catat BAP Batal/Rusak
            </h1>
            <p className="text-muted-foreground max-w-2xl text-sm">
              Nomeratur harus sudah menjadi bagian pemakaian BAP ini. Pencatatan
              tidak mengembalikan stock maupun alokasi.
            </p>
          </div>
        </div>

        <div className="grid max-w-5xl gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
          <Card>
            <CardHeader>
              <CardTitle>Informasi pencatatan</CardTitle>
            </CardHeader>
            <CardContent>
              <Form
                {...SkpdBapCancellationController.store.form(bap.id)}
                className="grid gap-6"
              >
                {({ errors, processing }) => (
                  <>
                    <section className="grid gap-4 sm:grid-cols-2">
                      <div className="grid gap-2">
                        <Label>BAP SKPD</Label>
                        <div className="bg-muted rounded-xl px-3 py-2.5 text-sm font-medium">
                          BAP #{bap.id} · {bap.loket.name}
                        </div>
                        <p className="text-muted-foreground text-xs">
                          Tanggal pelayanan: {formatDate(bap.service_date)}
                        </p>
                      </div>
                      <div className="grid gap-2">
                        <Label>Range pemakaian</Label>
                        <div className="bg-muted rounded-xl px-3 py-2.5 font-mono text-sm tabular-nums">
                          {formatRange(bap.numerator_start, bap.numerator_end)}
                        </div>
                        <p className="text-muted-foreground text-xs">
                          Hanya nomor dalam range BAP yang dapat dicatat.
                        </p>
                      </div>
                    </section>

                    <section className="grid gap-4 sm:grid-cols-2">
                      <div className="grid gap-2">
                        <Label htmlFor="numerator">Nomeratur</Label>
                        <Input
                          id="numerator"
                          name="numerator"
                          value={numerator}
                          onChange={(event) =>
                            setNumerator(numeratorDigits(event.target.value))
                          }
                          onBlur={() =>
                            setNumerator((value) =>
                              value === "" ? "" : value.padStart(7, "0"),
                            )
                          }
                          className="font-mono tabular-nums"
                          inputMode="numeric"
                          maxLength={7}
                          placeholder="0582612"
                          aria-invalid={Boolean(errors.numerator)}
                        />
                        <InputError message={errors.numerator} />
                      </div>
                      <div className="grid gap-2">
                        <Label htmlFor="reason">Alasan</Label>
                        <Select
                          name="reason"
                          value={reason}
                          onValueChange={(value) =>
                            setReason(value as Reason["value"])
                          }
                        >
                          <SelectTrigger
                            id="reason"
                            className="w-full"
                            aria-invalid={Boolean(errors.reason)}
                          >
                            <SelectValue placeholder="Pilih alasan" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectGroup>
                              {reasons.map((item) => (
                                <SelectItem key={item.value} value={item.value}>
                                  {item.label}
                                </SelectItem>
                              ))}
                            </SelectGroup>
                          </SelectContent>
                        </Select>
                        <InputError message={errors.reason} />
                      </div>
                    </section>

                    <div className="grid gap-2">
                      <Label htmlFor="description">Keterangan</Label>
                      <Textarea
                        id="description"
                        name="description"
                        maxLength={1000}
                        placeholder="Jelaskan kondisi batal atau rusak secara singkat."
                        aria-invalid={Boolean(errors.description)}
                      />
                      <InputError message={errors.description} />
                    </div>

                    <div className="flex flex-wrap justify-end gap-3">
                      <Button variant="outline" asChild>
                        <Link href={showBap(bap.id)}>Batal</Link>
                      </Button>
                      <Button disabled={processing}>Simpan pencatatan</Button>
                    </div>
                  </>
                )}
              </Form>
            </CardContent>
          </Card>

          <div className="grid content-start gap-4">
            <Card>
              <CardHeader>
                <CardTitle>Review</CardTitle>
              </CardHeader>
              <CardContent className="grid gap-3 text-sm">
                <ReviewRow label="BAP" value={`#${bap.id}`} />
                <ReviewRow
                  label="Nomeratur"
                  value={
                    numerator === ""
                      ? "Belum diisi"
                      : formatNomeratur(Number(numerator))
                  }
                  mono
                />
                <ReviewRow
                  label="Alasan"
                  value={selectedReason?.label ?? "—"}
                />
                <ReviewRow
                  label="Batal/rusak tercatat"
                  value={`${formatQuantity(bap.cancellation_quantity)} set`}
                />
                <ReviewRow
                  label="Pemakaian normal saat ini"
                  value={`${formatQuantity(bap.normal_usage_quantity)} set`}
                />
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Ketentuan</CardTitle>
              </CardHeader>
              <CardContent className="grid gap-2 text-sm">
                <p>
                  Total pemakaian BAP tetap {formatQuantity(bap.total_usage)}{" "}
                  set.
                </p>
                <p className="text-muted-foreground">
                  Klasifikasi ini tidak mengubah inventory, sisa alokasi, atau
                  ketersediaan nomeratur.
                </p>
              </CardContent>
            </Card>
          </div>
        </div>
      </main>
    </>
  );
}

function ReviewRow({
  label,
  value,
  mono = false,
}: {
  label: string;
  value: string;
  mono?: boolean;
}) {
  return (
    <div className="flex items-start justify-between gap-4">
      <span className="text-muted-foreground">{label}</span>
      <span
        className={`text-right font-medium ${mono ? "font-mono text-xs whitespace-nowrap" : ""}`}
      >
        {value}
      </span>
    </div>
  );
}

CreateBapCancellation.layout = {
  breadcrumbs: [
    { title: "BAP SKPD", href: bapsIndex() },
    { title: "BAP Batal/Rusak", href: index() },
    { title: "Catat batal/rusak", href: index() },
  ],
};
