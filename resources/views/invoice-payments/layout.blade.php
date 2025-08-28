@extends('layouts.main')

@section('title_page', 'Invoice Payment Management')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Invoice Payments</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Tab Navigation -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="payment-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ request()->routeIs('invoices.payments.dashboard') ? 'active' : '' }}"
                                        id="dashboard-tab" href="{{ route('invoices.payments.dashboard') }}" role="tab">
                                        <i class="fas fa-chart-pie mr-2"></i>Dashboard
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ request()->routeIs('invoices.payments.waiting') ? 'active' : '' }}"
                                        id="waiting-tab" href="{{ route('invoices.payments.waiting') }}" role="tab">
                                        <i class="fas fa-clock mr-2"></i>Waiting Payment
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ request()->routeIs('invoices.payments.paid') ? 'active' : '' }}"
                                        id="paid-tab" href="{{ route('invoices.payments.paid') }}" role="tab">
                                        <i class="fas fa-check-circle mr-2"></i>Paid Invoices
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            @yield('payment-content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('styles')
    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1rem;
            font-weight: 500;
        }

        .nav-tabs .nav-link:hover {
            color: #495057;
            border-bottom-color: #dee2e6;
        }

        .nav-tabs .nav-link.active {
            color: #007bff;
            border-bottom-color: #007bff;
            background-color: transparent;
        }

        .nav-tabs .nav-link i {
            font-size: 0.9rem;
        }
    </style>
@endsection
