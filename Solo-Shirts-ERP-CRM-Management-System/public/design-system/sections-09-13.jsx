/* global React, Icon, Section, GroupLabel, Spec, Btn, StatusBadge, CategoryPill, Avatar, IconBtn */

/* ---------- SECTION 9: CARDS ---------- */
function KanbanCard({ accent, code, cat, catIcon, customer, tailor, due, time, tag }) {
  return (
    <div style={{ width: 232, background: '#fff', borderRadius: 14, boxShadow: 'var(--shadow-xs)', border: '1px solid var(--color-border)', borderLeft: accent ? `3px solid ${accent}` : '1px solid var(--color-border)', padding: 16 }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
        <span className="mono" style={{ fontSize: 12, color: 'var(--color-text-secondary)' }}>{code}</span>
        <CategoryPill icon={catIcon}>{cat}</CategoryPill>
      </div>
      <div style={{ fontSize: 15, fontWeight: 600, marginBottom: 4 }}>{customer}</div>
      <div style={{ fontSize: 12.5, color: 'var(--color-text-secondary)', display: 'flex', alignItems: 'center', gap: 5 }}><Icon name="user" size={13} color="var(--color-text-muted)" />{tailor}</div>
      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, color: 'var(--color-text-muted)', margin: '8px 0 12px' }}><span style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}><Icon name="calendar" size={12} />{due}</span><span style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}><Icon name="clock" size={12} />{time}</span></div>
      {tag}
      <button style={{ width: '100%', height: 34, marginTop: tag ? 10 : 0, borderRadius: 9999, border: '1px solid var(--color-border-mid)', background: '#fff', fontSize: 13, fontWeight: 600, color: 'var(--color-brand-dark)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6 }}>Transition <Icon name="chevronRight" size={14} color="var(--color-brand-dark)" /></button>
    </div>
  );
}
function StaffCard({ initials, name, role, items, avg, rework, reworkColor }) {
  return (
    <div style={{ width: 208, background: '#fff', borderRadius: 16, border: '1px solid var(--color-border)', boxShadow: 'var(--shadow-xs)', padding: 18 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 11, marginBottom: 14 }}>
        <Avatar initials={initials} size={44} dot="online" />
        <div><div style={{ fontSize: 14.5, fontWeight: 600 }}>{name}</div><span style={{ fontSize: 11, padding: '2px 8px', borderRadius: 9999, background: 'var(--color-brand-light)', color: 'var(--color-brand-dark)', fontWeight: 500 }}>{role}</span></div>
      </div>
      <div style={{ display: 'flex', justifyContent: 'space-between', textAlign: 'center' }}>
        {[['Items/wk', items, 'var(--color-text-primary)'], ['Avg time', avg, 'var(--color-text-primary)'], ['Rework', rework, reworkColor]].map(([l, v, c]) => <div key={l}><div style={{ fontSize: 16, fontWeight: 700, color: c }}>{v}</div><div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 2 }}>{l}</div></div>)}
      </div>
    </div>
  );
}
function Section9() {
  return (
    <Section num="09" title="Cards" desc="ContentCard is the base wrapper (20px radius, shadow-xs, subtle hover lift). Specialised cards — right-panel list rows, kanban cards, staff cards — all inherit the same surface language.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 280px', gap: 32, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>KanbanCard — Normal / Overdue / Rework</div>
          <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
            <KanbanCard code="ORD-184" cat="Tailoring" catIcon="shirt" customer="Ramesh Kumar" tailor="Suresh M." due="Jun 14" time="4h in stage" />
            <KanbanCard accent="var(--color-danger)" code="ORD-091" cat="Cutting" catIcon="scissors" customer="Anil Verma" tailor="Manoj R." due="Jun 09" time="2d in stage" tag={<span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 10px', borderRadius: 8, background: '#FEE2E2', color: '#DC2626', fontSize: 12, fontWeight: 500 }}><span className="pulse-dot" style={{ width: 7, height: 7, borderRadius: '50%', background: '#DC2626' }} />3 days overdue</span>} />
            <KanbanCard accent="var(--color-warning)" code="ORD-072" cat="Rework" catIcon="refresh" customer="Vijay S." tailor="Karthik P." due="Jun 16" time="1h in stage" tag={<span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 10px', borderRadius: 8, background: '#FEF3C7', color: '#D97706', fontSize: 12, fontWeight: 500 }}><Icon name="refresh" size={13} color="#D97706" />Rework — collar</span>} />
          </div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '26px 0 10px' }}>StaffCard</div>
          <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
            <StaffCard initials="SM" name="Suresh M." role="Tailor" items="34" avg="3.2h" rework="9%" reworkColor="var(--color-success)" />
            <StaffCard initials="KP" name="Karthik P." role="Tailor" items="28" avg="4.1h" rework="18%" reworkColor="var(--color-warning)" />
            <StaffCard initials="MR" name="Manoj R." role="Cutting" items="41" avg="2.4h" rework="27%" reworkColor="var(--color-danger)" />
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>RightPanelListCard</div>
          <div style={{ width: 280, background: '#fff', borderRadius: 20, border: '1px solid var(--color-border)', boxShadow: 'var(--shadow-xs)', padding: '18px 0' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 18px 14px' }}><span style={{ fontSize: 15, fontWeight: 600 }}>Delivery Queue</span><IconBtn icon="more" label="More" /></div>
            {[['RK', 'Ramesh Kumar', 'ORD-184', '₹4,200', 'var(--color-success)', 'Ready'], ['AV', 'Anil Verma', 'ORD-091', '₹2,800', 'var(--color-danger)', 'Overdue'], ['VS', 'Vijay Singh', 'ORD-072', '₹3,150', 'var(--color-warning)', 'QC'], ['PM', 'Priya M.', 'ORD-066', '₹1,900', 'var(--color-info)', 'Tailoring']].map((r, i) => (
              <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '9px 18px', borderTop: i ? '0.5px solid var(--color-border)' : 'none' }}>
                <Avatar initials={r[0]} size={36} />
                <div style={{ flex: 1, minWidth: 0 }}><div style={{ fontSize: 13.5, fontWeight: 500 }}>{r[1]}</div><div className="mono" style={{ fontSize: 11, color: 'var(--color-text-muted)' }}>{r[2]}</div></div>
                <div style={{ textAlign: 'right' }}><div className="mono" style={{ fontSize: 13, fontWeight: 600 }}>{r[3]}</div><div style={{ fontSize: 11, color: r[4], fontWeight: 500 }}>{r[5]}</div></div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 10: DATA TABLE ---------- */
function Section10() {
  const rows = [
    ['ORD-2026-000184', 'Ramesh Kumar', 'shirt', 'Tailoring', 'inprogress', '₹4,200', 'default'],
    ['ORD-2026-000183', 'Anil Verma', 'scissors', 'Cutting', 'overdue', '₹2,800', 'overdue'],
    ['ORD-2026-000182', 'Vijay Singh', 'checkCircle', 'QC', 'pending', '₹3,150', 'selected'],
    ['ORD-2026-000181', 'Priya Menon', 'truck', 'Delivery', 'delivered', '₹1,900', 'default'],
    ['ORD-2026-000180', 'Karthik Raj', 'shirt', 'Tailoring', 'ready', '₹5,400', 'default'],
  ];
  const rowBg = { default: '#fff', selected: 'var(--color-brand-50)', overdue: '#FEF6F6' };
  const rowBd = { default: 'transparent', selected: 'var(--color-brand)', overdue: 'var(--color-danger)' };
  return (
    <Section num="10" title="Data Table" alt desc="DataTable/Dense with sortable gray-50 headers, 52px rows and warm-cream hover. Selected rows get an amber left border, overdue rows a red one. Order codes render in JetBrains Mono; amounts use Indian currency format.">
      <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 20, boxShadow: 'var(--shadow-xs)', overflow: 'hidden' }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1.6fr 1.4fr 1fr 1fr 0.8fr 100px', background: 'var(--color-surface-alt)', padding: '12px 22px', borderBottom: '1px solid var(--color-border-mid)' }}>
          {[['Order', true], ['Customer', true], ['Category', false], ['Status', false], ['Amount', true], ['Action', false]].map(([h, sort], i) => <div key={h} style={{ fontSize: 11.5, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.04em', color: 'var(--color-text-secondary)', display: 'flex', alignItems: 'center', gap: 4, justifyContent: i === 4 ? 'flex-end' : 'flex-start' }}>{h}{sort && <Icon name="sort" size={12} color="var(--color-text-muted)" />}</div>)}
        </div>
        {rows.map((r, i) => (
          <div key={i} style={{ display: 'grid', gridTemplateColumns: '1.6fr 1.4fr 1fr 1fr 0.8fr 100px', alignItems: 'center', padding: '0 22px', height: 52, background: rowBg[r[6]], borderBottom: '0.5px solid var(--color-border)', borderLeft: `2px solid ${rowBd[r[6]]}` }}>
            <span className="mono" style={{ fontSize: 12.5, fontWeight: 500 }}>{r[0]}</span>
            <span style={{ fontSize: 14, color: 'var(--color-gray-700)' }}>{r[1]}</span>
            <span><CategoryPill icon={r[2]}>{r[3]}</CategoryPill></span>
            <span><StatusBadge kind={r[4]} pulse={r[4] === 'overdue'} /></span>
            <span className="mono" style={{ fontSize: 13.5, fontWeight: 600, textAlign: 'right' }}>{r[5]}</span>
            <span style={{ display: 'flex', justifyContent: 'flex-end' }}><IconBtn icon="more" label="Row actions" /></span>
          </div>
        ))}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 22px', background: 'var(--color-surface-alt)' }}>
          <span style={{ fontSize: 12.5, color: 'var(--color-text-secondary)' }}>Showing 1–20 of 142</span>
          <div style={{ display: 'flex', gap: 6 }}>
            <IconBtn icon="chevronRight" label="Previous" />
            {[1, 2, 3].map(p => <span key={p} style={{ width: 32, height: 32, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: 8, fontSize: 13, fontWeight: 500, background: p === 1 ? 'var(--color-brand)' : '#fff', color: p === 1 ? '#fff' : 'var(--color-gray-700)', border: p === 1 ? 'none' : '1px solid var(--color-border-mid)' }}>{p}</span>)}
            <IconBtn icon="chevronRight" label="Next" />
          </div>
        </div>
      </div>
      <div style={{ marginTop: 16, background: '#fff', border: '1px dashed var(--color-border-mid)', borderRadius: 16, padding: '32px', textAlign: 'center' }}>
        <Icon name="search" size={28} color="var(--color-text-muted)" style={{ margin: '0 auto 10px' }} />
        <div style={{ fontSize: 14, fontWeight: 600 }}>No items found</div>
        <div style={{ fontSize: 12.5, color: 'var(--color-text-muted)', margin: '4px 0 14px' }}>Try adjusting your filters or search term</div>
        <Btn variant="secondary" size="sm">Clear filters</Btn>
      </div>
    </Section>
  );
}

/* ---------- SECTION 11: NAVIGATION ---------- */
function NavItem({ icon, label, active, collapsed }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 12, height: 44, padding: collapsed ? 0 : '0 14px', justifyContent: collapsed ? 'center' : 'flex-start', borderRadius: 10, background: active ? 'var(--color-sidebar-active)' : 'transparent', color: active ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)', fontWeight: active ? 600 : 500, fontSize: 14 }}>
      <Icon name={icon} size={19} color={active ? 'var(--color-brand)' : 'var(--color-text-muted)'} />{!collapsed && label}
    </div>
  );
}
function Sidebar({ collapsed, dark }) {
  const groups = [['Workspace', [['dashboard', 'Overview', true], ['shirt', 'Orders'], ['workflow', 'Production']]], ['Operations', [['scissors', 'Cutting'], ['shield', 'Quality'], ['truck', 'Delivery'], ['package', 'Inventory']]], ['Business', [['barChart', 'Reports'], ['wallet', 'Finance']]]];
  const bg = dark ? '#1C1917' : '#fff';
  const tcol = dark ? '#A8A29E' : 'var(--color-text-secondary)';
  return (
    <div style={{ width: collapsed ? 68 : 240, background: bg, borderRight: `1px solid ${dark ? '#3F3F46' : 'var(--color-border)'}`, borderRadius: 16, padding: collapsed ? '18px 12px' : '20px 14px', height: 'fit-content' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: collapsed ? 0 : '0 6px', justifyContent: collapsed ? 'center' : 'flex-start', marginBottom: 22 }}>
        <span style={{ width: 32, height: 32, borderRadius: 9, background: 'var(--color-brand)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, fontSize: 14 }}>SS</span>
        {!collapsed && <span style={{ fontWeight: 700, fontSize: 15.5, color: dark ? '#FAFAF9' : 'var(--color-text-primary)' }}>Solo Shirts</span>}
      </div>
      {groups.map(([g, items]) => (
        <div key={g} style={{ marginBottom: 14 }}>
          {!collapsed && <div style={{ fontSize: 10.5, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.08em', color: dark ? '#78716C' : 'var(--color-text-muted)', padding: '0 8px', marginBottom: 6 }}>{g}</div>}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            {items.map(it => (
              <div key={it[1]} style={{ display: 'flex', alignItems: 'center', gap: 12, height: 42, padding: collapsed ? 0 : '0 14px', justifyContent: collapsed ? 'center' : 'flex-start', borderRadius: 10, background: it[2] ? (dark ? 'rgba(217,119,6,0.15)' : 'var(--color-sidebar-active)') : 'transparent', color: it[2] ? (dark ? '#FDBA4D' : 'var(--color-brand-dark)') : tcol, fontWeight: it[2] ? 600 : 500, fontSize: 13.5 }}>
                <Icon name={it[0]} size={18} color={it[2] ? (dark ? '#FDBA4D' : 'var(--color-brand)') : (dark ? '#78716C' : 'var(--color-text-muted)')} />{!collapsed && it[1]}
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}
function Section11() {
  return (
    <Section num="11" title="Navigation" desc="A light white sidebar — like Sixpay, never dark. Active items use a pure amber pill (no left border line). The minimal topbar carries a branch switcher, pill search and action icons.">
      <div style={{ display: 'flex', gap: 28, alignItems: 'flex-start', flexWrap: 'wrap' }}>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Sidebar — Expanded (light)</div><Sidebar /></div>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Collapsed</div><Sidebar collapsed /></div>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Dark preview</div><Sidebar dark /></div>
      </div>

      <GroupLabel>Topbar</GroupLabel>
      <div style={{ display: 'flex', alignItems: 'center', gap: 16, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: '12px 18px' }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '8px 14px', borderRadius: 9999, border: '1px solid var(--color-border-mid)', fontSize: 13.5, fontWeight: 500 }}><Icon name="building" size={15} color="var(--color-text-secondary)" />HQ <Icon name="chevronDown" size={14} color="var(--color-text-muted)" /></span>
        <div style={{ flex: 1, maxWidth: 460, display: 'flex', alignItems: 'center', gap: 9, height: 40, padding: '0 14px', borderRadius: 9999, background: 'var(--bg-neutral)' }}><Icon name="search" size={16} color="var(--color-text-muted)" /><span style={{ flex: 1, fontSize: 13.5, color: 'var(--color-text-muted)' }}>Search order, customer, invoice…</span><span style={{ display: 'inline-flex', gap: 3 }}><Kbd>Ctrl</Kbd><Kbd>K</Kbd></span></div>
        <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: 10 }}><IconBtn icon="chat" label="Messages" /><IconBtn icon="bell" label="Notifications" badge="3" /><Avatar initials="AD" size={36} dot="online" /></div>
      </div>

      <GroupLabel>Bottom nav — mobile only</GroupLabel>
      <div style={{ maxWidth: 380, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: '10px 6px', display: 'flex', justifyContent: 'space-around' }}>
        {[['dashboard', 'Home', true], ['shirt', 'Orders'], ['workflow', 'Production'], ['package', 'Inventory'], ['more', 'More']].map(t => (
          <div key={t[1]} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 4, color: t[2] ? 'var(--color-brand-dark)' : 'var(--color-text-muted)' }}>
            <Icon name={t[0]} size={20} color={t[2] ? 'var(--color-brand)' : 'var(--color-text-muted)'} /><span style={{ fontSize: 11, fontWeight: t[2] ? 600 : 500 }}>{t[1]}</span>{t[2] && <span style={{ width: 4, height: 4, borderRadius: '50%', background: 'var(--color-brand)' }} />}
          </div>
        ))}
      </div>
    </Section>
  );
}

/* ---------- SECTION 12: TABS + STEPPER + AVATAR + BREADCRUMB ---------- */
function Section12() {
  const steps = ['Customer', 'Measurement', 'Items', 'Payment', 'Confirm'];
  const cur = 2;
  return (
    <Section num="12" title="Tabs · Stepper · Avatar · Breadcrumb" alt desc="Two tab styles — Sixpay-style pill tabs and in-card underline tabs. A 5-step stepper drives the front-desk order flow. Avatars come in four sizes with status dots and grouping.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 32 }}>
        <div>
          <GroupLabel>Tabs</GroupLabel>
          <div style={{ display: 'inline-flex', gap: 4, background: 'var(--bg-neutral)', padding: 4, borderRadius: 12 }}>
            {['Orders', 'Measurements', 'Balance', 'Timeline'].map((t, i) => <span key={t} style={{ padding: '8px 16px', borderRadius: 9, fontSize: 13.5, fontWeight: 500, background: i === 0 ? '#fff' : 'transparent', color: i === 0 ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)', boxShadow: i === 0 ? 'var(--shadow-xs)' : 'none' }}>{t}</span>)}
          </div>
          <div style={{ display: 'flex', gap: 24, borderBottom: '1px solid var(--color-border-mid)', marginTop: 22 }}>
            {['Details', 'History', 'Files'].map((t, i) => <span key={t} style={{ padding: '0 0 12px', fontSize: 13.5, fontWeight: i === 0 ? 600 : 500, color: i === 0 ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)', borderBottom: i === 0 ? '2px solid var(--color-brand)' : '2px solid transparent', marginBottom: -1 }}>{t}</span>)}
          </div>
        </div>
        <div>
          <GroupLabel>Avatar — sizes · status · group</GroupLabel>
          <div style={{ display: 'flex', alignItems: 'center', gap: 18 }}>
            <Avatar initials="RK" size={24} /><Avatar initials="RK" size={32} /><Avatar initials="RK" size={40} dot="online" /><Avatar initials="RK" size={48} dot="offline" />
            <div style={{ display: 'flex', marginLeft: 12 }}>{['SM', 'KP', 'MR', 'PV'].map((a, i) => <span key={a} style={{ marginLeft: i ? -10 : 0, border: '2px solid #fff', borderRadius: '50%' }}><Avatar initials={a} size={34} /></span>)}<span style={{ marginLeft: -10, width: 34, height: 34, borderRadius: '50%', border: '2px solid #fff', background: 'var(--bg-neutral)', color: 'var(--color-gray-700)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 600 }}>+3</span></div>
          </div>
          <GroupLabel>Breadcrumb</GroupLabel>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13 }}>
            <span style={{ color: 'var(--color-text-secondary)' }}>Dashboard</span><span style={{ color: 'var(--color-gray-300)' }}>/</span><span style={{ color: 'var(--color-text-secondary)' }}>Production</span><span style={{ color: 'var(--color-gray-300)' }}>/</span><span className="mono" style={{ fontWeight: 500 }}>ORD-2026-00184</span>
          </div>
        </div>
      </div>

      <GroupLabel>Stepper — 5 steps</GroupLabel>
      <div style={{ display: 'flex', alignItems: 'center', background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: '24px 28px' }}>
        {steps.map((s, i) => (
          <React.Fragment key={s}>
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 }}>
              <span style={{ width: 32, height: 32, borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 13, fontWeight: 600, background: i < cur ? 'var(--color-brand)' : 'transparent', color: i < cur ? '#fff' : i === cur ? 'var(--color-brand)' : 'var(--color-text-muted)', border: i < cur ? 'none' : `2px solid ${i === cur ? 'var(--color-brand)' : 'var(--color-border-mid)'}` }}>{i < cur ? <Icon name="check" size={15} color="#fff" sw={2.5} /> : i + 1}</span>
              <span style={{ fontSize: 12, fontWeight: i === cur ? 600 : 500, color: i <= cur ? 'var(--color-text-primary)' : 'var(--color-text-muted)' }}>{s}</span>
            </div>
            {i < steps.length - 1 && <div style={{ flex: 1, height: 2, background: i < cur ? 'var(--color-brand)' : 'var(--color-border-mid)', margin: '0 8px', marginBottom: 22 }} />}
          </React.Fragment>
        ))}
      </div>
    </Section>
  );
}

/* ---------- SECTION 13: OVERLAYS ---------- */
function Toast({ accent, icon, title, body, action }) {
  return (
    <div style={{ width: 320, background: '#fff', borderRadius: 16, boxShadow: 'var(--shadow-xl)', borderLeft: `4px solid ${accent}`, padding: '14px 16px', display: 'flex', gap: 11 }}>
      <Icon name={icon} size={19} color={accent} style={{ marginTop: 1 }} />
      <div style={{ flex: 1 }}>
        <div style={{ fontSize: 13.5, fontWeight: 600 }}>{title}</div>
        <div style={{ fontSize: 12.5, color: 'var(--color-text-secondary)', marginTop: 2, lineHeight: 1.4 }}>{body}</div>
        {action}
      </div>
      <Icon name="x" size={15} color="var(--color-text-muted)" />
    </div>
  );
}
function Section13() {
  return (
    <Section num="13" title="Overlays" desc="Right-side drawers, centered modals and corner confirm dialogs. Toasts dock bottom-left (Sixpay notification position), auto-dismiss in 4s, and error toasts always surface a copyable request_id.">
      <div style={{ display: 'grid', gridTemplateColumns: '320px 1fr', gap: 28, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>DrawerPanel — 480px right</div>
          <div style={{ width: 300, background: '#fff', borderRadius: 16, border: '1px solid var(--color-border-mid)', boxShadow: 'var(--shadow-lg)', overflow: 'hidden' }}>
            <div style={{ padding: '16px 20px', borderBottom: '1px solid var(--color-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}><span style={{ fontWeight: 600, fontSize: 15 }}>Order Details</span><Icon name="x" size={17} color="var(--color-text-muted)" /></div>
            <div style={{ padding: 20, fontSize: 13, color: 'var(--color-text-secondary)' }}><div className="mono" style={{ color: 'var(--color-text-primary)', fontWeight: 500, marginBottom: 8 }}>ORD-2026-000184</div>Ramesh Kumar · Tailoring<div style={{ marginTop: 12 }}><StatusBadge kind="inprogress" /></div></div>
            <div style={{ padding: '14px 20px', borderTop: '1px solid var(--color-border)', display: 'flex', gap: 10, justifyContent: 'flex-end', background: 'var(--color-surface-alt)' }}><Btn variant="ghost" size="sm">Cancel</Btn><Btn variant="primary" size="sm">Confirm</Btn></div>
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 10 }}>Modal &amp; ConfirmDialog</div>
          <div style={{ display: 'flex', gap: 20, flexWrap: 'wrap' }}>
            <div style={{ width: 300, background: '#fff', borderRadius: 20, boxShadow: 'var(--shadow-xl)', padding: 24 }}>
              <div style={{ fontSize: 17, fontWeight: 600 }}>Assign tailor</div>
              <div style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: '6px 0 18px' }}>Select a tailor for ORD-184.</div>
              <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}><Btn variant="ghost" size="sm">Cancel</Btn><Btn variant="primary" size="sm">Save</Btn></div>
            </div>
            <div style={{ width: 280, background: '#fff', borderRadius: 16, boxShadow: 'var(--shadow-lg)', padding: 22, textAlign: 'center' }}>
              <span style={{ width: 44, height: 44, borderRadius: '50%', background: '#FEE2E2', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: 12 }}><Icon name="alert" size={22} color="var(--color-danger)" /></span>
              <div style={{ fontSize: 15.5, fontWeight: 600 }}>Cancel this order?</div>
              <div style={{ fontSize: 12.5, color: 'var(--color-text-secondary)', margin: '6px 0 16px' }}>This action can't be undone.</div>
              <div style={{ display: 'flex', gap: 10 }}><Btn variant="secondary" size="sm" full>Keep</Btn><Btn variant="danger" size="sm" full>Cancel order</Btn></div>
            </div>
          </div>
        </div>
      </div>
      <GroupLabel>Toasts — bottom-left, auto-dismiss 4s</GroupLabel>
      <div style={{ display: 'flex', gap: 18, flexWrap: 'wrap' }}>
        <Toast accent="var(--color-success)" icon="checkCircle" title="Order confirmed" body="ORD-184 moved to Tailoring." />
        <Toast accent="var(--color-danger)" icon="xCircle" title="Export failed" body={<span className="mono" style={{ fontSize: 11.5 }}>REQ-9382AB <Icon name="copy" size={11} style={{ display: 'inline', verticalAlign: -1 }} /></span>} action={<button style={{ marginTop: 8, fontSize: 12, fontWeight: 600, color: 'var(--color-danger)', background: 'none', border: 'none', cursor: 'pointer', padding: 0 }}>Retry</button>} />
        <Toast accent="var(--color-warning)" icon="alert" title="Low stock" body="White Linen — 4m left." action={<button style={{ marginTop: 8, fontSize: 12, fontWeight: 600, color: 'var(--color-brand-dark)', background: 'none', border: 'none', cursor: 'pointer', padding: 0 }}>View roll</button>} />
      </div>
    </Section>
  );
}

Object.assign(window, { Section9, Section10, Section11, Section12, Section13 });
