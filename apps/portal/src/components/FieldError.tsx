export function FieldError({
  name,
  errors,
}: {
  name: string;
  errors?: Record<string, string[]>;
}) {
  const message = errors?.[name]?.[0];

  return message ? (
    <p id={`${name}-error`} role="alert" className="mt-1 text-sm text-red-700">
      {message}
    </p>
  ) : null;
}
