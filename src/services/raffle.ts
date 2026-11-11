import { raffleCoupons } from '../db/schema';
import type { Db } from '../db';
import { ensureDefaultStore } from './orders';

// Sorteo LESCO GNC: quien se registra queda inscripto automáticamente.
// El admin solo sortea entre los inscriptos y marca el ganador.

function randomRaffleCode(): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  const bytes = crypto.getRandomValues(new Uint8Array(8));
  let out = '';
  for (const b of bytes) out += chars[b % chars.length];
  return out;
}

export type RaffleHolder = {
  name: string;
  email?: string | null;
  notes?: string | null;
};

/** Devuelve el código del cupón o null si no se pudo generar. */
export async function enrollInRaffle(
  db: Db,
  holder: RaffleHolder,
): Promise<string | null> {
  try {
    const storeId = await ensureDefaultStore(db);
    for (let attempt = 0; attempt < 3; attempt++) {
      const code = randomRaffleCode();
      try {
        const inserted = await db
          .insert(raffleCoupons)
          .values({
            code,
            storeId,
            holderName: holder.name,
            holderEmail: holder.email ?? null,
            notes: holder.notes ?? 'Alta automática por registro',
          })
          .returning({ code: raffleCoupons.code });
        return inserted[0]!.code;
      } catch {
        // code único repetido: se reintenta con otro
      }
    }
    return null;
  } catch {
    return null;
  }
}
