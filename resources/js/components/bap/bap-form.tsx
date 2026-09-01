import { Form, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { format } from "date-fns";
import { id } from "date-fns/locale";
import { CalendarIcon } from "lucide-react";
import SkpdBapController from "@/actions/App/Http/Controllers/SkpdBapController";
import InputError from "@/components/input-error";
import {
  formatNomeratur,
  formatQuantity,
  formatRange,
} from "@/components/inventory/format";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import { create, index } from "@/routes/baps";

type Allocation = {
  id: number;
  numerator_start: number;
  numerator_end: number;
  remaining_quantity: number;
};

type Props = {
  mode: "create" | "edit";
  bap?: {
    id: number;
    service_date: string;
    numerator_start: number;
    numerator_end: number;
    online_usage_count: number;
    loket: { id: number; name: string };
  };
  loket?: { id: number; name: string } | null;
  lokets?: { id: number; name: string }[];
  defaultServiceDate?: string;
  expectedNumeratorStart?: number | null;
  allocations?: Allocation[];
};

function digitsOnly(value: string): string {
  return value.replace(/\D/g, "");
}

function numeratorDigits(value: string): string {
  return digitsOnly(value).slice(0, 7);
}

function quantityForRange(start: string, end: string): number | null {
  if (!/^\d{7}$/.test(start) || !/^\d{7}$/.test(end)) {
    return null;
  }

  const quantity = Number(end) - Number(start) + 1;

  return quantity > 0 ? quantity : null;
}

export function BapForm({
  mode,
  bap,
  loket,
  lokets = [],
  defaultServiceDate,
  expectedNumeratorStart,
  allocations = [],
}: Props) {
  const formLoket = bap?.loket ?? loket;
  const initialServiceDateStr = bap?.service_date ?? defaultServiceDate ?? "";
  const [serviceDate, setServiceDate] = useState<Date | undefined>(
    initialServiceDateStr
      ? new Date(`${initialServiceDateStr}T00:00:00`)
      : undefined,
  );
  const [numeratorStart, setNumeratorStart] = useState(
    bap
      ? formatNomeratur(bap.numerator_start)
      : expectedNumeratorStart === null || expectedNumeratorStart === undefined
        ? ""
        : formatNomeratur(expectedNumeratorStart),
  );
  const [numeratorEnd, setNumeratorEnd] = useState(
    bap ? formatNomeratur(bap.numerator_end) : "",
  );
  const [onlineUsageCount, setOnlineUsageCount] = useState(
    bap ? String(bap.online_usage_count) : "0",
  );
  const totalUsage = quantityForRange(numeratorStart, numeratorEnd);
  const onlineUsage = onlineUsageCount === "" ? 0 : Number(onlineUsageCount);
  const onlineIsValid = Number.isInteger(onlineUsage) && onlineUsage >= 0;
  const nonOnlineUsage =
    totalUsage !== null && onlineIsValid && onlineUsage <= totalUsage
      ? totalUsage - onlineUsage
      : null;
  const formAction =
    mode === "create"
      ? SkpdBapController.store()
      : SkpdBapController.update(bap?.id ?? 0);

  return (
    <div className="grid max-w-5xl gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
      <Card>
        <CardContent>
          <Form
            action={formAction}
            className="grid gap-4 sm:grid-cols-2 sm:gap-6"
          >
            {({ errors, processing }) => (
              <>
                <div className="grid gap-2 sm:col-span-2">
                  <Label htmlFor="loket_id">Loket Pelayanan</Label>
                  {mode === "create" && lokets.length > 0 ? (
                    <>
                      <input
                        name="loket_id"
                        type="hidden"
                        value={formLoket?.id ?? ""}
                      />
                      <Select
                        value={formLoket?.id.toString() ?? ""}
                        onValueChange={(value) =>
                          router.get(
                            create.url({
                              query: {
                                loket: value,
                              },
                            }),
                          )
                        }
                      >
                        <SelectTrigger
                          id="loket_id"
                          aria-invalid={Boolean(errors.loket_id)}
                        >
                          <SelectValue placeholder="Pilih Loket aktif" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            {lokets.map((option) => (
                              <SelectItem
                                key={option.id}
                                value={option.id.toString()}
                              >
                                {option.name}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                      <InputError message={errors.loket_id} />
                      <p className="text-muted-foreground text-xs">
                        Semua Loket aktif dapat dipilih untuk transaksi ini dan
                        tidak disimpan pada akun Superadmin.
                      </p>
                    </>
                  ) : (
                    <>
                      <div className="bg-muted rounded-xl px-3 py-2.5 text-sm font-medium">
                        {formLoket?.name ?? "Loket tidak tersedia"}
                      </div>
                      <p className="text-muted-foreground text-xs">
                        Loket Pelayanan ditetapkan dari akun Petugas dan tidak
                        dapat diubah dari form ini.
                      </p>
                    </>
                  )}
                </div>

                <div className="grid gap-2 sm:col-span-2">
                  <Label htmlFor="service_date">Tanggal pelayanan</Label>
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant="outline"
                        className={cn(
                          "w-full justify-start text-left font-normal",
                          !serviceDate && "text-muted-foreground",
                          errors.service_date &&
                            "border-destructive text-destructive",
                        )}
                      >
                        <CalendarIcon className="mr-2 size-4" />
                        {serviceDate ? (
                          format(serviceDate, "PPP", { locale: id })
                        ) : (
                          <span>Pilih tanggal</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={serviceDate}
                        onSelect={setServiceDate}
                        disabled={(date) => date > new Date()}
                        locale={id}
                      />
                    </PopoverContent>
                  </Popover>
                  <input
                    type="hidden"
                    name="service_date"
                    value={serviceDate ? format(serviceDate, "yyyy-MM-dd") : ""}
                  />
                  <InputError message={errors.service_date} />
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="numerator_start">Nomeratur awal</Label>
                  <Input
                    id="numerator_start"
                    name="numerator_start"
                    value={numeratorStart}
                    onChange={(event) =>
                      setNumeratorStart(numeratorDigits(event.target.value))
                    }
                    className="font-mono tabular-nums"
                    inputMode="numeric"
                    maxLength={7}
                    placeholder="0582608"
                    aria-invalid={Boolean(errors.numerator_start)}
                  />
                  <InputError message={errors.numerator_start} />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="numerator_end">Nomeratur akhir</Label>
                  <Input
                    id="numerator_end"
                    name="numerator_end"
                    value={numeratorEnd}
                    onChange={(event) =>
                      setNumeratorEnd(numeratorDigits(event.target.value))
                    }
                    className="font-mono tabular-nums"
                    inputMode="numeric"
                    maxLength={7}
                    placeholder="0582620"
                    aria-invalid={Boolean(errors.numerator_end)}
                  />
                  <InputError message={errors.numerator_end} />
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="online_usage_count">
                    SKPD pembayaran online
                  </Label>
                  <Input
                    id="online_usage_count"
                    name="online_usage_count"
                    value={onlineUsageCount}
                    onChange={(event) =>
                      setOnlineUsageCount(digitsOnly(event.target.value))
                    }
                    inputMode="numeric"
                    placeholder="0"
                    aria-invalid={Boolean(errors.online_usage_count)}
                  />
                  <InputError message={errors.online_usage_count} />
                </div>
                <div className="bg-muted flex flex-col justify-center rounded-xl p-4">
                  <p className="text-muted-foreground text-sm">
                    Total pemakaian
                  </p>
                  <p className="mt-1 text-2xl font-semibold tabular-nums">
                    {totalUsage === null
                      ? "—"
                      : `${formatQuantity(totalUsage)} set`}
                  </p>
                  <p className="text-muted-foreground mt-1 text-xs">
                    Dihitung otomatis dari nomeratur awal hingga akhir.
                  </p>
                </div>

                <div className="flex flex-wrap justify-end gap-3 pt-2 sm:col-span-2">
                  <Button variant="outline" asChild>
                    <Link href={index()}>Batal</Link>
                  </Button>
                  <Button
                    disabled={processing || (mode === "create" && !formLoket)}
                  >
                    {mode === "create"
                      ? "Simpan draft BAP"
                      : "Simpan perubahan draft"}
                  </Button>
                </div>
              </>
            )}
          </Form>
        </CardContent>
      </Card>

      <div className="grid content-start gap-4">
        <Card>
          <CardHeader>
            <CardTitle>Review pemakaian</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 text-sm">
            <ReviewRow
              label="Loket Pelayanan"
              value={formLoket?.name ?? "—"}
            />
            <ReviewRow
              label="Tanggal"
              value={
                serviceDate
                  ? format(serviceDate, "PPP", { locale: id })
                  : "Belum diisi"
              }
            />
            <ReviewRow
              label="Nomeratur"
              value={
                totalUsage === null
                  ? "Masukkan range valid"
                  : formatRange(Number(numeratorStart), Number(numeratorEnd))
              }
              mono
            />
            <ReviewRow
              label="Total"
              value={
                totalUsage === null ? "—" : `${formatQuantity(totalUsage)} set`
              }
            />
            <ReviewRow
              label="Online"
              value={
                onlineIsValid
                  ? `${formatQuantity(onlineUsage)} set`
                  : "Tidak valid"
              }
            />
            <ReviewRow
              label="Non-online"
              value={
                nonOnlineUsage === null
                  ? "—"
                  : `${formatQuantity(nonOnlineUsage)} set`
              }
            />
            <ReviewRow label="Status" value="Draft — belum diajukan" />
          </CardContent>
        </Card>

        {mode === "create" ? (
          <Card>
            <CardHeader>
              <CardTitle>Alokasi aktif Loket Pelayanan</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-3 text-sm">
              {allocations.length === 0 ? (
                <p className="text-muted-foreground">
                  Belum ada alokasi accepted yang dapat digunakan.
                </p>
              ) : (
                allocations.map((allocation) => (
                  <div
                    key={allocation.id}
                    className="border-border grid gap-1 rounded-xl border p-3"
                  >
                    <span className="font-mono text-xs font-medium">
                      {formatRange(
                        allocation.numerator_start,
                        allocation.numerator_end,
                      )}
                    </span>
                    <span className="text-muted-foreground text-xs">
                      Sisa {formatQuantity(allocation.remaining_quantity)} set
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        ) : null}
      </div>
    </div>
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
