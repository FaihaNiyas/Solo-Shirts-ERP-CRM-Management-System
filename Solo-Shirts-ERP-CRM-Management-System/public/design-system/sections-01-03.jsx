/* global React, Icon, Section, GroupLabel, Spec */

/* ---------- SECTION 1: COLOR TOKENS ---------- */
function Swatch({ hex, name, token, note, big }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
      <div style={{
        height: big ? 84 : 64, borderRadius: 12, background: hex,
        border: '1px solid rgba(0,0,0,0.06)', boxShadow: 'inset 0 0 0 1px rgba(255,255,255,0.04)',
      }} />
      <div>
        <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--color-text-primary)' }}>{name}</div>
        <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-text-secondary)', marginTop: 2 }}>{token}</div>
        <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-text-muted)' }}>{hex}</div>
        {note && <div style={{ fontSize: 11.5, color: 'var(--color-text-muted)', marginTop: 5, lineHeight: 1.45 }}>{note}</div>}
      </div>
    </div>
  );
}

function StatusColorCard({ name, c, bg, icon, usage }) {
  return (
    <div style={{ border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 16, background: '#fff' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 14 }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '3px 10px 3px 8px', borderRadius: 9999, background: bg, color: c, fontSize: 12, fontWeight: 500 }}>
          <span style={{ width: 7, height: 7, borderRadius: '50%', background: c }} />{name}
        </span>
        <span style={{ marginLeft: 'auto', display: 'inline-flex', alignItems: 'center', gap: 5, padding: '3px 9px', borderRadius: 9999, border: `1px solid ${c}`, color: c, fontSize: 11.5, fontWeight: 500 }}>
          <Icon name={icon} size={12} sw={2} color={c} />outline
        </span>
      </div>
      <div style={{ display: 'flex', gap: 8 }}>
        <div style={{ flex: 1 }}>
          <div style={{ height: 28, borderRadius: 6, background: c }} />
          <div className="mono" style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 5 }}>{c}</div>
        </div>
        <div style={{ flex: 1 }}>
          <div style={{ height: 28, borderRadius: 6, background: bg, border: '1px solid var(--color-border-mid)' }} />
          <div className="mono" style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 5 }}>{bg}</div>
        </div>
      </div>
      <div style={{ fontSize: 12, color: 'var(--color-text-secondary)', marginTop: 12, lineHeight: 1.45 }}>{usage}</div>
    </div>
  );
}

function Section1() {
  return (
    <Section num="01" title="Color Tokens" desc="Amber Gold is the single primary accent. Blue is reserved exclusively for In Progress and Info. Status colors signal meaning, never decoration.">
      <GroupLabel>Brand — Amber Gold (only primary accent)</GroupLabel>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 24, marginBottom: 44 }}>
        <Swatch big hex="#D97706" name="Amber Gold" token="--color-brand" note="Primary CTA, active nav pill, focus rings, chart primary series." />
        <Swatch big hex="#FEF3C7" name="Amber Tint" token="--color-brand-light" note="Active nav background, selected rows, hover states." />
        <Swatch big hex="#B45309" name="Burnt Amber" token="--color-brand-dark" note="Pressed button, dark hover state." />
        <Swatch big hex="#FDE68A" name="Amber 200" token="--color-brand-muted" note="Sparkline lines in metric cards." />
      </div>

      <GroupLabel>Surfaces — warm light theme</GroupLabel>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(6,1fr)', gap: 20, marginBottom: 44 }}>
        <Swatch hex="#FFFCF5" name="Warm Cream" token="--color-bg" note="Page background — warm, never pure white." />
        <Swatch hex="#FFFFFF" name="White Sidebar" token="--color-sidebar-bg" note="LIGHT sidebar — not dark." />
        <Swatch hex="#FEF3C7" name="Amber Tint Pill" token="--color-sidebar-active" note="Active nav background." />
        <Swatch hex="#FFFFFF" name="Surface" token="--color-surface" note="Cards, panels, inputs." />
        <Swatch hex="#F9FAFB" name="Gray 50" token="--color-surface-alt" note="Table alt rows." />
        <Swatch hex="#F3F4F6" name="Gray 100" token="--color-border" note="Card borders (subtle)." />
      </div>

      <GroupLabel>Text</GroupLabel>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 20, marginBottom: 44 }}>
        <Swatch hex="#111827" name="Gray 900" token="--color-text-primary" note="Titles, numbers." />
        <Swatch hex="#6B7280" name="Gray 500" token="--color-text-secondary" note="Labels, descriptions." />
        <Swatch hex="#9CA3AF" name="Gray 400" token="--color-text-muted" note="Placeholders, metadata." />
        <Swatch hex="#FFFFFF" name="White" token="--color-text-inverse" note="On filled backgrounds." />
      </div>

      <GroupLabel>Status — filled chip · outline · dot</GroupLabel>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5,1fr)', gap: 16, marginBottom: 44 }}>
        <StatusColorCard name="Approved" c="#16A34A" bg="#DCFCE7" icon="checkCircle" usage="Approved, Delivered, Up trend." />
        <StatusColorCard name="Pending" c="#D97706" bg="#FEF3C7" icon="clock" usage="Pending, Rework — same hue as brand." />
        <StatusColorCard name="Rejected" c="#DC2626" bg="#FEE2E2" icon="xCircle" usage="Rejected, Overdue, Down trend." />
        <StatusColorCard name="In Progress" c="#2563EB" bg="#DBEAFE" icon="refresh" usage="In Progress, Info — ONLY use of blue." />
        <StatusColorCard name="Draft" c="#6B7280" bg="#F3F4F6" icon="edit" usage="Draft, Inactive, neutral." />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 24 }}>
        <div style={{ background: 'var(--color-brand-50)', border: '1px solid var(--color-brand-muted)', borderRadius: 14, padding: '16px 18px', display: 'flex', gap: 12 }}>
          <Icon name="info" size={18} color="var(--color-brand-dark)" style={{ marginTop: 1 }} />
          <div style={{ fontSize: 13, color: 'var(--color-gray-700)', lineHeight: 1.5 }}>
            <b style={{ color: 'var(--color-brand-dark)' }}>QC color fix.</b> QC never uses an undefined purple. QC badge = neutral gray <span className="mono">#F3F4F6</span> background with an amber indicator dot and <span className="mono">#374151</span> text.
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 10 }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 11px 4px 9px', borderRadius: 9999, background: '#F3F4F6', color: '#374151', fontSize: 12, fontWeight: 500, borderLeft: '3px solid #D97706' }}>
                <span style={{ width: 7, height: 7, borderRadius: '50%', background: '#D97706' }} />QC
              </span>
            </div>
          </div>
        </div>

        {/* Dark mode token strip */}
        <div style={{ background: '#1C1917', borderRadius: 14, padding: 18 }}>
          <div className="mono" style={{ fontSize: 11, letterSpacing: '0.12em', textTransform: 'uppercase', color: '#A8A29E', marginBottom: 14 }}>Dark mode token strip — secondary preview</div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 12 }}>
            {[['#0C0A09', 'bg-dark'], ['#1C1917', 'sidebar-dark'], ['#292524', 'surface-alt'], ['#3F3F46', 'border-dark'], ['#FAFAF9', 'text-dark'], ['#A8A29E', 'text-sec'], ['#D97706', 'brand'], ['#451A03', 'brand-bg']].map(([hex, t]) => (
              <div key={t}>
                <div style={{ height: 38, borderRadius: 8, background: hex, border: '1px solid rgba(255,255,255,0.08)' }} />
                <div className="mono" style={{ fontSize: 9.5, color: '#A8A29E', marginTop: 5 }}>{t}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 2: TYPOGRAPHY ---------- */
function TypeRow({ sample, name, spec, mono, style }) {
  return (
    <div style={{ display: 'grid', gridTemplateColumns: '1fr 320px', alignItems: 'baseline', gap: 24, padding: '16px 0', borderBottom: '1px solid var(--color-border)' }}>
      <div style={{ fontFamily: mono ? 'var(--font-mono)' : 'var(--font-sans)', color: 'var(--color-text-primary)', ...style }}>{sample}</div>
      <div>
        <div style={{ fontSize: 13.5, fontWeight: 600 }}>{name}</div>
        <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-text-secondary)', marginTop: 3 }}>{spec}</div>
      </div>
    </div>
  );
}

function Section2() {
  return (
    <Section num="02" title="Typography Scale" alt desc="Primary typeface Inter for all UI. JetBrains Mono for order codes, IDs, currency precision and request_ids.">
      <div style={{ display: 'flex', gap: 12, marginBottom: 28 }}>
        <span className="meta-pill" style={{ display: 'inline-flex', alignItems: 'center', gap: 8, padding: '8px 16px', borderRadius: 9999, border: '1px solid var(--color-border-mid)', background: '#fff', fontSize: 14 }}><b style={{ fontWeight: 700 }}>Aa</b> Inter — Primary</span>
        <span className="meta-pill mono" style={{ display: 'inline-flex', alignItems: 'center', gap: 8, padding: '8px 16px', borderRadius: 9999, border: '1px solid var(--color-border-mid)', background: '#fff', fontSize: 14 }}><b style={{ fontWeight: 700 }}>Aa</b> JetBrains Mono — Code</span>
      </div>
      <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: '8px 28px' }}>
        <TypeRow sample="₹1,42,500" name="Display" spec="40px / 700 / tracking -0.02" style={{ fontSize: 40, fontWeight: 700, letterSpacing: '-0.02em' }} />
        <TypeRow sample="Production Dashboard" name="H1" spec="24px / 600" style={{ fontSize: 24, fontWeight: 600 }} />
        <TypeRow sample="Today's Orders" name="H2" spec="20px / 600" style={{ fontSize: 20, fontWeight: 600 }} />
        <TypeRow sample="Active Items" name="H3" spec="16px / 600" style={{ fontSize: 16, fontWeight: 600 }} />
        <TypeRow sample="Customer Name" name="H4 — table headers" spec="14px / 600" style={{ fontSize: 14, fontWeight: 600 }} />
        <TypeRow sample="Long readable content for descriptions and notes." name="Body-L" spec="16px / 400 / lh 1.6" style={{ fontSize: 16, lineHeight: 1.6 }} />
        <TypeRow sample="Default UI text used across most interfaces." name="Body-M" spec="14px / 400 / lh 1.5" style={{ fontSize: 14, lineHeight: 1.5 }} />
        <TypeRow sample="Secondary info and timestamps." name="Body-S" spec="13px / 400 / lh 1.4" style={{ fontSize: 13, lineHeight: 1.4, color: 'var(--color-text-secondary)' }} />
        <TypeRow sample="Sublabels and axis labels" name="Caption" spec="12px / 400" style={{ fontSize: 12, color: 'var(--color-text-muted)' }} />
        <TypeRow mono sample="ORD-2026-000184" name="Mono" spec="JetBrains Mono · 13px / 500" style={{ fontSize: 13, fontWeight: 500 }} />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 320px', alignItems: 'baseline', gap: 24, padding: '16px 0' }}>
          <div style={{ display: 'flex', gap: 16 }}>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--color-success)', fontSize: 12, fontWeight: 500 }}><Icon name="trendUp" size={13} color="var(--color-success)" />12.4%</span>
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--color-danger)', fontSize: 12, fontWeight: 500 }}><Icon name="trendDown" size={13} color="var(--color-danger)" />3.2%</span>
          </div>
          <div>
            <div style={{ fontSize: 13.5, fontWeight: 600 }}>Trend</div>
            <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-text-secondary)', marginTop: 3 }}>12px / 500 · green up · red down</div>
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 3: SPACING / RADIUS / SHADOW ---------- */
function Section3() {
  const spacing = [4, 8, 12, 16, 20, 24, 32, 48, 64];
  const radii = [['sm', 4, 'micro badges'], ['md', 8, 'buttons, inputs'], ['lg', 12, 'standard cards'], ['xl', 16, 'large cards, modals'], ['2xl', 20, 'hero cards'], ['full', 28, 'pills, avatars']];
  const shadows = [['xs', 'var(--shadow-xs)', 'default card'], ['sm', 'var(--shadow-sm)', 'hover lift'], ['md', 'var(--shadow-md)', 'dropdowns'], ['lg', 'var(--shadow-lg)', 'drawers'], ['xl', 'var(--shadow-xl)', 'modals']];
  return (
    <Section num="03" title="Spacing · Radius · Shadow" desc="A 4px base spacing rhythm, generous card radii (16–20px) and barely-visible shadows create the airy, premium feel. No gradients anywhere except skeleton shimmer.">
      <div style={{ display: 'grid', gridTemplateColumns: '1.1fr 1fr', gap: 40 }}>
        <div>
          <GroupLabel>Spacing scale</GroupLabel>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {spacing.map(s => (
              <div key={s} style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
                <span className="mono" style={{ width: 44, fontSize: 12, color: 'var(--color-text-secondary)' }}>{s}px</span>
                <div style={{ height: 14, width: s * 3.4, background: 'var(--color-brand)', borderRadius: 3 }} />
              </div>
            ))}
          </div>
        </div>
        <div>
          <GroupLabel>Border radius</GroupLabel>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
            {radii.map(([name, r, use]) => (
              <div key={name}>
                <div style={{ height: 64, background: 'var(--color-brand-light)', border: '1.5px solid var(--color-brand-muted)', borderRadius: r === 28 ? 9999 : r }} />
                <div className="mono" style={{ fontSize: 11.5, fontWeight: 600, color: 'var(--color-brand-dark)', marginTop: 8 }}>radius-{name}</div>
                <div style={{ fontSize: 11.5, color: 'var(--color-text-muted)' }}>{use}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <GroupLabel>Shadows — very subtle</GroupLabel>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5,1fr)', gap: 24, background: 'var(--color-surface-alt)', padding: 32, borderRadius: 16 }}>
        {shadows.map(([name, sh, use]) => (
          <div key={name} style={{ textAlign: 'center' }}>
            <div style={{ height: 72, background: '#fff', borderRadius: 14, boxShadow: sh, border: '1px solid var(--color-border)' }} />
            <div className="mono" style={{ fontSize: 11.5, fontWeight: 600, color: 'var(--color-text-primary)', marginTop: 14 }}>shadow-{name}</div>
            <div style={{ fontSize: 11.5, color: 'var(--color-text-muted)' }}>{use}</div>
          </div>
        ))}
      </div>
    </Section>
  );
}

Object.assign(window, { Section1, Section2, Section3 });
