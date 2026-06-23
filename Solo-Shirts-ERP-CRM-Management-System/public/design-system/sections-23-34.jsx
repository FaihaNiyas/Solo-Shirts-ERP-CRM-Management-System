/* global React, Icon, Section, GroupLabel, Btn, StatusBadge, CategoryPill, Avatar, Kbd, Sparkline, IconBtn */

/* ---------- SECTION 23: APPROVAL WORKFLOW ---------- */
function Section23() {
  return (
    <Section num="23" title="Approval Workflow Components" desc="Measurement and finance approvals run through a review panel with idempotent Approve/Reject actions. Rejection requires a reason. The decision history is append-only — never editable.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 24, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ApprovalStatusCard</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {[['pending', 'Awaiting Supervisor', 'Submitted 2h ago'], ['approved', 'Approved by Supervisor', '15 Jan · 10:24'], ['rejected', 'Needs correction', 'Collar measurement off']].map((r, i) => (
              <div key={i} style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, padding: 14 }}>
                <StatusBadge kind={r[0]} />
                <div style={{ fontSize: 13.5, fontWeight: 500, marginTop: 8 }}>{r[1]}</div>
                <div style={{ fontSize: 12, color: 'var(--color-text-muted)', marginTop: 2 }}>{r[2]}</div>
              </div>
            ))}
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ApprovalActionPanel</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            <div style={{ fontSize: 13.5, fontWeight: 600, marginBottom: 6 }}>Measurement v4 review</div>
            <div style={{ fontSize: 12.5, color: 'var(--color-text-secondary)', marginBottom: 14 }}>2 fields changed since v3.</div>
            <div style={{ display: 'flex', gap: 10 }}><Btn variant="success" size="sm" icon="check" full>Approve</Btn><Btn variant="danger" size="sm" icon="x" full>Reject</Btn></div>
            <div style={{ marginTop: 14, paddingTop: 14, borderTop: '1px solid var(--color-border)' }}>
              <div style={{ fontSize: 12, fontWeight: 500, color: 'var(--color-gray-700)', marginBottom: 6 }}>Rejection reason</div>
              <div style={{ minHeight: 54, padding: 10, borderRadius: 9, border: '1px solid var(--color-danger)', fontSize: 12.5, color: 'var(--color-text-muted)' }}>Required when rejecting…</div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 5, color: 'var(--color-danger)', fontSize: 12, marginTop: 6 }}><Icon name="alert" size={13} color="var(--color-danger)" />Rejection reason is required</div>
            </div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ApprovalTimeline — append-only</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 18 }}>
            {[['10:24', 'Supervisor', 'approved', 'v3 approved'], ['09:10', 'Front Desk', 'pending', 'v4 submitted'], ['Yesterday', 'Supervisor', 'rejected', 'v2 — collar off by 2cm']].map((r, i, a) => (
              <div key={i} style={{ display: 'flex', gap: 12 }}>
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}><span style={{ width: 9, height: 9, borderRadius: '50%', background: i === 0 ? 'var(--color-brand)' : 'var(--color-border-mid)' }} />{i < a.length - 1 && <span style={{ width: 2, flex: 1, background: 'var(--color-border)' }} />}</div>
                <div style={{ paddingBottom: 16 }}><div style={{ display: 'flex', alignItems: 'center', gap: 8 }}><StatusBadge kind={r[2]} /></div><div style={{ fontSize: 12.5, marginTop: 5 }}>{r[3]}</div><div style={{ fontSize: 11.5, color: 'var(--color-text-muted)', marginTop: 2 }}>{r[0]} · {r[1]}</div></div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 24: SLA / OVERDUE ---------- */
function Section24() {
  const slas = [['On track', '2h remaining', 'var(--color-success)', 'var(--bg-success)', 'checkCircle'], ['Near due', 'Due today', 'var(--color-warning)', 'var(--bg-warning)', 'clock'], ['Overdue', '3 days overdue', 'var(--color-danger)', 'var(--bg-danger)', 'alert'], ['Breached', 'SLA breached', 'var(--color-danger)', 'var(--bg-danger)', 'xCircle']];
  return (
    <Section num="24" title="SLA / Overdue Components" alt desc="Timers shift neutral → amber (near due) → red (overdue). Rework always stays amber, never red. The SLA progress bar steps color at thresholds with no gradient.">
      <GroupLabel>SlaTimerBadge</GroupLabel>
      <div className="spec-stage" style={{ display: 'flex', gap: 14, flexWrap: 'wrap' }}>
        {slas.map(([n, t, c, bg, ic]) => <span key={n} style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '6px 13px', borderRadius: 9999, background: bg, color: c, fontSize: 13, fontWeight: 500 }}><Icon name={ic} size={14} color={c} />{t}</span>)}
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 28, marginTop: 8 }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 12 }}>SlaProgressBar</div>
          {[['Cutting', 35, 'var(--color-success)'], ['Tailoring', 78, 'var(--color-warning)'], ['QC', 100, 'var(--color-danger)']].map(([s, p, c]) => (
            <div key={s} style={{ marginBottom: 12 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12.5, marginBottom: 5 }}><span>{s}</span><span style={{ color: c, fontWeight: 500 }}>{p === 100 ? 'Overdue' : p > 70 ? 'Near due' : 'On track'}</span></div>
              <div style={{ height: 8, borderRadius: 9999, background: 'var(--bg-neutral)' }}><div style={{ width: `${p}%`, height: '100%', borderRadius: 9999, background: c }} /></div>
            </div>
          ))}
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 12 }}>OverdueReasonPanel</div>
          <div style={{ background: '#FEF6F6', border: '1px solid #FECACA', borderLeft: '3px solid var(--color-danger)', borderRadius: 12, padding: 16 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, color: 'var(--color-danger)', fontWeight: 600, fontSize: 13.5, marginBottom: 10 }}><Icon name="alert" size={16} color="var(--color-danger)" />ORD-091 — 3 days overdue</div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px 16px', fontSize: 12.5 }}>
              {[['Stage', 'Tailoring'], ['Time delayed', '54h'], ['Responsible', 'Karthik P.'], ['Next action', 'Reassign tailor']].map(([l, v]) => <div key={l}><span style={{ color: 'var(--color-text-muted)' }}>{l}</span><div style={{ fontWeight: 500 }}>{v}</div></div>)}
            </div>
          </div>
          <div style={{ marginTop: 12, fontSize: 12.5, color: 'var(--color-text-secondary)' }}>StageTimeIndicator: <span style={{ color: 'var(--color-danger)', fontWeight: 500 }}>54h in Tailoring</span> (over SLA → red)</div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 25: NOTIFICATION CENTER ---------- */
function Section25() {
  const notifs = [
    ['alert', '6 items overdue in Anna Nagar', 'Review and reassign tailors', '12m', 'var(--color-danger)', true],
    ['package', 'Low stock: White Linen — 4m left', 'Below 10m threshold', '1h', 'var(--color-warning)', true],
    ['checkCircle', 'Measurement v4 approved', 'Approved by Supervisor', '2h', 'var(--color-success)', false],
    ['refresh', 'QC rework required — ITEM-0092', 'Collar finishing', '3h', 'var(--color-warning)', false],
    ['send', 'Order ready message sent via WhatsApp', 'To Ramesh Kumar · +91 98XXX', '4h', 'var(--color-info)', false],
  ];
  return (
    <Section num="25" title="Notification Center" desc="A bell with an unread badge opens a tabbed panel. Unread items get an amber tint, amber dot and bold title. Customer communication rows log when WhatsApp/SMS was sent, so staff know the customer was informed.">
      <div style={{ display: 'flex', gap: 28, alignItems: 'flex-start', flexWrap: 'wrap' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>NotificationBell</div>
          <IconBtn icon="bell" label="Notifications" badge="5" />
        </div>
        <div style={{ flex: 1, minWidth: 380 }}>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>NotificationPanel</div>
          <div style={{ maxWidth: 420, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, boxShadow: 'var(--shadow-lg)', overflow: 'hidden' }}>
            <div style={{ display: 'flex', gap: 4, padding: '12px 16px 0' }}>{['All', 'Unread', 'Alerts', 'System'].map((t, i) => <span key={t} style={{ padding: '6px 12px', borderRadius: '8px 8px 0 0', fontSize: 12.5, fontWeight: i === 0 ? 600 : 500, color: i === 0 ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)', borderBottom: i === 0 ? '2px solid var(--color-brand)' : '2px solid transparent' }}>{t}</span>)}</div>
            <div style={{ borderTop: '1px solid var(--color-border)' }}>
              {notifs.map((n, i) => (
                <div key={i} style={{ display: 'flex', gap: 12, padding: '13px 16px', borderTop: i ? '0.5px solid var(--color-border)' : 'none', background: n[5] ? 'var(--color-brand-50)' : '#fff' }}>
                  <span style={{ width: 32, height: 32, borderRadius: 9, background: 'var(--bg-neutral)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}><Icon name={n[0]} size={16} color={n[4]} /></span>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: 13, fontWeight: n[5] ? 600 : 500 }}>{n[1]}</div>
                    <div style={{ fontSize: 12, color: 'var(--color-text-secondary)', marginTop: 2 }}>{n[2]}</div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 5 }}><span style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>{n[3]}</span>{n[5] && <span style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--color-brand)' }} />}<button style={{ fontSize: 11.5, fontWeight: 600, color: 'var(--color-brand-dark)', background: 'none', border: 'none', cursor: 'pointer', padding: 0 }}>View</button></div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 26: EXPORT / DOWNLOAD ---------- */
function Section26() {
  return (
    <Section num="26" title="Export / Download Components" alt desc="Export menus offer PDF/Excel/CSV/Print with size estimates. Download buttons carry five states and async report jobs progress through Queued → Processing → Ready → Failed, surfacing request_id on failure.">
      <div style={{ display: 'grid', gridTemplateColumns: '220px 1fr 1fr', gap: 24, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ExportMenu</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, boxShadow: 'var(--shadow-md)', padding: 6 }}>
            {[['file', 'PDF', '~240 KB'], ['barChart', 'Excel', '~80 KB'], ['file', 'CSV', '~12 KB'], ['printer', 'Print', '']].map(([ic, l, s]) => (
              <div key={l} style={{ display: 'flex', alignItems: 'center', gap: 9, padding: '9px 11px', borderRadius: 8, fontSize: 13.5, color: 'var(--color-gray-700)' }}><Icon name={ic} size={16} color="var(--color-text-muted)" />{l}<span style={{ marginLeft: 'auto', fontSize: 11, color: 'var(--color-text-muted)' }}>{s}</span></div>
            ))}
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>DownloadButton — 5 states</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10, alignItems: 'flex-start' }}>
            <Btn variant="secondary" size="sm" icon="download">Export PDF</Btn>
            <Btn variant="secondary" size="sm" loading>Preparing file</Btn>
            <Btn variant="secondary" size="sm" icon="download">Downloading…</Btn>
            <Btn variant="success" size="sm" icon="check">Downloaded</Btn>
            <span style={{ display: 'flex', alignItems: 'center', gap: 10 }}><Btn variant="danger" size="sm" icon="refresh">Failed — Retry</Btn><span className="mono" style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>REQ-A18C</span></span>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ReportJobStatusCard</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {[['Queued', 'In queue…', 'var(--color-text-muted)', 'clock'], ['Processing', 'Generating…', 'var(--color-warning)', 'refresh'], ['Ready', 'Monthly Revenue Report', 'var(--color-success)', 'checkCircle'], ['Failed', 'Generation failed', 'var(--color-danger)', 'xCircle']].map((r, i) => (
              <div key={i} style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, padding: 13, display: 'flex', alignItems: 'center', gap: 11 }}>
                <Icon name={r[3]} size={18} color={r[2]} />
                <div style={{ flex: 1 }}><div style={{ fontSize: 13, fontWeight: 500 }}>{r[1]}</div>{i === 3 && <span className="mono" style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>REQ-7C90</span>}</div>
                {i === 2 && <Btn variant="primary" size="sm" icon="download">Download</Btn>}
                {i === 3 && <Btn variant="secondary" size="sm">Retry</Btn>}
              </div>
            ))}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 27: DATA DENSITY ---------- */
function DensityTable({ rowH, pad, label, sub }) {
  return (
    <div style={{ flex: 1 }}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 2 }}>{label}</div>
      <div style={{ fontSize: 11.5, color: 'var(--color-text-muted)', marginBottom: 10 }}>{sub}</div>
      <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, overflow: 'hidden' }}>
        {['Ramesh K.', 'Anil V.', 'Vijay S.'].map((n, i) => (
          <div key={n} style={{ display: 'flex', alignItems: 'center', gap: 10, height: rowH, padding: `0 ${pad}px`, borderTop: i ? '0.5px solid var(--color-border)' : 'none' }}>
            <Avatar initials={n.split(' ')[0][0] + n.split(' ')[1][0]} size={Math.min(rowH - 16, 36)} />
            <span style={{ fontSize: rowH > 56 ? 14 : 13, flex: 1 }}>{n}</span>
            <StatusBadge kind="inprogress" />
          </div>
        ))}
      </div>
    </div>
  );
}
function Section27() {
  return (
    <Section num="27" title="Data Density Modes" desc="The same components in three densities. Comfortable for tablets and new users, Default balanced, Compact for factory power users. Compact still meets the 44px minimum touch target.">
      <div style={{ display: 'flex', gap: 24 }}>
        <DensityTable rowH={64} pad={24} label="Comfortable" sub="row 64 · pad 24" />
        <DensityTable rowH={52} pad={20} label="Default" sub="row 52 · pad 20" />
        <DensityTable rowH={44} pad={14} label="Compact" sub="row 44 · pad 14" />
      </div>
    </Section>
  );
}

/* ---------- SECTION 28: KEYBOARD SHORTCUTS ---------- */
function Section28() {
  const groups = [['Navigation', [['Search', ['Ctrl', 'K']], ['Filter', ['Ctrl', 'F']]]], ['Front Desk', [['New customer', ['Ctrl', 'N']], ['Measurement', ['Ctrl', 'M']], ['Add item', ['Ctrl', 'O']], ['Confirm', ['Ctrl', '↵']], ['Print', ['Ctrl', 'P']]]], ['Global', [['Close', ['Esc']], ['Focus search', ['/']]]]];
  return (
    <Section num="28" title="Keyboard Shortcut Components" alt desc="Shortcut hint pills, a Ctrl+K command palette and a grouped help panel. Every shortcut is a helper only — all actions remain clickable and tappable.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.2fr', gap: 28, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ShortcutCommandMenu — Ctrl+K</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, boxShadow: 'var(--shadow-lg)', overflow: 'hidden' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '14px 16px', borderBottom: '1px solid var(--color-border)' }}><Icon name="search" size={18} color="var(--color-text-muted)" /><span style={{ flex: 1, fontSize: 14, color: 'var(--color-text-muted)' }}>Type a command or search…</span><Kbd>Esc</Kbd></div>
            <div style={{ padding: 8 }}>
              <div style={{ fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--color-text-muted)', padding: '6px 10px' }}>Actions</div>
              {[['plus', 'New order', ['Ctrl', 'N']], ['ruler', 'Add measurement', ['Ctrl', 'M']], ['scan', 'Scan QR', []]].map(([ic, l, k]) => (
                <div key={l} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '9px 10px', borderRadius: 8, fontSize: 13.5, background: l === 'New order' ? 'var(--color-brand-50)' : 'transparent' }}><Icon name={ic} size={16} color="var(--color-text-secondary)" />{l}<span style={{ marginLeft: 'auto', display: 'flex', gap: 3 }}>{k.map(x => <Kbd key={x}>{x}</Kbd>)}</span></div>
              ))}
            </div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>ShortcutHelpPanel</div>
          <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 20 }}>
            {groups.map(([g, items]) => (
              <div key={g} style={{ marginBottom: 16 }}>
                <div style={{ fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--color-text-muted)', marginBottom: 8 }}>{g}</div>
                {items.map(([l, k]) => <div key={l} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '5px 0', fontSize: 13.5 }}>{l}<span style={{ display: 'flex', gap: 3 }}>{k.map((x, i) => <Kbd key={i}>{x}</Kbd>)}</span></div>)}
              </div>
            ))}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 29: MOBILE WORKFLOW ---------- */
function Phone({ children, w = 200 }) {
  return <div style={{ width: w, background: '#fff', border: '8px solid #1C1917', borderRadius: 30, overflow: 'hidden', boxShadow: 'var(--shadow-lg)' }}>{children}</div>;
}
function Section29() {
  return (
    <Section num="29" title="Mobile Workflow Components" desc="Dense tables become readable card lists — never just shrunk. A sticky action bar, large scan FAB and compact stepper keep the factory floor thumb-friendly with 44px minimum targets.">
      <div style={{ display: 'flex', gap: 28, flexWrap: 'wrap', alignItems: 'flex-start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>MobileCardList + ActionBar</div>
          <Phone>
            <div style={{ padding: '14px 14px 0', background: 'var(--color-bg)' }}>
              <div style={{ fontSize: 16, fontWeight: 700, marginBottom: 12 }}>Orders</div>
              {[['ORD-184', 'Ramesh K.', 'inprogress'], ['ORD-091', 'Anil V.', 'overdue']].map(r => (
                <div key={r[0]} style={{ background: '#fff', border: '1px solid var(--color-border)', borderRadius: 12, padding: 12, marginBottom: 10, boxShadow: 'var(--shadow-xs)' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}><span className="mono" style={{ fontSize: 12, color: 'var(--color-text-secondary)' }}>{r[0]}</span><StatusBadge kind={r[2]} pulse={r[2] === 'overdue'} /></div>
                  <div style={{ fontSize: 14, fontWeight: 600, marginTop: 6 }}>{r[1]}</div>
                  <CategoryPill icon="shirt">Tailoring</CategoryPill>
                </div>
              ))}
            </div>
            <div style={{ display: 'flex', gap: 8, padding: 12, borderTop: '1px solid var(--color-border)', background: '#fff' }}><Btn variant="ghost" size="sm" full>Cancel</Btn><Btn variant="primary" size="sm" full>Confirm</Btn></div>
          </Phone>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>MobileScanButton (FAB)</div>
          <div style={{ width: 64, height: 64, borderRadius: '50%', background: 'var(--color-brand)', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 8px 20px rgba(217,119,6,0.4)' }}><Icon name="scan" size={28} color="#fff" /></div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '24px 0 10px' }}>MobileStepper</div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            {[0, 1, 2, 3, 4].map(i => i === 1 ? <span key={i} style={{ padding: '5px 12px', borderRadius: 9999, background: 'var(--color-brand-light)', color: 'var(--color-brand-dark)', fontSize: 12, fontWeight: 600 }}>Measurement</span> : <span key={i} style={{ width: 8, height: 8, borderRadius: '50%', background: i < 1 ? 'var(--color-brand)' : 'var(--color-border-mid)' }} />)}
          </div>
        </div>
        <div style={{ flex: 1, minWidth: 200 }}>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Rule</div>
          <div style={{ display: 'flex', gap: 11, padding: 16, borderRadius: 12, background: 'var(--bg-info)', color: '#1E40AF', fontSize: 13, lineHeight: 1.55 }}><Icon name="info" size={18} color="var(--color-info)" style={{ marginTop: 1, flexShrink: 0 }} />Do not simply shrink the desktop table on mobile. Dense tables must become readable card lists. Thumb-friendly, with a 44px minimum touch target everywhere.</div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 30: LOADING / EMPTY / ERROR ---------- */
function Section30() {
  return (
    <Section num="30" title="Loading · Empty · Error" alt desc="Skeletons are the only place a gradient (shimmer) is allowed. Every empty state pairs an outline icon with a clear CTA. Every error state surfaces a copyable request_id.">
      <GroupLabel>Skeletons — shimmer</GroupLabel>
      <div style={{ display: 'flex', gap: 20, flexWrap: 'wrap' }}>
        <div style={{ flex: 1, minWidth: 220, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 18 }}>
          <div className="mono" style={{ fontSize: 11, color: 'var(--color-text-muted)', marginBottom: 12 }}>CardSkeleton</div>
          <div className="shimmer" style={{ height: 14, width: '50%', borderRadius: 6, marginBottom: 14 }} />
          {[90, 75, 60].map((w, i) => <div key={i} className="shimmer" style={{ height: 10, width: `${w}%`, borderRadius: 5, marginBottom: 9 }} />)}
        </div>
        <div style={{ flex: 1, minWidth: 220, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 18 }}>
          <div className="mono" style={{ fontSize: 11, color: 'var(--color-text-muted)', marginBottom: 12 }}>TableSkeleton</div>
          {[100, 100, 100, 100].map((w, i) => <div key={i} style={{ display: 'flex', gap: 12, marginBottom: 12 }}><div className="shimmer" style={{ width: 28, height: 28, borderRadius: 8 }} /><div className="shimmer" style={{ flex: 1, height: 12, borderRadius: 6, marginTop: 8 }} /></div>)}
        </div>
        <div style={{ flex: 1, minWidth: 220, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 18 }}>
          <div className="mono" style={{ fontSize: 11, color: 'var(--color-text-muted)', marginBottom: 12 }}>FormSkeleton</div>
          {[0, 1, 2].map(i => <div key={i} style={{ marginBottom: 12 }}><div className="shimmer" style={{ height: 9, width: 60, borderRadius: 5, marginBottom: 7 }} /><div className="shimmer" style={{ height: 36, borderRadius: 9 }} /></div>)}
        </div>
      </div>

      <GroupLabel>Empty states</GroupLabel>
      <div style={{ display: 'flex', gap: 20, flexWrap: 'wrap' }}>
        {[['shirt', 'No orders yet', 'Create your first order', 'Create order', 'primary'], ['users', 'No customers found', 'Try a different search term', 'Create customer', 'secondary'], ['checkCircle', 'No pending approvals', 'Everything is approved', null, null, 'var(--color-success)'], ['bell', 'All caught up', 'No new notifications', null, null]].map((e, i) => (
          <div key={i} style={{ flex: 1, minWidth: 200, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 24, textAlign: 'center' }}>
            <Icon name={e[0]} size={28} color={e[5] || 'var(--color-text-muted)'} style={{ margin: '0 auto 10px' }} />
            <div style={{ fontSize: 14, fontWeight: 600, color: e[5] || 'var(--color-text-primary)' }}>{e[1]}</div>
            <div style={{ fontSize: 12.5, color: 'var(--color-text-muted)', margin: '4px 0 14px' }}>{e[2]}</div>
            {e[3] && <Btn variant={e[4]} size="sm">{e[3]}</Btn>}
          </div>
        ))}
      </div>

      <GroupLabel>Error states — request_id always shown</GroupLabel>
      <div style={{ display: 'flex', gap: 20, flexWrap: 'wrap' }}>
        {[['lock', "You don't have access", 'Contact your admin', 'Permission'], ['wifiOff', 'Connection failed', 'Check your network', 'Network', 'Retry'], ['scan', 'Invalid QR code', 'Scan a valid order code', 'QRInvalid', 'Try again'], ['package', 'Insufficient stock', 'Not enough available', 'StockOut']].map((e, i) => (
          <div key={i} style={{ flex: 1, minWidth: 200, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 22, textAlign: 'center' }}>
            <Icon name={e[0]} size={26} color="var(--color-danger)" style={{ margin: '0 auto 10px' }} />
            <div style={{ fontSize: 13.5, fontWeight: 600 }}>{e[1]}</div>
            <div style={{ fontSize: 12, color: 'var(--color-text-muted)', margin: '4px 0 12px' }}>{e[2]}</div>
            <span className="mono" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 11, background: 'var(--bg-neutral)', padding: '3px 8px', borderRadius: 6, color: 'var(--color-text-secondary)' }}>REQ-{['4F2A', '9B17', '3C88', 'D041'][i]} <Icon name="copy" size={11} /></span>
            {e[4] && <div style={{ marginTop: 12 }}><Btn variant="secondary" size="sm" icon="refresh">{e[4]}</Btn></div>}
          </div>
        ))}
      </div>
    </Section>
  );
}

/* ---------- SECTION 31: RESPONSIVE ---------- */
function Section31() {
  return (
    <Section num="31" title="Responsive Layout Rules" desc="Three breakpoints. Desktop keeps the 240px sidebar and right-panel list. Tablet collapses the sidebar to icons and 2-column cards. Mobile drops the sidebar for bottom nav and turns tables into card lists.">
      <div style={{ display: 'flex', gap: 28, alignItems: 'flex-end', flexWrap: 'wrap' }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ width: 300, height: 190, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, padding: 8, display: 'flex', gap: 6 }}>
            <div style={{ width: 56, background: 'var(--bg-neutral)', borderRadius: 7 }} />
            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 6 }}><div style={{ height: 20, background: 'var(--color-brand-light)', borderRadius: 5 }} /><div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 5 }}>{[0, 1, 2, 3].map(i => <div key={i} style={{ height: 32, background: 'var(--bg-neutral)', borderRadius: 5 }} />)}</div><div style={{ flex: 1, background: 'var(--bg-neutral)', borderRadius: 5 }} /></div>
            <div style={{ width: 50, background: 'var(--bg-neutral)', borderRadius: 7 }} />
          </div>
          <div style={{ fontSize: 13, fontWeight: 600, marginTop: 10 }}>Desktop</div><div style={{ fontSize: 11.5, color: 'var(--color-text-muted)' }}>1280px+ · sidebar 240 · 4-col</div>
        </div>
        <div style={{ textAlign: 'center' }}>
          <div style={{ width: 200, height: 170, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 12, padding: 8, display: 'flex', gap: 6 }}>
            <div style={{ width: 24, background: 'var(--bg-neutral)', borderRadius: 6 }} />
            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 6 }}><div style={{ height: 18, background: 'var(--color-brand-light)', borderRadius: 5 }} /><div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 5 }}>{[0, 1].map(i => <div key={i} style={{ height: 30, background: 'var(--bg-neutral)', borderRadius: 5 }} />)}</div><div style={{ flex: 1, background: 'var(--bg-neutral)', borderRadius: 5 }} /></div>
          </div>
          <div style={{ fontSize: 13, fontWeight: 600, marginTop: 10 }}>Tablet</div><div style={{ fontSize: 11.5, color: 'var(--color-text-muted)' }}>768–1279 · icons 68 · 2-col</div>
        </div>
        <div style={{ textAlign: 'center' }}>
          <div style={{ width: 110, height: 190, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 14, padding: 8, display: 'flex', flexDirection: 'column', gap: 6 }}>
            <div style={{ height: 16, background: 'var(--color-brand-light)', borderRadius: 4 }} /><div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 5 }}>{[0, 1, 2].map(i => <div key={i} style={{ flex: 1, background: 'var(--bg-neutral)', borderRadius: 5 }} />)}</div><div style={{ height: 22, background: 'var(--bg-neutral)', borderRadius: 6 }} />
          </div>
          <div style={{ fontSize: 13, fontWeight: 600, marginTop: 10 }}>Mobile</div><div style={{ fontSize: 11.5, color: 'var(--color-text-muted)' }}>&lt;768 · bottom nav · 1-col</div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 32: ACCESSIBILITY ---------- */
function Section32() {
  const items = ['WCAG 2.2 AA minimum', 'Focus ring: 2px amber · 2px offset · always visible', 'All interactive elements keyboard-reachable', 'Icon-only buttons have aria-label', 'Color never the sole status indicator', 'Tables use proper th headers', 'Modals and drawers trap focus', 'Escape closes modal / drawer', 'Reduced motion preference respected', 'Min touch target 44×44px'];
  return (
    <Section num="32" title="Accessibility" alt desc="Baseline requirements baked into every component. Focus is always visible, color always paired with an icon or text, and motion respects user preference.">
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: '12px 32px', marginBottom: 24 }}>
        {items.map(t => <div key={t} style={{ display: 'flex', alignItems: 'center', gap: 10, fontSize: 13.5, color: 'var(--color-gray-700)' }}><Icon name="checkCircle" size={17} color="var(--color-success)" />{t}</div>)}
      </div>
      <div style={{ display: 'flex', gap: 20, alignItems: 'center', flexWrap: 'wrap' }}>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Focus ring</div><button style={{ height: 40, padding: '0 16px', borderRadius: 10, background: 'var(--color-brand)', color: '#fff', fontWeight: 600, fontSize: 14, border: 'none', outline: '2px solid var(--color-brand)', outlineOffset: 2, boxShadow: '0 0 0 4px rgba(217,119,6,0.2)' }}>Confirm Order</button></div>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Color + icon, never color alone</div><div style={{ display: 'flex', gap: 10 }}><StatusBadge kind="approved" /><StatusBadge kind="rejected" /><StatusBadge kind="overdue" /></div></div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 33: DARK MODE ---------- */
function Section33() {
  return (
    <Section num="33" title="Dark Mode Component Previews" desc="Light mode is default. Dark mode is a secondary preview using warm blacks, not cold zinc. Amber stays unchanged; charts keep amber bars with muted warm-gray grids and no neon.">
      <div style={{ background: '#0C0A09', borderRadius: 20, padding: 28, display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 20 }}>
        {/* metric */}
        <div style={{ background: '#1C1917', borderRadius: 16, padding: 20 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between' }}><span style={{ fontSize: 12.5, color: '#A8A29E' }}>Today's Orders</span><Icon name="shirt" size={16} color="#A8A29E" /></div>
          <div style={{ fontSize: 34, fontWeight: 700, color: '#FAFAF9', marginTop: 8 }}>23</div>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 12, color: '#4ADE80', marginTop: 4 }}><Icon name="trendUp" size={13} color="#4ADE80" />12.4%</div>
          <div style={{ marginTop: 12 }}><Sparkline data={[4, 6, 5, 8, 7, 11, 10, 13]} color="#4ADE80" fill w={180} h={32} /></div>
        </div>
        {/* chart */}
        <div style={{ background: '#1C1917', borderRadius: 16, padding: 20 }}>
          <div style={{ fontSize: 13.5, fontWeight: 600, color: '#FAFAF9', marginBottom: 14 }}>Orders Completed</div>
          <div style={{ display: 'flex', alignItems: 'flex-end', gap: 8, height: 90 }}>{[60, 80, 100, 45, 70, 65, 75].map((h, i) => <div key={i} style={{ flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'flex-end', gap: 3 }}><div style={{ height: `${h}%`, background: '#D97706', borderRadius: 4 }} /></div>)}</div>
        </div>
        {/* input */}
        <div style={{ background: '#1C1917', borderRadius: 16, padding: 20 }}>
          <div style={{ fontSize: 13.5, fontWeight: 600, color: '#FAFAF9', marginBottom: 14 }}>TextInput</div>
          <div style={{ height: 40, display: 'flex', alignItems: 'center', padding: '0 12px', borderRadius: 10, background: '#292524', border: '1px solid var(--color-brand)', boxShadow: '0 0 0 3px rgba(217,119,6,0.25)', color: '#FAFAF9', fontSize: 13.5 }}>Ramesh Kumar</div>
          <div style={{ marginTop: 14 }}><span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 11px', borderRadius: 9999, background: '#451A03', color: '#FDBA4D', fontSize: 12, fontWeight: 500 }}><span style={{ width: 7, height: 7, borderRadius: '50%', background: '#FDBA4D' }} />Pending</span></div>
        </div>
        {/* table */}
        <div style={{ gridColumn: 'span 2', background: '#1C1917', borderRadius: 16, overflow: 'hidden' }}>
          <div style={{ display: 'grid', gridTemplateColumns: '1.5fr 1fr 1fr', padding: '11px 18px', background: '#292524' }}>{['Order', 'Customer', 'Status'].map(h => <span key={h} style={{ fontSize: 11, fontWeight: 600, textTransform: 'uppercase', color: '#A8A29E' }}>{h}</span>)}</div>
          {[['ORD-184', 'Ramesh K.', 'inprogress', false], ['ORD-091', 'Anil V.', 'overdue', true]].map((r, i) => (
            <div key={i} style={{ display: 'grid', gridTemplateColumns: '1.5fr 1fr 1fr', padding: '12px 18px', borderTop: '0.5px solid #3F3F46', alignItems: 'center', background: r[3] ? 'rgba(217,119,6,0.08)' : 'transparent', borderLeft: r[3] ? '2px solid #D97706' : '2px solid transparent' }}>
              <span className="mono" style={{ fontSize: 12.5, color: '#FAFAF9' }}>{r[0]}</span><span style={{ fontSize: 13, color: '#D6D3D1' }}>{r[1]}</span><span><StatusBadge kind={r[2]} /></span>
            </div>
          ))}
        </div>
        {/* toast */}
        <div style={{ background: '#1C1917', borderRadius: 16, padding: 16, borderLeft: '4px solid var(--color-success)', display: 'flex', gap: 11, alignItems: 'flex-start' }}>
          <Icon name="checkCircle" size={18} color="#4ADE80" />
          <div><div style={{ fontSize: 13, fontWeight: 600, color: '#FAFAF9' }}>Order confirmed</div><div style={{ fontSize: 12, color: '#A8A29E', marginTop: 2 }}>Moved to Tailoring.</div></div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 34: RULES + QA ---------- */
function RuleBlock({ title, items }) {
  return (
    <div>
      <div style={{ fontSize: 11.5, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--color-brand-dark)', marginBottom: 10 }}>{title}</div>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 7 }}>{items.map(t => <div key={t} style={{ display: 'flex', gap: 8, fontSize: 12.5, color: 'var(--color-gray-700)', lineHeight: 1.45 }}><Icon name="check" size={14} color="var(--color-success)" style={{ marginTop: 2, flexShrink: 0 }} />{t}</div>)}</div>
    </div>
  );
}
function Section34() {
  const qa = ['Component library only — no full dashboard', 'Light sidebar (white) — not dark', 'Warm cream bg #FFFCF5 throughout', 'Amber only primary accent', 'Blue only for Info / In Progress', 'No emoji — outline line icons only', 'QC uses gray + amber, no purple', 'No gradients except skeleton shimmer', '8 chart types with loading/empty/error', 'Metric cards have sparklines', 'Figma-style component naming', 'All button & form states shown', 'QR, barcode & print previews', 'Inventory: Remaining/Reserved/Available', 'Measurement visualizer + garment map', 'Role, permission & branch states', 'Approval, SLA & overdue components', 'Bulk actions & advanced filters', 'Notification center & export states', 'Density modes & keyboard shortcuts', 'Mobile components & responsive rules', 'Dark mode token strip + previews', 'request_id on every error state', 'Indian currency ₹1,42,500 everywhere', 'JetBrains Mono for codes & IDs', 'Ready for developer handoff'];
  return (
    <Section num="34" title="Design Rules &amp; QA" alt desc="The single source of truth. Every ERP screen is assembled from these components — nothing designed from scratch outside this system.">
      <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 20, padding: 32, boxShadow: 'var(--shadow-xs)' }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 32 }}>
          <RuleBlock title="Theme" items={['Light white sidebar — not dark', 'Warm cream page bg #FFFCF5', 'Card radius 16–20px', 'Barely-visible shadows (xs default)', 'Wide, airy whitespace']} />
          <RuleBlock title="Color" items={['Amber #D97706 is the only accent', 'Blue only for In Progress / Info', 'QC: gray neutral + amber dot', 'No gradients except shimmer', 'Sparklines: up green / down red']} />
          <RuleBlock title="Icons & Type" items={['Lucide outline, 1.75px stroke', 'No emoji, no filled icons', 'aria-label on icon buttons', 'Metric numbers 40px / 700', 'Codes & IDs in JetBrains Mono']} />
          <RuleBlock title="Components" items={['Every error shows request_id', 'Overdue red border / rework amber', 'Stock = Remaining/Reserved/Available', 'Measurements versioned, never edited', 'Finance append-only']} />
        </div>
      </div>

      <GroupLabel>QA Checklist — developer handoff</GroupLabel>
      <div style={{ background: 'var(--color-brand-50)', border: '1px solid var(--color-brand-muted)', borderRadius: 20, padding: 28 }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: '10px 40px' }}>
          {qa.map(t => <div key={t} style={{ display: 'flex', alignItems: 'center', gap: 10, fontSize: 13, color: 'var(--color-gray-700)' }}><span style={{ width: 18, height: 18, borderRadius: 5, background: 'var(--color-brand)', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}><Icon name="check" size={12} color="#fff" sw={2.5} /></span>{t}</div>)}
        </div>
      </div>
      <div style={{ textAlign: 'center', marginTop: 36, fontSize: 12.5, color: 'var(--color-text-muted)' }}>Solo Shirts India ERP · Design System v1.0 · <span className="mono">Inter / JetBrains Mono</span> · Precision tailoring, managed.</div>
    </Section>
  );
}

Object.assign(window, { Section23, Section24, Section25, Section26, Section27, Section28, Section29, Section30, Section31, Section32, Section33, Section34 });
