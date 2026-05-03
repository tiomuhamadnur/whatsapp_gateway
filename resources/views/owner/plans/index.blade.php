<x-cms.layouts.app title="Product Plans" heading="Product Plans" eyebrow="Owner">
    <section class="mb-6 flex justify-end">
        <button type="button" data-modal-open="plan-create" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">Create New Plan</button>
        <x-cms.modal id="plan-create" title="Create New Plan">
        <form method="POST" action="{{ route('owner.plans.store') }}" data-confirm="Create this product plan?" class="grid gap-4 lg:grid-cols-2">
            @csrf
            <label>
                <span class="text-sm font-medium">Plan name <span class="required-mark">*</span></span>
                <input name="name" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="text-sm font-medium">Slug</span>
                <input name="slug" placeholder="starter" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="text-sm font-medium">Daily message quota <span class="required-mark">*</span></span>
                <input name="daily_message_quota" type="number" min="0" value="1000" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="text-sm font-medium">Price <span class="required-mark">*</span></span>
                <input name="price" type="number" min="0" value="0" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="text-sm font-medium">Max sessions <span class="required-mark">*</span></span>
                <input name="max_sessions" type="number" min="0" value="1" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            </label>
            <label class="lg:col-span-2">
                <span class="text-sm font-medium">Description</span>
                <textarea name="description" rows="2" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"></textarea>
            </label>
            <div class="lg:col-span-2">
                <div class="text-sm font-medium">Allowed message types</div>
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach ($types as $type)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="allowed_message_types[]" value="{{ $type }}" @checked($type === 'text')>
                            {{ $type }}
                        </label>
                    @endforeach
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="can_send_media" value="1">
                Can send media
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="can_use_webhook" value="1">
                Can use webhook
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="enforce_footer" value="1">
                Force platform footer
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_custom" value="1">
                Custom plan
            </label>
            <label class="lg:col-span-2">
                <span class="text-sm font-medium">Footer text</span>
                <input name="footer_text" placeholder="Powered by WA Gateway" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            </label>
            <div class="lg:col-span-2">
                <button class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">Create Plan</button>
            </div>
        </form>
        </x-cms.modal>
    </section>

    <x-cms.data-table search="Search plans..." per-page="10">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Price & Rules</th>
                    <th class="px-4 py-3">Types</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Edit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 align-top">
                @foreach ($plans as $plan)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $plan->name }}</div>
                            <div class="text-zinc-500">{{ $plan->slug }}</div>
                            <div class="mt-1 max-w-xs text-zinc-500">{{ $plan->description }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $plan->currency }} {{ number_format($plan->price, 0, ',', '.') }} / {{ $plan->billing_period }}</div>
                            <div>{{ number_format($plan->daily_message_quota) }} msg/day</div>
                            <div>{{ number_format($plan->max_sessions) }} sessions</div>
                            <div>Media: {{ $plan->can_send_media ? 'yes' : 'no' }}</div>
                            <div>Webhook: {{ $plan->can_use_webhook ? 'yes' : 'no' }}</div>
                            <div>Footer: {{ $plan->enforce_footer ? 'locked' : 'off' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ implode(', ', $plan->allowed_message_types ?? []) }}</td>
                        <td class="px-4 py-3">{{ $plan->is_active ? 'active' : 'inactive' }}</td>
                        <td class="px-4 py-3">
                            <button type="button" data-modal-open="plan-edit-{{ $plan->id }}" class="rounded-md bg-zinc-950 px-3 py-1.5 text-sm font-semibold text-white">Edit</button>
                            <x-cms.modal id="plan-edit-{{ $plan->id }}" title="Edit Plan: {{ $plan->name }}">
                            <form method="POST" action="{{ route('owner.plans.update', $plan) }}" data-confirm="Save changes to this plan?" class="grid gap-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <input name="name" value="{{ $plan->name }}" class="rounded-md border border-zinc-300 px-2 py-1.5" placeholder="Plan name">
                                    <input name="slug" value="{{ $plan->slug }}" class="rounded-md border border-zinc-300 px-2 py-1.5" placeholder="Slug">
                                    <input name="daily_message_quota" type="number" value="{{ $plan->daily_message_quota }}" class="rounded-md border border-zinc-300 px-2 py-1.5" placeholder="Quota">
                                    <input name="max_sessions" type="number" value="{{ $plan->max_sessions }}" class="rounded-md border border-zinc-300 px-2 py-1.5" placeholder="Max sessions">
                                    <input name="price" type="number" value="{{ $plan->price }}" class="rounded-md border border-zinc-300 px-2 py-1.5" placeholder="Price">
                                    <input name="billing_period" value="{{ $plan->billing_period }}" class="rounded-md border border-zinc-300 px-2 py-1.5" placeholder="Billing period">
                                </div>
                                <textarea name="description" rows="2" class="rounded-md border border-zinc-300 px-2 py-1.5">{{ $plan->description }}</textarea>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($types as $type)
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="allowed_message_types[]" value="{{ $type }}" @checked(in_array($type, $plan->allowed_message_types ?? [], true))>
                                            {{ $type }}
                                        </label>
                                    @endforeach
                                </div>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="can_send_media" value="1" @checked($plan->can_send_media)> Media</label>
                                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="can_use_webhook" value="1" @checked($plan->can_use_webhook)> Webhook</label>
                                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="enforce_footer" value="1" @checked($plan->enforce_footer)> Footer</label>
                                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_custom" value="1" @checked($plan->is_custom)> Custom</label>
                                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
                                </div>
                                <input name="footer_text" value="{{ $plan->footer_text }}" placeholder="Footer text" class="rounded-md border border-zinc-300 px-2 py-1.5">
                                <button class="rounded-md bg-zinc-950 px-3 py-1.5 text-sm font-semibold text-white">Update</button>
                            </form>
                            </x-cms.modal>
                            <form method="POST" action="{{ route('owner.plans.destroy', $plan) }}" data-confirm="Deactivate this plan?" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700">Deactivate</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-cms.data-table>
</x-cms.layouts.app>
