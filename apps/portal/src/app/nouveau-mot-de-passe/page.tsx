import type { Metadata } from "next";
import { AuthCard } from "@/components/AuthCard";
import { PasswordForm } from "@/components/PasswordForm";
import { getMessages } from "@/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: (await getMessages()).password.resetTitle,
    robots: { index: false, follow: false },
  };
}

export default async function ResetPasswordPage({
  searchParams,
}: PageProps<"/nouveau-mot-de-passe">) {
  const { token, email } = await searchParams;
  const { password: t } = await getMessages();
  const isComplete = typeof token === "string" && typeof email === "string";

  return (
    <AuthCard
      title={t.resetTitle}
      intro={isComplete ? t.resetIntro(email) : undefined}
    >
      {isComplete ? (
        <PasswordForm purpose="reset" token={token} email={email} />
      ) : (
        <p role="alert" className="text-sm text-red-800">
          {t.incompleteLink}
        </p>
      )}
    </AuthCard>
  );
}
