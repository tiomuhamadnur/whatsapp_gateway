<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ProductPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductPlanController extends Controller
{
    public function index(): View
    {
        return view('owner.plans.index', [
            'plans' => ProductPlan::query()->orderBy('sort_order')->get(),
            'types' => ['text', 'image', 'document', 'audio', 'video'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductPlan::query()->create($this->validatedData($request));

        return back()->with('status', 'Paket layanan berhasil dibuat.');
    }

    public function update(Request $request, ProductPlan $plan): RedirectResponse
    {
        $plan->update($this->validatedData($request, $plan));

        return back()->with('status', 'Paket layanan berhasil diperbarui.');
    }

    public function destroy(ProductPlan $plan): RedirectResponse
    {
        $plan->update(['is_active' => false]);

        return back()->with('status', 'Paket layanan dinonaktifkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?ProductPlan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_period' => ['nullable', 'string', 'max:20'],
            'daily_message_quota' => ['required', 'integer', 'min:0'],
            'max_sessions' => ['required', 'integer', 'min:0'],
            'allowed_message_types' => ['nullable', 'array'],
            'allowed_message_types.*' => ['in:text,image,document,audio,video'],
            'can_send_media' => ['nullable', 'boolean'],
            'can_use_webhook' => ['nullable', 'boolean'],
            'enforce_footer' => ['nullable', 'boolean'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'is_custom' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = $validated['slug'] ?? $plan?->slug ?? Str::slug($validated['name']);

        return [
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'IDR',
            'billing_period' => $validated['billing_period'] ?? 'monthly',
            'daily_message_quota' => $validated['daily_message_quota'],
            'max_sessions' => $validated['max_sessions'],
            'allowed_message_types' => array_values($validated['allowed_message_types'] ?? ['text']),
            'can_send_media' => (bool) ($validated['can_send_media'] ?? false),
            'can_use_webhook' => (bool) ($validated['can_use_webhook'] ?? false),
            'enforce_footer' => (bool) ($validated['enforce_footer'] ?? false),
            'footer_text' => $validated['footer_text'] ?? null,
            'is_custom' => (bool) ($validated['is_custom'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
