// Central measurement guide config — drives the bilingual visual measurement
// form. Keys MATCH the backend shirt_data / pant_data field names so values
// save without any mapping. `diagram_key` selects the body region the
// MeasurementVisualGuide highlights when a field is focused.

export type MeasurementProductType = 'shirt' | 'trouser'

export interface MeasurementGuideField {
  /** Backend field key (shirt_data / pant_data). */
  key: string
  label: string
  /** Tamil label — shown under the English label to reduce language barriers. */
  label_ta: string
  unit: string
  guide_title: string
  guide_text: string
  /** Region id the diagram highlights (see MeasurementVisualGuide). */
  diagram_key: string
  required?: boolean
}

// Practical, in-order field list per product type. Required fields are the
// minimal set tailoring needs; the rest are optional for flexibility.
const SHIRT_FIELDS: MeasurementGuideField[] = [
  { key: 'neck', label: 'Neck', label_ta: 'கழுத்து அளவு', unit: 'in', diagram_key: 'neck', guide_title: 'How to measure neck', guide_text: 'Measure around the base of the neck where the collar sits.' },
  { key: 'collar', label: 'Collar', label_ta: 'காலர் அளவு', unit: 'in', diagram_key: 'neck', guide_title: 'How to measure collar', guide_text: 'Around the neck with one finger of ease for collar comfort.' },
  { key: 'shoulder', label: 'Shoulder', label_ta: 'தோள் அளவு', unit: 'in', diagram_key: 'shoulder', required: true, guide_title: 'How to measure shoulder', guide_text: 'From the tip of one shoulder straight across to the other.' },
  { key: 'chest', label: 'Chest', label_ta: 'மார்பு அளவு', unit: 'in', diagram_key: 'chest', required: true, guide_title: 'How to measure chest', guide_text: 'Around the fullest part of the chest, just under the arms.' },
  { key: 'waist', label: 'Waist', label_ta: 'இடுப்பு அளவு', unit: 'in', diagram_key: 'waist', guide_title: 'How to measure waist', guide_text: 'Around the natural waistline, keeping the tape level.' },
  { key: 'hip', label: 'Hip / Seat', label_ta: 'இடுப்பு கீழ் அளவு', unit: 'in', diagram_key: 'hip', guide_title: 'How to measure hip', guide_text: 'Around the fullest part of the seat.' },
  { key: 'shirt_length', label: 'Shirt Length', label_ta: 'சட்டை நீளம்', unit: 'in', diagram_key: 'length', required: true, guide_title: 'How to measure shirt length', guide_text: 'From the shoulder/neck point down to the desired hem.' },
  { key: 'sleeve_length', label: 'Sleeve Length', label_ta: 'கை நீளம்', unit: 'in', diagram_key: 'sleeve', required: true, guide_title: 'How to measure sleeve length', guide_text: 'From the shoulder point along the arm to the wrist.' },
  { key: 'arm_round', label: 'Sleeve Round / Armhole', label_ta: 'கை சுற்றளவு', unit: 'in', diagram_key: 'armhole', guide_title: 'How to measure armhole', guide_text: 'Around the armhole / upper sleeve at its fullest.' },
  { key: 'bicep', label: 'Bicep', label_ta: 'மேற்கை அளவு', unit: 'in', diagram_key: 'bicep', guide_title: 'How to measure bicep', guide_text: 'Around the fullest part of the upper arm.' },
  { key: 'cuff', label: 'Cuff', label_ta: 'மணிக்கட்டு அளவு', unit: 'in', diagram_key: 'cuff', guide_title: 'How to measure cuff', guide_text: 'Around the wrist where the cuff closes.' },
  { key: 'front_chest', label: 'Front Length', label_ta: 'முன் நீளம்', unit: 'in', diagram_key: 'front', guide_title: 'How to measure front', guide_text: 'Across the front chest, between the armhole creases.' },
  { key: 'cross_back', label: 'Back Width', label_ta: 'பின் அளவு', unit: 'in', diagram_key: 'back', guide_title: 'How to measure back', guide_text: 'Across the back, shoulder blade to shoulder blade.' },
  { key: 'wrist', label: 'Wrist', label_ta: 'மணிக்கட்டு', unit: 'in', diagram_key: 'cuff', guide_title: 'How to measure wrist', guide_text: 'Snug around the wrist bone.' },
]

const TROUSER_FIELDS: MeasurementGuideField[] = [
  { key: 'waist', label: 'Waist', label_ta: 'இடுப்பு அளவு', unit: 'in', diagram_key: 'waist', required: true, guide_title: 'How to measure waist', guide_text: 'Around the natural waist where the trouser sits.' },
  { key: 'hip', label: 'Hip / Seat', label_ta: 'இடுப்பு கீழ் அளவு', unit: 'in', diagram_key: 'hip', guide_title: 'How to measure hip', guide_text: 'Around the fullest part of the seat.' },
  { key: 'thigh', label: 'Thigh', label_ta: 'தொடை அளவு', unit: 'in', diagram_key: 'thigh', guide_title: 'How to measure thigh', guide_text: 'Around the fullest part of the thigh.' },
  { key: 'knee', label: 'Knee', label_ta: 'முழங்கால் அளவு', unit: 'in', diagram_key: 'knee', guide_title: 'How to measure knee', guide_text: 'Around the leg at the knee.' },
  { key: 'bottom', label: 'Bottom / Ankle', label_ta: 'கால் அடி அளவு', unit: 'in', diagram_key: 'bottom', guide_title: 'How to measure bottom', guide_text: 'Around the opening at the ankle.' },
  { key: 'length', label: 'Trouser Length', label_ta: 'கால் நீளம்', unit: 'in', diagram_key: 'length', required: true, guide_title: 'How to measure trouser length', guide_text: 'From the waistband down the outside of the leg to the hem.' },
  { key: 'out_seam', label: 'Outseam', label_ta: 'வெளி நீளம்', unit: 'in', diagram_key: 'length', guide_title: 'How to measure outseam', guide_text: 'Outer leg seam from waist to hem.' },
  { key: 'in_seam', label: 'Inseam', label_ta: 'உள் நீளம்', unit: 'in', diagram_key: 'inseam', required: true, guide_title: 'How to measure inseam', guide_text: 'From the crotch down the inside of the leg to the hem.' },
  { key: 'crotch', label: 'Rise / Crotch', label_ta: 'க்ராட்ச் அளவு', unit: 'in', diagram_key: 'rise', guide_title: 'How to measure rise', guide_text: 'From the waistband down to the crotch seam.' },
  { key: 'fly', label: 'Fly', label_ta: 'ஜிப் நீளம்', unit: 'in', diagram_key: 'rise', guide_title: 'How to measure fly', guide_text: 'Length of the fly / zip opening.' },
]

export const MEASUREMENT_GUIDE: Record<MeasurementProductType, MeasurementGuideField[]> = {
  shirt: SHIRT_FIELDS,
  trouser: TROUSER_FIELDS,
}

/** Backend data key for a product type: shirt → shirt_data, trouser → pant_data. */
export function dataKeyFor(productType: MeasurementProductType): 'shirt_data' | 'pant_data' {
  return productType === 'trouser' ? 'pant_data' : 'shirt_data'
}

/** Backend profile/order product_type value (trouser maps to the backend 'pant'). */
export function productTypeApiValue(productType: MeasurementProductType): 'shirt' | 'pant' {
  return productType === 'trouser' ? 'pant' : 'shirt'
}
