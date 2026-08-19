@extends('layouts.main')

@section('title_page')
    Create Signature Specimen
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.signature-specimens.index') }}">Signature Specimens</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Signature Specimen</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.signature-specimens.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.signature-specimens.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nik">NIK</label>
                                    <input type="text" name="nik" id="nik" class="form-control @error('nik') is-invalid @enderror"
                                        value="{{ old('nik') }}">
                                    @error('nik')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department_id">Department</label>
                                    <select name="department_id" id="department_id" class="form-control @error('department_id') is-invalid @enderror">
                                        <option value="">— Select —</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                                {{ $department->location_code }} — {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="d-block">Active</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                            @checked(old('is_active', true))>
                                        <label class="custom-control-label" for="is_active">Specimen is active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Projects <span class="text-danger">*</span></label>
                            <select name="project_ids[]" class="form-control @error('project_ids') is-invalid @enderror" multiple required>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected(collect(old('project_ids', []))->contains($project->id))>
                                        {{ $project->code }} — {{ $project->owner }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple projects.</small>
                            @error('project_ids')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Specimen images <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                                    id="images" name="images[]" accept="image/jpeg,image/png" multiple required>
                                <label class="custom-file-label" for="images">Choose one or more images (JPG/PNG)</label>
                            </div>
                            @error('images')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('images.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Specimen
                        </button>
                        <a href="{{ route('admin.signature-specimens.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $('.custom-file-input').on('change', function() {
            var files = $(this)[0].files;
            var label = files.length > 1 ? files.length + ' files selected' : (files[0] ? files[0].name : 'Choose file');
            $(this).next('.custom-file-label').text(label);
        });
    </script>
@endsection
