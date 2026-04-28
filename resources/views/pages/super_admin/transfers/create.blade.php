@extends('layouts.master')
@section('page_title', 'Create Student Transfer')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Create Student Transfer</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <form method="post" action="{{ route('transfers.store') }}">
            @csrf

            <div class="form-group">
                <label class="font-weight-semibold">Student</label>
                <select name="student_id" class="form-control select-search" required>
                    <option value="">Select student</option>
                    @foreach($students as $student)
                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                        {{ $student->name }} ({{ $student->code }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="font-weight-semibold">Receiving School</label>
                <input id="school-search" type="text" class="form-control mb-2" placeholder="Search school by unique code, name, or email">
                <select id="to-school-id" name="to_school_id" class="form-control" required>
                    <option value="">Search and choose receiving school</option>
                </select>
            </div>

            <div class="form-group">
                <label class="font-weight-semibold">Transfer Note (optional)</label>
                <textarea name="transfer_note" class="form-control" rows="3" placeholder="Reason/details for this transfer">{{ old('transfer_note') }}</textarea>
            </div>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">Create Transfer Request</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        var searchInput = document.getElementById('school-search');
        var select = document.getElementById('to-school-id');
        var timer = null;

        function setOptions(rows) {
            select.innerHTML = '<option value="">Search and choose receiving school</option>';
            rows.forEach(function(school) {
                var opt = document.createElement('option');
                opt.value = school.id;
                opt.textContent = school.name + ' (' + (school.unique_code || 'N/A') + ') - ' + (school.email || '');
                select.appendChild(opt);
            });
        }

        searchInput.addEventListener('input', function() {
            var q = searchInput.value.trim();
            clearTimeout(timer);

            if (q.length < 2) {
                setOptions([]);
                return;
            }

            timer = setTimeout(function() {
                fetch('{{ route('transfers.search_school') }}?q=' + encodeURIComponent(q), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(rows) {
                        setOptions(rows || []);
                    })
                    .catch(function() {
                        setOptions([]);
                    });
            }, 250);
        });
    })();
</script>
@endsection