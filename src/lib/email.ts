export type EmailMessage = {
  to: string;
  subject: string;
  text: string;
};

export async function sendEmail(msg: EmailMessage): Promise<void> {
  console.log(
    `[email:stub] to=${msg.to} subject="${msg.subject}" body="${msg.text.replace(/\s+/g, ' ').slice(0, 200)}"`,
  );
}
