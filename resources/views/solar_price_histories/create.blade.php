@extends('layouts.main')

@section('title_page')
    New solar price history
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('solar-price-histories.index') }}">Solar Price Histories</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (! $pertaminaId)
                <div class="alert alert-warning">Supplier “PERTAMINA” was not found.</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card card-primary">
                <form method="post" action="{{ route('solar-price-histories.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="invoice_id">Invoice (PERTAMINA) <span class="text-danger">*</span></label>
                            <select class="form-control" name="invoice_id" id="invoice_id" required>
                                <option value="">— Select —</option>
                                @foreach ($invoices as $inv)
                                    <option value="{{ $inv->id }}" @selected(old('invoice_id') == $inv->id)>
                                        {{ $inv->invoice_number }} · {{ $inv->invoice_date?->format('Y-m-d') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="invoice_line_detail_id">Line (must contain SOLAR) <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" name="invoice_line_detail_id" id="invoice_line_detail_id"
                                required>
                                <option value="">— Select invoice first —</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="unit_price">Unit price <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" class="form-control" name="unit_price" id="unit_price"
                                value="{{ old('unit_price') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="period_start">Period start <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="period_start" id="period_start"
                                value="{{ old('period_start') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="period_end">Period end <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="period_end" id="period_end"
                                value="{{ old('period_end') }}" required>
                        </div>
                        <p class="text-muted small">Common patterns: <strong>1st–14th</strong> of a month, or
                            <strong>15th–last day</strong> of that month.
                        </p>
                        <div class="form-group">
                            <label for="quantity">Quantity (optional)</label>
                            <input type="text" class="form-control" name="quantity" id="quantity"
                                value="{{ old('quantity') }}">
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount (optional)</label>
                            <input type="text" class="form-control" name="amount" id="amount"
                                value="{{ old('amount') }}">
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control" name="notes" id="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('solar-price-histories.index') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function() {
            const base = @json(url('solar-price-histories/invoices'));
            const invoiceEl = document.getElementById('invoice_id');
            const lineEl = document.getElementById('invoice_line_detail_id');
            const oldLine = @json(old('invoice_line_detail_id'));

            function loadLines(invoiceId) {
                lineEl.innerHTML = '<option value="">Loading…</option>';
                if (!invoiceId) {
                    lineEl.innerHTML = '<option value="">— Select invoice first —</option>';
                    return;
                }
                fetch(base + '/' + invoiceId + '/solar-lines', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j)))
                    .then(data => {
                        lineEl.innerHTML = '<option value="">— Select line —</option>';
                        (data.lines || []).forEach(function(l) {
                            const opt = document.createElement('option');
                            opt.value = l.id;
                            opt.textContent = '#' + l.line_no + ' ' + l.description;
                            if (oldLine != null && String(oldLine) === String(l.id)) {
                                opt.selected = true;
                            }
                            lineEl.appendChild(opt);
                        });
                    })
                    .catch(function() {
                        lineEl.innerHTML = '<option value="">— No SOLAR lines —</option>';
                    });
            }
            invoiceEl.addEventListener('change', function() {
                loadLines(this.value);
            });
            if (invoiceEl.value) {
                loadLines(invoiceEl.value);
            }
        })();
    </script>
@endsection
