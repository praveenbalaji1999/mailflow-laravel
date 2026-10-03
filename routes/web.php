<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailHistoryController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SmtpSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// ─── Authentication & Web UI ────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::post('/recipients', [RecipientController::class, 'store'])->name('recipients.store');
    Route::put('/recipients/{recipient}', [RecipientController::class, 'update'])->name('recipients.update');
    Route::delete('/recipients/{recipient}', [RecipientController::class, 'destroy'])->name('recipients.destroy');
    Route::post('/recipients/import', [RecipientController::class, 'importCsv'])->name('recipients.import');

    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.add');
    Route::delete('/groups/{group}/members/{recipient}', [GroupController::class, 'removeMember'])->name('groups.members.remove');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/compose', [CampaignController::class, 'compose'])->name('campaigns.compose');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campaigns/{campaign}/process', [CampaignController::class, 'processQueue'])->name('campaigns.process');
    Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::get('/email-history', [EmailHistoryController::class, 'index'])->name('email-history.index');
    Route::get('/smtp-settings', [SmtpSettingController::class, 'index'])->name('smtp-settings.index');
    Route::post('/smtp-settings', [SmtpSettingController::class, 'save'])->name('smtp-settings.save');
    Route::post('/smtp-settings/test', [SmtpSettingController::class, 'test'])->name('smtp-settings.test');
});

// ─── Ensure Tracking Tables Exist Helper ────────────────────────────────────
function ensureMailFlowTables() {
    if (!Schema::hasTable('email_tracking')) {
        Schema::create('email_tracking', function (Blueprint $t) {
            $t->id();
            $t->string('tracking_id', 64)->unique();
            $t->string('recipient_email', 255)->nullable();
            $t->unsignedBigInteger('campaign_id')->nullable();
            $t->unsignedInteger('open_count')->default(0);
            $t->unsignedInteger('click_count')->default(0);
            $t->timestamp('first_opened_at')->nullable();
            $t->timestamp('last_opened_at')->nullable();
            $t->timestamp('first_clicked_at')->nullable();
            $t->timestamp('last_clicked_at')->nullable();
            $t->timestamps();
        });
    }
    if (!Schema::hasTable('tracked_links')) {
        Schema::create('tracked_links', function (Blueprint $t) {
            $t->id();
            $t->string('tracking_id', 64)->index();
            $t->string('link_id', 64)->index();
            $t->text('original_url');
            $t->unsignedInteger('click_count')->default(0);
            $t->timestamp('first_clicked_at')->nullable();
            $t->timestamp('last_clicked_at')->nullable();
            $t->timestamps();
        });
    }
    if (!Schema::hasTable('tracking_events')) {
        Schema::create('tracking_events', function (Blueprint $t) {
            $t->id();
            $t->string('tracking_id', 64)->index();
            $t->string('event_type', 32)->index();
            $t->string('link_id', 64)->nullable();
            $t->timestamp('occurred_at')->index();
            $t->string('user_agent', 255)->nullable();
            $t->string('ip_address', 64)->nullable();
            $t->timestamps();
        });
    }
}

// ─── 24/7 Public Cloud Tracking Endpoints (With & Without /api Prefix) ───────
$trackOpenHandler = function (string $trackingId, Request $request) {
    ensureMailFlowTables();
    $now = now();
    
    // Update or insert tracking record
    $exists = DB::table('email_tracking')->where('tracking_id', $trackingId)->first();
    if ($exists) {
        DB::table('email_tracking')->where('tracking_id', $trackingId)->update([
            'open_count'      => $exists->open_count + 1,
            'first_opened_at' => $exists->first_opened_at ?? $now,
            'last_opened_at'  => $now,
            'updated_at'      => $now,
        ]);
    } else {
        DB::table('email_tracking')->insert([
            'tracking_id'     => $trackingId,
            'open_count'      => 1,
            'first_opened_at' => $now,
            'last_opened_at'  => $now,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }

    DB::table('tracking_events')->insert([
        'tracking_id' => $trackingId,
        'event_type'  => 'open',
        'occurred_at' => $now,
        'user_agent'  => substr($request->header('User-Agent') ?? '', 0, 255),
        'ip_address'  => $request->ip(),
        'created_at'  => $now,
        'updated_at'  => $now,
    ]);

    $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    return response($pixel, 200, [
        'Content-Type'  => 'image/gif',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'        => 'no-cache',
        'Expires'       => '0',
    ]);
};

$trackClickHandler = function (string $trackingId, string $linkId, Request $request) {
    ensureMailFlowTables();
    $now = now();
    
    $link = DB::table('tracked_links')
        ->where('tracking_id', $trackingId)
        ->where('link_id', $linkId)
        ->first();

    $destination = ($link && !empty($link->original_url)) ? $link->original_url : 'https://google.com';

    if ($link) {
        DB::table('tracked_links')
            ->where('id', $link->id)
            ->update([
                'click_count'      => $link->click_count + 1,
                'first_clicked_at' => $link->first_clicked_at ?? $now,
                'last_clicked_at'  => $now,
                'updated_at'       => $now,
            ]);
    }

    $tracking = DB::table('email_tracking')->where('tracking_id', $trackingId)->first();
    if ($tracking) {
        DB::table('email_tracking')->where('tracking_id', $trackingId)->update([
            'click_count'      => $tracking->click_count + 1,
            'first_clicked_at' => $tracking->first_clicked_at ?? $now,
            'last_clicked_at'  => $now,
            'updated_at'       => $now,
        ]);
    }

    DB::table('tracking_events')->insert([
        'tracking_id' => $trackingId,
        'event_type'  => 'click',
        'link_id'     => $linkId,
        'occurred_at' => $now,
        'user_agent'  => substr($request->header('User-Agent') ?? '', 0, 255),
        'ip_address'  => $request->ip(),
        'created_at'  => $now,
        'updated_at'  => $now,
    ]);

    return redirect()->away($destination, 302, [
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
};

// Register routes with and without /api prefix
Route::get('/api/track/open/{trackingId}', $trackOpenHandler);
Route::get('/track/open/{trackingId}', $trackOpenHandler);

Route::get('/api/track/click/{trackingId}/{linkId}', $trackClickHandler);
Route::get('/track/click/{trackingId}/{linkId}', $trackClickHandler);

// ─── Sync Push & Pull Endpoints ─────────────────────────────────────────────
Route::post('/api/sync/push', function (Request $request) {
    ensureMailFlowTables();
    $items = $request->input('items', []);
    $syncedIds = [];

    foreach ($items as $item) {
        $id = $item['id'] ?? null;
        $type = $item['entity_type'] ?? '';
        $uuid = $item['entity_uuid'] ?? '';
        $payload = json_decode($item['payload'] ?? '{}', true) ?: [];

        if ($type === 'email_tracking') {
            DB::table('email_tracking')->updateOrInsert(
                ['tracking_id' => $payload['tracking_id'] ?? $uuid],
                [
                    'recipient_email' => $payload['recipient_email'] ?? '',
                    'campaign_id'     => $payload['campaign_id'] ?? null,
                    'updated_at'      => now(),
                ]
            );
        } elseif ($type === 'tracked_link') {
            DB::table('tracked_links')->updateOrInsert(
                [
                    'tracking_id' => $payload['tracking_id'] ?? '',
                    'link_id'     => $payload['link_id'] ?? $uuid,
                ],
                [
                    'original_url' => $payload['original_url'] ?? '',
                    'updated_at'   => now(),
                ]
            );
        }

        if ($id !== null) $syncedIds[] = $id;
    }

    return response()->json(['status' => 'success', 'synced_ids' => $syncedIds]);
});

Route::post('/api/sync/pull', function (Request $request) {
    ensureMailFlowTables();
    $since = $request->input('since');
    $q = DB::table('tracking_events');
    if (!empty($since)) $q->where('occurred_at', '>=', $since);
    $events = $q->orderBy('occurred_at', 'asc')->limit(1000)->get();

    return response()->json([
        'status' => 'success',
        'events' => $events->map(fn($e) => [
            'id'          => $e->id,
            'tracking_id' => $e->tracking_id,
            'event_type'  => $e->event_type,
            'link_id'     => $e->link_id,
            'occurred_at' => $e->occurred_at,
            'user_agent'  => $e->user_agent,
            'ip_address'  => $e->ip_address,
        ])
    ]);
});

Route::get('/api/health', fn() => response()->json([
    'status'  => 'ok',
    'service' => 'MailFlow 24/7 Cloud Tracking Gateway',
    'time'    => now()
]));
