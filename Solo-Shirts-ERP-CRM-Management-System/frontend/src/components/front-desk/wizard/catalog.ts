// TODO(phase-2): Replace these local placeholder options with real catalog
// data from the backend, e.g.
//   GET /api/v1/catalog/fabrics  → CatalogOption[]
//   GET /api/v1/catalog/styles   → CatalogOption[]
//   GET /api/v1/catalog/fits     → CatalogOption[]
// Keep the CatalogOption shape so the selectors don't need to change when the
// data source is swapped. No business logic should depend on these ids.

import type { CatalogOption } from './types'

export const FABRIC_OPTIONS: CatalogOption[] = [
  { id: 'white_cotton', label: 'White Cotton' },
  { id: 'blue_oxford', label: 'Blue Oxford' },
  { id: 'pink_stripes', label: 'Pink Stripes' },
  { id: 'egyptian_cotton', label: 'White Egyptian Cotton' },
  { id: 'linen_natural', label: 'Natural Linen' },
]

export const STYLE_OPTIONS: CatalogOption[] = [
  { id: 'formal', label: 'Formal' },
  { id: 'semi_formal', label: 'Semi Formal' },
  { id: 'casual', label: 'Casual' },
]

export const FIT_OPTIONS: CatalogOption[] = [
  { id: 'regular', label: 'Regular' },
  { id: 'slim', label: 'Slim' },
  { id: 'relaxed', label: 'Relaxed' },
]
