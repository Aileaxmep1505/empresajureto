<?php

namespace App\Http\Controllers\PartContable;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function all(Request $request)
    {
        // Gate admin si aplica
        // abort_unless(auth()->user()?->hasRole('admin'), 403);

        $q = trim((string) $request->get('q', ''));
        $companyId = $request->get('company_id');
        $action = $request->get('action');
        $userId = $request->get('user_id');

        $baseQuery = UserActivity::query()
            ->with([
                'user:id,name,email',
                'company:id,name,slug',
                'document',
            ])
            ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
            ->when($action, fn ($qq) => $qq->where('action', $action))
            ->when($userId, fn ($qq) => $qq->where('user_id', $userId))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('ip', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%")
                        ->orWhere('module', 'like', "%{$q}%")
                        ->orWhere('screen', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('path', 'like', "%{$q}%")
                        ->orWhere('route', 'like', "%{$q}%")
                        ->orWhere('method', 'like', "%{$q}%")
                        ->orWhere('status_code', 'like', "%{$q}%")
                        ->orWhere('referer', 'like', "%{$q}%")
                        ->orWhere('request_id', 'like', "%{$q}%")
                        ->orWhere('session_id', 'like', "%{$q}%")
                        ->orWhere('meta', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                              ->orWhere('email', 'like', "%{$q}%");
                        })
                        ->orWhereHas('company', function ($c) use ($q) {
                            $c->where('name', 'like', "%{$q}%")
                              ->orWhere('slug', 'like', "%{$q}%");
                        })
                        ->orWhereHas('document', function ($d) use ($q) {
                            $d->where('title', 'like', "%{$q}%");

                            if (\Schema::hasColumn($d->getModel()->getTable(), 'filename')) {
                                $d->orWhere('filename', 'like', "%{$q}%");
                            }

                            if (\Schema::hasColumn($d->getModel()->getTable(), 'original_name')) {
                                $d->orWhere('original_name', 'like', "%{$q}%");
                            }
                        });
                });
            })
            ->latest('id');

        $total = (clone $baseQuery)->count();
        $perPage = max(1, min($total ?: 1, 50000));

        $rows = $baseQuery->paginate($perPage)->withQueryString();

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $actions = UserActivity::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('partcontable.activity_all', compact(
            'rows',
            'companies',
            'users',
            'actions',
            'q',
            'companyId',
            'action',
            'userId'
        ));
    }
}