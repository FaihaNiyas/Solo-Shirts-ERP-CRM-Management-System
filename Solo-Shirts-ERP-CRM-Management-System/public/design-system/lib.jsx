/* global React */
const { useState } = React;

/* ============================================================
   ICON SET — Lucide-style outline, 1.75 stroke, round caps
   ============================================================ */
const ICONS = {
  search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
  chat: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
  scissors: '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
  shirt: '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
  shield: '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
  checkCircle: '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
  truck: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
  receipt: '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
  card: '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
  package: '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
  layers: '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
  barChart: '<line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/>',
  sliders: '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
  scan: '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/>',
  qr: '<rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/>',
  printer: '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect width="12" height="8" x="6" y="14" rx="1"/>',
  download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
  upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
  xCircle: '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
  lock: '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  building: '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
  branch: '<line x1="6" x2="6" y1="3" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
  user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  calendar: '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
  alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
  info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
  chevronDown: '<path d="m6 9 6 6 6-6"/>',
  chevronRight: '<path d="m9 18 6-6-6-6"/>',
  chevronUp: '<path d="m18 15-6-6-6 6"/>',
  plus: '<path d="M5 12h14"/><path d="M12 5v14"/>',
  minus: '<path d="M5 12h14"/>',
  x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
  refresh: '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
  wifiOff: '<path d="M12 20h.01"/><path d="M8.5 16.429a5 5 0 0 1 7 0"/><path d="M5 12.859a10 10 0 0 1 5.17-2.69"/><path d="M19 12.859a10 10 0 0 0-2.007-1.523"/><path d="M2 8.82a15 15 0 0 1 4.177-2.643"/><path d="M22 8.82a15 15 0 0 0-11.288-3.764"/><path d="m2 2 20 20"/>',
  copy: '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
  ruler: '<path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-4.6-4.6a1 1 0 0 1 0-1.4L15.3 2.7a1 1 0 0 1 1.4 0l4.6 4.6a1 1 0 0 1 0 1.4Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/><path d="m4.5 13.5 2 2"/>',
  more: '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  arrowUp: '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
  arrowDown: '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
  trendUp: '<path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>',
  trendDown: '<path d="M16 17h6v-6"/><path d="m22 17-8.5-8.5-5 5L2 7"/>',
  settings: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
  dashboard: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
  file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
  filter: '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
  star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
  eye: '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
  clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
  smartphone: '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
  tablet: '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/>',
  monitor: '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
  moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
  keyboard: '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="M6 8h.01"/><path d="M10 8h.01"/><path d="M14 8h.01"/><path d="M18 8h.01"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/><path d="M7 16h10"/>',
  trash: '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
  edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
  pin: '<path d="M20 10c0 4.99-5.54 10.19-7.4 11.8a1 1 0 0 1-1.2 0C9.54 20.19 4 14.99 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
  headphones: '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
  workflow: '<rect width="8" height="8" x="3" y="3" rx="2"/><path d="M7 11v4a2 2 0 0 0 2 2h4"/><rect width="8" height="8" x="13" y="13" rx="2"/>',
  send: '<path d="M14.54 21.69a.5.5 0 0 0 .94-.02l6.5-19a.5.5 0 0 0-.64-.64l-19 6.5a.5.5 0 0 0-.02.94l7.93 3.18a2 2 0 0 1 1.11 1.11z"/><path d="m21.85 2.15-10.94 10.94"/>',
  command: '<path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3"/>',
  enter: '<polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/>',
  sort: '<path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/>',
  wallet: '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
  needle: '<path d="m3 21 4-4"/><path d="M21 3 8 16l-3 .5L5 19l2.5-.5L21 3Z"/><circle cx="18.5" cy="5.5" r="1"/>',
  box: '<rect width="18" height="14" x="3" y="6" rx="2"/><path d="M3 10h18"/><path d="M9 6V4"/>',
  zap: '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
  external: '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
};

function Icon({ name, size = 18, sw = 1.75, color, style, className }) {
  const html = ICONS[name] || ICONS.info;
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none"
      stroke={color || 'currentColor'} strokeWidth={sw} strokeLinecap="round"
      strokeLinejoin="round" className={className}
      style={{ flexShrink: 0, display: 'block', ...style }}
      dangerouslySetInnerHTML={{ __html: html }} />
  );
}

/* ============================================================
   LAYOUT PRIMITIVES
   ============================================================ */
function Section({ num, title, desc, children, alt }) {
  return (
    <section className="section" style={alt ? { background: 'rgba(255,255,255,0.45)' } : undefined}>
      <div className="section-head">
        <span className="section-num">{num}</span>
        <h2 className="section-title">{title}</h2>
      </div>
      {desc && <p className="section-desc">{desc}</p>}
      {children}
    </section>
  );
}

function GroupLabel({ children }) {
  return <p className="group-label">{children}</p>;
}

function Spec({ label, fig, note, children, stageStyle, stageClass = '', tokens }) {
  return (
    <div className="spec">
      {(label || fig) && (
        <div className="spec-label">{fig ? <><b>{fig}</b></> : label}</div>
      )}
      <div className={`spec-stage ${stageClass}`} style={stageStyle}>{children}</div>
      {note && <div className="spec-note">{note}</div>}
      {tokens && <div className="spec-note">{tokens.map((t, i) => <span key={i} className="tok" style={{ marginRight: 6 }}>{t}</span>)}</div>}
    </div>
  );
}

/* ============================================================
   CROSS-SECTION ATOMS
   ============================================================ */
const STATUS = {
  approved: { c: 'var(--color-success)', bg: 'var(--bg-success)', label: 'Approved', icon: 'checkCircle' },
  pending: { c: 'var(--color-warning)', bg: 'var(--bg-warning)', label: 'Pending', icon: 'clock' },
  rejected: { c: 'var(--color-danger)', bg: 'var(--bg-danger)', label: 'Rejected', icon: 'xCircle' },
  inprogress: { c: 'var(--color-info)', bg: 'var(--bg-info)', label: 'In Progress', icon: 'refresh' },
  draft: { c: 'var(--color-neutral)', bg: 'var(--bg-neutral)', label: 'Draft', icon: 'edit' },
  ready: { c: 'var(--color-success)', bg: 'var(--bg-success)', label: 'Ready', icon: 'checkCircle' },
  overdue: { c: 'var(--color-danger)', bg: 'var(--bg-danger)', label: 'Overdue', icon: 'alert' },
  rework: { c: 'var(--color-warning)', bg: 'var(--bg-warning)', label: 'Rework', icon: 'refresh' },
  delivered: { c: 'var(--color-success)', bg: 'var(--bg-success)', label: 'Delivered', icon: 'truck' },
  cancelled: { c: 'var(--color-neutral)', bg: 'var(--bg-neutral)', label: 'Cancelled', icon: 'x' },
};

function StatusBadge({ kind, pulse, strike }) {
  const s = STATUS[kind] || STATUS.draft;
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 6, padding: '3px 10px 3px 8px',
      borderRadius: 9999, background: s.bg, color: s.c, fontSize: 12, fontWeight: 500,
      lineHeight: 1.4, textDecoration: strike ? 'line-through' : 'none',
    }}>
      <span className={pulse ? 'pulse-dot' : ''} style={{ width: 7, height: 7, borderRadius: '50%', background: s.c }} />
      {s.label}
    </span>
  );
}

function CategoryPill({ icon, children, active }) {
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 11px 4px 9px',
      borderRadius: 9999, fontSize: 12, fontWeight: 500, lineHeight: 1.4,
      background: active ? 'var(--color-brand-light)' : 'var(--bg-neutral)',
      color: active ? 'var(--color-brand-dark)' : 'var(--color-gray-700)',
    }}>
      <Icon name={icon} size={13} sw={1.9} />{children}
    </span>
  );
}

function Btn({ variant = 'primary', size = 'md', icon, iconRight, disabled, loading, children, full, active, style }) {
  const sizes = {
    sm: { h: 32, px: 14, fs: 13, r: 9999 },
    md: { h: 40, px: 16, fs: 14, r: 10 },
    lg: { h: 48, px: 22, fs: 15, r: 12 },
  }[size];
  const variants = {
    primary: { bg: 'var(--color-brand)', col: '#fff', bd: 'transparent' },
    secondary: { bg: '#fff', col: 'var(--color-gray-700)', bd: 'var(--color-border-mid)' },
    ghost: { bg: 'transparent', col: 'var(--color-brand-dark)', bd: 'transparent' },
    danger: { bg: 'var(--color-danger)', col: '#fff', bd: 'transparent' },
    success: { bg: 'var(--color-success)', col: '#fff', bd: 'transparent' },
    outline: { bg: 'transparent', col: 'var(--color-text-secondary)', bd: 'var(--color-border-mid)' },
    pill: { bg: active ? 'var(--color-brand-light)' : 'var(--bg-neutral)', col: active ? 'var(--color-brand-dark)' : 'var(--color-gray-700)', bd: 'transparent' },
  }[variant];
  return (
    <button disabled={disabled || loading} style={{
      display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 8,
      height: sizes.h, padding: `0 ${sizes.px}px`, fontSize: sizes.fs, fontWeight: 600,
      fontFamily: 'var(--font-sans)', borderRadius: sizes.r, cursor: disabled ? 'not-allowed' : 'pointer',
      background: variants.bg, color: variants.col, border: `1px solid ${variants.bd}`,
      opacity: disabled ? 0.4 : loading ? 0.8 : 1, width: full ? '100%' : 'auto',
      transition: 'all .15s', whiteSpace: 'nowrap', ...style,
    }}>
      {loading && <Spinner color={variants.col} />}
      {!loading && icon && <Icon name={icon} size={sizes.fs + 2} sw={2} />}
      {loading ? 'Processing…' : children}
      {!loading && iconRight && <Icon name={iconRight} size={sizes.fs + 2} sw={2} />}
    </button>
  );
}

function Spinner({ color = 'currentColor', size = 15 }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" style={{ animation: 'spin 0.7s linear infinite' }}>
      <circle cx="12" cy="12" r="9" fill="none" stroke={color} strokeWidth="3" strokeOpacity="0.25" />
      <path d="M21 12a9 9 0 0 0-9-9" fill="none" stroke={color} strokeWidth="3" strokeLinecap="round" />
      <style>{`@keyframes spin{to{transform:rotate(360deg)}}`}</style>
    </svg>
  );
}

function IconBtn({ icon, label, badge, active }) {
  return (
    <button aria-label={label} style={{
      position: 'relative', width: 36, height: 36, display: 'inline-flex', alignItems: 'center',
      justifyContent: 'center', borderRadius: 10, cursor: 'pointer',
      background: active ? 'var(--color-brand-light)' : '#fff',
      color: active ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)',
      border: '1px solid var(--color-border-mid)',
    }}>
      <Icon name={icon} size={18} />
      {badge != null && (
        <span style={{
          position: 'absolute', top: -5, right: -5, minWidth: 18, height: 18, padding: '0 4px',
          borderRadius: 9999, background: 'var(--color-danger)', color: '#fff', fontSize: 11,
          fontWeight: 600, display: 'flex', alignItems: 'center', justifyContent: 'center',
          border: '2px solid #fff',
        }}>{badge}</span>
      )}
    </button>
  );
}

function Kbd({ children }) {
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', minWidth: 22, height: 22, padding: '0 6px',
      justifyContent: 'center', background: 'var(--bg-neutral)', borderRadius: 6,
      border: '1px solid var(--color-border-mid)', borderBottomWidth: 2,
      fontFamily: 'var(--font-mono)', fontSize: 11.5, fontWeight: 500, color: 'var(--color-gray-700)',
    }}>{children}</span>
  );
}

function Avatar({ initials, size = 40, dot, img }) {
  return (
    <span style={{ position: 'relative', display: 'inline-block', flexShrink: 0 }}>
      <span style={{
        width: size, height: size, borderRadius: '50%', background: 'var(--color-brand-light)',
        color: 'var(--color-brand-dark)', display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontWeight: 600, fontSize: size * 0.38,
      }}>{initials}</span>
      {dot && size >= 40 && (
        <span style={{
          position: 'absolute', bottom: 0, right: 0, width: size * 0.28, height: size * 0.28,
          borderRadius: '50%', border: '2px solid #fff',
          background: dot === 'online' ? 'var(--color-success)' : 'var(--color-neutral)',
        }} />
      )}
    </span>
  );
}

/* Sparkline: line + optional area fill */
function Sparkline({ data, color, fill, w = 160, h = 36, sw = 2 }) {
  const min = Math.min(...data), max = Math.max(...data), span = max - min || 1;
  const pts = data.map((v, i) => [(i / (data.length - 1)) * w, h - ((v - min) / span) * (h - 6) - 3]);
  const line = pts.map((p, i) => `${i ? 'L' : 'M'}${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
  const area = `${line} L${w} ${h} L0 ${h} Z`;
  return (
    <svg width={w} height={h} viewBox={`0 0 ${w} ${h}`} style={{ display: 'block', overflow: 'visible' }}>
      {fill && <path d={area} fill={color} opacity="0.1" />}
      <path d={line} fill="none" stroke={color} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round" />
      <circle cx={pts[pts.length - 1][0]} cy={pts[pts.length - 1][1]} r="2.5" fill={color} />
    </svg>
  );
}

/* QR placeholder (deterministic pattern) */
function QRGlyph({ size = 96, color = '#111827' }) {
  const n = 9, cells = [];
  for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) {
    const corner = (x < 3 && y < 3) || (x > 5 && y < 3) || (x < 3 && y > 5);
    const on = corner ? !(x === 1 + (x > 5 ? 6 : 0) && y === 1) && (x === 0 || x === 2 || y === 0 || y === 2 || (x === 1 && y === 1) ? true : false)
      : ((x * 7 + y * 13 + x * y) % 3 === 0);
    if (on || (corner && (x % 2 === 0 || y % 2 === 0))) cells.push([x, y]);
  }
  const c = size / n;
  return (
    <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} style={{ display: 'block' }}>
      <rect width={size} height={size} fill="#fff" />
      {cells.map(([x, y], i) => <rect key={i} x={x * c} y={y * c} width={c} height={c} fill={color} />)}
    </svg>
  );
}

/* Hatched bar fill pattern def — include once */
function HatchDefs() {
  return (
    <svg width="0" height="0" style={{ position: 'absolute' }} aria-hidden="true">
      <defs>
        <pattern id="hatch" patternUnits="userSpaceOnUse" width="7" height="7" patternTransform="rotate(45)">
          <rect width="7" height="7" fill="#F3F4F6" />
          <line x1="0" y1="0" x2="0" y2="7" stroke="#D9DCE1" strokeWidth="3" />
        </pattern>
        <pattern id="hatchAmber" patternUnits="userSpaceOnUse" width="7" height="7" patternTransform="rotate(45)">
          <rect width="7" height="7" fill="#FEF3C7" />
          <line x1="0" y1="0" x2="0" y2="7" stroke="#FCD34D" strokeWidth="3" />
        </pattern>
      </defs>
    </svg>
  );
}

Object.assign(window, {
  Icon, Section, GroupLabel, Spec, StatusBadge, CategoryPill, Btn, Spinner,
  IconBtn, Kbd, Avatar, Sparkline, QRGlyph, HatchDefs, STATUS,
});
