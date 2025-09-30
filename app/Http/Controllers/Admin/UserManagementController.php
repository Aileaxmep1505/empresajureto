<?php
namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;


class UserManagementController extends Controller
{
public function index(Request $request)
{
$q = $request->get('q');
$users = User::query()
->when($q, fn($x)=>$x->where(function($y) use($q){
$y->where('name','like',"%$q%")
->orWhere('email','like',"%$q%");
}))
->orderByRaw("FIELD(status,'pending','approved','revoked')")
->orderBy('id','desc')
->paginate(12)
->withQueryString();
$roles = Role::all();
return view('admin.users.index', compact('users','roles'));
}


public function approve(User $user)
{
$user->update(['status'=>'approved','approved_at'=>now()]);
return back()->with('status','Usuario aprobado');
}


public function revoke(User $user)
{
$user->update(['status'=>'revoked','approved_at'=>null]);
return back()->with('status','Acceso revocado');
}


public function assignRole(Request $request, User $user)
{
$data = $request->validate(['role'=>'required|string|exists:roles,name']);
$user->syncRoles([$data['role']]);
return back()->with('status','Rol asignado');
}


public function removeRole(User $user, string $role)
{
$user->removeRole($role);
return back()->with('status','Rol retirado');
}
}