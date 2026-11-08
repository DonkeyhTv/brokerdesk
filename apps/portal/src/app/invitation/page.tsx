import type { Metadata } from "next";
import { AuthCard } from "@/components/AuthCard";
import { PasswordForm } from "@/components/PasswordForm";
import { getMessages } from "@/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: (await getMessages()).password.invitationTitle,
    robots: { index: false, follow: false },
  };
}

export default async function InvitationPage({
  searchParams,
}: PageProps<"/invitation">) {
  const { token, email } = await searchParams;
  const { password: t } = await getMessages();
  const isComplete = typeof token === "string" && typeof email === "string";

  return (
    <AuthCard
      title={t.invitationTitle}
      intro={isComplete ? t.invitationIntro(email) : undefined}
    >
      {isComplete ? (
        <PasswordForm purpose="invitation" token={token} email={email} />
      ) : (
        <p role="alert" className="text-sm text-red-800">
          {t.incompleteLink}
        </p>
      )}
    </AuthCard>
  );
}
