<?php

namespace App\Http\Controllers;

use App\Models\EmailHistory;
use Illuminate\Http\Request;

class EmailHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q            = trim((string) $request->get('q', ''));
        $statusFilter = (string) $request->get('status', '');

        $query = EmailHistory::with('campaign')->latest('created_at');

        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('recipient_email', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%"));
        }
        if (in_array($statusFilter, ['sent', 'pending', 'failed'])) {
            $query->where('status', $statusFilter);
        }

        $history = $query->paginate(15)->withQueryString();

        return view('email-history.index', compact('history', 'q', 'statusFilter'));
    }
}
