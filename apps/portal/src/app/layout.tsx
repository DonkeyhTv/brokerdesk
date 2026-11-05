import type { Metadata } from "next";
import "./globals.css";

const description =
  "Consultez vos contrats d'assurance, téléchargez vos documents et déclarez un sinistre en ligne, auprès de votre courtier.";

export const metadata: Metadata = {
  metadataBase: new URL(process.env.PORTAL_URL ?? "http://localhost:3000"),
  title: {
    default: "BrokerDesk – Portail client",
    template: "%s | BrokerDesk",
  },
  description,
  openGraph: {
    type: "website",
    locale: "fr_BE",
    siteName: "BrokerDesk",
    title: "BrokerDesk – Portail client",
    description,
  },
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="fr" className="h-full">
      <body className="flex min-h-full flex-col">{children}</body>
    </html>
  );
}
