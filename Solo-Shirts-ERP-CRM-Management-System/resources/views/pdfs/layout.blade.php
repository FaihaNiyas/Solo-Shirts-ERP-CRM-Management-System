<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Document') — Solo Shirts India</title>
    <style>
        /* PDF print styles — Solo Shirts ERP Design System */
        /* White bg, black text, amber as brand accent only */
        * {
            font-family: DejaVu Sans, Arial, sans-serif;
            box-sizing: border-box;
        }
        body {
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 20px 24px;
            background: #ffffff;
            line-height: 1.4;
        }

        /* ---- Header ---- */
        .pdf-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #D97706;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .pdf-header__left  { display: table-cell; vertical-align: middle; width: 60%; }
        .pdf-header__right { display: table-cell; vertical-align: middle; text-align: right; }

        .brand-mark {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #D97706;
            color: #ffffff;
            font-weight: 700;
            font-size: 11px;
            text-align: center;
            line-height: 28px;
            border-radius: 6px;
            vertical-align: middle;
            margin-right: 6px;
        }
        .brand-name {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            vertical-align: middle;
            letter-spacing: -0.01em;
        }
        .brand-tagline {
            font-size: 9px;
            color: #6B7280;
            margin-top: 2px;
        }
        .doc-meta {
            font-size: 10px;
            color: #6B7280;
            line-height: 1.6;
        }
        .doc-meta strong {
            color: #111827;
            font-weight: 600;
        }
        .doc-meta .doc-number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        /* ---- Branch info bar ---- */
        .branch-bar {
            background: #FEF3C7;
            border: 1px solid #FDE68A;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 14px;
            font-size: 10px;
            color: #B45309;
            font-weight: 500;
        }

        /* ---- Section labels ---- */
        .section-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9CA3AF;
            margin: 14px 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #F3F4F6;
        }

        /* ---- Two-column info grid ---- */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .info-grid__col { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .info-grid__col:last-child { padding-right: 0; }
        .info-row { margin-bottom: 5px; }
        .info-row__label { font-size: 9.5px; color: #6B7280; font-weight: 500; }
        .info-row__value { font-size: 11px; color: #111827; font-weight: 600; }
        .info-row__mono  { font-family: 'Courier New', Courier, monospace; font-size: 10.5px; color: #111827; font-weight: 600; }

        /* ---- Tables ---- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th {
            background: #F9FAFB;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6B7280;
            padding: 7px 10px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        td {
            padding: 7px 10px;
            font-size: 10.5px;
            color: #374151;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr.alt-row td    { background: #FAFAFA; }
        .right { text-align: right; }
        .center{ text-align: center; }
        .bold  { font-weight: 700; }
        .mono  { font-family: 'Courier New', Courier, monospace; }

        /* ---- Totals block ---- */
        .totals-block {
            width: 240px;
            margin-left: auto;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .totals-row {
            display: table;
            width: 100%;
        }
        .totals-row__label { display: table-cell; padding: 6px 12px; font-size: 10.5px; color: #6B7280; }
        .totals-row__value { display: table-cell; padding: 6px 12px; text-align: right; font-size: 10.5px; font-weight: 600; color: #111827; }
        .totals-row--total {
            background: #111827;
        }
        .totals-row--total .totals-row__label,
        .totals-row--total .totals-row__value {
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
        }
        .totals-row--paid .totals-row__value  { color: #16A34A; }
        .totals-row--balance .totals-row__value { color: #DC2626; }

        /* ---- Status badges ---- */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .status-approved  { background: #DCFCE7; color: #15803D; }
        .status-pending   { background: #FEF3C7; color: #B45309; }
        .status-rejected  { background: #FEE2E2; color: #B91C1C; }
        .status-inprogress{ background: #DBEAFE; color: #1D4ED8; }
        .status-draft     { background: #F3F4F6; color: #4B5563; }

        /* ---- Measurement grid ---- */
        .measurement-grid {
            display: table;
            width: 100%;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .measurement-row { display: table-row; }
        .measurement-row:nth-child(even) .measurement-cell { background: #FAFAFA; }
        .measurement-cell {
            display: table-cell;
            width: 25%;
            padding: 6px 10px;
            border-right: 1px solid #F3F4F6;
            border-bottom: 1px solid #F3F4F6;
        }
        .measurement-cell:last-child { border-right: none; }
        .measurement-cell__label { font-size: 9px; color: #6B7280; font-weight: 500; }
        .measurement-cell__value { font-size: 12px; font-weight: 700; color: #111827; }
        .measurement-cell__unit  { font-size: 9px; color: #9CA3AF; }

        /* ---- Checklist / stage boxes ---- */
        .stage-check { display: inline-block; margin-right: 12px; font-size: 10.5px; }
        .stage-check__box {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid #374151;
            vertical-align: middle;
            margin-right: 4px;
            border-radius: 2px;
        }
        .stage-check__box--done { background: #D97706; border-color: #D97706; }

        /* ---- Signature line ---- */
        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 32px;
            padding-top: 6px;
            font-size: 9.5px;
            color: #6B7280;
        }

        /* ---- Footer ---- */
        .pdf-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #F3F4F6;
            font-size: 9px;
            color: #9CA3AF;
            display: table;
            width: 100%;
        }
        .pdf-footer__left  { display: table-cell; }
        .pdf-footer__right { display: table-cell; text-align: right; }

        /* ---- Label sticker layouts ---- */
        .label-sticker {
            border: 1.5px solid #374151;
            border-radius: 4px;
            padding: 8px 10px;
            display: inline-block;
            min-width: 180px;
        }
        .label-sticker__code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            letter-spacing: 0.02em;
        }
        .label-sticker__detail { font-size: 9.5px; color: #374151; margin-top: 2px; }

        /* Page break utility */
        .page-break { page-break-after: always; }

        /* Print optimizations */
        @media print {
            body { margin: 0; padding: 16px 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header__left">
            <span class="brand-mark">SS</span>
            <span class="brand-name">Solo Shirts India</span>
            <div class="brand-tagline">Precision tailoring, managed.</div>
        </div>
        <div class="pdf-header__right">
            <div class="doc-meta">
                @yield('doc-meta')
            </div>
        </div>
    </div>

    @hasSection('branch')
    <div class="branch-bar">@yield('branch')</div>
    @endif

    @yield('content')

    <div class="pdf-footer">
        <div class="pdf-footer__left">
            Solo Shirts India &mdash; @yield('footer-left', 'soloShirts.in')
        </div>
        <div class="pdf-footer__right">
            @yield('footer-right', 'Generated by Solo Shirts ERP')
        </div>
    </div>
</body>
</html>
