@extends('pdfs.layout')

@section('title', 'Pickup Receipt')

@section('doc-meta')
    <strong>Pickup Receipt</strong><br>
    <span class="doc-number">{{ $batch->receipt_no ?? $batch->batch_no }}</span><br>
    {{ optional($batch->handed_over_at ?? $batch->created_at)->format('d M Y, h:i A') }}
@endsection

@section('branch')
    {{ $branch?->name ?? '' }} &nbsp;|&nbsp; Pickup: {{ ucwords(str_replace('_', ' ', $batch->pickup_type)) }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Customer</div>
            <div class="info-row">
                <div class="info-row__value">{{ $customer?->name ?? '—' }}</div>
            </div>
            <div class="info-row" style="margin-top:8px;">
                <div class="info-row__label">Order</div>
                <div class="info-row__mono">{{ $order?->order_code ?? '—' }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Tax Invoice (unchanged)</div>
                <div class="info-row__mono">{{ $invoiceNo ?? '—' }}</div>
            </div>
        </div>
        <div class="info-grid__col">
            <div class="section-label">Pickup</div>
            <div class="info-row">
                <div class="info-row__label">Batch</div>
                <div class="info-row__mono">{{ $batch->batch_no }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Status</div>
                <div class="info-row__value">{{ ucwords(str_replace('_', ' ', $batch->status)) }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Handed over by</div>
                <div class="info-row__value">{{ $staff ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="section-label">Items Collected in This Pickup</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Product</th>
                <th class="right">Item Total</th>
                <th class="right">Paid Before</th>
                <th class="right">Paid Now</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $bi)
                <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td class="mono bold">{{ $bi->orderItem?->item_code }}</td>
                    <td>{{ $bi->orderItem?->product_type }}</td>
                    <td class="right">₹{{ number_format($bi->item_total_paise / 100, 2) }}</td>
                    <td class="right">₹{{ number_format($bi->paid_before_paise / 100, 2) }}</td>
                    <td class="right">₹{{ number_format($bi->paid_in_batch_paise / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align:right; margin-top:10px;">
        <div style="font-size:11px; color:#6B7280;">Paid in this pickup:
            <span style="font-size:13px; font-weight:700; color:#111827;">₹{{ number_format($batch->paid_paise / 100, 2) }}</span>
        </div>
        <div style="font-size:11px; color:#6B7280; margin-top:4px;">Pickup balance:
            <span style="font-size:13px; font-weight:700;">₹{{ number_format($batch->balance_paise / 100, 2) }}</span>
        </div>
        <div style="font-size:11px; color:#6B7280; margin-top:4px;">Remaining order balance (other items):
            <span style="font-size:13px; font-weight:700;">₹{{ number_format($orderBalancePaise / 100, 2) }}</span>
        </div>
    </div>

    @if($payments->count())
    <div class="section-label" style="margin-top:16px;">Payments</div>
    <table>
        <thead>
            <tr>
                <th>Method</th>
                <th>Reference</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $p)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $p->method)) }}</td>
                    <td class="mono">{{ $p->reference_no ?? '—' }}</td>
                    <td class="right">₹{{ number_format($p->amount_paise / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div style="margin-top: 16px; padding: 8px 12px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:4px; font-size:10px; color:#6B7280;">
        This is a pickup acknowledgement for the items listed above. The official GST tax invoice
        ({{ $invoiceNo ?? '—' }}) remains the single financial document for this order.
    </div>

    <div style="display:table; width:100%; margin-top: 24px;">
        <div style="display:table-cell; width:50%;">
            <div class="signature-line">Customer Signature</div>
        </div>
        <div style="display:table-cell; width:50%; padding-left:20px;">
            <div class="signature-line">Front Desk</div>
        </div>
    </div>
@endsection

@section('footer-left', 'Pickup Receipt · ' . ($batch->receipt_no ?? $batch->batch_no))
@section('footer-right', 'Solo Shirts India · soloShirts.in')
