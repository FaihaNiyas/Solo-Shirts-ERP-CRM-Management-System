/* global React, ReactDOM, HatchDefs */

function Masthead() {
  return (
    <header className="masthead">
      <p className="eyebrow">Solo Shirts India ERP · Component Library</p>
      <h1>Design System</h1>
      <p className="tagline">Precision tailoring, managed. A premium, airy component library for the Solo Shirts ERP — amber accent, warm cream surfaces, a light sidebar, and developer-ready handoff specs.</p>
      <div className="meta-row">
        <span className="meta-pill"><span className="dot" style={{ background: '#D97706' }} />Amber Gold · only accent</span>
        <span className="meta-pill"><span className="dot" style={{ background: '#FFFCF5', border: '1px solid #E5E7EB' }} />Warm cream surfaces</span>
        <span className="meta-pill">Inter · JetBrains Mono</span>
        <span className="meta-pill">Lucide outline icons · 1.75px</span>
        <span className="meta-pill">34 sections · Figma-named</span>
      </div>
    </header>
  );
}

const SECTIONS = Array.from({ length: 34 }, (_, i) => window['Section' + (i + 1)]);

function App() {
  return (
    <div className="artboard-wrap">
      <HatchDefs />
      <div className="artboard">
        <Masthead />
        {SECTIONS.map((S, i) => (typeof S === 'function' ? <S key={i} /> : null))}
      </div>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
