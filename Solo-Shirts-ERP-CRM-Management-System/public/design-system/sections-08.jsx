/* global React, Icon, Section, GroupLabel, Btn, Spinner */

function ChartCard({ title, sub, toggle, children, h = 'auto', span }) {
  return (
    <div style={{ background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 20, padding: 22, boxShadow: 'var(--shadow-xs)', gridColumn: span ? `span ${span}` : 'auto', display: 'flex', flexDirection: 'column' }}>
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 18 }}>
        <div>
          <div style={{ fontSize: 16, fontWeight: 600 }}>{title}</div>
          {sub && <div style={{ fontSize: 12.5, color: 'var(--color-text-muted)', marginTop: 3 }}>{sub}</div>}
        </div>
        {toggle}
      </div>
      <div style={{ flex: 1 }}>{children}</div>
    </div>
  );
}
function PeriodToggle({ opts = ['Weekly', 'Monthly'], active = 0 }) {
  return (
    <div style={{ display: 'flex', gap: 4, background: 'var(--bg-neutral)', padding: 3, borderRadius: 9999 }}>
      {opts.map((o, i) => <span key={o} style={{ padding: '5px 13px', borderRadius: 9999, fontSize: 12.5, fontWeight: 500, background: i === active ? '#fff' : 'transparent', color: i === active ? 'var(--color-brand-dark)' : 'var(--color-text-secondary)', boxShadow: i === active ? 'var(--shadow-xs)' : 'none' }}>{o}</span>)}
    </div>
  );
}
function Legend({ items }) {
  return <div style={{ display: 'flex', gap: 16, fontSize: 12, color: 'var(--color-text-secondary)' }}>{items.map(([c, l, hatch]) => <span key={l} style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}><span style={{ width: 12, height: 12, borderRadius: 3, background: hatch ? 'repeating-linear-gradient(45deg,#FEF3C7 0 3px,#FCD34D 3px 5px)' : c, border: c === '#fff' ? '1px solid #ccc' : 'none' }} />{l}</span>)}</div>;
}

/* CHART 1 — grouped bar */
function GroupedBar() {
  const days = [['Mon', 14, 11], ['Tue', 18, 13], ['Wed', 20, 12], ['Thu', 9, 14], ['Fri', 16, 10], ['Sat', 15, 13], ['Sun', 17, 11]];
  const H = 180, max = 20;
  return (
    <div>
      <div style={{ display: 'flex' }}>
        <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'space-between', height: H, paddingRight: 10, fontSize: 11, color: 'var(--color-text-muted)' }}>{[20, 15, 10, 5, 0].map(v => <span key={v}>{v}</span>)}</div>
        <svg viewBox={`0 0 420 ${H + 26}`} style={{ flex: 1 }}>
          {[0, 0.25, 0.5, 0.75, 1].map(g => <line key={g} x1="0" x2="420" y1={H * g} y2={H * g} stroke="#F3F4F6" />)}
          {days.map((d, i) => {
            const bw = 18, gap = 6, gx = 14 + i * 58;
            const h1 = (d[1] / max) * H, h2 = (d[2] / max) * H;
            const tall = d[1] === 20;
            return (
              <g key={d[0]}>
                <rect x={gx} y={H - h1} width={bw} height={h1} rx="4" fill="#D97706" />
                <rect x={gx + bw + gap} y={H - h2} width={bw} height={h2} rx="4" fill="url(#hatch)" />
                <text x={gx + bw + gap / 2} y={H + 18} textAnchor="middle" fontSize="11" fill="#9CA3AF">{d[0]}</text>
                {tall && <g><rect x={gx - 6} y={H - h1 - 26} width="44" height="20" rx="6" fill="#111827" /><text x={gx + 16} y={H - h1 - 12} textAnchor="middle" fontSize="11" fontWeight="600" fill="#fff">20</text></g>}
              </g>
            );
          })}
        </svg>
      </div>
      <div style={{ marginTop: 12 }}><Legend items={[['#D97706', 'This week'], [null, 'Last week', true]]} /></div>
    </div>
  );
}

/* CHART 2 — multi line area */
function MultiLine() {
  const W = 420, H = 170;
  const rev = [3, 3.4, 3.2, 4, 4.6, 4.3, 5.2, 5, 5.8, 6.4, 6.1, 7];
  const col = [2.6, 2.8, 3, 3.2, 3.8, 4, 4.2, 4.6, 4.8, 5.4, 5.6, 6.2];
  const mk = (arr, max = 8) => arr.map((v, i) => [(i / (arr.length - 1)) * W, H - (v / max) * H]);
  const path = pts => pts.map((p, i) => `${i ? 'L' : 'M'}${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
  const rp = mk(rev), cp = mk(col);
  return (
    <div>
      <div style={{ display: 'flex' }}>
        <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'space-between', height: H, paddingRight: 10, fontSize: 11, color: 'var(--color-text-muted)' }}>{['₹8L', '₹6L', '₹4L', '₹2L', '₹0'].map(v => <span key={v}>{v}</span>)}</div>
        <svg viewBox={`0 0 ${W} ${H + 6}`} style={{ flex: 1, overflow: 'visible' }}>
          {[0, 0.25, 0.5, 0.75, 1].map(g => <line key={g} x1="0" x2={W} y1={H * g} y2={H * g} stroke="#F3F4F6" />)}
          <line x1="0" x2={W} y1={H * 0.3} y2={H * 0.3} stroke="#D1D5DB" strokeDasharray="4 4" />
          <path d={`${path(rp)} L${W} ${H} L0 ${H} Z`} fill="#D97706" opacity="0.08" />
          <path d={path(rp)} fill="none" stroke="#D97706" strokeWidth="2.5" strokeLinecap="round" />
          <path d={path(cp)} fill="none" stroke="#16A34A" strokeWidth="2.5" strokeLinecap="round" />
          <circle cx={rp[9][0]} cy={rp[9][1]} r="4" fill="#D97706" stroke="#fff" strokeWidth="2" />
          <g><rect x={rp[9][0] - 44} y={rp[9][1] - 52} width="92" height="42" rx="8" fill="#111827" /><text x={rp[9][0]} y={rp[9][1] - 36} textAnchor="middle" fontSize="10" fill="#A8A29E">Day 10</text><text x={rp[9][0]} y={rp[9][1] - 21} textAnchor="middle" fontSize="11" fontWeight="600" fill="#fff">₹6.4L · ₹5.4L</text></g>
        </svg>
      </div>
      <div style={{ marginTop: 12 }}><Legend items={[['#D97706', 'Revenue'], ['#16A34A', 'Collections'], ['#D1D5DB', 'Target']]} /></div>
    </div>
  );
}

/* CHART 3 — donut */
function Donut() {
  const segs = [['Delivered', 38, '#16A34A'], ['Production', 28, '#D97706'], ['Ready', 18, '#2563EB'], ['Pending', 12, '#9CA3AF'], ['Overdue', 4, '#DC2626']];
  const R = 64, C = 2 * Math.PI * R; let off = 0;
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
      <svg width="160" height="160" viewBox="0 0 160 160">
        <g transform="rotate(-90 80 80)">
          {segs.map(([n, v, c]) => { const len = (v / 100) * C; const el = <circle key={n} cx="80" cy="80" r={R} fill="none" stroke={c} strokeWidth="20" strokeDasharray={`${len} ${C - len}`} strokeDashoffset={-off} />; off += len; return el; })}
        </g>
        <text x="80" y="74" textAnchor="middle" fontSize="30" fontWeight="700" fill="#111827">142</text>
        <text x="80" y="94" textAnchor="middle" fontSize="11" fill="#9CA3AF">Total Orders</text>
      </svg>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px 18px', flex: 1 }}>
        {segs.map(([n, v, c]) => <span key={n} style={{ display: 'flex', alignItems: 'center', gap: 7, fontSize: 12.5, color: 'var(--color-gray-700)' }}><span style={{ width: 9, height: 9, borderRadius: '50%', background: c }} />{n}<b style={{ marginLeft: 'auto', fontWeight: 600 }}>{v}%</b></span>)}
      </div>
    </div>
  );
}

/* CHART 4 — stacked horizontal */
function HBar() {
  const rows = [['HQ', 420000, 100, '8%', true], ['Anna Nagar', 280000, 67, '4%', false], ['Velachery', 190000, 45, '2%', true]];
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
      {rows.map(([n, amt, pct, t, up]) => (
        <div key={n}>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 7, fontSize: 13 }}>
            <span style={{ fontWeight: 500 }}>{n}</span>
            <span style={{ display: 'flex', gap: 10, alignItems: 'center' }}><span className="mono" style={{ fontWeight: 600 }}>₹{amt.toLocaleString('en-IN')}</span><span style={{ display: 'inline-flex', alignItems: 'center', gap: 3, color: up ? 'var(--color-success)' : 'var(--color-danger)', fontSize: 12 }}><Icon name={up ? 'trendUp' : 'trendDown'} size={12} color={up ? 'var(--color-success)' : 'var(--color-danger)'} />{t}</span></span>
          </div>
          <div style={{ height: 14, background: 'var(--bg-neutral)', borderRadius: 7 }}><div style={{ width: `${pct}%`, height: '100%', background: 'var(--color-brand)', borderRadius: 7 }} /></div>
        </div>
      ))}
    </div>
  );
}

/* CHART 5 — sparkline strip */
function SparkStrip() {
  const days = [['Mon', [4, 6, 5, 8], 24, true], ['Tue', [8, 7, 9, 11], 31, true], ['Wed', [11, 9, 8, 7], 22, false], ['Thu', [7, 8, 10, 13], 28, true], ['Fri', [13, 11, 10, 9], 19, false], ['Sat', [9, 11, 12, 14], 26, true], ['Sun', [14, 12, 13, 11], 21, false]];
  const W = 60, H = 40;
  return (
    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
      {days.map(([d, data, val, up]) => { const mn = Math.min(...data), mx = Math.max(...data), sp = mx - mn || 1; const pts = data.map((v, i) => `${(i / 3) * W},${H - ((v - mn) / sp) * (H - 6) - 3}`).join(' '); const c = up ? '#16A34A' : '#DC2626'; return (
        <div key={d} style={{ textAlign: 'center' }}>
          <div style={{ fontSize: 12, fontWeight: 600, marginBottom: 4 }}>{val}</div>
          <svg width={W} height={H}><polyline points={pts} fill="none" stroke={c} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
          <div style={{ fontSize: 11, color: 'var(--color-text-muted)', marginTop: 4 }}>{d}</div>
        </div>
      ); })}
    </div>
  );
}

/* CHART 6 — vertical KPI bars */
function KPIBars() {
  const bars = [['Cutting', 72, '4%', true], ['Tailoring', 58, '2%', false], ['Kaja', 85, '6%', true], ['Finishing', 40, '8%', false], ['QC', 91, '3%', true], ['Packing', 33, '5%', false]];
  const H = 150;
  return (
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', gap: 12 }}>
      {bars.map(([n, pct, t, up]) => (
        <div key={n} style={{ flex: 1, textAlign: 'center' }}>
          <div style={{ fontSize: 12.5, fontWeight: 600, marginBottom: 6 }}>{pct}%</div>
          <div style={{ height: H, background: 'var(--bg-neutral)', borderRadius: 8, display: 'flex', alignItems: 'flex-end', overflow: 'hidden' }}><div style={{ width: '100%', height: `${pct}%`, background: 'var(--color-brand)', borderRadius: '8px 8px 0 0' }} /></div>
          <div style={{ fontSize: 11.5, fontWeight: 500, marginTop: 8 }}>{n}</div>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 2, fontSize: 11, color: up ? 'var(--color-success)' : 'var(--color-danger)' }}><Icon name={up ? 'trendUp' : 'trendDown'} size={11} color={up ? 'var(--color-success)' : 'var(--color-danger)'} />{t}</div>
        </div>
      ))}
    </div>
  );
}

/* CHART 7 — calendar heatmap */
function HeatMap() {
  const scale = ['#F3F4F6', '#FEF3C7', '#FDE68A', '#FCD34D', '#D97706', '#B45309'];
  return (
    <div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7,1fr)', gap: 7 }}>
        {['M', 'T', 'W', 'T', 'F', 'S', 'S'].map((d, i) => <div key={i} style={{ textAlign: 'center', fontSize: 11, color: 'var(--color-text-muted)', marginBottom: 2 }}>{d}</div>)}
        {Array.from({ length: 35 }).map((_, i) => { const lvl = (i * 7 + 3) % 6; const today = i === 17; return <div key={i} style={{ aspectRatio: '1', borderRadius: 7, background: scale[lvl], border: today ? '2px solid #D97706' : '1px solid rgba(0,0,0,0.03)' }} />; })}
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 16, fontSize: 11, color: 'var(--color-text-muted)' }}>Fewer{scale.map((c, i) => <span key={i} style={{ width: 14, height: 14, borderRadius: 4, background: c }} />)}More</div>
    </div>
  );
}

/* CHART 8 — radial gauge pair */
function Gauge({ value, label, color, status, statusColor }) {
  const R = 56, C = Math.PI * R;
  return (
    <div style={{ textAlign: 'center', flex: 1 }}>
      <svg width="150" height="90" viewBox="0 0 150 90">
        <path d="M19 80 A56 56 0 0 1 131 80" fill="none" stroke="#F3F4F6" strokeWidth="12" strokeLinecap="round" />
        <path d="M19 80 A56 56 0 0 1 131 80" fill="none" stroke={color} strokeWidth="12" strokeLinecap="round" strokeDasharray={`${(value / 100) * C} ${C}`} />
        <text x="75" y="72" textAnchor="middle" fontSize="28" fontWeight="700" fill="#111827">{value}%</text>
      </svg>
      <div style={{ fontSize: 13, fontWeight: 500, marginTop: 2 }}>{label}</div>
      <div style={{ fontSize: 12, color: statusColor, fontWeight: 500, marginTop: 2 }}>{status}</div>
    </div>
  );
}

/* states row */
function StateBox({ title, children }) {
  return (
    <div style={{ flex: 1, background: '#fff', border: '1px solid var(--color-border-mid)', borderRadius: 16, padding: 20 }}>
      <div className="mono" style={{ fontSize: 11.5, color: 'var(--color-brand-dark)', fontWeight: 600, marginBottom: 16 }}>{title}</div>
      {children}
    </div>
  );
}

function Section8() {
  return (
    <Section num="08" title="Chart Library — 8 Types" alt desc="Each chart sits in its own white card with title left and period toggle right. Primary series is solid amber; comparisons use a gray diagonal hatch. Flat colors — no gradients on bars. Every chart ships loading, empty and error states.">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 24 }}>
        <ChartCard title="Orders Completed" toggle={<PeriodToggle />}><GroupedBar /></ChartCard>
        <ChartCard title="Revenue Trend" sub="last 30 days" toggle={<PeriodToggle opts={['30d', '90d']} />}><MultiLine /></ChartCard>
        <ChartCard title="Order Status"><Donut /></ChartCard>
        <ChartCard title="Branch Performance" sub="Revenue this month"><HBar /></ChartCard>
        <ChartCard title="Production Throughput" sub="last 7 days"><SparkStrip /></ChartCard>
        <ChartCard title="Stage Completion Today"><KPIBars /></ChartCard>
        <ChartCard title="Delivery Schedule" sub="June 2025"><HeatMap /></ChartCard>
        <ChartCard title="Quality &amp; Delivery Gauges"><div style={{ display: 'flex', gap: 12 }}><Gauge value={87} label="QC Pass Rate" color="#D97706" status="Below target" statusColor="var(--color-warning)" /><Gauge value={94} label="On-time Delivery" color="#16A34A" status="Above target" statusColor="var(--color-success)" /></div></ChartCard>
      </div>

      <GroupLabel>Chart states — loading · empty · error</GroupLabel>
      <div style={{ display: 'flex', gap: 20 }}>
        <StateBox title="ChartState/Loading">
          <div style={{ display: 'flex', alignItems: 'flex-end', gap: 10, height: 120 }}>{[60, 90, 50, 110, 70, 95, 80].map((h, i) => <div key={i} className="shimmer" style={{ flex: 1, height: h, borderRadius: 6 }} />)}</div>
        </StateBox>
        <StateBox title="ChartState/Empty">
          <div style={{ height: 120, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 10, color: 'var(--color-text-muted)' }}>
            <Icon name="barChart" size={30} color="var(--color-text-muted)" />
            <div style={{ fontSize: 13, fontWeight: 500, color: 'var(--color-text-secondary)' }}>No data for this period</div>
            <div style={{ fontSize: 12 }}>Try a different date range</div>
          </div>
        </StateBox>
        <StateBox title="ChartState/Error">
          <div style={{ height: 120, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
            <Icon name="alert" size={28} color="var(--color-danger)" />
            <div style={{ fontSize: 13, fontWeight: 500, color: 'var(--color-text-secondary)' }}>Couldn't load chart</div>
            <span className="mono" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 11, background: 'var(--bg-neutral)', padding: '3px 8px', borderRadius: 6, color: 'var(--color-text-secondary)' }}>REQ-7F2A91BC <Icon name="copy" size={12} /></span>
          </div>
        </StateBox>
      </div>
    </Section>
  );
}

Object.assign(window, { Section8, ChartCard, PeriodToggle });
