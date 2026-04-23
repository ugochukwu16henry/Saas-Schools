@extends('layouts.master')
@section('page_title', 'AI Announcement Draft')

@section('content')
<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title">Generate Announcement Draft</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <p class="text-muted">Use AI to draft a message, then review and edit it before publishing.</p>

        <form id="ai-announcement-form">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Audience</label>
                        <input type="text" class="form-control" name="audience" value="Parents and students" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tone</label>
                        <select class="form-control" name="tone" required>
                            <option value="Professional" selected>Professional</option>
                            <option value="Friendly">Friendly</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Encouraging">Encouraging</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Language</label>
                        <input type="text" class="form-control" name="language" value="English" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Context (optional)</label>
                <textarea class="form-control" name="context" rows="3" placeholder="Example: Mid-term examinations start next week"></textarea>
            </div>

            <div class="form-group">
                <label>Key points</label>
                <textarea class="form-control" name="key_points" rows="5" required placeholder="List the specific points you want in the announcement"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="ai-generate-btn">Generate draft</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Generated Draft</h6>
    </div>
    <div class="card-body">
        <div id="ai-meta" class="text-muted small mb-2"></div>
        <textarea id="ai-draft-output" class="form-control" rows="14" placeholder="Your AI draft will appear here..."></textarea>
        <p class="text-muted small mt-2 mb-0">AI-generated content may be inaccurate. Review before sending.</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function() {
        var form = document.getElementById('ai-announcement-form');
        var output = document.getElementById('ai-draft-output');
        var meta = document.getElementById('ai-meta');
        var button = document.getElementById('ai-generate-btn');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            button.disabled = true;
            button.innerText = 'Generating...';
            meta.innerText = '';

            var formData = new FormData(form);
            fetch("{{ route('ai.announcement.generate') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            }).then(function(response) {
                return response.json().then(function(data) {
                    return { status: response.status, data: data };
                });
            }).then(function(result) {
                if (result.status >= 200 && result.status < 300) {
                    output.value = result.data.draft || '';
                    var provider = result.data.provider ? ('Provider: ' + result.data.provider) : '';
                    var model = result.data.model ? (' Model: ' + result.data.model) : '';
                    var fallback = result.data.fallback_from ? (' Fallback from: ' + result.data.fallback_from) : '';
                    meta.innerText = provider + model + fallback;
                } else {
                    output.value = '';
                    meta.innerText = result.data.message || 'Unable to generate draft.';
                }
            }).catch(function() {
                output.value = '';
                meta.innerText = 'Network error while generating draft.';
            }).finally(function() {
                button.disabled = false;
                button.innerText = 'Generate draft';
            });
        });
    })();
</script>
@endsection
