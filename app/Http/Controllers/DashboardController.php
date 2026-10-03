<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EmailHistory;
use App\Models\EmailQueue;
use App\Models\Recipient;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRecipients = Recipient::count();
        $emailsSent      = EmailHistory::where('status', 'sent')->count();
        $pending         = EmailQueue::whereIn('status', ['pending', 'processing'])->count();
        $failed          = EmailHistory::where('status', 'failed')->count();

        $activityLabels = [];
        $activityData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day              = now()->subDays($i)->format('Y-m-d');
            $activityLabels[] = now()->subDays($i)->format('D');
            $activityData[]   = EmailHistory::where('status', 'sent')
                ->whereDate('sent_at', $day)
                ->count();
        }

        $recent = Campaign::latest()->limit(5)->get();

        return view('dashboard', compact(
            'totalRecipients', 'emailsSent', 'pending', 'failed',
            'activityLabels', 'activityData', 'recent'
        ));
    }
}
