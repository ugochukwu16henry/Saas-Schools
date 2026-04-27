@extends('platform.layouts.master')

@section('page_title', 'Outbound Webhooks')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Outbound Webhooks</h4>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">Register Endpoint</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('platform.webhooks.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-semibold">Name</label>
                        <input name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">URL</label>
                        <input name="url" type="url" class="form-control" value="{{ old('url') }}" placeholder="https://example.com/webhooks/riseflow" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Events</label>
                        <input name="events" class="form-control" value="{{ old('events') }}" placeholder="school.registered,billing.payment_failure,*. Leave empty for all">
                        <small class="text-muted">Comma-separated event names. Use * for all events.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Signing Secret (optional)</label>
                        <input name="secret" class="form-control" value="{{ old('secret') }}" placeholder="Leave blank to auto-generate">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Endpoint</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Events</th>
                            <th>Delivery Health</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($webhooks as $hook)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $hook->name }}</div>
                                <div class="text-muted small">{{ $hook->url }}</div>
                            </td>
                            <td>
                                @php $events = $hook->events ?: ['*']; @endphp
                                <span class="text-muted">{{ implode(', ', $events) }}</span>
                            </td>
                            <td>
                                <div class="small text-muted">Deliveries: {{ number_format((int) $hook->deliveries_count) }}</div>
                                @if($hook->last_success_at)
                                <div class="small text-success">Last success: {{ $hook->last_success_at->format('d M Y h:i A') }}</div>
                                @endif
                                @if($hook->last_failure_at)
                                <div class="small text-danger">Last failure: {{ $hook->last_failure_at->format('d M Y h:i A') }}</div>
                                @endif
                                @if(!$hook->last_success_at && !$hook->last_failure_at)
                                <span class="text-muted">No deliveries yet</span>
                                @endif
                            </td>
                            <td>
                                @if($hook->is_active)
                                <span class="badge badge-success">Active</span>
                                @else
                                <span class="badge badge-secondary">Paused</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex" style="gap:6px;">
                                    <form method="POST" action="{{ route('platform.webhooks.toggle', $hook) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ $hook->is_active ? 'Pause' : 'Resume' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('platform.webhooks.destroy', $hook) }}" onsubmit="return confirm('Delete this webhook endpoint?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No webhook endpoints configured.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $webhooks->links() }}</div>
    </div>
</div>
@endsection