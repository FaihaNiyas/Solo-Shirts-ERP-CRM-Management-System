@extends('pdfs.layout')

@section('title', 'Invoice ' . $invoice->invoice_no)

@section('doc-meta')
    <span class="doc-number">{{ $invoice->invoice_no }}</span><br>
    <strong>GST Tax Invoice</strong><br>
    Issued: {{ optional($invoice->issued_at)->format('d M Y') ?? '—' }}<br>
    Treatment: {{ $invoice->gst_treatment }}
@endsection

@section('branch')
    Branch: {{ $invoice->branch?->name ?? 'HQ' }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Bill To</div>
            <div class="info-row">
                <div class="info-row__value">{{ $invoice->customer?->name ?? '—' }}</div>
                @if($invoice->customer?->phone)
                    <div class="info-row__label">{{ $invoice->customer->phone }}</div>
                @endif
            </div>
        </div>
        <div class="info-grid__col">
            <div class="section-label">Invoice Details</div>
            <div class="info-row">
                <div class="info-row__label">Invoice No</div>
                <div class="info-row__mono">{{ $invoice->invoice_no }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Status</div>
                <div class="info-row__value">
                    <span class="status-badge status-{{ strtolower($invoice->status ?? 'pending') }}">
                        {{ ucfirst($invoice->status ?? 'Pending') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="section-label">Line Items</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>HSN</th>
                <th class="right">Qty</th>
                <th class="right">Rate (₹)</th>
                <th class="right">Taxable (₹)</th>
                <th class="right">GST%</th>
                <th class="right">Tax (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="mono">{{ $line->hsn_code }}</td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">{{ number_format($line->unit_price_paise / 100, 2) }}</td>
                    <td class="right">{{ number_format($line->taxable_paise / 100, 2) }}</td>
                    <td class="right">{{ $line->gst_rate }}%</td>
                    <td class="right">{{ number_format($line->tax_paise / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-block">
        <div class="totals-row">
            <div class="totals-row__label">Subtotal</div>
            <div class="totals-row__value">₹{{ number_format($invoice->subtotal_paise / 100, 2) }}</div>
        </div>
        @if($invoice->cgst_paise > 0)
        <div class="totals-row">
            <div class="totals-row__label">CGST</div>
            <div class="totals-row__value">₹{{ number_format($invoice->cgst_paise / 100, 2) }}</div>
        </div>
        <div class="totals-row">
            <div class="totals-row__label">SGST</div>
            <div class="totals-row__value">₹{{ number_format($invoice->sgst_paise / 100, 2) }}</div>
        </div>
        @endif
        @if($invoice->igst_paise > 0)
        <div class="totals-row">
            <div class="totals-row__label">IGST</div>
            <div class="totals-row__value">₹{{ number_format($invoice->igst_paise / 100, 2) }}</div>
        </div>
        @endif
        @if($invoice->delivery_charges_paise > 0)
        <div class="totals-row">
            <div class="totals-row__label">Delivery</div>
            <div class="totals-row__value">₹{{ number_format($invoice->delivery_charges_paise / 100, 2) }}</div>
        </div>
        @endif
        @if($invoice->discount_paise > 0)
        <div class="totals-row">
            <div class="totals-row__label">Discount</div>
            <div class="totals-row__value" style="color:#16A34A;">−₹{{ number_format($invoice->discount_paise / 100, 2) }}</div>
        </div>
        @endif
        <div class="totals-row totals-row--total">
            <div class="totals-row__label">Total</div>
            <div class="totals-row__value">₹{{ number_format($invoice->total_paise / 100, 2) }}</div>
        </div>
    </div>

    <div style="clear:both; margin-top: 20px; text-align: center; color: #9CA3AF; font-size: 10px;">
        Thank you for choosing Solo Shirts India. For queries, contact your branch.
    </div>
@endsection

@section('footer-left', 'Tax Invoice · GSTIN: ' . config('app.gstin', '—'))
@section('footer-right', 'Invoice ' . $invoice->invoice_no . ' · Page 1 of 1')
