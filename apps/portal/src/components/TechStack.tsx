import { STACK, type StackLayerId } from "@/lib/stack";

export function TechStack({
  current,
  compact = false,
}: {
  /** The layer the visitor is using right now. */
  current?: StackLayerId;
  /** Names and tools only, for the login page. */
  compact?: boolean;
}) {
  return (
    <dl className={compact ? "space-y-2" : "grid gap-4 sm:grid-cols-2"}>
      {STACK.map((layer) => (
        <div
          key={layer.id}
          className={
            compact
              ? "flex flex-wrap items-baseline gap-x-2 gap-y-1"
              : "card p-5"
          }
        >
          <dt
            className={`font-semibold text-slate-900 ${compact ? "w-full text-xs sm:w-32 sm:shrink-0" : "text-sm"}`}
          >
            {layer.name}
            {layer.id === current && (
              <span className="ml-1 rounded-full bg-brand-100 px-1.5 py-0.5 text-[10px] font-medium text-brand-700">
                vous êtes ici
              </span>
            )}
          </dt>
          <dd
            className={`flex flex-wrap gap-1 ${compact ? "sm:flex-1" : "mt-2"}`}
          >
            {layer.tools.map((tool) => (
              <span
                key={tool}
                className="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200"
              >
                {tool}
              </span>
            ))}
          </dd>
          {!compact && (
            <dd className="mt-3 text-sm text-slate-600">{layer.role}</dd>
          )}
        </div>
      ))}
    </dl>
  );
}
