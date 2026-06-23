// Thin API wrappers for the Phase 2 PDF / print endpoints. Kept as plain async
// functions (not hooks) because the Print Center drives them imperatively per
// sub-order row.

import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

export interface ItemPdfResult {
  id: number // document id
  download_url: string
  order_item_id: number
  item_code: string
  pdf_status: string
}

export async function generateItemPdf(orderId: number, itemId: number): Promise<ItemPdfResult> {
  const res = await apiGet<ItemPdfResult>(ENDPOINTS.itemJobCard(orderId, itemId))
  return res.data
}

export async function logPrint(
  orderId: number,
  itemId: number,
  isReprint: boolean,
  documentId?: number | null,
  reason?: string,
): Promise<void> {
  await apiMutate('post', ENDPOINTS.itemPrintLog(orderId, itemId), {
    is_reprint: isReprint,
    document_id: documentId ?? null,
    reason: reason ?? null,
  })
}
