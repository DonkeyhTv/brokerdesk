const euros = new Intl.NumberFormat("fr-BE", {
  style: "currency",
  currency: "EUR",
});

export function formatMoney(cents: number): string {
  return euros.format(cents / 100);
}

/** Dates arrive as YYYY-MM-DD: they are read without any time zone, to never shift a day. */
export function formatDate(value: string): string {
  const [year, month, day] = value.slice(0, 10).split("-");

  return `${day}/${month}/${year}`;
}

export function formatFileSize(bytes: number): string {
  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} Ko`
    : `${(bytes / (1024 * 1024)).toFixed(1).replace(".", ",")} Mo`;
}
