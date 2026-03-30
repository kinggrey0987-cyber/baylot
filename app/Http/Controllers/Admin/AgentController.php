<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    public function index(): View
    {
        return view('admin.agents.index', [
            'agents' => User::whereIn('role', ['agent', 'broker'])->latest()->paginate(15),
        ]);
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => $request->string('role')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Staff account created.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_if(! in_array($user->role, ['agent', 'broker'], true), 422, 'Only agent or broker accounts can be toggled.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Agent status updated.');
    }
}
