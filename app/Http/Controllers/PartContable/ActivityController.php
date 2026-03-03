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
        // ✅ Gate admin si aplica
        // abort_unless(auth()->user()?->hasRole('admin'), 403);

        $q = trim((string) $request->get('q', ''));
        $companyId = $request->get('company_id');
        $action = $request->get('action');
        $userId = $request->get('user_id');

        $baseQuery = UserActivity::query()
            ->with(['user','company','document'])
            ->when($companyId, fn($qq) => $qq->where('company_id', $companyId))
            ->when($action, fn($qq) => $qq->where('action', $action))
            ->when($userId, fn($qq) => $qq->where('user_id', $userId))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('ip','like',"%{$q}%")
                      ->orWhere('action','like',"%{$q}%")
                      ->orWhere('path','like',"%{$q}%")
                      ->orWhere('route','like',"%{$q}%")
                      ->orWhere('screen','like',"%{$q}%")
                      ->orWhere('module','like',"%{$q}%")
                      ->orWhereHas('user', fn($u)=>$u->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"))
                      ->orWhereHas('company', fn($c)=>$c->where('name','like',"%{$q}%"))
                      ->orWhereHas('document', fn($d)=>$d->where('title','like',"%{$q}%"));
                });
            })
            ->latest('id');

        // ✅ “sin paginación” visual: una sola página enorme (y no rompe tu Blade)
        $total = (clone $baseQuery)->count();
        $perPage = max(1, min($total ?: 1, 50000)); // cap para no morir si hay millones

        $rows = $baseQuery->paginate($perPage)->withQueryString();

        $companies = Company::query()->orderBy('name')->get(['id','name','slug']);
        $users = User::query()->orderBy('name')->get(['id','name','email']);
        $actions = UserActivity::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('partcontable.activity_all', compact(
            'rows','companies','users','actions','q','companyId','action','userId'
        ));
    }
}