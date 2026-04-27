@extends('platform.layouts.master')

@section('page_title', 'Notifications')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
    <h4 class="mb-0">Platform Notifications</h4>
    <div class="d-flex" style="gap:8px;">
        <a href="{{ route('platform.notifications.index') }}" class="btn btn-sm {{ request('unread') ? 'btn-outline-secondary' : 'btn-secondary' }}">All</a>
        <a href="{{ route('platform.notifications.index', ['unread' => 1]) }}" class="btn btn-sm {{ request('unread') ? 'btn-secondary' : 'btn-outline-secondary' }}">Unread Only ({{ number_format($unreadCount) }})</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:170px;">Time</th>
                    <th style="width:160px;">Type</th>
                    <th>Details</th>
                    <th style="width:170px;">School</th>
                    <th style="width:130px;">Status</th>
                    <th style="width:120px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $item)
                <tr class="{{ $item->read_at ? '' : 'table-warning' }}">
                    <td>{{ optional($item->created_at)->format('d M Y h:i A') }}</td>
                    <td><span class="badge badge-{{ $item->read_at ? 'secondary' : 'info' }}">{{ str_replace('_', ' ', ucfirst($item->type)) }}</span></td>
                    <td>
                        <div class="font-weight-semibold">{{ $item->title }}</div>
                        <div class="text-muted">{{ $item->message }}</div>
                    </td>
                    <td>
                        @if($item->school)
                        <a href="{{ route('platform.schools.show', $item->school) }}">{{ $item->school->name }}</a>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item->read_at)
                        <span class="badge badge-success">Read</span>
                        @else
                        <span class="badge badge-warning">Unread</span>
                        @endif
                    </td>
                    <td>
                        @if(!$item->read_at)
                        <form method="POST" action="{{ route('platform.notifications.read', $item) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                        </form>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No notifications yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $notifications->links() }}</div>
@endsection