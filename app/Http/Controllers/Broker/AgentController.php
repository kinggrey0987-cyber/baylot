<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrokerAgentRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    public function index(): View
    {
        $broker = auth()->user();

        return view('broker.agents.index', [
            'agents' => User::query()
                ->where('role', 'agent')
                ->where('broker_id', $broker->id)
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(StoreBrokerAgentRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => 'agent',
            'broker_id' => $request->user()->id,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Agent account created under your brokerage.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_if($user->role !== 'agent' || $user->broker_id !== auth()->id(), 403, 'You can only update your own agents.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Agent account status updated.');
    }
}
