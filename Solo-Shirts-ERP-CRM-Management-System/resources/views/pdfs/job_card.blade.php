@extends('pdfs.layout')

@section('title', 'Job Card ' . $order->order_code)

@section('doc-meta')
    <span class="doc-number">{{ $order->order_code }}</span><br>
    <strong>Production Job Card</strong><br>
    Delivery: {{ optional($order->expected_delivery_date)->format('d M Y') ?? '—' }}
@endsection

@section('branch')
    Branch: {{ $order->branch?->name ?? 'HQ' }} &nbsp;|&nbsp; Created: {{ optional($order->created_at)->format('d M Y, h:i A') }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Customer</div>
            <div class="info-row">
                <div class="info-row__value">{{ $order->customer?->name ?? '—' }}</div>
                @if($order->customer?->phone)
                    <div class="info-row__label">{{ $order->customer->phone }}</div>
                @endif
            </div>
            <div class="info-row" style="margin-top: 8px;">
                <div class="info-row__label">Order Code</div>
                <div class="info-row__mono">{{ $order->order_code }}</div>
            </div>
        </div>
        <div class="info-grid__col">
            <div class="section-label">Production Stage</div>
            <div style="margin-top: 6px;">
                <div class="stage-check">
                    <span class="stage-check__box"></span>Cutting
                </div>
                <div class="stage-check">
                    <span class="stage-check__box"></span>Tailoring
                </div>
                <div class="stage-check">
                    <span class="stage-check__box"></span>QC
                </div>
                <div class="stage-check">
                    <span class="stage-check__box"></span>Packed
                </div>
            </div>
        </div>
    </div>

    <div class="section-label">Order Items</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Product</th>
                <th class="right">Qty</th>
                <th>Stage</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td class="mono bold">{{ $item->item_code }}</td>
                    <td>{{ $item->product_type }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower(str_replace('_', '', (string) $item->state)) }}">
                            {{ ucwords(str_replace('_', ' ', (string) $item->state)) }}
                        </span>
                    </td>
                    <td style="color:#6B7280;">{{ $item->notes ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->notes)
    <div class="section-label">Production Notes</div>
    <div style="padding: 8px 10px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 4px; font-size: 10.5px; color: #374151;">
        {{ $order->notes }}
    </div>
    @endif

    <div style="display:table; width:100%; margin-top: 24px;">
        <div style="display:table-cell; width:50%;">
            <div class="signature-line">Cutting Master</div>
        </div>
        <div style="display:table-cell; width:50%; padding-left:20px;">
            <div class="signature-line">Production Supervisor</div>
        </div>
    </div>
@endsection

@section('footer-left', 'Job Card · ' . $order->order_code)
@section('footer-right', 'Printed: {{ now()->format(\'d M Y, h:i A\') }}')
