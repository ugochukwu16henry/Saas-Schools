@extends('layouts.master')
@section('page_title', 'Bulk import students')
@section('content')
    <div class="card">
        <div class="card-header bg-white header-elements-inline">
            <h6 class="card-title">Import students from Excel</h6>
            {!! Qs::getPanelOptions() !!}
        </div>
        <div class="card-body">
            <p class="text-muted">
                Download the template, fill the <strong>Students</strong> sheet, then upload here.
                Default login password for new students is <strong>student</strong>.
                You can complete parent, dorm, blood group, and other fields later via <em>Student Information → Edit</em>.
            </p>
            <p>
                <a href="{{ route('students.bulk.template') }}" class="btn btn-outline-primary">Download Excel template</a>
                <a href="{{ route('students.create') }}" class="btn btn-link">Single admit</a>
            </p>

            @if(session('billing_required'))
                <div class="alert alert-warning">
                    <a href="{{ route('billing.prompt') }}">Go to billing</a> to activate your subscription, then try again.
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @if(session('bulk_import_errors'))
                <div class="alert alert-danger">
                    <strong>Fix these rows and re-upload:</strong>
                    <ul class="mb-0">
                        @foreach(session('bulk_import_errors') as $rowNum => $message)
                            <li>Row {{ $rowNum }}: {{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('students.bulk.store') }}" enctype="multipart/form-data" class="mt-3">
                @csrf
                <div class="form-group">
                    <label>Excel file (.xlsx or .xls) <span class="text-danger">*</span></label>
                    <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls" required>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nal_id">Nationality (applied to all rows) <span class="text-danger">*</span></label>
                            <select data-placeholder="Choose..." required name="nal_id" id="nal_id" class="select-search form-control">
                                <option value=""></option>
                                @foreach($nationals as $nal)
                                    <option {{ (string) old('nal_id') === (string) $nal->id ? 'selected' : '' }} value="{{ $nal->id }}">{{ $nal->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="state_id">State <span class="text-danger">*</span></label>
                            <select onchange="getLGA(this.value)" required name="state_id" id="state_id" class="select-search form-control" data-placeholder="Choose..">
                                <option value=""></option>
                                @foreach($states as $st)
                                    <option {{ (string) old('state_id') === (string) $st->id ? 'selected' : '' }} value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lga_id">LGA <span class="text-danger">*</span></label>
                            <select required name="lga_id" id="lga_id" class="select-search form-control" data-placeholder="Select state first">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="default_address">Default address (applied to all rows; edit individual students later) <span class="text-danger">*</span></label>
                    <input type="text" name="default_address" id="default_address" class="form-control" required minlength="6" maxlength="120" value="{{ old('default_address', 'Address to be updated from student profile') }}" placeholder="At least 6 characters">
                </div>

                <button type="submit" class="btn btn-primary">Upload and import</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            var oldState = @json(old('state_id'));
            var oldLga = @json(old('lga_id'));
            if (oldState) {
                getLGA(oldState);
                if (oldLga) {
                    setTimeout(function () {
                        $('#lga_id').val(String(oldLga)).trigger('change');
                    }, 600);
                }
            }
        });
    </script>
@endsection
