<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->fresh(); // evita valores cacheados
        return view('profile.show', compact('user'));
    }

    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'avatar_cropped' => ['nullable', 'string'],
        ]);

        if (!$request->hasFile('photo') && !$request->filled('avatar_cropped')) {
            return back()->withErrors(['photo' => 'Selecciona una imagen o recórtala antes de guardar.']);
        }

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        if ($request->filled('avatar_cropped')) {
            $dataUrl = $request->input('avatar_cropped');

            if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $dataUrl, $m)) {
                return back()->withErrors(['photo' => 'Formato de imagen no válido.']);
            }

            $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];

            $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
            if ($binary === false) {
                return back()->withErrors(['photo' => 'No se pudo procesar la imagen recortada.']);
            }

            if (strlen($binary) > 3 * 1024 * 1024) {
                return back()->withErrors(['photo' => 'La imagen recortada excede 3MB.']);
            }

            $filename = 'avatars/' . uniqid('avt_') . '.' . $ext;
            Storage::disk('public')->put($filename, $binary);
            $user->avatar_path = $filename;
        } elseif ($request->hasFile('photo')) {
            $user->avatar_path = $request->file('photo')->store('avatars', 'public');
        }

        $user->save();
        auth()->setUser($user->fresh());

        return back()->with('ok', 'Foto actualizada correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Tu contraseña actual no es correcta.',
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('ok', 'Contraseña actualizada correctamente.');
    }

    /**
     * Guarda/actualiza el NIP (hash) en users.approval_pin_hash
     * ✅ EXACTAMENTE 6 dígitos
     */
    public function updatePin(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'regex:/^\d{6}$/'],
        ], [
            'pin.required' => 'Ingresa el NIP.',
            'pin.regex'    => 'El NIP debe ser exactamente de 6 dígitos.',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!method_exists($user, 'setApprovalPin')) {
            return response()->json([
                'ok' => false,
                'message' => 'Falta el método setApprovalPin() en el modelo User.',
            ], 422);
        }

        $user->setApprovalPin((string) $request->input('pin'));

        return response()->json([
            'ok'      => true,
            'message' => 'NIP actualizado correctamente.',
        ]);
    }
}
