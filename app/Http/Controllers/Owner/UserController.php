<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ProductPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('owner.users.index', [
            'users' => User::query()
                ->with(['subscriptions.productPlan'])
                ->withCount(['whatsappSessions', 'messages', 'tokens'])
                ->latest()
                ->get(),
            'plans' => ProductPlan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:client,admin,superadmin'],
            'product_plan_id' => ['nullable', 'exists:product_plans,id'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update(['role' => $validated['role']]);

        if (! empty($validated['product_plan_id'])) {
            $plan = ProductPlan::query()->findOrFail($validated['product_plan_id']);

            Subscription::query()->updateOrCreate(
                ['user_id' => $user->id, 'is_active' => true],
                [
                    'product_plan_id' => $plan->id,
                    'plan_name' => $plan->slug,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'message_quota' => $plan->daily_message_quota,
                    'max_sessions' => $plan->max_sessions,
                    'starts_at' => now(),
                    'ends_at' => $validated['ends_at'] ?? now()->addMonth(),
                    'messages_used_today' => 0,
                    'quota_resets_on' => now()->toDateString(),
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                ]
            );
        }

        return back()->with('status', 'User updated successfully.');
    }

    public function issueToken(User $user): RedirectResponse
    {
        $token = $user->createToken('Owner Issued API Key')->plainTextToken;

        return back()
            ->with('status', 'A new API token was created for '.$user->email.'.')
            ->with('owner_plain_text_token', $token)
            ->with('owner_token_user', $user->email);
    }
}
