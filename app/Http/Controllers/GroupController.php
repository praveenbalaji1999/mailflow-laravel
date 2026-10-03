<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Recipient;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::withCount('recipients')->orderBy('name')->get();

        return view('groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150|unique:groups,name',
            'description' => 'nullable|string',
        ]);

        Group::create($data);

        return back()->with('success', 'Group created successfully.');
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name'        => "required|string|max:150|unique:groups,name,{$group->id}",
            'description' => 'nullable|string',
        ]);

        $group->update($data);

        return back()->with('success', 'Group updated successfully.');
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return back()->with('success', 'Group deleted successfully.');
    }

    public function show(Group $group, Request $request)
    {
        $q          = trim((string) $request->get('q', ''));
        $recipients = $group->recipients()
            ->when($q, fn($qb) => $qb->where(fn($q2) => $q2->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->paginate(10)->withQueryString();

        $allRecipients = Recipient::where('status', 'active')
            ->whereNotIn('id', $group->recipients()->pluck('recipients.id'))
            ->orderBy('name')->get();

        return view('groups.show', compact('group', 'recipients', 'allRecipients', 'q'));
    }

    public function addMember(Request $request, Group $group)
    {
        $data = $request->validate(['recipient_id' => 'required|exists:recipients,id']);
        $group->recipients()->syncWithoutDetaching([$data['recipient_id']]);

        return back()->with('success', 'Member added.');
    }

    public function removeMember(Group $group, Recipient $recipient)
    {
        $group->recipients()->detach($recipient->id);

        return back()->with('success', 'Member removed.');
    }
}
