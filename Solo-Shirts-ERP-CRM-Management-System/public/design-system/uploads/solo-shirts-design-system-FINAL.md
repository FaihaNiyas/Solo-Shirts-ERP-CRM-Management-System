⚠️ CRITICAL INSTRUCTION — READ BEFORE STARTING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DO NOT CREATE A DASHBOARD PAGE.
DO NOT CREATE A FULL ERP SCREEN.
DO NOT CREATE A MARKETING PAGE.
DO NOT COPY THE SIXPAY LAYOUT.

CREATE ONLY A COMPONENT LIBRARY SHEET.
ONE LONG VERTICAL ARTBOARD (~1440px WIDE).
EVERY COMPONENT CLEARLY LABELED WITH FIGMA-STYLE NAMING.
READY FOR DEVELOPER HANDOFF.

Every component must show:
• Component name (Figma naming: Button/Primary/Default)
• All variants
• All states
• Usage note
• Token names where relevant
• Light mode preview
• Dark mode preview (selected components only)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REFERENCE IMAGE — HOW TO USE IT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

I am uploading the Sixpay fintech app screenshot.

Use it ONLY to calibrate these visual qualities:
✓ Light white sidebar (NOT dark — this is critical)
✓ Warm off-white page background
✓ Airy generous whitespace between components
✓ Large bold metric numbers with mini sparklines
✓ Clean white cards with barely-visible shadow
✓ Bar chart with solid vs hatched comparison bars
✓ Right-panel list layout with category pills
✓ Clean minimal topbar (search + actions)
✓ Overall premium fintech cleanliness

DO NOT copy from the image:
✗ Sixpay's green colors (use amber instead)
✗ Sixpay's logos or brand names
✗ Sixpay's financial content (stocks, crypto)
✗ Sixpay's layout structure

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
BRAND IDENTITY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Product: Solo Shirts India ERP
Tagline: Precision tailoring, managed.
Industry: Premium garment and tailoring management

Visual mood:
Sixpay's premium fintech feel — but for tailoring.
Warm instead of cool. Amber instead of green.
A well-stitched bespoke suit meets modern software.

Style keywords:
Airy. Warm. Premium. Precise. Clean. Light.
Data-confident. Craft-focused. Enterprise-grade.

DO NOT make it:
✗ Dark or heavy (light sidebar, not dark)
✗ Generic SaaS blue
✗ Bootstrap or admin template
✗ Glassmorphism or neon
✗ Cold or clinical
✗ Childish or overly colorful

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ICON RULES — APPLY EVERYWHERE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DO NOT use emoji icons anywhere in the design system.
DO NOT use filled icons.

Use professional outline line icons only:
• Lucide-style outline icons
• 1.75px stroke width
• Rounded line caps
• Consistent sizing (16–20px inline, 24px decorative)
• Minimal, clean, premium

Icon mapping for ERP:
Tailoring:   shirt or needle-thread line icon
Cutting:     scissors line icon
QC:          shield-check or check-circle line icon
Delivery:    truck line icon
Finance:     receipt or credit-card line icon
Inventory:   boxes or package line icon
Production:  layers or workflow line icon
Reports:     bar-chart-2 line icon
Settings:    sliders line icon
Search:      search line icon
Scan/QR:     scan or qr-code line icon
Print:       printer line icon
Download:    download line icon
Export:      upload (rotated) line icon
Approve:     check-circle line icon
Reject:      x-circle line icon
Lock:        lock line icon
Branch:      git-branch or building line icon
User:        user or users line icon
Calendar:    calendar line icon
Alert:       alert-triangle line icon
Info:        info line icon

All icon-only buttons must include accessible aria-label.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 1 — COLOR TOKENS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Show every color as a labeled swatch.
Each swatch: color block + token name + hex + usage note.

── BRAND (AMBER GOLD — only primary accent) ───────

--color-brand:          #D97706
Amber Gold
Primary CTA buttons, active nav pill, focus rings,
chart primary series. ONLY accent color.

--color-brand-light:    #FEF3C7
Amber Tint
Active nav background (like Sixpay green pill),
selected table rows, hover states.

--color-brand-dark:     #B45309
Burnt Amber
Pressed button, dark hover state.

--color-brand-muted:    #FDE68A
Amber 200
Sparkline lines in metric cards.

CRITICAL: Amber is the ONLY primary accent.
Blue is ONLY for In Progress and Info states.
Do not use blue as brand, CTA, or chart primary.

── SURFACES (light theme — matches Sixpay feel) ───

--color-bg:             #FFFCF5
Warm Cream
Page background — warm off-white, not pure white.
This warmth is critical to the premium feel.

--color-sidebar-bg:     #FFFFFF
White Sidebar
LIGHT sidebar — matches Sixpay. NOT dark.

--color-sidebar-active: #FEF3C7
Amber Tint Pill
Active nav item background (amber version of
Sixpay's green active pill).

--color-surface:        #FFFFFF
White
Cards, panels, modals, inputs.

--color-surface-alt:    #F9FAFB
Gray 50
Table alt rows, inner panel backgrounds.

--color-border:         #F3F4F6
Gray 100
Card borders (very subtle).

--color-border-mid:     #E5E7EB
Gray 200
Input borders, stronger dividers.

── TEXT ───────────────────────────────────────────

--color-text-primary:   #111827    Gray 900  — titles, numbers
--color-text-secondary: #6B7280    Gray 500  — labels, descriptions
--color-text-muted:     #9CA3AF    Gray 400  — placeholders, metadata
--color-text-inverse:   #FFFFFF    White     — on filled backgrounds

── STATUS (each shown as filled chip + outline + dot)

--color-success:        #16A34A    bg: #DCFCE7   — Approved, Delivered, Up trend
--color-warning:        #D97706    bg: #FEF3C7   — Pending, Rework (same as brand)
--color-danger:         #DC2626    bg: #FEE2E2   — Rejected, Overdue, Down trend
--color-info:           #2563EB    bg: #DBEAFE   — In Progress, Info ONLY
--color-neutral:        #6B7280    bg: #F3F4F6   — Draft, Inactive

QC COLOR FIX:
Do NOT use undefined purple for QC.
QC badge uses neutral gray + amber indicator:
  Background: #F3F4F6 (neutral gray)
  Indicator:  amber dot or amber left border
  Text:       #374151 (gray-700)

── DARK MODE TOKEN STRIP ──────────────────────────

Show as secondary token strip alongside light swatches.
Light mode is default — dark mode is secondary preview only.

--color-bg-dark:             #0C0A09  Warm black (not cold zinc)
--color-sidebar-bg-dark:     #1C1917  Warm dark brown
--color-surface-dark:        #1C1917  Dark warm card
--color-surface-alt-dark:    #292524  Slightly lighter
--color-border-dark:         #3F3F46  Zinc 700
--color-text-primary-dark:   #FAFAF9  Warm white
--color-text-secondary-dark: #A8A29E  Warm gray
--color-brand-darkmode:      #D97706  Amber unchanged
--color-brand-light-darkmode:#451A03  Dark amber bg

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 2 — TYPOGRAPHY SCALE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Primary: Inter   |   Code: JetBrains Mono
Show each as labeled live text sample.

Display:    40px / 700 / tracking -0.02  — ₹1,42,500
            (dominant metric numbers, like Sixpay)
H1:         24px / 600               — Production Dashboard
H2:         20px / 600               — Today's Orders
H3:         16px / 600               — Active Items
H4:         14px / 600               — Customer Name (table headers)
Body-L:     16px / 400 / lh 1.6      — Long readable content
Body-M:     14px / 400 / lh 1.5      — Default UI text
Body-S:     13px / 400 / lh 1.4      — Secondary info, timestamps
Caption:    12px / 400               — Sublabels, axis labels
Mono:       13px / 500 (JetBrains Mono) — ORD-2026-000184
Trend:      12px / 500               — ↑ 12.4% (green) / ↓ 3.2% (red)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 3 — SPACING / RADIUS / SHADOW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SPACING: 4 / 8 / 12 / 16 / 20 / 24 / 32 / 48 / 64px
Show as horizontal bars with label + usage.

BORDER RADIUS:
radius-sm:   4px   — micro badges
radius-md:   8px   — buttons, inputs
radius-lg:   12px  — standard cards
radius-xl:   16px  — large cards, modals
radius-2xl:  20px  — hero cards (Sixpay style)
radius-full: 9999px — pill badges, avatars

SHADOWS (very subtle — Sixpay feel):
shadow-xs: 0 1px 2px rgba(0,0,0,0.04)     — default card
shadow-sm: 0 2px 8px rgba(0,0,0,0.06)     — hover lift
shadow-md: 0 4px 16px rgba(0,0,0,0.08)    — dropdowns
shadow-lg: 0 8px 24px rgba(0,0,0,0.10)    — drawers
shadow-xl: 0 16px 40px rgba(0,0,0,0.12)   — modals

GRADIENT RULE:
NO gradients on cards, buttons, charts, sidebar, surfaces.
EXCEPTION: Only skeleton shimmer may use a subtle gradient.
Chart bars: solid amber (no gradient).
Track: gray-100 background (no gradient).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 4 — BUTTONS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Figma naming: Button/Primary/Default, Button/Primary/Loading, etc.
Each variant shows: Default / Hover / Loading / Disabled.

Button/Primary        — amber bg #D97706 / white text / radius 10px / h 40px
Button/Secondary      — white bg / gray border / gray-800 text / amber hover bg
Button/Ghost          — transparent bg / amber text / amber-50 hover bg
Button/Danger         — red bg #DC2626 / white text
Button/Success        — green bg #16A34A / white text
Button/Outline-Subtle — transparent / gray-200 border / gray-500 text
Button/Pill           — gray-100 bg / gray-700 text / radius-full / h 32px
                        Active state: amber-50 bg + amber text
Button/Icon           — 36×36px / white bg / gray border / outline icon centered
Button/PrimaryLarge   — h 48px / 15px font / major page actions only

Loading state: spinner left + "Processing..." text, disabled, 80% opacity.
Disabled state: 40% opacity, not-allowed cursor, no hover.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 5 — FORM ELEMENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TextInput/Default     — h 40px / radius 10px / gray border / white bg
TextInput/Focus       — amber border + 2px amber glow ring
TextInput/Error       — red border + error text below with alert-triangle icon
TextInput/Disabled    — gray-50 bg / muted text / not-allowed

SearchInput           — gray-100 bg / radius-full / no border / search icon left
                        Ctrl+K hint pill right inside. Sixpay-style.
                        Placeholder: Search order, customer, invoice...

SelectDropdown        — same base + chevron / open: shadow-md dropdown
                        Active option: amber-50 bg + amber text

DateRangePicker       — calendar icon + date text + chevron pill button
                        Example: Jun 1 – Jun 30, 2025 ▾

Textarea              — min-h 80px / same border + focus rules

Checkbox              — 16px / radius 4px / checked: amber fill + white ✓
Radio                 — 16px circle / checked: amber ring + amber dot
Toggle                — 44×24px / off: gray / on: amber / 200ms

Label                 — 13px 500 gray-700 / required: red asterisk
ErrorText             — 12px red / alert-triangle icon left
HelperText            — 12px gray-500

MeasurementInput      — TextInput + unit pill suffix ([cm] or [in])
                        Warning state: amber border + "Above threshold" alert

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 6 — STATUS BADGES + TAGS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Badge base: radius-full / padding 3px 10px / 12px 500

STATUS BADGES (dot + label):
StatusBadge/Approved       — green bg + green text
StatusBadge/Pending        — amber bg + amber text
StatusBadge/Rejected       — red bg + red text
StatusBadge/InProgress     — blue bg + blue text
StatusBadge/Draft          — gray bg + gray text
StatusBadge/Ready          — green bg + green text
StatusBadge/Overdue/Pulse  — red bg + red text + pulsing red dot
StatusBadge/Rework         — amber bg + amber text
StatusBadge/Delivered      — green bg + muted text
StatusBadge/Cancelled      — gray bg + gray strikethrough

PRODUCTION STATE BADGES (wider pills):
Draft / FabricAllocated / Cutting / Tailoring /
KajaButton / Finishing / QC / Packing / ReadyForDelivery / Delivered

Color system:
Draft:            gray
FabricAllocated:  amber-light
Cutting:          amber
Tailoring:        blue-light
KajaButton:       blue
Finishing:        blue
QC:               gray-neutral + amber dot (no purple)
Packing:          neutral
ReadyForDelivery: green-light
Delivered:        green

ALERT TAGS (icon + label + left border 3px):
AlertTag/Overdue    — red bg / red border / alert-triangle icon
AlertTag/LowStock   — amber bg / amber border / alert icon
AlertTag/Approved   — green bg / green border / check-circle icon
AlertTag/Rework     — amber bg / amber border / refresh-cw icon

CATEGORY PILLS (line icons only — no emoji):
Used on order rows, activity feed.
Style: gray-100 bg / gray-700 text / radius-full / 12px
[shirt icon] Tailoring  |  [scissors icon] Cutting
[check icon] QC Done    |  [truck icon] Delivery
[wallet icon] Finance   |  [refresh icon] Rework

NUMERIC BADGE: 20px circle / red bg / white 11px 600

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 7 — METRIC CARDS WITH SPARKLINES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Most important card type — matches Sixpay's metric cards.
Figma: MetricCard/Positive/Default, MetricCard/Danger/Overdue, etc.

BASE STYLE:
Width ~220px / Padding 24px / Radius 20px
White bg / border 1px gray-100 / shadow-xs

CONTENT STRUCTURE:
Row 1: label 13px gray-500 + optional line icon right
Row 2: large number 40px 700 gray-900 (dominant)
Row 3: trend indicator (↑ 12% green / ↓ 3% red)
Row 4: mini sparkline 32px tall (full card width)

SPARKLINES: no axis, no labels — pure visual signal
Up trend:    green line + green area fill 8% opacity
Down trend:  red line + red area fill
Neutral:     amber line + amber area fill

FOUR VARIANTS:
MetricCard/Positive   — Today's Orders: 23 / ↑ 12.4% / green sparkline
MetricCard/Warning    — Pending Deliveries: 14 / — 0% / amber / amber left border 3px
MetricCard/Danger     — Overdue Items: 6 / ↑ 3 items / red sparkline / red left border 3px
MetricCard/Wide       — Monthly Revenue: ₹8,42,500 (spans 2 cols)
                        Income ₹9,20,000 green / Expenses ₹77,500 red
                        Bottom: bar sparkline (not line)

Dark mode preview for MetricCard/Positive:
Dark warm bg (#1C1917) / white number / same amber/green sparklines

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 8 — CHART LIBRARY (8 TYPES)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Each chart in its own white card. Title top-left. Period toggle top-right.
Minimal grid. Clean axes. Flat colors — NO gradients on bars.
Primary series: solid amber #D97706.
Comparison: gray-100 hatched diagonal pattern (Sixpay style).

── CHART 1: GROUPED BAR (main overview) ─────────
Title: Orders Completed | Weekly / Monthly pills
Two series: amber solid (this week) + gray hatched (last week)
X: Mon–Sun / Y: 0 5 10 15 20
Callout tooltip on tallest bar only

── CHART 2: MULTI-LINE AREA ─────────────────────
Title: Revenue Trend — last 30 days
Line 1 Revenue: amber solid, amber area fill 8%
Line 2 Collections: green solid, no fill
Line 3 Target: gray dashed, no fill
Y axis: ₹0 ₹2L ₹4L ₹6L ₹8L
Show tooltip callout with all 3 values

── CHART 3: DONUT / RING ────────────────────────
Title: Order Status
Center: 142 / Total Orders
Segments: Delivered 38% green / Production 28% amber /
          Ready 18% blue / Pending 12% gray / Overdue 4% red
2-column legend below with % values

── CHART 4: STACKED HORIZONTAL BAR ─────────────
Title: Branch Performance — Revenue this month
HQ:         solid amber bar / ₹4,20,000 / ↑ 8% green
Anna Nagar: shorter bar / ₹2,80,000 / ↓ 4% red
Velachery:  shorter / ₹1,90,000 / ↑ 2% green
Track: gray-100 / No gradient on bar fill

── CHART 5: SPARKLINE STRIP ─────────────────────
Title: Production Throughput — last 7 days
7 mini sparklines horizontal (Mon–Sun)
Each: 60px wide / 40px tall / no axis
Day label below / value label above
Up = green / Down = red per day

── CHART 6: VERTICAL KPI PROGRESS BARS ─────────
Title: Stage Completion Today
6 vertical bars: Cutting 72% / Tailoring 58% /
Kaja 85% / Finishing 40% / QC 91% / Packing 33%
Bar: solid amber / Track: gray-100 (no gradient)
% above each bar / stage label below / trend % under label

── CHART 7: CALENDAR HEAT MAP ───────────────────
Title: Delivery Schedule — June 2025
7×5 grid (Mon–Sun × weeks)
Cell: 36×36px rounded squares
Colors: gray-100 (0) → amber-100 → amber-300 → amber-500 → amber-700 (10+)
Today: amber border ring
Legend strip below

── CHART 8: RADIAL GAUGE (pair) ─────────────────
Title left: QC Pass Rate — 87% (amber, below target)
Title right: On-time Delivery — 94% (green, above target)
180° arc / track gray-100 / fill solid color
Large center number / small status text below

All charts: show Loading state (skeleton bars/lines)
            Empty state (no data illustration + message)
            Error state (error icon + request_id)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 9 — CARDS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ContentCard          — radius 20px / white / shadow-xs / border gray-100
                       Header: title 16px/600 + icon button right
                       Body: padding 20–24px
                       Hover: shadow-sm 150ms

RightPanelListCard   — Sixpay stock-panel style / 260px wide
                       Each row: 44px / avatar + name+subtitle + value+trend
                       Divider: 0.5px gray-100 / hover: warm cream tint
                       Examples: Order Feed, Top Tailors, Delivery Queue

KanbanCard           — 220px / radius 14px / shadow-xs
                       Left border 3px: none/red(overdue)/amber(rework)
                       Order code (mono) + category pill / customer 15px/600
                       Tailor name + due date + time-in-state
                       [Transition →] full-width pill button
                       Show: Card/Normal / Card/Overdue / Card/Rework

StaffCard            — 200px / radius 16px / avatar + name + role badge
                       Items/week / Avg time / Rework rate %
                       Rework % amber if >15%, red if >25%

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 10 — DATA TABLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Figma: DataTable/Dense/Default, DataTable/Dense/Selected

Table card wrapper: radius 20px / white / shadow-xs

Header: gray-50 bg / gray-500 12px uppercase / sort icon ↕
Row/Default:   52px / border 0.5px gray-50 / hover: warm cream
Row/Selected:  amber-50 bg + amber left border 2px
Row/Overdue:   red-50 bg + red left border 2px

Cell/Text:     14px gray-700
Cell/Mono:     JetBrains Mono 13px gray-900 (order codes)
Cell/Badge:    StatusBadge component
Cell/Category: category pill (line icon + text)
Cell/Amount:   14px 600 right-aligned Indian format ₹

Sample 5-col table: Order / Customer / Category / Status / Action

Empty: centered SVG icon + "No items found" + sub text + [Clear filters]
Pagination: "Showing 1–20 of 142" / amber active page pill

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 11 — NAVIGATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SIDEBAR — LIGHT (matches Sixpay — NOT dark)
Show: Expanded (240px) + Collapsed (68px)
Background: #FFFFFF (white)
Border-right: 1px gray-100

Logo: "SS" amber circle + "Solo Shirts" dark text

Nav item: 44px / radius 10px / outline icon + text
Inactive:  icon gray-400 / text gray-600 / transparent bg
Hover:     gray-50 bg / gray-700 text
Active:    amber-50 bg (pill) / amber icon / amber-700 text
           NO left border line — pure pill style like Sixpay

Sections: WORKSPACE / OPERATIONS / BUSINESS / ADMIN
Bottom: avatar + name + role + settings icon

Dark mode sidebar preview (warm dark):
bg #1C1917 / active: amber-50@15% amber text / same pill structure

TOPBAR — clean minimal
Height 64px / white / border-bottom gray-100
Left:   branch switcher pill "HQ ▾"
Center: search bar gray-100 bg / radius-full / Ctrl+K hint
Right:  [chat icon] [bell icon with badge] [avatar]

BOTTOM NAV — mobile only
64px / white / border-top / 5 tabs
Active: amber icon + amber text + amber dot
Show: Dashboard / Orders / Production / Inventory / More

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 12 — TABS + STEPPER + AVATAR + BREADCRUMB
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TABS — two styles:
  Pill tabs (Sixpay style): gray-100 container / white active pill / shadow-xs
    [Orders] [Measurements] [Balance] [Timeline]
  Underline tabs (inside cards): amber 2px underline / amber text active

STEPPER — 5 steps horizontal:
  Completed: amber-filled circle + white ✓
  Active: amber outline + amber number
  Upcoming: gray outline + gray number
  Connector: gray default / amber for completed

AVATAR — 4 sizes: 24 / 32 / 40 / 48px
  amber-100 bg / amber-700 initials
  Status dot on 40px+: green=online / gray=offline
  Avatar group: overlapping 4 + "+3" pill

BREADCRUMB: 13px / separator "/" gray-300
  Dashboard / Production / ORD-2026-00184

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 13 — OVERLAYS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DrawerPanel          — 480px right / shadow-xl / sticky footer [Cancel][Confirm]
Modal/Default        — 480px center / radius 20px / backdrop rgba(0,0,0,0.35)
ConfirmDialog/Warning — 400px / amber circle icon / [Cancel][Confirm amber]
ConfirmDialog/Danger  — 400px / red circle icon  / [Keep][Cancel red]
ToastNotification/Success  — 320px / green border-left 4px / check-circle icon
ToastNotification/Error    — red border / request_id mono + copy icon / [Retry]
ToastNotification/Warning  — amber border / alert icon / [View] link
All toasts: bottom-left (Sixpay notification card position)
            white bg / radius 16px / shadow-xl / auto-dismiss 4s

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 14 — SPECIALTY ERP COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ScanFeedbackBar/Success  — green-50 / check icon / "Customer found — Ramesh Kumar"
ScanFeedbackBar/Error    — red-50 / x icon / "Invalid QR" / request_id + copy

Timeline                 — vertical connector list
                           Dot 10px: latest=amber / others=gray
                           Connector: 2px gray-100
                           Row: time / actor / action / optional note

VersionDiffBadge         — v2: 40cm (gray strikethrough) → v3: 44cm (green bold)
                           Changed row: amber-50 highlight background

OverdueTag               — red-50 / pulsing red dot / "3 days overdue"

CurrencyDisplay/Large    — ₹1,42,500  40px 700
CurrencyDisplay/Normal   — ₹12,500    14px 600
CurrencyDisplay/Small    — ₹850       13px 400
RULE: Indian format ALWAYS (₹1,42,500 not ₹142,500)

RequestIdDisplay         — JetBrains Mono 12px / gray-100 bg / copy icon
                           REQ-7F2A91BC [⎘]

IdempotencyButton        — 4 states: [Confirm Order] / [⟳ Processing...] /
                           [✓ Confirmed] / [↺ Retry]

HelpTooltip              — dark card 220px / white text / 2 lines max
                           Triggered by ? (info circle icon)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 15 — ROLE-BASED UI STATES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Figma: PermissionState/Visible, PermissionState/Hidden, PermissionState/Locked

Show how same component behaves for different roles:
Roles: Owner/Admin / Front Desk / Measurement Staff /
       Production Supervisor / Cutting Master / Tailor /
       Kaja Button / QC Supervisor / Inventory Manager /
       Accountant / Delivery Staff

STATES TO SHOW:

Action/Visible       — button rendered, full opacity
Action/Hidden        — button not rendered (no placeholder)
Action/Disabled      — button grayed + tooltip "Permission required"
Action/Locked        — lock icon + "Owner approval required" text
Action/BranchLocked  — building icon + "Other branch data"

PermissionLockedState COMPONENT:
White card / professional lock line icon
Title: Permission required
Text: You do not have access to perform this action.
Caption: Contact your admin if this is needed.
No emoji. Use outline lock icon only.

EXAMPLE TABLE (matrix format):
Feature              | Owner | Supervisor | Tailor
Record payment       | ✓ Show| ✗ Hide    | ✗ Hide
Approve measurement  | ✓ Show| ✓ Show    | ✗ Hide
Rework override      | ✓ Show| ✓ Show    | ✗ Hide
Transition item      | ✓ Show| ✓ Show    | ✓ Own only
View finance         | ✓ Show| ✗ Hide    | ✗ Hide

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 16 — BRANCH-SCOPED UI COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

BranchSwitcher       — pill button "HQ ▾" / dropdown: branches list
                       Active branch: amber-50 bg + amber text
                       Owner only — non-owners don't see this

BranchTag            — small pill showing branch name
                       Used in Owner/Admin cross-branch views
                       Example: [building icon] HQ

BranchRestrictedNotice — info card
                         Info circle icon
                         "Viewing data for your assigned branch only."
                         Non-owners see this instead of switcher

BranchComparisonMiniCard — Owner/Admin only
                           Two branches side by side: Revenue / Pending / Overdue
                           ⚠ highlights where branch exceeds average

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 17 — QR / BARCODE COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

QrScannerInput       — search-style input / scan icon left
                       Placeholder: Scan QR or enter code manually

QrScannerPanel       — large scan area with dashed border
                       Scan-line animation area
                       Manual entry fallback below
                       Tabs: [Camera] [File upload] [Manual]

QrScanSuccess        — green state / check-circle icon
                       "Customer found — Ramesh Kumar"
                       Entity detail below

QrScanError          — red state / x-circle icon
                       "Invalid QR code"
                       Request ID: REQ-9382AB (mono + copy)
                       [Try again] button

QrCodeDisplay        — white card / QR preview (placeholder grid)
                       Entity name / Entity code (mono) below
                       [Download] [Print] buttons

BarcodeLabelPreview  — small print-label preview
                       Code bars + code text below
                       Used for fabric rolls, rack slots, bundles

ScanHistoryItem      — list row: time + type + code + result
                       Last 10 scans in scanner mode panel

USE CASES (show as labeled row):
Customer QR / Fabric roll QR / Cutting bundle QR /
Rack slot QR / Delivery confirmation QR

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 18 — PRINT / PDF COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Print visual rules:
• White bg / black text / amber only as brand accent
• No shadows / no gradients / high contrast
• QR must be clearly scannable
• All codes in JetBrains Mono

PREVIEWS TO SHOW (small thumbnails, labeled):

InvoicePreview/A4
  Header: Solo Shirts logo + branch + date + invoice number (mono)
  Customer section / Order items table
  Subtotal / GST breakdown / Total / Paid / Balance
  QR code bottom-right / "Thank you" footer

JobCardPreview/A5
  Order code (large mono) / Customer name
  Garment type + measurements grid
  Fabric reference / Production notes
  QR code / Stage checkboxes: ☐Cutting ☐Tailoring ☐QC ☐Packed

MeasurementSheetPreview/A5
  Version number + approval status
  Measurement fields in 2-column grid
  Approver name + timestamp + signature line

DeliverySlipPreview
  Customer / Items checklist / OTP area
  QR code / Signature line

FabricRollLabel (small sticker)
  Roll code (mono) / Fabric type / Color / Remaining meters / QR

RackSlotLabel (small)
  RACK-A12 (large) / QR below

BundleTagLabel
  Bundle ID (mono) / Order item / Stage / QR

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 19 — BULK ACTION COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

BulkActionToolbar    — appears above table on row selection
                       Sticky / white bg / shadow-sm
                       Left: "3 selected" amber pill
                       Center: action buttons (outline style)
                       Right: [Clear selection] ghost

Bulk actions shown:
[shirt icon] Assign Tailor  |  [printer icon] Print Job Cards
[download icon] Export      |  [check icon] Mark Ready
[truck icon] Dispatch       |  [more icon] More ▾

BulkActionMenu dropdown: Archive / Download PDF / Export CSV
Disabled actions: lock icon + tooltip "Select items in same stage"
Permission locked: lock icon + "Owner approval required"
Destructive action: opens ConfirmDialog before executing

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 20 — ADVANCED FILTER SYSTEM
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

AdvancedFilterBar/Desktop   — horizontal bar above table
AdvancedFilterBar/Tablet    — wraps to 2 rows
MobileFilterSheet           — full bottom sheet on mobile

Filter types available:
Search / Date range / Branch / Status / Production stage /
Tailor / Customer / Fabric type / Payment status /
Delivery status / Approval status / Overdue only toggle /
Rework only toggle

DateRangeFilter             — [Jun 1 – Jun 30 ▾] / calendar dropdown

FilterChip                  — active filter pill with remove icon
                              Status: Overdue [✕]
                              Stage: Cutting [✕]
                              Branch: HQ [✕]

Active filter bar (below main filter bar):
All chips shown / [Clear all] / [Save filter]

SavedFilterView (dropdown):
★ Today's Orders
★ Overdue Items
★ Ready for Delivery
★ Low Stock Rolls

Mobile: [Filter icon button] → opens bottom sheet
        Bottom sheet: full list of filters / [Apply] [Clear]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 21 — INVENTORY LEDGER COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CRITICAL RULE: Always show stock as 3 separate values.
Never merge into a single "stock" number.

InventoryLedgerCard      — white card / radius 20px
                           Top: Fabric Roll: LIN-WHT-009
                           Three metric strips:
                           Remaining: 24m  |  Reserved: 8m  |  Available: 16m
                           Each with own label, color, and icon

StockAvailabilityStrip   — horizontal 3-value bar
                           [📦 Remaining 24m] [🔒 Reserved 8m] [✓ Available 16m]
                           Color: gray / amber / green

StockMovementTimeline    — timeline of ledger movements
                           Types: Received / Reserved / Consumed /
                                  Released / Adjusted / Damaged
                           Each row: time / actor / type badge / qty / balance after

LowStockAlertCard        — amber warning card
                           [alert icon] White Linen LIN-WHT-009
                           Available: 4m / Threshold: 10m
                           [View Roll] button

StockAdjustmentPanel     — authorized users only
                           Warning: "This movement will be permanently audited."
                           Adjust type: adjust_in / adjust_out
                           Reason field (required)
                           Confirm with IdempotencyButton

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 22 — MEASUREMENT VISUALIZER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MeasurementGuideVisualizer   — clean shirt/garment outline
                               Neutral gray garment silhouette
                               Amber highlight on active measurement area
                               Tape-measure line indicator (amber)
                               Helper text below: "Around the fullest part..."

GarmentMeasurementMap        — full garment with labeled measurement areas
                               Each area: dot indicator + label
                               Clicking area = focuses input field
                               Focusing input = highlights area

Measurement fields mapped:
Chest / Shoulder / Sleeve / Shirt Length / Collar /
Waist / Hip / Pant Length / Bottom / Cuff / Pocket / Fit

MeasurementFieldGroup        — form group: label + input + unit + threshold warning
                               Vertical stack of measurement inputs

MeasurementThresholdWarning  — amber border input
                               "Above normal threshold" alert-triangle + text below

MeasurementVersionDiff       — version comparison card
                               Changed fields with amber-50 highlight background
                               Old value (gray strikethrough) → New value (green bold)

Version indicator:
"v3 — 15 Jan 2025 — Approved ✓" (green)
"v4 — Pending Approval" (amber) with warning

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 23 — APPROVAL WORKFLOW COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ApprovalStatusCard       — status badge + approver + timestamp
                           States: Pending/Approved/Rejected/Needs Correction

ApprovalActionPanel      — [Approve] success button + [Reject] danger button
                           Reject expands: reason textarea (required)
                           Both use IdempotencyButton

VersionDiffApprovalCard  — full measurement diff for approver review
                           Changed fields highlighted / [Approve] [Reject]

RejectionReasonPanel     — textarea + validation error state
                           "Rejection reason is required" red error text

ApprovalTimeline         — append-only history
                           Row: time / actor / decision badge / reason if rejected
                           Cannot edit or delete any entry

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 24 — SLA / OVERDUE COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SlaTimerBadge            — compact badge showing time state
                           On track: neutral / Near due: amber / Overdue: red / SLA breached: red

States to show:
[✓ 2h remaining]  |  [⚠ Due today]  |  [✗ 3 days overdue]  |  [✗ SLA breached]

SlaProgressBar           — thin horizontal bar
                           Green fill progressing to amber (near) to red (overdue)
                           No gradient — step color change at thresholds

StageTimeIndicator       — "4h in Tailoring" small gray text on kanban card
                           If over SLA: turns red

OverdueReasonPanel       — expands from overdue kanban card
                           Stage / Time delayed / Responsible actor / Next action

Color rules:
On track:  neutral or green indicator
Near due:  amber (not red)
Overdue:   red border + red OverdueTag
Rework:    amber border + amber tag (never red)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 25 — NOTIFICATION CENTER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NotificationBell         — bell icon + red numeric badge (unread count)

NotificationPanel        — right drawer or dropdown
                           Tabs: All / Unread / Alerts / System

NotificationListItem     — 4 rows:
                           Row 1: outline icon + title (bold if unread)
                           Row 2: description text
                           Row 3: time + status dot + [View] link
                           Unread: amber-50 bg + amber dot + bold title

Notification types (with outline icons):
[alert-triangle] 6 items overdue in Anna Nagar
[package] Low stock: White Linen — 4m left
[check-circle] Measurement v4 approved
[refresh-cw] QC rework required — ITEM-0092
[credit-card] Payment of ₹3,200 recorded
[truck] Delivery OTP confirmed — Ramesh Kumar
[message-square] Order ready message sent via WhatsApp ← customer communication log

Customer communication log row shows:
When WhatsApp/SMS was sent + to whom + message type
This tells staff "customer was already informed"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 26 — EXPORT / DOWNLOAD COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ExportMenu               — dropdown: PDF / Excel / CSV / Print
                           Each option: format icon + label + size estimate

DownloadButton           — 5 states:
                           [download] Export PDF
                           [⟳] Preparing file...
                           [↓] Downloading...
                           [✓] Downloaded
                           [✗] Failed — request_id shown

PdfDownloadCard          — document type / created date / file size
                           [Download] + [Regenerate if allowed] buttons

ReportJobStatusCard      — async report generation
                           States: Queued / Processing / Ready / Failed
                           Queued: gray spinner + "In queue..."
                           Processing: amber spinner + "Generating..."
                           Ready: green check + [Download Report] button
                           Failed: red × + request_id mono + [Retry]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 27 — DATA DENSITY MODES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DensityPreviewGroup — show same component in 3 modes side by side:

Comfortable (tablet/new user):
  Table row: 64px / Card padding: 24px
  More whitespace / Larger text / Bigger touch targets

Default:
  Table row: 52px / Card padding: 20px
  Balanced spacing

Compact (factory/power user):
  Table row: 40px / Card padding: 14px
  Tighter spacing — more data visible

Apply to: DataTable row / KanbanCard / InventoryListItem / FormGroup
Rule: Compact must still meet accessibility. Min touch target 44px.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 28 — KEYBOARD SHORTCUT COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

KeyboardShortcutHint     — small rounded pill / gray-100 bg / mono text
                           Example: [Ctrl] [K]  /  [Ctrl] [Enter]  /  [Esc]

ShortcutCommandMenu      — Ctrl+K global command palette
                           Search bar top / grouped results below

ShortcutHelpPanel        — modal or drawer listing shortcuts
                           Grouped by category:

Navigation: Ctrl+K (search) / Ctrl+F (filter)
Front Desk: Ctrl+N (new customer) / Ctrl+M (measurement) /
            Ctrl+O (add item) / Ctrl+Enter (confirm) / Ctrl+P (print)
Global:     Esc (close) / / (focus search)

Rule: Every action must still be clickable/tappable.
Shortcuts are helpers only.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 29 — MOBILE WORKFLOW COMPONENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MobileActionBar          — sticky bottom / white / border-top
                           Confirm / Save / Transition / Dispatch actions
                           [Cancel] ghost left + [Confirm Order] amber right

MobileScanButton         — large circular FAB
                           Amber bg / outline scan icon / 64×64px
                           Positioned bottom-right or center

MobileCardList           — replaces dense table on mobile
                           Each row becomes a card (same info, readable layout)
                           Swipe-to-reveal actions (optional)

MobileStepper            — compact 5-step progress for:
                           Front Desk order flow / Measurement entry
                           Current step: amber pill / Others: gray dots

MobileFilterSheet        — bottom sheet / max 80vh
                           Draggable handle top / filter list / sticky [Apply] [Clear]

Rule: Do NOT simply shrink desktop table on mobile.
Dense tables must become readable card lists.
Thumb-friendly. Min 44px touch targets always.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 30 — LOADING / EMPTY / ERROR (COMPLETE)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SKELETONS (subtle shimmer — only place gradients allowed)
PageSkeleton   — sidebar + topbar outline + content blocks
TableSkeleton  — 4 rows of gray bars, varying widths
ChartSkeleton  — bar chart outlines in gray-100
CardSkeleton   — header bar + 3 content lines + action bar
FormSkeleton   — label block + input block × 3

EMPTY STATES (centered SVG outline icon + text + CTA)
No orders yet          — Create first order
No customers found     — Search a different term or Create customer
No fabric rolls        — Add your first roll
No pending approvals   — Everything is approved ✓ (green tone)
No notifications       — All caught up ✓
No reports yet         — Run your first report
No deliveries today    — No dispatches scheduled

ERROR STATES (every error must show request_id)
ErrorState/Permission  — lock icon / "You don't have access" / Contact admin
ErrorState/Network     — wifi-off icon / "Connection failed" / [Retry]
ErrorState/Validation  — alert icon / field-level error messages
ErrorState/QRInvalid   — scan icon / "Invalid QR" / REQ-xxx (mono + copy)
ErrorState/ExportFailed — download icon / "Export failed" / REQ-xxx + [Retry]
ErrorState/StockOut    — package icon / "Insufficient stock available"
ErrorState/BackendGap  — info icon / "Feature pending backend confirmation"

InlineError            — red text + alert-triangle / connected to form field
RequestIdDisplay       — mono chip with copy button on ALL error states

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 31 — RESPONSIVE LAYOUT RULES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Show 3 mini device mockups side by side:

Desktop (1280px+):
White sidebar 240px + topbar 64px + content area
Right-panel list (Sixpay stock-panel pattern)
4-column metric card row / full tables

Tablet (768–1279px):
Sidebar collapses to icons 68px
2-column metric cards / horizontal-scroll tables
Drawers still from right side

Mobile (<768px):
No sidebar / bottom nav 64px
Compact topbar: logo + search icon + bell
Filters → MobileFilterSheet
Metric cards: 1-column stack
Tables → MobileCardList

Min touch target: 44×44px everywhere

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 32 — ACCESSIBILITY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ WCAG 2.2 AA minimum
✓ Focus ring: 2px amber / 2px offset / always visible
✓ All interactive elements keyboard-reachable
✓ Icon-only buttons have aria-label
✓ Color NEVER sole status indicator (always pair with icon or text)
✓ Tables: proper th headers
✓ Modals and drawers: focus trap
✓ Escape key closes modal/drawer
✓ Reduced motion preference respected
✓ Min touch target 44×44px

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 33 — DARK MODE COMPONENT PREVIEWS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Light mode is DEFAULT. Show full artboard in light mode.
Dark mode shown as SECONDARY previews only — not full artboard.

Show dark mode variants for these components only:
• MetricCard — dark warm bg / white numbers / same sparkline colors
• DataTable  — dark bg / white text / amber selected row
• SidebarNav — darker warm sidebar / same amber active pill
• ChartCard  — dark bg / amber bars / muted warm gray grid
• ToastNotification — dark bg / same border colors
• TextInput  — dark surface / white text / amber focus ring
• DrawerPanel — dark warm bg / white content

Dark mode chart rules:
Primary series: amber solid (unchanged)
Grid lines: muted warm gray (rgba white 10%)
Axis text: gray-400 equivalent warm
Background: #1C1917 (dark warm)
No neon / No bright saturated colors

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SECTION 34 — DESIGN RULES NOTE BOX
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Styled note card at very bottom of artboard.
Title: Solo Shirts ERP — Design System Rules

─ THEME ──────────────────────────────────────────
✓ LIGHT sidebar (white bg) — NOT dark sidebar
✓ Warm cream page background #FFFCF5
✓ Generous radius 16–20px on cards (Sixpay feel)
✓ Barely-visible shadows (shadow-xs default)
✓ Wide whitespace — premium and airy

─ COLOR ──────────────────────────────────────────
✓ Amber #D97706 is the ONLY primary accent
✓ Blue ONLY for "In Progress" and "Info"
✓ QC: gray neutral + amber indicator (no purple)
✓ No gradients except skeleton shimmer
✓ Status colors = meaning only, not decoration
✓ Sparklines: up = green / down = red

─ ICONS ──────────────────────────────────────────
✓ Outline line icons only (Lucide style, 1.75px stroke)
✓ No emoji icons anywhere
✓ No filled icons
✓ Icon-only buttons always have aria-label

─ TYPOGRAPHY ─────────────────────────────────────
✓ Metric numbers: 40px 700 dominant
✓ Currency: Indian format ₹1,42,500 ALWAYS
✓ Order codes / invoice numbers: JetBrains Mono
✓ request_id: JetBrains Mono + copy button

─ COMPONENTS ─────────────────────────────────────
✓ Every error shows request_id in monospace
✓ Overdue items: red left border 3px
✓ Rework items: amber left border 3px
✓ Metric cards always have sparklines
✓ Stock always shows 3 values: Remaining / Reserved / Available
✓ Measurements are versioned, never edit-in-place
✓ Finance is append-only, never editable
✓ Bulk action toolbar appears on row selection

─ FINAL RULE ─────────────────────────────────────
This sheet is the single source of truth.
Every ERP screen uses these components exactly.
Nothing is designed from scratch outside this system.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FINAL QA CHECKLIST (bottom of artboard)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Title: Solo Shirts ERP Design System — QA Checklist

☐ Component library only — no full dashboard page
☐ Light sidebar (white) — not dark
☐ Warm cream bg #FFFCF5 used throughout
☐ Sixpay used for mood only — not copied
☐ Amber only primary accent — no random colors
☐ Blue only for Info and In Progress
☐ No emoji icons — outline line icons only
☐ No undefined purple (QC uses gray+amber)
☐ No gradients except skeleton shimmer
☐ 8 chart types all shown with loading/empty/error
☐ Metric cards have sparklines
☐ Figma-style component naming throughout
☐ All button states: default/hover/loading/disabled
☐ All form states: default/focus/error/disabled
☐ QR and barcode components included
☐ Print and PDF previews included
☐ Inventory shows Remaining/Reserved/Available separately
☐ Measurement visualizer with garment map included
☐ Role and permission states included
☐ Branch-scoped UI components included
☐ Approval workflow components included
☐ SLA and overdue components included
☐ Bulk action toolbar included
☐ Advanced filter system included
☐ Notification center included
☐ Export/download states included
☐ Data density modes: comfortable/default/compact
☐ Keyboard shortcut components included
☐ Mobile-specific components included
☐ Loading/empty/error states for all contexts
☐ Dark mode token strip + selected component previews
☐ Accessibility rules checklist included
☐ request_id visible on every error state
☐ Indian currency format ₹1,42,500 everywhere
☐ JetBrains Mono for all codes and IDs
☐ Ready for developer handoff

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FINAL OUTPUT REQUIREMENT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Output: ONE long vertical artboard (~1440px wide).
All 34 sections above, clearly separated.
Every component labeled with Figma-style naming.
Premium, clean, airy (Sixpay-inspired feel).
Amber accent, warm cream background, light sidebar.
Developer-handoff ready.

DO NOT create: a dashboard / full screen / marketing page
DO NOT copy: Sixpay layout or Sixpay's green colors
ONLY create: a professional component library sheet
