/* global React, Icon, Section, GroupLabel, Btn, StatusBadge, CategoryPill, Avatar, QRGlyph, Spinner */

function MonoChip({ children, copy }) {
  return <span className="mono" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 11.5, background: 'var(--bg-neutral)', padding: '3px 8px', borderRadius: 6, color: 'var(--color-gray-700)' }}>{children}{copy && <Icon name="copy" size={12} color="var(--color-text-muted)" />}</span>;
}

/* ---------- SECTION 14: SPECIALTY ERP ---------- */
function Section14() {
  return (
    <Section num="14" title="Specialty ERP Components" alt desc="The domain-specific atoms: scan feedback bars, append-only timelines, versioned measurement diffs, idempotent action buttons, and request_id displays. Currency always renders in Indian format.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 28 }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ScanFeedbackBar — Success / Error</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '12px 16px', borderRadius: 12, background: '#DCFCE7', color: '#15803D' }}><Icon name="checkCircle" size={20} color="#16A34A" /><span style={{ fontSize: 13.5, fontWeight: 500 }}>Customer found — Ramesh Kumar</span></div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '12px 16px', borderRadius: 12, background: '#FEE2E2', color: '#B91C1C' }}><Icon name="xCircle" size={20} color="#DC2626" /><span style={{ fontSize: 13.5, fontWeight: 500 }}>Invalid QR</span><span style={{ marginLeft: 'auto' }}><MonoChip copy>REQ-9382AB</MonoChip></span></div>
          </div>

          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '24px 0 10px' }}>Timeline — append-only</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 18 }}>
            {[['09:42', 'Suresh M.', 'Moved to Tailoring', true], ['08:15', 'Manoj R.', 'Cutting completed', false], ['Yesterday', 'Front Desk', 'Order created · ₹4,200', false]].map((r, i, a) => (
              <div key={i} style={{ display: 'flex', gap: 14 }}>
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}><span style={{ width: 10, height: 10, borderRadius: '50%', background: r[3] ? 'var(--color-brand)' : 'var(--color-border-mid)', border: r[3] ? '2px solid #FDE68A' : 'none' }} />{i < a.length - 1 && <span style={{ width: 2, flex: 1, background: 'var(--color-border)' }} />}</div>
                <div style={{ paddingBottom: i < a.length - 1 ? 18 : 0 }}><div style={{ fontSize: 13.5, fontWeight: 500 }}>{r[2]}</div><div style={{ fontSize: 12, color: 'var(--color-text-muted)', marginTop: 2 }}>{r[0]} · {r[1]}</div></div>
              </div>
            ))}
          </div>
        </div>

        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>VersionDiffBadge &amp; OverdueTag</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, padding: 14 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '8px 10px', borderRadius: 8, background: 'var(--color-brand-50)' }}>
              <span style={{ fontSize: 13, color: 'var(--color-text-secondary)' }}>Chest</span>
              <span className="mono" style={{ fontSize: 13, color: 'var(--color-text-muted)', textDecoration: 'line-through' }}>v2 · 40cm</span>
              <Icon name="chevronRight" size={14} color="var(--color-text-muted)" />
              <span className="mono" style={{ fontSize: 13, color: 'var(--color-success)', fontWeight: 700 }}>v3 · 44cm</span>
            </div>
          </div>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '6px 12px', borderRadius: 8, background: '#FEE2E2', color: '#DC2626', fontSize: 12.5, fontWeight: 500, marginTop: 14 }}><span className="pulse-dot" style={{ width: 7, height: 7, borderRadius: '50%', background: '#DC2626' }} />3 days overdue</div>

          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '24px 0 10px' }}>CurrencyDisplay &amp; RequestId</div>
          <div style={{ display: 'flex', alignItems: 'baseline', gap: 24, marginBottom: 14 }}>
            <span style={{ fontSize: 34, fontWeight: 700, letterSpacing: '-0.02em' }}>₹1,42,500</span>
            <span className="mono" style={{ fontSize: 14, fontWeight: 600 }}>₹12,500</span>
            <span className="mono" style={{ fontSize: 13 }}>₹850</span>
          </div>
          <MonoChip copy>REQ-7F2A91BC</MonoChip>

          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '24px 0 10px' }}>IdempotencyButton — 4 states</div>
          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
            <Btn variant="primary" size="sm">Confirm Order</Btn>
            <Btn variant="primary" size="sm" loading>Confirm Order</Btn>
            <Btn variant="success" size="sm" icon="check">Confirmed</Btn>
            <Btn variant="secondary" size="sm" icon="refresh">Retry</Btn>
          </div>
        </div>
      </div>
      <div style={{ marginTop: 22, display: 'flex', alignItems: 'center', gap: 12 }}>
        <span style={{ fontSize: 13, color: 'var(--color-text-secondary)' }}>HelpTooltip</span>
        <span style={{ position: 'relative' }}>
          <Icon name="info" size={16} color="var(--color-text-muted)" />
        </span>
        <span style={{ background: '#111827', color: '#fff', fontSize: 12, lineHeight: 1.4, padding: '10px 12px', borderRadius: 10, maxWidth: 220, boxShadow: 'var(--shadow-md)' }}>Measurements are versioned — confirming creates a new version, never edits the previous one.</span>
      </div>
    </Section>
  );
}

/* ---------- SECTION 15: ROLE-BASED UI ---------- */
function Section15() {
  const matrix = [['Record payment', 1, 0, 0], ['Approve measurement', 1, 1, 0], ['Rework override', 1, 1, 0], ['Transition item', 1, 1, 2], ['View finance', 1, 0, 0]];
  const cell = v => v === 1 ? <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--color-success)', fontSize: 12.5, fontWeight: 500 }}><Icon name="check" size={14} color="var(--color-success)" />Show</span>
    : v === 2 ? <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--color-warning)', fontSize: 12.5, fontWeight: 500 }}><Icon name="user" size={13} color="var(--color-warning)" />Own only</span>
    : <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: 'var(--color-text-muted)', fontSize: 12.5 }}><Icon name="x" size={13} color="var(--color-text-muted)" />Hide</span>;
  return (
    <Section num="15" title="Role-Based UI States" desc="The same action renders differently per role: visible, hidden (no placeholder), disabled with tooltip, or locked behind owner approval. Color is never the sole signal — every state carries an icon.">
      <GroupLabel>Action states</GroupLabel>
      <div className="spec-stage" style={{ display: 'flex', gap: 16, flexWrap: 'wrap', alignItems: 'flex-start' }}>
        <div style={{ textAlign: 'center' }}><Btn variant="primary" size="sm">Record Payment</Btn><div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>Visible</div></div>
        <div style={{ textAlign: 'center' }}><div style={{ height: 32, display: 'flex', alignItems: 'center', padding: '0 14px', border: '1px dashed var(--color-border-mid)', borderRadius: 8, color: 'var(--color-text-muted)', fontSize: 12 }}>not rendered</div><div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>Hidden</div></div>
        <div style={{ textAlign: 'center' }}><Btn variant="primary" size="sm" disabled>Record Payment</Btn><div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>Disabled + tooltip</div></div>
        <div style={{ textAlign: 'center' }}><Btn variant="secondary" size="sm" icon="lock">Owner approval</Btn><div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>Locked</div></div>
        <div style={{ textAlign: 'center' }}><Btn variant="secondary" size="sm" icon="building">Other branch</Btn><div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>BranchLocked</div></div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '300px 1fr', gap: 28, marginTop: 8 }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>PermissionLockedState</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 28, textAlign: 'center' }}>
            <span style={{ width: 52, height: 52, borderRadius: '50%', background: 'var(--bg-neutral)', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: 14 }}><Icon name="lock" size={24} color="var(--color-text-secondary)" /></span>
            <div style={{ fontSize: 15, fontWeight: 600 }}>Permission required</div>
            <div style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: '6px 0 4px', lineHeight: 1.5 }}>You do not have access to perform this action.</div>
            <div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>Contact your admin if this is needed.</div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Permission matrix</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, overflow: 'hidden' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1.6fr 1fr 1fr 1fr', background: 'var(--color-surface-alt)', padding: '11px 18px' }}>{['Feature', 'Owner', 'Supervisor', 'Tailor'].map(h => <span key={h} style={{ fontSize: 11.5, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.04em', color: 'var(--color-text-secondary)' }}>{h}</span>)}</div>
            {matrix.map((r, i) => <div key={i} style={{ display: 'grid', gridTemplateColumns: '1.6fr 1fr 1fr 1fr', padding: '12px 18px', borderTop: '0.5px solid var(--color-border)', alignItems: 'center' }}><span style={{ fontSize: 13.5, color: 'var(--color-gray-700)' }}>{r[0]}</span>{cell(r[1])}{cell(r[2])}{cell(r[3])}</div>)}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 16: BRANCH-SCOPED ---------- */
function Section16() {
  return (
    <Section num="16" title="Branch-Scoped UI" alt desc="Owners get a branch switcher and cross-branch comparison cards; everyone else sees a restricted notice scoped to their assigned branch.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1.2fr', gap: 24, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>BranchSwitcher — Owner</div>
          <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, padding: '9px 14px', borderRadius: 10, border: '1px solid var(--color-border-mid)', fontSize: 14, fontWeight: 500, background: '#fff' }}><Icon name="building" size={16} color="var(--color-text-secondary)" />HQ <Icon name="chevronDown" size={15} color="var(--color-text-muted)" /></span>
          <div style={{ marginTop: 6, width: 200, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, boxShadow: 'var(--shadow-md)', padding: 6 }}>
            {['HQ — Chennai', 'Anna Nagar', 'Velachery'].map((b, i) => <div key={b} style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 11px', borderRadius: 8, fontSize: 13.5, background: i === 0 ? 'var(--color-brand-light)' : 'transparent', color: i === 0 ? 'var(--color-brand-dark)' : 'var(--color-gray-700)', fontWeight: i === 0 ? 600 : 400 }}><Icon name="building" size={14} color={i === 0 ? 'var(--color-brand)' : 'var(--color-text-muted)'} />{b}</div>)}
          </div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '22px 0 10px' }}>BranchTag</div>
          <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 11px', borderRadius: 9999, background: 'var(--bg-neutral)', fontSize: 12.5, fontWeight: 500, color: 'var(--color-gray-700)' }}><Icon name="building" size={13} />HQ</span>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>BranchRestrictedNotice</div>
          <div style={{ display: 'flex', gap: 11, padding: 16, borderRadius: 12, background: 'var(--bg-info)', color: '#1E40AF' }}>
            <Icon name="info" size={19} color="var(--color-info)" style={{ marginTop: 1 }} />
            <div style={{ fontSize: 13, lineHeight: 1.5 }}>Viewing data for your assigned branch only. Non-owners cannot switch branches.</div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>BranchComparisonMiniCard — Owner</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1.4fr 1fr 1fr', gap: 8, fontSize: 11.5, color: 'var(--color-text-muted)', marginBottom: 8 }}><span>Branch</span><span>Revenue</span><span>Overdue</span></div>
            {[['HQ', '₹4,20,000', '2', false], ['Anna Nagar', '₹2,80,000', '6', true]].map(r => <div key={r[0]} style={{ display: 'grid', gridTemplateColumns: '1.4fr 1fr 1fr', gap: 8, padding: '8px 0', borderTop: '0.5px solid var(--color-border)', alignItems: 'center', fontSize: 13 }}><span style={{ fontWeight: 500 }}>{r[0]}</span><span className="mono" style={{ fontWeight: 600 }}>{r[1]}</span><span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: r[3] ? 'var(--color-danger)' : 'var(--color-gray-700)', fontWeight: r[3] ? 600 : 400 }}>{r[3] && <Icon name="alert" size={13} color="var(--color-danger)" />}{r[2]}</span></div>)}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 17: QR / BARCODE ---------- */
function Section17() {
  return (
    <Section num="17" title="QR / Barcode Components" desc="Scanner input, full scanner panel with camera/upload/manual tabs, success &amp; error feedback, printable QR cards and barcode labels for fabric rolls, bundles and rack slots.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.1fr 1fr', gap: 24, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>QrScannerInput</div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 9, height: 42, padding: '0 14px', borderRadius: 10, border: '1px solid var(--color-border-mid)', background: '#fff' }}><Icon name="scan" size={18} color="var(--color-text-secondary)" /><span style={{ flex: 1, fontSize: 13.5, color: 'var(--color-text-muted)' }}>Scan QR or enter code manually</span></div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '22px 0 10px' }}>QrScanSuccess / QrScanError</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            <div style={{ padding: 16, borderRadius: 12, background: '#DCFCE7' }}><div style={{ display: 'flex', alignItems: 'center', gap: 9, color: '#15803D' }}><Icon name="checkCircle" size={19} color="#16A34A" /><span style={{ fontSize: 13.5, fontWeight: 600 }}>Customer found</span></div><div style={{ fontSize: 13, color: '#166534', marginTop: 6 }}>Ramesh Kumar · ORD-2026-000184</div></div>
            <div style={{ padding: 16, borderRadius: 12, background: '#FEE2E2' }}><div style={{ display: 'flex', alignItems: 'center', gap: 9, color: '#B91C1C' }}><Icon name="xCircle" size={19} color="#DC2626" /><span style={{ fontSize: 13.5, fontWeight: 600 }}>Invalid QR code</span></div><div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 8 }}><MonoChip copy>REQ-9382AB</MonoChip><button style={{ fontSize: 12.5, fontWeight: 600, color: 'var(--color-danger)', background: 'none', border: 'none', cursor: 'pointer' }}>Try again</button></div></div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>QrScannerPanel</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            <div style={{ display: 'flex', gap: 4, background: 'var(--bg-neutral)', padding: 3, borderRadius: 9, marginBottom: 16 }}>{['Camera', 'File upload', 'Manual'].map((t, i) => <span key={t} style={{ flex: 1, textAlign: 'center', padding: '6px 0', borderRadius: 7, fontSize: 12.5, fontWeight: 500, background: i === 0 ? '#fff' : 'transparent', color: i === 0 ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)', boxShadow: i === 0 ? 'var(--shadow-xs)' : 'none' }}>{t}</span>)}</div>
            <div style={{ position: 'relative', height: 180, borderRadius: 12, border: '2px dashed var(--color-brand-muted)', background: 'var(--color-brand-50)', display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden' }}>
              <Icon name="qr" size={56} color="var(--color-brand-muted)" />
              <span className="scanline" style={{ position: 'absolute', left: 16, right: 16, height: 2, background: 'var(--color-brand)', boxShadow: '0 0 8px var(--color-brand)' }} />
            </div>
            <div style={{ textAlign: 'center', fontSize: 12.5, color: 'var(--color-text-muted)', marginTop: 12 }}>Point camera at the order QR code</div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>QrCodeDisplay</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 20, textAlign: 'center' }}>
            <div style={{ display: 'inline-block', padding: 10, border: '1px solid var(--color-border)', borderRadius: 12 }}><QRGlyph size={96} /></div>
            <div style={{ fontSize: 14, fontWeight: 600, marginTop: 12 }}>Ramesh Kumar</div>
            <div className="mono" style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>CUST-0041</div>
            <div style={{ display: 'flex', gap: 8, marginTop: 14 }}><Btn variant="secondary" size="sm" icon="download" full>Download</Btn><Btn variant="secondary" size="sm" icon="printer" full>Print</Btn></div>
          </div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '20px 0 10px' }}>BarcodeLabelPreview</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 10, padding: 14, textAlign: 'center' }}>
            <svg width="160" height="40" style={{ display: 'block', margin: '0 auto' }}>{Array.from({ length: 42 }).map((_, i) => <rect key={i} x={i * 3.7} y="0" width={(i % 3) + 1} height="40" fill="#111827" />)}</svg>
            <div className="mono" style={{ fontSize: 12, marginTop: 6 }}>LIN-WHT-009</div>
          </div>
        </div>
      </div>
      <GroupLabel>Use cases</GroupLabel>
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
        {[['user', 'Customer QR'], ['package', 'Fabric roll QR'], ['scissors', 'Cutting bundle QR'], ['box', 'Rack slot QR'], ['truck', 'Delivery confirmation QR']].map(([ic, l]) => <CategoryPill key={l} icon={ic}>{l}</CategoryPill>)}
      </div>
    </Section>
  );
}

/* ---------- SECTION 18: PRINT / PDF ---------- */
function PrintThumb({ title, tag, children, w = 200, h = 270 }) {
  return (
    <div>
      <div style={{ width: w, height: h, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 8, padding: 16, fontSize: 8.5, color: '#111827', overflow: 'hidden', position: 'relative' }}>{children}</div>
      <div style={{ fontSize: 12, fontWeight: 600, marginTop: 8 }}>{title}</div>
      <div className="mono" style={{ fontSize: 10.5, color: 'var(--color-text-muted)' }}>{tag}</div>
    </div>
  );
}
function Section18() {
  return (
    <Section num="18" title="Print / PDF Components" alt desc="Print surfaces drop shadows and gradients for high contrast: white background, black text, amber only as a brand accent. Codes use JetBrains Mono and QR codes stay clearly scannable.">
      <div style={{ display: 'flex', gap: 24, flexWrap: 'wrap' }}>
        <PrintThumb title="InvoicePreview" tag="A4">
          <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px solid #E5E7EB', paddingBottom: 8 }}><div><div style={{ display: 'flex', alignItems: 'center', gap: 4 }}><span style={{ width: 14, height: 14, borderRadius: 4, background: '#D97706', color: '#fff', fontSize: 7, display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700 }}>SS</span><b style={{ fontSize: 9 }}>Solo Shirts</b></div><div style={{ color: '#9CA3AF', marginTop: 3 }}>HQ · Chennai</div></div><div style={{ textAlign: 'right' }} className="mono"><div>INV-2026-0184</div><div style={{ color: '#9CA3AF' }}>14 Jun 2025</div></div></div>
          <div style={{ marginTop: 8, color: '#6B7280' }}>Bill to</div><div style={{ fontWeight: 600 }}>Ramesh Kumar</div>
          <div style={{ marginTop: 8 }}>{['Shirt — Linen ×2', 'Trouser — Cotton ×1'].map(l => <div key={l} style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '0.5px solid #F3F4F6', padding: '3px 0' }}><span>{l}</span><span className="mono">₹1,400</span></div>)}</div>
          <div style={{ position: 'absolute', bottom: 14, left: 16, right: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}><div style={{ background: '#000', width: 38, height: 38 }}><QRGlyph size={38} /></div><div style={{ textAlign: 'right' }}><div>Subtotal ₹3,800</div><div>GST 5% ₹190</div><b style={{ fontSize: 11 }}>Total ₹3,990</b></div></div>
        </PrintThumb>
        <PrintThumb title="JobCardPreview" tag="A5" h={230}>
          <div className="mono" style={{ fontSize: 14, fontWeight: 600 }}>ORD-184</div>
          <div style={{ fontWeight: 600, marginTop: 2 }}>Ramesh Kumar — Shirt</div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 4, marginTop: 8, color: '#6B7280' }}>{['Chest 44', 'Sleeve 24', 'Collar 16', 'Length 30'].map(m => <span key={m}>{m}cm</span>)}</div>
          <div style={{ marginTop: 8, color: '#6B7280' }}>Fabric: LIN-WHT-009</div>
          <div style={{ position: 'absolute', bottom: 14, left: 16, right: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}><div>{['Cutting', 'Tailoring', 'QC', 'Packed'].map(s => <div key={s} style={{ display: 'flex', alignItems: 'center', gap: 3 }}><span style={{ width: 8, height: 8, border: '1px solid #111' }} />{s}</div>)}</div><QRGlyph size={38} /></div>
        </PrintThumb>
        <PrintThumb title="MeasurementSheet" tag="A5" h={230}>
          <div style={{ display: 'flex', justifyContent: 'space-between' }}><b>Measurement</b><span style={{ color: '#16A34A' }}>v3 ✓ Approved</span></div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 4, marginTop: 10 }}>{['Chest 44', 'Shoulder 18', 'Sleeve 24', 'Length 30', 'Waist 36', 'Hip 40'].map(m => <div key={m} style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '0.5px solid #F3F4F6' }}><span style={{ color: '#6B7280' }}>{m.split(' ')[0]}</span><span className="mono">{m.split(' ')[1]}</span></div>)}</div>
          <div style={{ position: 'absolute', bottom: 14, left: 16, right: 16, color: '#9CA3AF' }}>Approved by Supervisor · 15 Jan<div style={{ borderTop: '1px solid #111', width: 70, marginTop: 14 }} /></div>
        </PrintThumb>
        <PrintThumb title="DeliverySlip" tag="—" h={230}>
          <b>Delivery Slip</b>
          <div style={{ marginTop: 6 }}>Ramesh Kumar</div>
          <div style={{ marginTop: 8 }}>{['Shirt ×2', 'Trouser ×1'].map(i => <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 4 }}><span style={{ width: 8, height: 8, border: '1px solid #111' }} />{i}</div>)}</div>
          <div style={{ marginTop: 10, padding: 6, border: '1px dashed #D97706', textAlign: 'center', color: '#B45309' }}>OTP: ____ ____</div>
          <div style={{ position: 'absolute', bottom: 14, left: 16, right: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}><div style={{ borderTop: '1px solid #111', width: 60 }}>Signature</div><QRGlyph size={36} /></div>
        </PrintThumb>
      </div>
      <GroupLabel>Small labels — stickers</GroupLabel>
      <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
        {[['FabricRollLabel', 'LIN-WHT-009', 'White Linen · 24m'], ['RackSlotLabel', 'RACK-A12', 'Aisle A'], ['BundleTagLabel', 'BDL-0092', 'Tailoring stage']].map(([t, code, sub]) => (
          <div key={t}><div style={{ width: 150, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 8, padding: 12, display: 'flex', alignItems: 'center', gap: 10 }}><QRGlyph size={44} /><div><div className="mono" style={{ fontSize: 12, fontWeight: 600 }}>{code}</div><div style={{ fontSize: 10, color: 'var(--color-text-muted)' }}>{sub}</div></div></div><div className="mono" style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>{t}</div></div>
        ))}
      </div>
    </Section>
  );
}

/* ---------- SECTION 19: BULK ACTIONS ---------- */
function Section19() {
  return (
    <Section num="19" title="Bulk Action Components" desc="Selecting table rows raises a sticky toolbar with a count pill, outline action buttons and a clear-selection ghost. Destructive bulk actions open a confirm dialog; locked actions show a lock icon and reason.">
      <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, boxShadow: 'var(--shadow-sm)', padding: '12px 18px', display: 'flex', alignItems: 'center', gap: 14, flexWrap: 'wrap' }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '5px 12px', borderRadius: 9999, background: 'var(--color-brand-light)', color: 'var(--color-brand-dark)', fontSize: 13, fontWeight: 600 }}>3 selected</span>
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          <Btn variant="outline" size="sm" icon="shirt">Assign Tailor</Btn>
          <Btn variant="outline" size="sm" icon="printer">Print Job Cards</Btn>
          <Btn variant="outline" size="sm" icon="download">Export</Btn>
          <Btn variant="outline" size="sm" icon="checkCircle">Mark Ready</Btn>
          <Btn variant="outline" size="sm" icon="truck">Dispatch</Btn>
          <Btn variant="outline" size="sm" iconRight="chevronDown">More</Btn>
        </div>
        <button style={{ marginLeft: 'auto', display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 13, fontWeight: 500, color: 'var(--color-text-secondary)', background: 'none', border: 'none', cursor: 'pointer' }}><Icon name="x" size={15} />Clear selection</button>
      </div>
      <div style={{ display: 'flex', gap: 20, marginTop: 20, flexWrap: 'wrap' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>BulkActionMenu</div>
          <div style={{ width: 210, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, boxShadow: 'var(--shadow-md)', padding: 6 }}>
            {[['file', 'Download PDF', false], ['download', 'Export CSV', false], ['box', 'Archive', false], ['trash', 'Delete', 'danger'], ['lock', 'Bulk approve', 'locked']].map(([ic, l, st]) => (
              <div key={l} style={{ display: 'flex', alignItems: 'center', gap: 9, padding: '8px 10px', borderRadius: 8, fontSize: 13.5, color: st === 'danger' ? 'var(--color-danger)' : st === 'locked' ? 'var(--color-text-muted)' : 'var(--color-gray-700)' }}><Icon name={ic} size={16} color={st === 'danger' ? 'var(--color-danger)' : 'var(--color-text-muted)'} />{l}{st === 'locked' && <span style={{ marginLeft: 'auto', fontSize: 10.5 }}>Owner only</span>}</div>
            ))}
          </div>
        </div>
        <div style={{ flex: 1, minWidth: 260, alignSelf: 'flex-start' }}>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Disabled action — mixed stages</div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: 14, borderRadius: 12, background: 'var(--bg-neutral)', color: 'var(--color-text-secondary)' }}><Icon name="lock" size={17} /><span style={{ fontSize: 13 }}>Select items in the same stage to transition together.</span></div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 20: ADVANCED FILTERS ---------- */
function FilterPill({ children, chevron }) {
  return <span style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '8px 13px', borderRadius: 10, border: '1px solid var(--color-border-mid)', background: '#fff', fontSize: 13, fontWeight: 500, color: 'var(--color-gray-700)' }}>{children}{chevron && <Icon name="chevronDown" size={14} color="var(--color-text-muted)" />}</span>;
}
function Chip({ label, value }) {
  return <span style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '5px 9px 5px 11px', borderRadius: 9999, background: 'var(--color-brand-light)', color: 'var(--color-brand-dark)', fontSize: 12.5, fontWeight: 500 }}>{label}: <b>{value}</b><Icon name="x" size={13} color="var(--color-brand-dark)" /></span>;
}
function Section20() {
  return (
    <Section num="20" title="Advanced Filter System" alt desc="A horizontal filter bar on desktop, wrapping on tablet and collapsing into a bottom sheet on mobile. Active filters appear as removable chips below, with saved views for common queries.">
      <GroupLabel>AdvancedFilterBar/Desktop</GroupLabel>
      <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, height: 38, padding: '0 13px', borderRadius: 10, background: 'var(--bg-neutral)', minWidth: 220 }}><Icon name="search" size={16} color="var(--color-text-muted)" /><span style={{ fontSize: 13, color: 'var(--color-text-muted)' }}>Search…</span></div>
        <FilterPill chevron><Icon name="calendar" size={15} color="var(--color-text-secondary)" />Jun 1 – Jun 30</FilterPill>
        <FilterPill chevron><Icon name="building" size={15} color="var(--color-text-secondary)" />Branch</FilterPill>
        <FilterPill chevron>Status</FilterPill>
        <FilterPill chevron>Stage</FilterPill>
        <FilterPill chevron>Tailor</FilterPill>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, padding: '8px 13px', borderRadius: 10, border: '1px solid var(--color-brand-muted)', background: 'var(--color-brand-50)', fontSize: 13, fontWeight: 500, color: 'var(--color-brand-dark)' }}>Overdue only <span style={{ width: 32, height: 18, borderRadius: 9999, background: 'var(--color-brand)', padding: 2, display: 'inline-flex', justifyContent: 'flex-end' }}><span style={{ width: 14, height: 14, borderRadius: '50%', background: '#fff' }} /></span></span>
        <button style={{ display: 'inline-flex', alignItems: 'center', gap: 6, height: 38, padding: '0 13px', borderRadius: 10, border: '1px solid var(--color-border-mid)', background: '#fff', fontSize: 13, fontWeight: 500, color: 'var(--color-text-secondary)' }}><Icon name="filter" size={15} />More filters</button>
      </div>
      <GroupLabel>Active filters</GroupLabel>
      <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
        <Chip label="Status" value="Overdue" /><Chip label="Stage" value="Cutting" /><Chip label="Branch" value="HQ" />
        <button style={{ fontSize: 13, fontWeight: 600, color: 'var(--color-danger)', background: 'none', border: 'none', cursor: 'pointer' }}>Clear all</button>
        <button style={{ display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 13, fontWeight: 600, color: 'var(--color-brand-dark)', background: 'none', border: 'none', cursor: 'pointer' }}><Icon name="star" size={14} color="var(--color-brand)" />Save filter</button>
      </div>
      <div style={{ display: 'flex', gap: 28, marginTop: 8 }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>SavedFilterView</div>
          <div style={{ width: 220, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, boxShadow: 'var(--shadow-md)', padding: 6 }}>
            {["Today's Orders", 'Overdue Items', 'Ready for Delivery', 'Low Stock Rolls'].map(v => <div key={v} style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 10px', borderRadius: 8, fontSize: 13.5, color: 'var(--color-gray-700)' }}><Icon name="star" size={14} color="var(--color-brand)" />{v}</div>)}
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>MobileFilterSheet</div>
          <div style={{ width: 220, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: '16px 16px 0 0', boxShadow: 'var(--shadow-lg)', padding: 16 }}>
            <div style={{ width: 38, height: 4, borderRadius: 9999, background: 'var(--color-border-mid)', margin: '0 auto 14px' }} />
            <div style={{ fontSize: 14, fontWeight: 600, marginBottom: 12 }}>Filters</div>
            {['Status', 'Stage', 'Tailor'].map(f => <div key={f} style={{ display: 'flex', justifyContent: 'space-between', padding: '9px 0', borderTop: '0.5px solid var(--color-border)', fontSize: 13.5 }}>{f}<Icon name="chevronRight" size={15} color="var(--color-text-muted)" /></div>)}
            <div style={{ display: 'flex', gap: 8, marginTop: 14 }}><Btn variant="secondary" size="sm" full>Clear</Btn><Btn variant="primary" size="sm" full>Apply</Btn></div>
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 21: INVENTORY LEDGER ---------- */
function StockStrip({ icon, label, value, color, bg }) {
  return (
    <div style={{ flex: 1, padding: 14, borderRadius: 12, background: bg }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 6, color, fontSize: 11.5, fontWeight: 500, marginBottom: 6 }}><Icon name={icon} size={14} color={color} />{label}</div>
      <div style={{ fontSize: 22, fontWeight: 700, color: 'var(--color-text-primary)' }}>{value}</div>
    </div>
  );
}
function Section21() {
  return (
    <Section num="21" title="Inventory Ledger Components" desc="Critical rule — stock is always three separate values: Remaining, Reserved and Available. Never merged. Movements are an audited append-only timeline.">
      <div style={{ display: 'grid', gridTemplateColumns: '1.3fr 1fr', gap: 28, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>InventoryLedgerCard</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 20, padding: 22, boxShadow: 'var(--shadow-xs)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 16 }}><Icon name="package" size={20} color="var(--color-text-secondary)" /><div><div style={{ fontSize: 15, fontWeight: 600 }}>White Linen</div><div className="mono" style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>LIN-WHT-009</div></div></div>
            <div style={{ display: 'flex', gap: 12 }}>
              <StockStrip icon="package" label="Remaining" value="24m" color="var(--color-gray-700)" bg="var(--bg-neutral)" />
              <StockStrip icon="lock" label="Reserved" value="8m" color="var(--color-warning)" bg="var(--bg-warning)" />
              <StockStrip icon="checkCircle" label="Available" value="16m" color="var(--color-success)" bg="var(--bg-success)" />
            </div>
          </div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '22px 0 10px' }}>StockMovementTimeline</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 18 }}>
            {[['Received', '+40m', 'var(--color-success)', '40m', 'Inventory Mgr'], ['Reserved', '-8m', 'var(--color-warning)', '32m', 'ORD-184'], ['Consumed', '-16m', 'var(--color-danger)', '16m', 'Cutting'], ['Adjusted', '+8m', 'var(--color-info)', '24m', 'Audit']].map((r, i, a) => (
              <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '9px 0', borderTop: i ? '0.5px solid var(--color-border)' : 'none' }}>
                <span style={{ fontSize: 11.5, color: 'var(--color-text-muted)', width: 70 }}>{['09:42', '08:15', 'Yest.', '2d'][i]}</span>
                <span style={{ fontSize: 12, fontWeight: 500, padding: '2px 9px', borderRadius: 9999, background: 'var(--bg-neutral)', color: 'var(--color-gray-700)' }}>{r[0]}</span>
                <span style={{ fontSize: 12, color: 'var(--color-text-muted)', flex: 1 }}>{r[4]}</span>
                <span className="mono" style={{ fontSize: 13, fontWeight: 600, color: r[2] }}>{r[1]}</span>
                <span className="mono" style={{ fontSize: 12, color: 'var(--color-text-muted)', width: 44, textAlign: 'right' }}>{r[3]}</span>
              </div>
            ))}
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>LowStockAlertCard</div>
          <div style={{ background: 'var(--bg-warning)', border: '1px solid var(--color-brand-muted)', borderRadius: 16, padding: 18 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 9, color: 'var(--color-warning)', marginBottom: 10 }}><Icon name="alert" size={18} color="var(--color-warning)" /><span style={{ fontSize: 13.5, fontWeight: 600, color: '#92400E' }}>White Linen — LIN-WHT-009</span></div>
            <div style={{ display: 'flex', gap: 20, fontSize: 13, color: '#92400E' }}><span>Available: <b>4m</b></span><span>Threshold: <b>10m</b></span></div>
            <Btn variant="secondary" size="sm" style={{ marginTop: 14 }}>View Roll</Btn>
          </div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '22px 0 10px' }}>StockAdjustmentPanel — authorized</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            <div style={{ display: 'flex', gap: 9, padding: 11, borderRadius: 10, background: 'var(--bg-warning)', color: '#92400E', fontSize: 12, marginBottom: 14 }}><Icon name="info" size={16} color="var(--color-warning)" style={{ marginTop: 1 }} />This movement will be permanently audited.</div>
            <div style={{ display: 'flex', gap: 8, marginBottom: 12 }}><Btn variant="pill" size="sm" active>adjust_in</Btn><Btn variant="pill" size="sm">adjust_out</Btn></div>
            <div style={{ minHeight: 50, padding: 11, borderRadius: 10, border: '1px solid var(--color-border-mid)', fontSize: 12.5, color: 'var(--color-text-muted)' }}>Reason (required)…</div>
            <Btn variant="primary" size="sm" full style={{ marginTop: 12 }}>Confirm adjustment</Btn>
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 22: MEASUREMENT VISUALIZER ---------- */
function Section22() {
  const fields = [['Chest', '44', true], ['Shoulder', '18', false], ['Sleeve', '24', false], ['Length', '30', false], ['Collar', '16', false], ['Waist', '36', false]];
  return (
    <Section num="22" title="Measurement Visualizer" alt desc="A neutral garment silhouette highlights the active measurement area in amber. Focusing a field highlights its area and vice-versa. Version diffs surface changed fields with old (strikethrough) → new (green) values.">
      <div style={{ display: 'grid', gridTemplateColumns: '260px 1fr 1fr', gap: 28, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>GarmentMeasurementMap</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 20, textAlign: 'center' }}>
            <svg width="180" height="190" viewBox="0 0 180 190">
              <path d="M60 30 L40 45 L30 75 L45 85 L52 70 L52 165 L128 165 L128 70 L135 85 L150 75 L140 45 L120 30 L108 38 Q90 52 72 38 Z" fill="#F9FAFB" stroke="#D1D5DB" strokeWidth="1.5" strokeLinejoin="round" />
              <line x1="52" y1="92" x2="128" y2="92" stroke="#D97706" strokeWidth="2" strokeDasharray="4 3" />
              <circle cx="52" cy="92" r="3.5" fill="#D97706" /><circle cx="128" cy="92" r="3.5" fill="#D97706" />
              <rect x="74" y="80" width="32" height="20" rx="5" fill="#D97706" /><text x="90" y="94" textAnchor="middle" fontSize="11" fontWeight="600" fill="#fff">Chest</text>
            </svg>
            <div style={{ fontSize: 12.5, color: 'var(--color-text-secondary)', marginTop: 8, lineHeight: 1.5 }}>Measure around the fullest part of the chest, keeping the tape level.</div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>MeasurementFieldGroup</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            {fields.map(([f, v, active]) => (
              <div key={f} style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 10 }}>
                <span style={{ width: 80, fontSize: 13, color: 'var(--color-gray-700)' }}>{f}</span>
                <div style={{ display: 'flex', flex: 1 }}>
                  <div style={{ flex: 1, height: 36, display: 'flex', alignItems: 'center', padding: '0 11px', borderRadius: '8px 0 0 8px', border: `1px solid ${active ? 'var(--color-brand)' : 'var(--color-border-mid)'}`, background: '#fff', boxShadow: active ? '0 0 0 3px rgba(217,119,6,0.16)' : 'none', fontSize: 13.5 }}>{v}</div>
                  <span style={{ display: 'inline-flex', alignItems: 'center', padding: '0 10px', height: 36, background: 'var(--bg-neutral)', border: '1px solid var(--color-border-mid)', borderLeft: 'none', borderRadius: '0 8px 8px 0', fontSize: 12, color: 'var(--color-text-secondary)' }}>cm</span>
                </div>
              </div>
            ))}
            <div style={{ display: 'flex', alignItems: 'center', gap: 5, color: 'var(--color-warning)', fontSize: 12, marginTop: 2 }}><Icon name="alert" size={13} color="var(--color-warning)" />Sleeve above normal threshold</div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>MeasurementVersionDiff</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 12.5, color: 'var(--color-warning)', fontWeight: 600 }}><Icon name="clock" size={14} color="var(--color-warning)" />v4 — Pending Approval</span>
            </div>
            {[['Chest', '40', '44'], ['Sleeve', '23', '24']].map(([f, o, n]) => (
              <div key={f} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '9px 11px', borderRadius: 8, background: 'var(--color-brand-50)', marginBottom: 8 }}>
                <span style={{ width: 70, fontSize: 13, color: 'var(--color-text-secondary)' }}>{f}</span>
                <span className="mono" style={{ fontSize: 13, color: 'var(--color-text-muted)', textDecoration: 'line-through' }}>{o}cm</span>
                <Icon name="chevronRight" size={14} color="var(--color-text-muted)" />
                <span className="mono" style={{ fontSize: 13, fontWeight: 700, color: 'var(--color-success)' }}>{n}cm</span>
              </div>
            ))}
            <div style={{ fontSize: 12, color: 'var(--color-text-muted)', marginTop: 6 }}>Previous: <span style={{ color: 'var(--color-success)', fontWeight: 500 }}>v3 — 15 Jan 2025 — Approved ✓</span></div>
          </div>
        </div>
      </div>
    </Section>
  );
}

Object.assign(window, { Section14, Section15, Section16, Section17, Section18, Section19, Section20, Section21, Section22 });
