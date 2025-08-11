@extends('layouts.main')

@section('title_page')
    Invoice Attachment Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Attachments</li>
@endsection

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
            </div>
        </div>
    </div>
@endsection
