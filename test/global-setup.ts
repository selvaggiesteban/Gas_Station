import { readD1Migrations } from '@cloudflare/vitest-plugin';

export default async function globalSetup(context: {
  provide: (key: 'd1Migrations', value: unknown) => void;
}) {
  const migrations = await readD1Migrations('./migrations');
  context.provide('d1Migrations', migrations);
}
