export function Logo() {
  return (
    <span className="flex items-center gap-2.5">
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img src="/icon.svg" alt="" width={28} height={28} />
      <span className="text-lg font-semibold text-slate-900">BrokerDesk</span>
    </span>
  );
}
