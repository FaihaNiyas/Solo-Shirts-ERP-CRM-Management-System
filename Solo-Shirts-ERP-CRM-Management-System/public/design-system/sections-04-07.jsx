/* global React, Icon, Section, GroupLabel, Spec, Btn, Spinner, StatusBadge, CategoryPill, Sparkline */
const { useState: useS4 } = React;

/* ---------- SECTION 4: BUTTONS ---------- */
function StateCol({ title, children }) {
  return (
    <div style={{ flex: 1, minWidth: 0 }}>
      <div style={{ fontSize: 11, fontWeight: 600, color: 'var(--color-text-muted)', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: 12 }}>{title}</div>
      <div style={{ display: 'flex', alignItems: 'flex-start' }}>{children}</div>
    </div>
  );
}
function BtnRow({ fig, render }) {
  return (
    <div style={{ display: 'grid', gridTemplateColumns: '220px repeat(4,1fr)', alignItems: 'center', gap: 20, padding: '18px 0', borderBottom: '1px solid var(--color-border)' }}>
      <div className="mono" style={{ fontSize: 12, color: 'var(--color-brand-dark)', fontWeight: 600 }}>{fig}</div>
      {render('default')}{render('hover')}{render('loading')}{render('disabled')}
    </div>
  );
}
function HoverNote({ children }) { return <div style={{ fontSize: 10.5, color: 'var(--color-text-muted)', marginTop: 6 }}>{children}</div>; }

function Section4() {
  const cell = (node, note) => <div>{node}{note && <HoverNote>{note}</HoverNote>}</div>;
  return (
    <Section num="04" title="Buttons" alt desc="Figma naming Button/Primary/Default etc. Every variant documents Default · Hover · Loading · Disabled. Amber primary, 40px default height, 10px radius.">
      <div style={{ display: 'grid', gridTemplateColumns: '220px repeat(4,1fr)', gap: 20, paddingBottom: 10, borderBottom: '2px solid var(--color-border-mid)' }}>
        <div style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--color-text-muted)' }}>Component</div>
        {['Default', 'Hover', 'Loading', 'Disabled'].map(s => <div key={s} style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.06em', color: 'var(--color-text-muted)' }}>{s}</div>)}
      </div>
      {[
        ['Button/Primary', 'primary', 'Confirm Order'],
        ['Button/Secondary', 'secondary', 'Add Item'],
        ['Button/Ghost', 'ghost', 'Cancel'],
        ['Button/Danger', 'danger', 'Reject'],
        ['Button/Success', 'success', 'Approve'],
        ['Button/Outline-Subtle', 'outline', 'Export'],
      ].map(([fig, v, label]) => (
        <BtnRow key={fig} fig={fig} render={(st) => (
          st === 'default' ? cell(<Btn variant={v}>{label}</Btn>)
          : st === 'hover' ? cell(<Btn variant={v} style={v === 'primary' ? { background: 'var(--color-brand-dark)' } : v === 'secondary' ? { background: 'var(--color-brand-light)', color: 'var(--color-brand-dark)', borderColor: 'var(--color-brand-muted)' } : v === 'ghost' ? { background: 'var(--color-brand-50)' } : { filter: 'brightness(0.92)' }}>{label}</Btn>, st === 'hover' ? '+ darker / tint bg' : null)
          : st === 'loading' ? cell(<Btn variant={v} loading>{label}</Btn>)
          : cell(<Btn variant={v} disabled>{label}</Btn>)
        )} />
      ))}

      <GroupLabel>Size &amp; shape variants</GroupLabel>
      <div className="spec-stage" style={{ display: 'flex', flexWrap: 'wrap', gap: 28, alignItems: 'center' }}>
        <div style={{ textAlign: 'center' }}><Btn variant="pill" size="sm">All Orders</Btn><HoverNote>Button/Pill · h32 · radius-full</HoverNote></div>
        <div style={{ textAlign: 'center' }}><Btn variant="pill" size="sm" active>Cutting</Btn><HoverNote>Pill · active (amber-50)</HoverNote></div>
        <div style={{ textAlign: 'center' }}>
          <button aria-label="Print" style={{ width: 36, height: 36, borderRadius: 10, border: '1px solid var(--color-border-mid)', background: '#fff', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: 'var(--color-text-secondary)', cursor: 'pointer' }}><Icon name="printer" /></button>
          <HoverNote>Button/Icon · 36×36 · aria-label</HoverNote>
        </div>
        <div style={{ textAlign: 'center' }}><Btn variant="primary" size="lg" icon="plus">New Order</Btn><HoverNote>Button/PrimaryLarge · h48</HoverNote></div>
        <div style={{ textAlign: 'center' }}><Btn variant="primary" icon="scan">Scan QR</Btn><HoverNote>Primary + leading icon</HoverNote></div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 5: FORM ELEMENTS ---------- */
function FieldShell({ children, focus, error, disabled, style }) {
  return (
    <div style={{
      display: 'flex', alignItems: 'center', gap: 9, height: 40, padding: '0 12px',
      borderRadius: 10, background: disabled ? 'var(--color-surface-alt)' : '#fff', fontSize: 14,
      border: `1px solid ${error ? 'var(--color-danger)' : focus ? 'var(--color-brand)' : 'var(--color-border-mid)'}`,
      boxShadow: focus ? '0 0 0 3px rgba(217,119,6,0.18)' : error ? '0 0 0 3px rgba(220,38,38,0.12)' : 'none',
      color: disabled ? 'var(--color-text-muted)' : 'var(--color-text-primary)', ...style,
    }}>{children}</div>
  );
}
function FieldLabel({ children, req }) {
  return <label style={{ display: 'block', fontSize: 13, fontWeight: 500, color: 'var(--color-gray-700)', marginBottom: 7 }}>{children}{req && <span style={{ color: 'var(--color-danger)' }}> *</span>}</label>;
}

function Toggle({ on }) {
  return <span style={{ display: 'inline-flex', width: 44, height: 24, borderRadius: 9999, background: on ? 'var(--color-brand)' : 'var(--color-border-mid)', padding: 2, transition: 'all .2s', justifyContent: on ? 'flex-end' : 'flex-start' }}><span style={{ width: 20, height: 20, borderRadius: '50%', background: '#fff', boxShadow: '0 1px 2px rgba(0,0,0,0.2)' }} /></span>;
}

function Section5() {
  return (
    <Section num="05" title="Form Elements" desc="Inputs share a 40px height and 10px radius. Focus = amber border + 2px amber glow. Errors always pair red border with an alert icon and message. Sixpay-style search uses a pill with Ctrl+K hint.">
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 28 }}>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>TextInput/Default</div><FieldLabel>Customer name</FieldLabel><FieldShell><span style={{ color: 'var(--color-text-muted)' }}>Ramesh Kumar</span></FieldShell></div>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>TextInput/Focus</div><FieldLabel>Customer name</FieldLabel><FieldShell focus><span>Ramesh Kumar</span><span style={{ width: 1, height: 18, background: 'var(--color-brand)', marginLeft: -3 }} /></FieldShell></div>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>TextInput/Error</div><FieldLabel req>Phone</FieldLabel><FieldShell error><span>98XX</span></FieldShell><div style={{ display: 'flex', alignItems: 'center', gap: 5, marginTop: 6, color: 'var(--color-danger)', fontSize: 12 }}><Icon name="alert" size={13} color="var(--color-danger)" />Enter a valid 10-digit number</div></div>
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>TextInput/Disabled</div><FieldLabel>Branch (locked)</FieldLabel><FieldShell disabled><span>HQ — Chennai</span><Icon name="lock" size={14} style={{ marginLeft: 'auto' }} /></FieldShell></div>
      </div>

      <GroupLabel>Search · Select · Date · Measurement</GroupLabel>
      <div style={{ display: 'grid', gridTemplateColumns: '1.4fr 1fr 1fr', gap: 24, alignItems: 'start' }}>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>SearchInput</div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 9, height: 42, padding: '0 8px 0 14px', borderRadius: 9999, background: 'var(--bg-neutral)' }}>
            <Icon name="search" size={17} color="var(--color-text-muted)" />
            <span style={{ flex: 1, fontSize: 14, color: 'var(--color-text-muted)' }}>Search order, customer, invoice…</span>
            <span style={{ display: 'inline-flex', gap: 3 }}><span className="mono" style={{ padding: '2px 6px', borderRadius: 6, background: '#fff', border: '1px solid var(--color-border-mid)', fontSize: 11, color: 'var(--color-text-secondary)' }}>Ctrl</span><span className="mono" style={{ padding: '2px 6px', borderRadius: 6, background: '#fff', border: '1px solid var(--color-border-mid)', fontSize: 11, color: 'var(--color-text-secondary)' }}>K</span></span>
          </div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '20px 0 8px' }}>SelectDropdown — open</div>
          <FieldShell><span>Tailoring</span><Icon name="chevronDown" size={16} style={{ marginLeft: 'auto' }} color="var(--color-text-muted)" /></FieldShell>
          <div style={{ marginTop: 6, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 10, boxShadow: 'var(--shadow-md)', padding: 6 }}>
            {['Cutting', 'Tailoring', 'QC', 'Packing'].map((o, i) => <div key={o} style={{ padding: '8px 12px', borderRadius: 7, fontSize: 13.5, background: i === 1 ? 'var(--color-brand-light)' : 'transparent', color: i === 1 ? 'var(--color-brand-dark)' : 'var(--color-gray-700)', fontWeight: i === 1 ? 600 : 400 }}>{o}{i === 1 && <Icon name="check" size={14} style={{ float: 'right' }} color="var(--color-brand-dark)" />}</div>)}
          </div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>DateRangePicker</div>
          <FieldShell style={{ borderRadius: 9999, width: 'fit-content', paddingRight: 14 }}><Icon name="calendar" size={16} color="var(--color-text-secondary)" /><span style={{ fontSize: 13.5 }}>Jun 1 – Jun 30, 2025</span><Icon name="chevronDown" size={15} color="var(--color-text-muted)" /></FieldShell>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, margin: '20px 0 8px' }}>Textarea</div>
          <div style={{ minHeight: 80, padding: 12, borderRadius: 10, border: '1px solid var(--color-border-mid)', background: '#fff', fontSize: 13.5, color: 'var(--color-text-muted)' }}>Production notes…</div>
        </div>
        <div>
          <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 8 }}>MeasurementInput</div>
          <FieldLabel>Chest</FieldLabel>
          <div style={{ display: 'flex' }}><FieldShell style={{ borderRadius: '10px 0 0 10px', flex: 1 }}><span>40</span></FieldShell><span style={{ display: 'inline-flex', alignItems: 'center', padding: '0 12px', height: 40, background: 'var(--bg-neutral)', border: '1px solid var(--color-border-mid)', borderLeft: 'none', borderRadius: '0 10px 10px 0', fontSize: 13, color: 'var(--color-text-secondary)', fontWeight: 500 }}>cm</span></div>
          <FieldLabel>Sleeve</FieldLabel>
          <div style={{ display: 'flex' }}><FieldShell error style={{ borderRadius: '10px 0 0 10px', flex: 1 }}><span>72</span></FieldShell><span style={{ display: 'inline-flex', alignItems: 'center', padding: '0 12px', height: 40, background: '#FEF3C7', border: '1px solid var(--color-warning)', borderLeft: 'none', borderRadius: '0 10px 10px 0', fontSize: 13, color: 'var(--color-warning)', fontWeight: 500 }}>cm</span></div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 5, marginTop: 6, color: 'var(--color-warning)', fontSize: 12 }}><Icon name="alert" size={13} color="var(--color-warning)" />Above normal threshold</div>
        </div>
      </div>

      <GroupLabel>Selection controls</GroupLabel>
      <div className="spec-stage" style={{ display: 'flex', gap: 40, flexWrap: 'wrap', alignItems: 'center' }}>
        {[['Checkbox', <span key="c" style={{ display: 'flex', gap: 12 }}><span style={{ width: 18, height: 18, borderRadius: 4, border: '1.5px solid var(--color-border-mid)' }} /><span style={{ width: 18, height: 18, borderRadius: 4, background: 'var(--color-brand)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="check" size={13} color="#fff" sw={2.5} /></span></span>],
          ['Radio', <span key="r" style={{ display: 'flex', gap: 12 }}><span style={{ width: 18, height: 18, borderRadius: '50%', border: '1.5px solid var(--color-border-mid)' }} /><span style={{ width: 18, height: 18, borderRadius: '50%', border: '1.5px solid var(--color-brand)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><span style={{ width: 8, height: 8, borderRadius: '50%', background: 'var(--color-brand)' }} /></span></span>],
          ['Toggle — off / on', <span key="t" style={{ display: 'flex', gap: 12 }}><Toggle /><Toggle on /></span>]].map(([n, node]) => (
          <div key={n}><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 12 }}>{n}</div>{node}</div>
        ))}
        <div><div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 12 }}>Helper / Error text</div><div style={{ fontSize: 12, color: 'var(--color-text-secondary)' }}>Measured at fullest point.</div><div style={{ display: 'flex', alignItems: 'center', gap: 5, marginTop: 4, color: 'var(--color-danger)', fontSize: 12 }}><Icon name="alert" size={13} color="var(--color-danger)" />This field is required</div></div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 6: BADGES + TAGS ---------- */
const PROD_STATES = [
  ['Draft', '#6B7280', '#F3F4F6'], ['Fabric Allocated', '#B45309', '#FEF3C7'], ['Cutting', '#D97706', '#FEF3C7'],
  ['Tailoring', '#2563EB', '#DBEAFE'], ['Kaja Button', '#2563EB', '#DBEAFE'], ['Finishing', '#2563EB', '#DBEAFE'],
  ['QC', '#374151', '#F3F4F6'], ['Packing', '#6B7280', '#F3F4F6'], ['Ready for Delivery', '#16A34A', '#DCFCE7'], ['Delivered', '#16A34A', '#DCFCE7'],
];
function AlertTag({ icon, c, bg, children }) {
  return <span style={{ display: 'inline-flex', alignItems: 'center', gap: 7, padding: '6px 12px', borderRadius: 8, background: bg, color: c, borderLeft: `3px solid ${c}`, fontSize: 12.5, fontWeight: 500 }}><Icon name={icon} size={14} color={c} sw={2} />{children}</span>;
}
function Section6() {
  return (
    <Section num="06" title="Status Badges &amp; Tags" alt desc="Pill badges pair a colored dot with a label so color is never the sole signal. Production state badges extend the same system across the 10-stage workflow. QC stays neutral gray with an amber indicator.">
      <GroupLabel>StatusBadge — dot + label</GroupLabel>
      <div className="spec-stage" style={{ display: 'flex', flexWrap: 'wrap', gap: 12 }}>
        <StatusBadge kind="approved" /><StatusBadge kind="pending" /><StatusBadge kind="rejected" /><StatusBadge kind="inprogress" /><StatusBadge kind="draft" /><StatusBadge kind="ready" /><StatusBadge kind="overdue" pulse /><StatusBadge kind="rework" /><StatusBadge kind="delivered" /><StatusBadge kind="cancelled" strike />
      </div>

      <GroupLabel>Production state badges — 10-stage workflow</GroupLabel>
      <div className="spec-stage" style={{ display: 'flex', flexWrap: 'wrap', gap: 12 }}>
        {PROD_STATES.map(([n, c, bg]) => (
          <span key={n} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '5px 13px 5px 11px', borderRadius: 9999, background: bg, color: c, fontSize: 12.5, fontWeight: 500, borderLeft: n === 'QC' ? '3px solid #D97706' : 'none' }}>
            <span style={{ width: 7, height: 7, borderRadius: '50%', background: n === 'QC' ? '#D97706' : c }} />{n}
          </span>
        ))}
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 32 }}>
        <div>
          <GroupLabel>Alert tags — icon + left border</GroupLabel>
          <div className="spec-stage" style={{ display: 'flex', flexDirection: 'column', gap: 12, alignItems: 'flex-start' }}>
            <AlertTag icon="alert" c="#DC2626" bg="#FEE2E2">Overdue — 3 days</AlertTag>
            <AlertTag icon="package" c="#D97706" bg="#FEF3C7">Low stock — 4m left</AlertTag>
            <AlertTag icon="checkCircle" c="#16A34A" bg="#DCFCE7">Approved</AlertTag>
            <AlertTag icon="refresh" c="#D97706" bg="#FEF3C7">Rework required</AlertTag>
          </div>
        </div>
        <div>
          <GroupLabel>Category pills + numeric badge</GroupLabel>
          <div className="spec-stage" style={{ display: 'flex', flexWrap: 'wrap', gap: 10, alignItems: 'center' }}>
            <CategoryPill icon="shirt">Tailoring</CategoryPill>
            <CategoryPill icon="scissors">Cutting</CategoryPill>
            <CategoryPill icon="checkCircle">QC Done</CategoryPill>
            <CategoryPill icon="truck">Delivery</CategoryPill>
            <CategoryPill icon="wallet">Finance</CategoryPill>
            <CategoryPill icon="refresh" active>Rework</CategoryPill>
            <span style={{ width: 20, height: 20, borderRadius: '50%', background: 'var(--color-danger)', color: '#fff', fontSize: 11, fontWeight: 600, display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>5</span>
          </div>
        </div>
      </div>
    </Section>
  );
}

/* ---------- SECTION 7: METRIC CARDS ---------- */
function MetricCard({ label, icon, value, trend, trendColor, data, fill, sparkColor, accent, wide, children }) {
  return (
    <div style={{ background: '#fff', border: '1px solid var(--color-border)', borderRadius: 20, padding: 24, boxShadow: 'var(--shadow-xs)', borderLeft: accent ? `3px solid ${accent}` : '1px solid var(--color-border)', gridColumn: wide ? 'span 2' : 'auto' }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <span style={{ fontSize: 13, color: 'var(--color-text-secondary)' }}>{label}</span>
        {icon && <Icon name={icon} size={17} color="var(--color-text-muted)" />}
      </div>
      <div style={{ fontSize: wide ? 40 : 40, fontWeight: 700, letterSpacing: '-0.02em', marginTop: 10, lineHeight: 1.05 }}>{value}</div>
      {trend && <div style={{ display: 'inline-flex', alignItems: 'center', gap: 4, marginTop: 6, fontSize: 12.5, fontWeight: 500, color: trendColor }}><Icon name={trendColor === 'var(--color-success)' ? 'trendUp' : trendColor === 'var(--color-danger)' ? 'trendDown' : 'minus'} size={14} color={trendColor} />{trend}</div>}
      {children}
      {data && <div style={{ marginTop: 16 }}><Sparkline data={data} color={sparkColor} fill={fill} w={wide ? 360 : 200} h={36} /></div>}
    </div>
  );
}
function Section7() {
  return (
    <Section num="07" title="Metric Cards with Sparklines" desc="The hero card type, modelled on Sixpay's metric tiles. Large 40px number dominates, a trend indicator sits below, and a mini sparkline gives a pure visual signal — up green, down red, neutral amber.">
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 20 }}>
        <MetricCard label="Today's Orders" icon="shirt" value="23" trend="12.4%" trendColor="var(--color-success)" data={[4, 6, 5, 8, 7, 11, 10, 13]} fill sparkColor="var(--color-success)" />
        <MetricCard label="Pending Deliveries" icon="truck" value="14" trend="0%" trendColor="var(--color-text-muted)" accent="var(--color-warning)" data={[8, 7, 9, 8, 8, 9, 8, 8]} fill sparkColor="var(--color-warning)" />
        <MetricCard label="Overdue Items" icon="alert" value="6" trend="3 items" trendColor="var(--color-danger)" accent="var(--color-danger)" data={[2, 2, 3, 3, 4, 5, 5, 6]} fill sparkColor="var(--color-danger)" />
        {/* dark preview */}
        <div style={{ background: '#1C1917', borderRadius: 20, padding: 24 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between' }}><span style={{ fontSize: 13, color: '#A8A29E' }}>Today's Orders</span><Icon name="shirt" size={17} color="#A8A29E" /></div>
          <div style={{ fontSize: 40, fontWeight: 700, color: '#FAFAF9', marginTop: 10, letterSpacing: '-0.02em' }}>23</div>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 4, marginTop: 6, fontSize: 12.5, color: '#4ADE80' }}><Icon name="trendUp" size={14} color="#4ADE80" />12.4%</div>
          <div style={{ marginTop: 16 }}><Sparkline data={[4, 6, 5, 8, 7, 11, 10, 13]} color="#4ADE80" fill w={200} h={36} /></div>
          <div className="mono" style={{ fontSize: 10, color: '#78716C', marginTop: 12 }}>MetricCard · dark</div>
        </div>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 20, marginTop: 20 }}>
        <MetricCard wide label="Monthly Revenue" icon="wallet" value="₹8,42,500" data={[5, 6, 5.5, 7, 6.5, 8, 7.5, 9, 8.5, 9.2]} fill sparkColor="var(--color-brand)">
          <div style={{ display: 'flex', gap: 24, marginTop: 14 }}>
            <div><div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>Income</div><div style={{ fontSize: 16, fontWeight: 600, color: 'var(--color-success)' }}>₹9,20,000</div></div>
            <div><div style={{ fontSize: 12, color: 'var(--color-text-muted)' }}>Expenses</div><div style={{ fontSize: 16, fontWeight: 600, color: 'var(--color-danger)' }}>₹77,500</div></div>
          </div>
        </MetricCard>
        <div style={{ gridColumn: 'span 2', display: 'flex', alignItems: 'center', padding: 24, background: 'var(--color-brand-50)', borderRadius: 20, border: '1px solid var(--color-brand-muted)' }}>
          <div style={{ fontSize: 13, color: 'var(--color-gray-700)', lineHeight: 1.55 }}>
            <b style={{ color: 'var(--color-brand-dark)' }}>Figma naming.</b> MetricCard/Positive/Default · MetricCard/Warning · MetricCard/Danger/Overdue · MetricCard/Wide.<br />
            Width ~220px · padding 24 · radius 20 · shadow-xs. Warning &amp; Danger variants carry a 3px colored left border. Wide variant spans 2 columns and uses a bar sparkline footer.
          </div>
        </div>
      </div>
    </Section>
  );
}

Object.assign(window, { Section4, Section5, Section6, Section7, MetricCard });
