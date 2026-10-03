<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Recipient;
use Illuminate\Http\Request;

class RecipientController extends Controller
{
    public function index(Request $request)
    {
        $q            = trim((string) $request->get('q', ''));
        $groupFilter  = (int) $request->get('group', 0);
        $statusFilter = (string) $request->get('status', '');

        $query = Recipient::query()->with('groups');

        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }
        if ($groupFilter > 0) {
            $query->whereHas('groups', fn($qb) => $qb->where('groups.id', $groupFilter));
        }
        if (in_array($statusFilter, ['active', 'inactive'])) {
            $query->where('status', $statusFilter);
        }

        $recipients = $query->latest()->paginate(10)->withQueryString();
        $groups     = Group::orderBy('name')->get();

        return view('recipients.index', compact('recipients', 'groups', 'q', 'groupFilter', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:recipients,email',
            'status'   => 'in:active,inactive',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $recipient = Recipient::create([
            'name'   => $data['name'],
            'email'  => strtolower(trim($data['email'])),
            'status' => $data['status'] ?? 'active',
        ]);

        if (!empty($data['group_id'])) {
            $recipient->groups()->attach($data['group_id']);
        }

        return back()->with('success', 'Recipient added successfully.');
    }

    public function update(Request $request, Recipient $recipient)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => "required|email|unique:recipients,email,{$recipient->id}",
            'status'   => 'in:active,inactive',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $recipient->update([
            'name'   => $data['name'],
            'email'  => strtolower(trim($data['email'])),
            'status' => $data['status'] ?? 'active',
        ]);

        $recipient->groups()->sync($data['group_id'] ? [$data['group_id']] : []);

        return back()->with('success', 'Recipient updated successfully.');
    }

    public function destroy(Recipient $recipient)
    {
        $recipient->delete();

        return back()->with('success', 'Recipient deleted successfully.');
    }

    public function importCsv(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $file      = $request->file('csv_file');
        $handle    = fopen($file->getPathname(), 'r');
        $header    = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle));
        $nameIdx   = array_search('name', $header, true);
        $emailIdx  = array_search('email', $header, true);
        $groupIdx  = array_search('group', $header, true);

        if ($nameIdx === false || $emailIdx === false) {
            fclose($handle);
            return back()->with('error', 'CSV must include name and email columns.');
        }

        $imported = $duplicates = $invalid = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name      = trim((string) ($row[$nameIdx] ?? ''));
            $email     = strtolower(trim((string) ($row[$emailIdx] ?? '')));
            $groupName = $groupIdx !== false ? trim((string) ($row[$groupIdx] ?? '')) : '';

            if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;
                continue;
            }

            if (Recipient::where('email', $email)->exists()) {
                $duplicates++;
                continue;
            }

            $recipient = Recipient::create(['name' => $name, 'email' => $email, 'status' => 'active']);
            $imported++;

            if ($groupName !== '') {
                $group = Group::firstOrCreate(['name' => $groupName], ['description' => 'Imported from CSV']);
                $recipient->groups()->attach($group->id);
            }
        }

        fclose($handle);

        return back()->with('success', "Imported: {$imported} · Duplicates: {$duplicates} · Invalid: {$invalid}");
    }
}
