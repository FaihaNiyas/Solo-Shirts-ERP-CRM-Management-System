@extends('pdfs.layout')

@section('title', 'Packing Slip ' . $item->item_code)

@section('doc-meta')
    <strong>Packing Slip</strong><br>
    <span class="doc-number">{{ $item->item_code }}</span><br>
    Order: {{ $order->order_code ?? '—' }}
@endsection

@section('branch')
    Branch: {{ $order->branch?->name ?? 'HQ' }} &nbsp;|&nbsp; Delivery: {{ optional($order->expected_delivery_date)->format('d M Y') ?? '—' }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Customer</div>
            <div class="info-row">
                <div class="info-row__value">{{ $customer?->name ?? '—' }}</div>
                @if($customer?->phone)
                    <div class="info-row__label">{{ $customer->phone }}</div>
                @endif
            </div>
        </div>
        <div class="info-grid__col">
            <div class="section-label">Sub-order</div>
            <div class="info-row">
                <div class="info-row__label">Item Code</div>
                <div class="info-row__mono">{{ $item->item_code }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Product</div>
                <div class="info-row__value">{{ ucfirst($item->product_type) }}</div>
            </div>
            <div class="info-row">
                <div class="info-row__label">Ready Rack Slot</div>
                <div class="info-row__mono">{{ $rackSlot['slot_code'] ?? 'Not assigned' }}</div>
            </div>
        </div>
    </div>

    <div class="section-label">Garment Details</div>
    <table>
        <tbody>
            <tr><td class="bold" style="width:40%">Fabric</td><td>{{ $fabric ?? '—' }}</td></tr>
            <tr class="alt-row"><td class="bold">Style</td><td>{{ $style ?? '—' }}</td></tr>
            <tr><td class="bold">Fit</td><td>{{ $fit ?? '—' }}</td></tr>
            <tr class="alt-row"><td class="bold">Measurement</td><td>{{ $profile ?? '—' }}{{ $version?->version_number ? ' · v' . $version->version_number : '' }}</td></tr>
        </tbody>
    </table>

    <div class="section-label">Packing Checklist</div>
    <table>
        <tbody>
            <tr><td style="width:70%">Measurement card enclosed</td><td class="center">{{ ($checklist?->checked_measurement_card) ? '☑' : '☐' }}</td></tr>
            <tr class="alt-row"><td>Buttons / accessories</td><td class="center">{{ ($checklist?->checked_buttons) ? '☑' : '☐' }}</td></tr>
            <tr><td>Ironing done</td><td class="center">{{ ($checklist?->checked_ironing) ? '☑' : '☐' }}</td></tr>
            <tr class="alt-row"><td>Folded</td><td class="center">{{ ($checklist?->checked_folded) ? '☑' : '☐' }}</td></tr>
            <tr><td>Packing cover</td><td class="center">{{ ($checklist?->checked_packing_cover) ? '☑' : '☐' }}</td></tr>
            <tr class="alt-row"><td>Label attached</td><td class="center">{{ ($checklist?->checked_label) ? '☑' : '☐' }}</td></tr>
        </tbody>
    </table>

    <div style="margin-top: 14px; padding: 10px 14px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 4px; font-size: 10.5px; color: #374151;">
        <strong style="color: #B45309;">Note:</strong>
        Balance is checked during handover. This slip confirms packing only — it does not mark the item delivered.
    </div>

    <div style="display:table; width:100%; margin-top: 22px;">
        <div style="display:table-cell; width:50%;">
            <div class="signature-line">
                Packed by{{ $checklist?->packed_at ? ': ' . optional($checklist->packed_at)->format('d M Y, H:i') : '' }}
            </div>
        </div>
        <div style="display:table-cell; width:50%; padding-left:20px;">
            <div class="signature-line">Handed over by</div>
        </div>
    </div>
@endsection

@section('footer-left', 'Packing Slip · ' . $item->item_code)
@section('footer-right', 'Solo Shirts India · Prepared by ' . ($preparedBy ?? '—'))
