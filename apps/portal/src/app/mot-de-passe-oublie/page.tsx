import type { Metadata } from "next";
import { AuthCard } from "@/components/AuthCard";
import { ForgotPasswordForm } from "@/components/ForgotPasswordForm";
import { getMessages } from "@/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: (await getMessages()).password.forgotTitle,
    robots: { index: false, follow: false },
  };
}

export default async function ForgotPasswordPage() {
  const { password: t } = await getMessages();

  return (
    <AuthCard title={t.forgotTitle} intro={t.forgotIntro}>
      <ForgotPasswordForm />
    </AuthCard>
  );
}
