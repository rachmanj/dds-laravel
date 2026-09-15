@extends('layouts.main')

@section('title_page')
    Kategori Item
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Kategori Item</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="mb-0">Kategori Item</h4>
                    <p class="text-muted small mb-0">Kelola mapping prefix kode item ke kategori inventory.</p>
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

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($hasSnapshot)
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card card-outline card-info">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm">Jumlah Item per Kategori (Snapshot {{ $snapshotDate?->format('d M Y') }})</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kategori</th>
                                                <th class="text-right">Jumlah Item</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($itemCountByCategory as $category => $count)
                                                <tr>
                                                    <td>{{ $category }}</td>
                                                    <td class="text-right">{{ number_format($count, 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-muted text-center">Tidak ada data item.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-outline card-secondary">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm">Kategori Aktif</h3>
                            </div>
                            <div class="card-body">
                                @if (count($usedCategories) > 0)
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach ($usedCategories as $category)
                                            <li><i class="fas fa-tag text-muted mr-1"></i> {{ $category }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">Belum ada kategori aktif.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Belum ada snapshot inventory sukses. Jumlah item per kategori akan tersedia setelah snapshot harian berjalan.
                </div>
            @endif

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Tambah Mapping Prefix</h3>
                </div>
                <form action="{{ route('logistics.categories.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="prefix">Prefix <span class="text-danger">*</span></label>
                                    <input type="text" name="prefix" id="prefix"
                                        class="form-control @error('prefix') is-invalid @enderror"
                                        value="{{ old('prefix') }}" placeholder="Contoh: SP" maxlength="50" required>
                                    @error('prefix')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category">Kategori <span class="text-danger">*</span></label>
                                    <input type="text" name="category" id="category"
                                        class="form-control @error('category') is-invalid @enderror"
                                        value="{{ old('category') }}" placeholder="Contoh: SPAREPART" maxlength="255" required>
                                    @error('category')
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Mapping Prefix</h3>
                    @if ($hasSnapshot)
                        <form action="{{ route('logistics.categories.recompute') }}" method="POST" class="mb-0"
                            id="recompute-form">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm" id="recompute-btn">
                                <i class="fas fa-sync-alt"></i> Terapkan ulang ke snapshot terakhir
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Prefix</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Diubah Oleh</th>
                                <th>Diubah Pada</th>
                                <th class="text-center" style="width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mappings as $mapping)
                                <tr>
                                    <td><code>{{ $mapping->prefix }}</code></td>
                                    <td>{{ $mapping->category }}</td>
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
                                            data-prefix="{{ $mapping->prefix }}"
                                            data-category="{{ $mapping->category }}"
                                            title="Ubah Kategori">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('logistics.categories.toggle', $mapping) }}" method="POST"
                                            class="d-inline">
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
                                    <td colspan="6" class="text-muted text-center">Belum ada mapping prefix.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                        <h5 class="modal-title" id="editMappingModalLabel">Ubah Kategori Prefix</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Prefix</label>
                            <input type="text" id="edit-prefix" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit-category">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="edit-category" class="form-control" maxlength="255"
                                required>
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
                var prefix = $(this).data('prefix');
                var category = $(this).data('category');

                $('#edit-prefix').val(prefix);
                $('#edit-category').val(category);
                $('#edit-mapping-form').attr('action', '{{ url('logistics/categories') }}/' + id);
                $('#editMappingModal').modal('show');
            });

            $('#recompute-form').on('submit', function(e) {
                if (!confirm('Terapkan ulang kategori ke seluruh item pada snapshot terakhir? Proses ini tidak mengubah data SAP.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
