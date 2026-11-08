import type { Metadata } from "next";
import { connection } from "next/server";
import { I18nProvider } from "@/i18n/client";
import { getLocale, getMessages } from "@/i18n/server";
import "./globals.css";

export async function generateMetadata(): Promise<Metadata> {
  const { app } = await getMessages();

  return {
    metadataBase: new URL(process.env.PORTAL_URL ?? "http://localhost:3000"),
    title: { default: app.title, template: "%s | BrokerDesk" },
    description: app.description,
    openGraph: {
      type: "website",
      locale: app.openGraphLocale,
      siteName: "BrokerDesk",
      title: app.title,
      description: app.description,
    },
  };
}

export default async function RootLayout({ children }: LayoutProps<"/">) {
  // Rendered for each request, so that every page carries the nonce of its content security policy.
  await connection();
  const locale = await getLocale();

  return (
    <html lang={locale} className="h-full">
      <body className="flex min-h-full flex-col">
        <I18nProvider locale={locale}>{children}</I18nProvider>
      </body>
    </html>
  );
}
