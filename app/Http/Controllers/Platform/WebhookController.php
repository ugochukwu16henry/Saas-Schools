<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformWebhookEndpoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = PlatformWebhookEndpoint::query()
            ->withCount(['deliveries'])
            ->latest('id')
            ->paginate(20);

        return view('platform.webhooks.index', compact('webhooks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:255'],
            'events' => ['nullable', 'string', 'max:500'],
            'secret' => ['nullable', 'string', 'max:120'],
        ]);

        $events = array_values(array_filter(array_map(
            static fn($e) => trim($e),
            explode(',', (string) ($data['events'] ?? ''))
        )));

        PlatformWebhookEndpoint::create([
            'name' => $data['name'],
            'url' => $data['url'],
            'events' => $events,
            'secret' => $data['secret'] ?: Str::random(40),
            'is_active' => true,
        ]);

        return back()->with('status', 'Webhook endpoint created.');
    }

    public function toggle(PlatformWebhookEndpoint $webhook): RedirectResponse
    {
        $webhook->update(['is_active' => ! $webhook->is_active]);

        return back()->with('status', 'Webhook endpoint status updated.');
    }

    public function destroy(PlatformWebhookEndpoint $webhook): RedirectResponse
    {
        $webhook->delete();

        return back()->with('status', 'Webhook endpoint deleted.');
    }
}
