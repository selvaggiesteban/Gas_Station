import { afterEach, describe, expect, it, vi } from 'vitest';
import {
  buildDataPayload,
  buildSmtpConversation,
  configFromEnv,
  configureEmail,
  parseReply,
  sendEmail,
  type EmailMessage,
} from '../src/lib/email';

const ENV_FALSO = {
  GMAIL_USER: 'user@gmail.com',
  GMAIL_APP_PASSWORD: 'secreta pass 123',
  EMAIL_FROM: 'Gas Station <no-reply@estacion.test>',
};

const MENSAJE: EmailMessage = {
  to: 'dest@test.com',
  subject: 'Restablecé tu contraseña',
  text: 'Hola, crea una nueva contrasena con el enlace de abajo.\n\nGracias.',
};

const FECHA = new Date('2026-01-02T03:04:05Z');
const NUL = String.fromCharCode(0);
const FIN_PAYLOAD = '\r\n.\r\n';

function decodeB64Utf8(b64: string): string {
  const bytes = Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
  return new TextDecoder().decode(bytes);
}

function cfgDe(env: Record<string, unknown> = ENV_FALSO) {
  const cfg = configFromEnv(env);
  if (!cfg) throw new Error('se esperaba una configuración válida');
  return cfg;
}

describe('email', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    configureEmail({});
  });

  it('sin configuración resuelve y emite el log stub', async () => {
    configureEmail({});
    const log = vi.spyOn(console, 'log').mockImplementation(() => {});
    await expect(
      sendEmail({
        to: 'ana@test.com',
        subject: 'Hola',
        text: 'hola mundo\nchau',
      }),
    ).resolves.toBeUndefined();
    expect(log).toHaveBeenCalledWith(
      '[email:stub] to=ana@test.com subject="Hola" body="hola mundo chau"',
    );
  });

  it('sin GMAIL_APP_PASSWORD vuelve al stub', async () => {
    configureEmail({ GMAIL_USER: 'user@gmail.com' });
    const log = vi.spyOn(console, 'log').mockImplementation(() => {});
    await sendEmail(MENSAJE);
    expect(log).toHaveBeenCalledTimes(1);
    expect(String(log.mock.calls[0]?.[0])).toContain(
      '[email:stub] to=dest@test.com',
    );
  });

  it('configFromEnv lee las variables y aplica los defaults', () => {
    expect(configFromEnv({})).toBeNull();
    expect(configFromEnv({ GMAIL_USER: 'user@gmail.com' })).toBeNull();
    expect(
      configFromEnv({
        GMAIL_USER: ' user@gmail.com ',
        GMAIL_APP_PASSWORD: ' pass ',
      }),
    ).toEqual({
      user: 'user@gmail.com',
      password: 'pass',
      host: 'smtp.gmail.com',
      port: 465,
      from: 'user@gmail.com',
    });
    expect(
      configFromEnv({
        GMAIL_USER: 'user@gmail.com',
        GMAIL_APP_PASSWORD: 'secreta',
        GMAIL_SMTP_HOST: 'smtp.ejemplo.com',
        GMAIL_SMTP_PORT: 2525,
        EMAIL_FROM: 'Estacion <no-reply@ejemplo.com>',
      }),
    ).toEqual({
      user: 'user@gmail.com',
      password: 'secreta',
      host: 'smtp.ejemplo.com',
      port: 2525,
      from: 'Estacion <no-reply@ejemplo.com>',
    });
  });

  it('construye la secuencia SMTP y AUTH PLAIN decodifica a user y password', () => {
    configureEmail(ENV_FALSO);
    const conv = buildSmtpConversation(MENSAJE, cfgDe(), FECHA);
    expect(conv.map((s) => s.send.split('\r\n')[0])).toEqual([
      'EHLO gas-station',
      expect.stringContaining('AUTH PLAIN '),
      'MAIL FROM:<user@gmail.com>',
      'RCPT TO:<dest@test.com>',
      'DATA',
      expect.stringContaining('From:'),
      'QUIT',
    ]);
    const auth = conv[1]!.send.slice('AUTH PLAIN '.length);
    expect(decodeB64Utf8(auth)).toBe(
      `${NUL}user@gmail.com${NUL}secreta pass 123`,
    );
    expect(conv[0]!.expectCodes).toEqual([250]);
    expect(conv[4]!.expectCodes).toEqual([354]);
    expect(conv[6]!.expectCodes).toEqual([221]);
  });

  it('codifica el asunto con RFC 2047 y cierra el cuerpo con CRLF punto', () => {
    const payload = buildDataPayload(MENSAJE, cfgDe(), FECHA);
    expect(payload).toContain('From: Gas Station <no-reply@estacion.test>');
    expect(payload).toContain('To: dest@test.com');
    expect(payload).toContain(
      'Subject: =?UTF-8?B?UmVzdGFibGVjw6kgdHUgY29udHJhc2XDsWE=?=',
    );
    expect(payload).toContain('Date: Fri, 02 Jan 2026 03:04:05 GMT');
    expect(payload).toContain('MIME-Version: 1.0');
    expect(payload).toContain('Content-Type: text/plain; charset=utf-8');
    expect(payload).toContain('Content-Transfer-Encoding: 8bit');
    expect(payload).toContain(
      'Hola, crea una nueva contrasena con el enlace de abajo.\r\n\r\nGracias.',
    );
    expect(payload.endsWith(FIN_PAYLOAD)).toBe(true);
  });

  it('codifica cuerpos con acentos en base64', () => {
    const payload = buildDataPayload(
      { to: 'dest@test.com', subject: 'Aviso', text: '¿Todo bien?' },
      cfgDe(),
      FECHA,
    );
    expect(payload).toContain('Content-Transfer-Encoding: base64');
    const body = payload.split('\r\n\r\n')[1];
    const b64 = body!.slice(0, -FIN_PAYLOAD.length);
    expect(decodeB64Utf8(b64)).toBe('¿Todo bien?');
    expect(payload.endsWith(FIN_PAYLOAD)).toBe(true);
  });

  it('escapa líneas del cuerpo que empiezan con punto', () => {
    const payload = buildDataPayload(
      { to: 'dest@test.com', subject: 'Aviso', text: '.inicio\nnormal' },
      cfgDe(),
      FECHA,
    );
    expect(payload).toContain(`\r\n\r\n..inicio\r\nnormal${FIN_PAYLOAD}`);
  });

  it('parsea replies SMTP multilínea', () => {
    expect(
      parseReply(['250-PIPELINING', '250-SIZE 35882577', '250 SMTPUTF8']).code,
    ).toBe(250);
    expect(parseReply(['250-PIPELINING', '250 SIZE 35882577']).code).toBe(250);
    expect(parseReply(['220 smtp.gmail.com ESMTP ready']).code).toBe(220);
    expect(parseReply(['535 5.7.8 Username and Password not accepted']).code).toBe(535);
    expect(() => parseReply([])).toThrow();
    expect(() => parseReply(['250-PIPELINING'])).toThrow();
  });
});
