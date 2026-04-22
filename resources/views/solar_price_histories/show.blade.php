@extends('layouts.main')

@section('title_page')
    Solar price history #{{ $solarPriceHistory->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('solar-price-histories.index') }}">Solar Price Histories</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    @can('edit-solar-price-histories')
                        <a href="{{ route('solar-price-histories.edit', $solarPriceHistory) }}"
                            class="btn btn-sm btn-warning">Edit</a>
                    @endcan
                    <a href="{{ route('solar-price-histories.index') }}"
                        class="btn btn-sm btn-default float-right">Back to
                        list</a>
                </div>
                <div class="card-body">
                    <table class="table table-borderless col-md-8">
                        <tr>
                            <th width="35%">Period</th>
                            <td>{{ $solarPriceHistory->period_start?->format('Y-m-d') }} →
                                {{ $solarPriceHistory->period_end?->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>Unit price</th>
                            <td>{{ number_format((float) $solarPriceHistory->unit_price, 4) }}</td>
                        </tr>
                        <tr>
                            <th>Quantity / amount (snapshot)</th>
                            <td>{{ $solarPriceHistory->quantity ?? '—' }} / {{ $solarPriceHistory->amount ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Invoice</th>
                            <td>
                                <a
                                    href="{{ route('invoices.show', $solarPriceHistory->invoice_id) }}">{{ $solarPriceHistory->invoice?->invoice_number ?? $solarPriceHistory->invoice_id }}</a>
                            </td>
                        </tr>
                        <tr>
                            <th>Line</th>
                            <td>
                                @if ($solarPriceHistory->invoiceLineDetail)
                                    #{{ $solarPriceHistory->invoiceLineDetail->line_no }}
                                    {{ $solarPriceHistory->invoiceLineDetail->description }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td>{{ $solarPriceHistory->notes ? nl2br(e($solarPriceHistory->notes)) : '—' }}</td>
                        </tr>
                        <tr>
                            <th>Created by</th>
                            <td>{{ $solarPriceHistory->creator?->name ?? '—' }} ·
                                {{ $solarPriceHistory->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
