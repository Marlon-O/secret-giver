<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function create(): View
    {
        return view('groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'participants' => collect($request->input('participants', []))
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values()
                ->all(),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'budget' => ['nullable', 'string', 'max:40'],
            'exchange_date' => ['nullable', 'date'],
            'wishlists_required' => ['sometimes', 'boolean'],
            'participants' => ['required', 'array', 'min:3', 'max:100'],
            'participants.*' => ['string', 'max:60', 'distinct:ignore_case'],
        ], [
            'participants.min' => 'Add at least 3 people.',
            'participants.*.distinct' => 'Each name must be different.',
        ]);

        [$group, $token] = DB::transaction(function () use ($data) {
            [$group, $token] = Group::createWithAdminToken([
                'name' => $data['name'],
                'budget' => $data['budget'] ?? null,
                'exchange_date' => $data['exchange_date'] ?? null,
                'wishlists_required' => $data['wishlists_required'] ?? false,
            ]);

            foreach ($data['participants'] as $name) {
                $group->participants()->create(['name' => $name]);
            }

            return [$group, $token];
        });

        return redirect()
            ->route('admin.show', [$group, $token])
            ->with('created', true);
    }
}
