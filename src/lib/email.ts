import { connect } from 'cloudflare:sockets';

export type EmailMessage = {
  to: string;
  subject: string;
  text: string;
};

export type EmailConfig = {
  user: string;
  password: string;
  host: string;
  port: number;
  from: string;
};

export type EmailEnv = {
  GMAIL_USER?: string;
  GMAIL_APP_PASSWORD?: string;
  GMAIL_SMTP_HOST?: string;
  GMAIL_SMTP_PORT?: string | number;
  EMAIL_FROM?: string;
};

export type SmtpStep = {
  send: string;
  expectCodes: number[];
  raw?: boolean;
};

export type SmtpReply = {
  code: number;
  lines: string[];
};

const EHLO_NAME = 'gas-station';
const SMTP_TIMEOUT_MS = 10_000;
const DEFAULT_SMTP_HOST = 'smtp.gmail.com';
const DEFAULT_SMTP_PORT = 465;

let config: EmailConfig | null = null;

export function configureEmail(env: EmailEnv): void {
  config = configFromEnv(env);
}

export function configFromEnv(env: EmailEnv): EmailConfig | null {
  const user = str(env.GMAIL_USER);
  const password = str(env.GMAIL_APP_PASSWORD);
  if (!user || !password) return null;
  return {
    user,
    password,
    host: str(env.GMAIL_SMTP_HOST) || DEFAULT_SMTP_HOST,
    port: smtpPort(env.GMAIL_SMTP_PORT),
    from: str(env.EMAIL_FROM) || user,
  };
}

export async function sendEmail(msg: EmailMessage): Promise<void> {
  const cfg = config;
  if (!cfg) {
    console.log(
      `[email:stub] to=${msg.to} subject="${msg.subject}" body="${msg.text.replace(/\s+/g, ' ').slice(0, 200)}"`,
    );
    return;
  }
  try {
    await deliver(cfg, msg);
    console.log(`[email:sent] to=${msg.to} subject="${msg.subject}"`);
  } catch (err) {
    console.error(
      `[email:error] to=${msg.to} subject="${msg.subject}" error=${message(err)}`,
    );
  }
}

export function buildSmtpConversation(
  msg: EmailMessage,
  cfg: EmailConfig,
  now: Date = new Date(),
): SmtpStep[] {
  return [
    { send: `EHLO ${EHLO_NAME}`, expectCodes: [250] },
    {
      send: `AUTH PLAIN ${encodeAuthPlain(cfg.user, cfg.password)}`,
      expectCodes: [235],
    },
    { send: `MAIL FROM:<${cfg.user}>`, expectCodes: [250] },
    { send: `RCPT TO:<${msg.to}>`, expectCodes: [250, 251] },
    { send: 'DATA', expectCodes: [354] },
    { send: buildDataPayload(msg, cfg, now), expectCodes: [250], raw: true },
    { send: 'QUIT', expectCodes: [221] },
  ];
}

export function buildDataPayload(
  msg: EmailMessage,
  cfg: EmailConfig,
  now: Date = new Date(),
): string {
  const body = msg.text.replace(/\r?\n/g, '\r\n');
  const asciiBody = /^[\x00-\x7F]*$/.test(body);
  const content = asciiBody ? dotStuff(body) : base64Chunked(body);
  const headers = [
    `From: ${encodeHeaderValue(cfg.from)}`,
    `To: ${encodeHeaderValue(msg.to)}`,
    `Subject: ${encodeHeaderValue(msg.subject)}`,
    `Date: ${now.toUTCString()}`,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=utf-8',
    `Content-Transfer-Encoding: ${asciiBody ? '8bit' : 'base64'}`,
  ];
  return `${headers.join('\r\n')}\r\n\r\n${content}\r\n.\r\n`;
}

export function encodeHeaderValue(value: string): string {
  const clean = value.replace(/[\r\n]+/g, ' ').trim();
  if (/^[\x20-\x7E]*$/.test(clean)) return clean;
  return `=?UTF-8?B?${base64Encode(clean)}?=`;
}

export function encodeAuthPlain(user: string, password: string): string {
  const nul = String.fromCharCode(0);
  return base64Encode(`${nul}${user}${nul}${password}`);
}

export function parseReply(lines: string[]): SmtpReply {
  const last = lines[lines.length - 1];
  const match = last === undefined ? null : /^(\d{3})(?: |$)/.exec(last);
  if (!match) {
    throw new Error(`smtp: respuesta inválida: ${last ?? ''}`);
  }
  return { code: Number(match[1]), lines };
}

async function deliver(cfg: EmailConfig, msg: EmailMessage): Promise<void> {
  const socket = connect(
    { hostname: cfg.host, port: cfg.port },
    { secureTransport: 'on', allowHalfOpen: false },
  );
  try {
    await socket.opened;
    const writer = socket.writable.getWriter();
    const reader = socket.readable.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    const readLine = async (): Promise<string> => {
      for (;;) {
        const idx = buffer.indexOf('\r\n');
        if (idx >= 0) {
          const line = buffer.slice(0, idx);
          buffer = buffer.slice(idx + 2);
          return line;
        }
        const chunk = await withTimeout(reader.read(), SMTP_TIMEOUT_MS);
        if (chunk.done) throw new Error('smtp: la conexión se cerró');
        if (chunk.value && chunk.value.byteLength > 0) {
          buffer += decoder.decode(chunk.value, { stream: true });
        }
      }
    };

    const readReply = async (): Promise<SmtpReply> => {
      const lines: string[] = [];
      for (;;) {
        const line = await readLine();
        lines.push(line);
        if (/^\d{3}(?: |$)/.test(line)) return parseReply(lines);
      }
    };

    const write = async (data: string): Promise<void> => {
      await withTimeout(
        writer.write(new TextEncoder().encode(data)),
        SMTP_TIMEOUT_MS,
      );
    };

    const greeting = await readReply();
    assertCodes(greeting, [220]);

    const steps = buildSmtpConversation(msg, cfg);
    for (const [i, step] of steps.entries()) {
      await write(step.raw ? step.send : `${step.send}\r\n`);
      try {
        const reply = await readReply();
        assertCodes(reply, step.expectCodes);
      } catch (err) {
        if (i === steps.length - 1) break;
        throw err;
      }
    }
  } finally {
    try {
      await socket.close();
    } catch {}
  }
}

function assertCodes(reply: SmtpReply, expectCodes: number[]): void {
  if (!expectCodes.includes(reply.code)) {
    throw new Error(
      `smtp: se esperaba ${expectCodes.join('/')} y llegó ${reply.code}: ${reply.lines.join(' | ')}`,
    );
  }
}

async function withTimeout<T>(p: Promise<T>, ms: number): Promise<T> {
  let timer: ReturnType<typeof setTimeout> | undefined;
  try {
    return await Promise.race([
      p,
      new Promise<never>((_, reject) => {
        timer = setTimeout(
          () => reject(new Error(`smtp: tiempo agotado (${ms}ms)`)),
          ms,
        );
      }),
    ]);
  } finally {
    if (timer !== undefined) clearTimeout(timer);
  }
}

function dotStuff(text: string): string {
  return text
    .split('\r\n')
    .map((line) => (line.startsWith('.') ? `.${line}` : line))
    .join('\r\n');
}

function base64Chunked(text: string): string {
  const b64 = base64Encode(text);
  const lines: string[] = [];
  for (let i = 0; i < b64.length; i += 76) lines.push(b64.slice(i, i + 76));
  return lines.join('\r\n');
}

function base64Encode(text: string): string {
  const bytes = new TextEncoder().encode(text);
  let binary = '';
  for (const b of bytes) binary += String.fromCharCode(b);
  return btoa(binary);
}

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

function smtpPort(v: string | number | undefined): number {
  const n = Number(v);
  return Number.isInteger(n) && n > 0 && n <= 65_535 ? n : DEFAULT_SMTP_PORT;
}

function message(err: unknown): string {
  return err instanceof Error ? err.message : String(err);
}
