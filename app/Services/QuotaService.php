<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Models\ProductPlan;
use Illuminate\Support\Facades\DB;

class QuotaService
{
    public function hasQuota(User $user): bool
    {
        if ($user->isPrivileged()) {
            return true;
        }

        $subscription = $this->activeSubscription($user);

        if ($subscription !== null) {
            $this->resetDailyUsageIfNeeded($subscription);
        }

        return $subscription !== null
            && $subscription->messages_used_today < $this->dailyQuota($subscription);
    }

    public function decrementQuota(User $user): void
    {
        if ($user->isPrivileged()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $subscription = $this->activeSubscription($user, lock: true);

            if ($subscription === null || $subscription->messages_used >= $subscription->message_quota) {
                throw new \RuntimeException('Quota exceeded.');
            }

            $this->resetDailyUsageIfNeeded($subscription);

            if ($subscription->messages_used_today >= $this->dailyQuota($subscription)) {
                throw new \RuntimeException('Quota exceeded.');
            }

            $subscription->increment('messages_used');
            $subscription->increment('messages_used_today');
        });
    }

    public function getRemainingQuota(User $user): int
    {
        if ($user->isPrivileged()) {
            return PHP_INT_MAX;
        }

        $subscription = $this->activeSubscription($user);

        if ($subscription === null) {
            return 0;
        }

        $this->resetDailyUsageIfNeeded($subscription);

        return max(0, $this->dailyQuota($subscription) - $subscription->messages_used_today);
    }

    public function getMaxSessions(User $user): int
    {
        if ($user->isPrivileged()) {
            return PHP_INT_MAX;
        }

        $subscription = $this->activeSubscription($user);

        if ($subscription === null) {
            return 0;
        }

        return $subscription->productPlan?->max_sessions ?? $subscription->max_sessions;
    }

    public function currentPlan(User $user): ?ProductPlan
    {
        return $this->activeSubscription($user)?->productPlan;
    }

    public function canSendType(User $user, string $type): bool
    {
        if ($user->isPrivileged()) {
            return true;
        }

        $plan = $this->currentPlan($user);
        $allowedTypes = $plan?->allowed_message_types ?: ['text'];

        return in_array($type, $allowedTypes, true);
    }

    public function applyMessagePolicy(User $user, string $type, string $content): string
    {
        if ($user->isPrivileged()) {
            return $content;
        }

        $plan = $this->currentPlan($user);

        if ($plan?->enforce_footer && $type === 'text') {
            $footer = $plan->footer_text ?: "\n\nPowered by WA Gateway";

            if (! str_ends_with($content, $footer)) {
                return $content.$footer;
            }
        }

        return $content;
    }

    private function activeSubscription(User $user, bool $lock = false): ?Subscription
    {
        $query = $user->subscriptions()
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latest('ends_at');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function dailyQuota(Subscription $subscription): int
    {
        return $subscription->productPlan?->daily_message_quota ?? $subscription->message_quota;
    }

    private function resetDailyUsageIfNeeded(Subscription $subscription): void
    {
        if ($subscription->quota_resets_on?->isSameDay(now())) {
            return;
        }

        $subscription->forceFill([
            'messages_used_today' => 0,
            'quota_resets_on' => now()->toDateString(),
        ])->save();
    }
}
