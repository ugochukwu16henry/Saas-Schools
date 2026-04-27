@if(!empty($dunningBanner))
<div class="alert alert-{{ $dunningBanner['level'] ?? 'warning' }} border-0 alert-dismissible mb-3">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <div><strong>{{ $dunningBanner['title'] ?? 'Billing notice' }}</strong></div>
    <div>{{ $dunningBanner['message'] ?? '' }}</div>
    @if(!empty($dunningBanner['meta']))
    <div class="small mt-1">{{ $dunningBanner['meta'] }}</div>
    @endif
    <div class="mt-2">
        <a href="{{ route('billing.prompt') }}" class="btn btn-sm btn-{{ ($dunningBanner['level'] ?? 'warning') === 'danger' ? 'light' : 'warning' }}">Open Billing</a>
    </div>
</div>
@endif