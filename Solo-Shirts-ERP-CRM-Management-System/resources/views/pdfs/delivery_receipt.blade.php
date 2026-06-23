
@extends('pdfs.layout')

@section('title', 'Delivery Receipt')

@section('doc-meta')
    <strong>Delivery Receipt</strong><br>
    <span class="doc-number">DEL-{{ str_pad($delivery->id, 6, '0', STR_PAD_LEFT) }}</span><br>
    {{ optional($delivery->completed_at ?? $delivery->created_at)->format('d M Y') }}
@endsection

@section('branch')
    Mode: {{ ucfirst($delivery->mode) }} &nbsp;|&nbsp; Status: {{ ucfirst($delivery->status) }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Customer</div>
            <div class="info-row">
                <div class="info-row__value">{{ $delivery->order?->customer?->name ?? '—' }}</div>
                @if($delivery->order?->customer?->phone)
                    <div class="info-row__label">{{ $delivery->order->customer->phone }}</div>
                @endif
            </div>
            <div class="info-row" style="margin-top:8px;">
                <div class="info-row__label">Order</div>
                <div class="info-row__mono">{{ $delivery->order?->order_code ?? '—' }}</div>
            </div>
        </div>
        <div class="info-grid__col">
            <div class="section-label">Delivery Info</div>
            <div class="info-row">
                <div class="info-row__label">Delivery Mode</div>
                <div class="info-row__value">{{ ucfirst($delivery->mode) }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Status</div>
                <div class="info-row__value">
                    <span class="status-badge status-{{ strtolower($delivery->status) }}">
                        {{ ucfirst($delivery->status) }}
                    </span>
                </div>
            </div>
            @if($delivery->completed_at)
            <div class="info-row">
                <div class="info-row__label">Completed</div>
                <div class="info-row__value">{{ optional($delivery->completed_at)->format('d M Y, h:i A') }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($delivery->order?->items?->count())
    <div class="section-label">Items Delivered</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="center">Received</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($delivery->order->items as $item)
                <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td class="mono bold">{{ $item->item_code }}</td>
                    <td>{{ $item->product_type }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="center">☐</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($delivery->delivery_charges_paise > 0)
    <div style="text-align:right; margin-top:8px;">
        <span style="font-size:11px; color:#6B7280;">Delivery Charges: </span>
        <span style="font-size:13px; font-weight:700;">₹{{ number_format($delivery->delivery_charges_paise / 100, 2) }}</span>
    </div>
    @endif

    <div style="margin-top: 20px;">
        <div class="section-label">OTP Confirmation</div>
        <div style="padding: 10px 14px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 4px; font-size: 11px; color: #6B7280;">
            OTP verified at delivery. Customer confirmed receipt of all items listed above.
        </div>
    </div>

    <div style="display:table; width:100%; margin-top: 24px;">
        <div style="display:table-cell; width:50%;">
            <div class="signature-line">Customer Signature</div>
        </div>
        <div style="display:table-cell; width:50%; padding-left:20px;">
            <div class="signature-line">Delivery Staff</div>
        </div>
    </div>
@endsection

@section('footer-left', 'Delivery Receipt · DEL-' . str_pad($delivery->id, 6, '0', STR_PAD_LEFT))
@section('footer-right', 'Solo Shirts India · soloShirts.in')
