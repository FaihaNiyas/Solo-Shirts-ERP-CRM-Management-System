@extends('pdfs.layout')

@section('title', 'Packing Slip ' . $order->order_code)

@section('doc-meta')
    <strong>Packing Slip</strong><br>
    <span class="doc-number">{{ $order->order_code }}</span><br>
    Delivery: {{ optional($order->expected_delivery_date)->format('d M Y') ?? '—' }}
@endsection

@section('branch')
    Branch: {{ $order->branch?->name ?? 'HQ' }} &nbsp;|&nbsp; Mode: {{ ucfirst($order->delivery_mode ?? '—') }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Ship To</div>
            <div class="info-row">
                <div class="info-row__value">{{ $order->customer?->name ?? '—' }}</div>
                @if($order->customer?->phone)
                    <div class="info-row__label">{{ $order->customer->phone }}</div>
                @endif
                @if($order->customer?->address)
                    <div class="info-row__label" style="margin-top:4px;">{{ $order->customer->address }}</div>
                @endif
            </div>
        </div>
        <div class="info-grid__col">
            <div class="section-label">Order Details</div>
            <div class="info-row">
                <div class="info-row__label">Order Code</div>
                <div class="info-row__mono">{{ $order->order_code }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Delivery Mode</div>
                <div class="info-row__value">{{ ucfirst($order->delivery_mode ?? '—') }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Total Items</div>
                <div class="info-row__value">{{ $order->items->count() }}</div>
            </div>
        </div>
    </div>

    <div class="section-label">Items in This Package</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="center">Checked</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
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

    <div style="margin-top: 16px; padding: 10px 14px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 4px; font-size: 10.5px; color: #374151;">
        <strong style="color: #B45309;">Packing Checklist:</strong>
        All items listed above have been inspected, passed QC, and packed for delivery.
        This slip must accompany the package.
    </div>

    <div style="display:table; width:100%; margin-top: 24px;">
        <div style="display:table-cell; width:50%;">
            <div class="signature-line">Packed by</div>
        </div>
        <div style="display:table-cell; width:50%; padding-left:20px;">
            <div class="signature-line">Verified by</div>
        </div>
    </div>
@endsection

@section('footer-left', 'Packing Slip · ' . $order->order_code)
@section('footer-right', 'Solo Shirts India · All items verified')
