@extends('layouts.main')

@section('title_page')
    Mapping Warehouse
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Mapping Warehouse</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-0">Mapping Warehouse → Project</h4>
                    <p class="text-muted small mb-0">
                        Petakan kode warehouse tujuan SAP (To Warehouse) ke site/project Delivery Part.
                    </p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($sapError)
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Gagal memuat daftar warehouse dari SAP: {{ $sapError }}
                </div>
            @endif

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Tambah Mapping</h3>
                </div>
                <form action="{{ route('logistics.warehouse-projects.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="whs_code">Kode Warehouse <span class="text-danger">*</span></label>
                                    <input type="text" name="whs_code" id="whs_code"
                                        class="form-control @error('whs_code') is-invalid @enderror"
                                        value="{{ old('whs_code') }}" placeholder="Contoh: 08-SPT" maxlength="50" required>
                                    @error('whs_code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project_id">Project / Site <span class="text-danger">*</span></label>
                                    <select name="project_id" id="project_id"
                                        class="form-control @error('project_id') is-invalid @enderror" required>
                                        <option value="">— Pilih project —</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>
                                                {{ $project->code }}@if ($project->location)
                                                    — {{ $project->location }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Mapping Terdaftar</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Diubah Oleh</th>
                                <th>Diubah Pada</th>
                                <th class="text-center" style="width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mappings as $mapping)
                                <tr>
                                    <td><code>{{ $mapping->whs_code }}</code></td>
                                    <td>
                                        <strong>{{ $mapping->project?->code }}</strong>
                                        @if ($mapping->project?->location)
                                            <span class="text-muted small d-block">{{ $mapping->project->location }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($mapping->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>{{ $mapping->updatedBy?->name ?? '-' }}</td>
                                    <td>{{ $mapping->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-xs edit-mapping"
                                            data-id="{{ $mapping->id }}"
                                            data-whs="{{ $mapping->whs_code }}"
                                            data-project-id="{{ $mapping->project_id }}"
                                            data-is-active="{{ $mapping->is_active ? '1' : '0' }}"
                                            title="Ubah Mapping">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('logistics.warehouse-projects.toggle', $mapping) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-{{ $mapping->is_active ? 'secondary' : 'success' }} btn-xs"
                                                title="{{ $mapping->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="fas fa-{{ $mapping->is_active ? 'ban' : 'check' }}"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted text-center">Belum ada mapping warehouse.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Warehouse Belum Dipetakan</h3>
                    <form method="GET" action="{{ route('logistics.warehouse-projects.index') }}"
                        class="form-inline mb-0 mt-2 mt-md-0">
                        <label class="mr-2 small text-muted mb-0" for="from_date">Rentang ITO</label>
                        <input type="date" name="from_date" id="from_date" class="form-control form-control-sm mr-1"
                            value="{{ $fromDate }}">
                        <span class="mx-1">—</span>
                        <input type="date" name="to_date" id="to_date" class="form-control form-control-sm mr-2"
                            value="{{ $toDate }}">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                    </form>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th class="text-right">Baris ITO</th>
                                <th class="text-right">Dokumen</th>
                                <th class="text-center" style="width: 280px;">Petakan ke Project</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unmappedWarehouses as $row)
                                <tr>
                                    <td><code>{{ $row['whs_code'] }}</code></td>
                                    <td class="text-right">{{ number_format($row['row_count'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($row['document_count'], 0, ',', '.') }}</td>
                                    <td>
                                        <form action="{{ route('logistics.warehouse-projects.store') }}" method="POST"
                                            class="form-inline justify-content-center">
                                            @csrf
                                            <input type="hidden" name="whs_code" value="{{ $row['whs_code'] }}">
                                            <select name="project_id" class="form-control form-control-sm mr-1" required>
                                                <option value="">Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}">{{ $project->code }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-xs">
                                                <i class="fas fa-link"></i> Petakan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center">
                                        @if ($sapError)
                                            Tidak dapat menghitung warehouse belum dipetakan.
                                        @else
                                            Semua warehouse pada rentang tanggal sudah dipetakan, atau tidak ada data ITO.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted small">
                    Daftar dihitung dari ITO OUT SAP pada rentang tanggal (cache 10 menit), dikurangi warehouse yang sudah
                    punya mapping di DDS.
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="editMappingModal" tabindex="-1" role="dialog" aria-labelledby="editMappingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="edit-mapping-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMappingModalLabel">Ubah Mapping Warehouse</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Warehouse</label>
                            <input type="text" id="edit-whs" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit-project-id">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="edit-project-id" class="form-control" required>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">
                                        {{ $project->code }}@if ($project->location)
                                            — {{ $project->location }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="is_active" value="1"
                                    id="edit-is-active">
                                <label class="custom-control-label" for="edit-is-active">Mapping aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            $('.edit-mapping').on('click', function() {
                var id = $(this).data('id');
                var whs = $(this).data('whs');
                var projectId = $(this).data('project-id');
                var isActive = $(this).data('is-active') === 1 || $(this).data('is-active') === '1';

                $('#edit-whs').val(whs);
                $('#edit-project-id').val(projectId);
                $('#edit-is-active').prop('checked', isActive);
                $('#edit-mapping-form').attr('action', '{{ url('logistics/warehouse-projects') }}/' + id);
                $('#editMappingModal').modal('show');
            });
        });
    </script>
@endsection
