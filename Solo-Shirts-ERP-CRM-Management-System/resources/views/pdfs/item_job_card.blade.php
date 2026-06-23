@extends('pdfs.layout')

@section('title', 'Job Card ' . $item->item_code)

@section('doc-meta')
    <span class="doc-number">{{ $item->item_code }}</span><br>
    <strong>Sub-Order Job Card</strong><br>
    Main Order: {{ $order->order_code }}
@endsection

@section('branch')
    Branch: {{ $order->branch?->name ?? 'HQ' }}
    &nbsp;|&nbsp; Order date: {{ optional($order->created_at)->format('d M Y') }}
    &nbsp;|&nbsp; Delivery: {{ optional($order->expected_delivery_date)->format('d M Y') ?? '—' }}
@endsection

@section('content')
    <div class="info-grid">
        <div class="info-grid__col">
            <div class="section-label">Customer</div>
            <div class="info-row__value">{{ $order->customer?->name ?? '—' }}</div>
            @if($order->customer?->phone)
                <div class="info-row__label">{{ $order->customer->phone }}</div>
            @endif

            <div class="info-row" style="margin-top: 10px;">
                <div class="info-row__label">Main Order</div>
                <div class="info-row__mono">{{ $order->order_code }}</div>
            </div>
            <div class="info-row" style="margin-top: 6px;">
                <div class="info-row__label">Sub-Order</div>
                <div class="info-row__mono">{{ $item->item_code }}</div>
            </div>
        </div>
    </div>

    @php
        $isPant = $item->product_type === 'pant';
        $productLabel = $isPant ? 'Trouser' : ($item->product_type === 'combo' ? 'Shirt + Trouser' : 'Shirt');
    @endphp

    <div class="section-label">Garment</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Fabric</th>
                <th>Style</th>
                <th>Fit</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="bold">{{ $productLabel }}</td>
                <td class="bold">{{ $design['fabric'] ?? $item->fabric_preference_text ?? '—' }}</td>
                <td>{{ $design['style'] ?? '—' }}</td>
                <td>{{ $design['fit'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    @if($version)
        <div class="info-row" style="margin-top: 8px;">
            <div class="info-row__label">Measurement Profile</div>
            <div class="info-row__value">{{ $version->profile?->name ?? '—' }} · v{{ $version->version_number }}</div>
        </div>
    @endif

    <div class="section-label">Measurements ({{ $productLabel }} — this item only)</div>
    @php
        $values = $isPant ? ($version?->pant_data ?? []) : ($version?->shirt_data ?? []);
        $measures = collect($values)
            ->filter(fn ($v, $k) => $v !== null && $v !== '' && !str_starts_with((string) $k, 'note_'))
            ->all();
    @endphp
    @if(count($measures) > 0)
        <div class="measurement-grid">
            @foreach(array_chunk($measures, 4, true) as $row)
                <div class="measurement-row">
                    @foreach($row as $field => $value)
                        <div class="measurement-cell">
                            <div class="measurement-cell__label">{{ ucwords(str_replace('_', ' ', (string) $field)) }}</div>
                            <div class="measurement-cell__value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        <div class="info-row__label">No measurement values recorded for this version.</div>
    @endif

    @if(!empty($design['notes']))
        <div class="section-label">Special Notes</div>
        <div style="padding: 8px 10px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 4px; font-size: 10.5px; color: #374151;">
            {{ $design['notes'] }}
        </div>
    @endif

    <div style="display:table; width:100%; margin-top: 24px;">
        <div style="display:table-cell; width:50%;">
            <div class="signature-line">Cutting Master</div>
        </div>
        <div style="display:table-cell; width:50%; padding-left:20px;">
            <div class="signature-line">Tailor</div>
        </div>
    </div>
@endsection

@section('footer-left', 'Sub-Order Job Card · ' . $item->item_code)
@section('footer-right', 'Prepared by ' . $preparedBy)
