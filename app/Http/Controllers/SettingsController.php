<?php

namespace App\Http\Controllers;

use App\Models\BondSetting;
use App\Models\Organization;
use App\Models\SettingCertification;
use App\Models\SettingDocument;
use App\Models\SettingRepresentative;
use App\Models\UserSettingProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = UserSettingProfile::firstOrCreate(['user_id' => $user->id]);
        $organization = Organization::firstOrCreate(['user_id' => $user->id]);
        $bondSetting = BondSetting::firstOrCreate(['user_id' => $user->id]);
        $documents = SettingDocument::where('user_id', $user->id)
            ->get()
            ->keyBy(fn (SettingDocument $document) => $document->section.'.'.$document->document_key);
        $certifications = SettingCertification::where('user_id', $user->id)->latest()->get();
        $representatives = SettingRepresentative::where('user_id', $user->id)->latest()->get();
        $documentDefinitions = config('settings_documents');

        return view('settings.profile', compact(
            'user',
            'profile',
            'organization',
            'bondSetting',
            'documents',
            'certifications',
            'representatives',
            'documentDefinitions'
        ));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'whatsapp' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            DB::transaction(function () use ($request, $data): void {
                $request->user()->update([
                    'name' => trim($data['name'].' '.$data['last_name']),
                    'email' => $data['email'],
                ]);

                UserSettingProfile::updateOrCreate(
                    ['user_id' => $request->user()->id],
                    [
                        'first_name' => $data['name'],
                        'last_name' => $data['last_name'],
                        'whatsapp' => $data['whatsapp'] ?? null,
                    ]
                );
            });

            return back()->with('success', 'Tu perfil se guardó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible guardar tu perfil. Intenta nuevamente.');
        }
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        try {
            $request->user()->update(['password' => Hash::make($data['password'])]);

            return back()->with('success', 'Tu contraseña se actualizó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible actualizar tu contraseña.');
        }
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        try {
            UserSettingProfile::updateOrCreate(
                ['user_id' => $request->user()->id],
                ['two_factor_enabled' => $request->boolean('two_factor_enabled')]
            );

            return back()->with('success', 'Las preferencias de seguridad se guardaron correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible guardar las preferencias de seguridad.');
        }
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country' => ['required', 'string', 'size:2'],
            'organization_type' => ['required', 'string', 'max:50'],
            'tax_id' => ['required', 'string', 'max:30'],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'institutional_email' => ['nullable', 'email', 'max:255'],
            'institutional_phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'legal_country' => ['required', 'string', 'size:2'],
            'legal_state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:12'],
            'city' => ['required', 'string', 'max:120'],
            'legal_address' => ['required', 'string', 'max:2000'],
        ]);

        try {
            Organization::updateOrCreate(['user_id' => $request->user()->id], $data);

            return back()->with('success', 'La identidad de la organización se guardó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible guardar la identidad de la organización.');
        }
    }

    public function updateBondSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'financial_statements_audited' => ['nullable', 'boolean'],
            'has_solidary_debtor' => ['nullable', 'boolean'],
            'solidary_business_name' => ['nullable', 'required_if:has_solidary_debtor,1', 'string', 'max:255'],
            'solidary_tax_id' => ['nullable', 'required_if:has_solidary_debtor,1', 'string', 'max:30'],
            'solidary_representative' => ['nullable', 'string', 'max:255'],
            'solidary_phone' => ['nullable', 'string', 'max:30'],
            'has_real_estate_guarantee' => ['nullable', 'boolean'],
            'property_type' => ['nullable', 'required_if:has_real_estate_guarantee,1', 'string', 'max:50'],
            'property_value' => ['nullable', 'numeric', 'min:0'],
            'property_address' => ['nullable', 'required_if:has_real_estate_guarantee,1', 'string', 'max:2000'],
        ]);

        $data['financial_statements_audited'] = $request->boolean('financial_statements_audited');
        $data['has_solidary_debtor'] = $request->boolean('has_solidary_debtor');
        $data['has_real_estate_guarantee'] = $request->boolean('has_real_estate_guarantee');

        try {
            BondSetting::updateOrCreate(['user_id' => $request->user()->id], $data);

            return back()->with('success', 'La configuración de fianzas se guardó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible guardar la configuración de fianzas.');
        }
    }

    public function uploadDocument(Request $request, string $section, string $key): RedirectResponse
    {
        $definition = config("settings_documents.$section.$key");
        abort_unless(is_array($definition), 404);

        $maxKb = ((int) ($definition['max_mb'] ?? 20)) * 1024;
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.$maxKb],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $newPath = null;

        try {
            $existing = SettingDocument::where([
                'user_id' => $request->user()->id,
                'section' => $section,
                'document_key' => $key,
            ])->first();

            $file = $data['file'];
            $newPath = $file->store("settings/{$request->user()->id}/{$section}", 'public');

            DB::transaction(function () use ($request, $section, $key, $definition, $data, $file, $newPath, $existing): void {
                SettingDocument::updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'section' => $section,
                        'document_key' => $key,
                    ],
                    [
                        'type' => $data['type'] ?? null,
                        'name' => $data['name'] ?? $definition['name'],
                        'description' => $data['description'] ?? $definition['description'] ?? null,
                        'path' => $newPath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'version' => ($existing?->version ?? 0) + 1,
                        'expires_at' => $data['expires_at'] ?? null,
                        'validation_status' => 'pending',
                    ]
                );
            });

            if ($existing?->path && $existing->path !== $newPath) {
                Storage::disk('public')->delete($existing->path);
            }

            return back()->with('success', 'El documento se guardó correctamente.');
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            return $this->operationFailed($request, $exception, 'No fue posible guardar el documento.');
        }
    }

    public function destroyDocument(Request $request, SettingDocument $document): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        try {
            $path = $document->path;
            $document->delete();
            Storage::disk('public')->delete($path);

            return back()->with('success', 'El documento se eliminó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible eliminar el documento.');
        }
    }

    public function storeAdditionalDocument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $path = null;

        try {
            $file = $data['file'];
            $path = $file->store("settings/{$request->user()->id}/additional_docs", 'public');

            SettingDocument::create([
                'user_id' => $request->user()->id,
                'section' => 'additional_docs',
                'document_key' => 'custom_'.str()->uuid(),
                'type' => $data['type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'version' => 1,
            ]);

            return back()->with('success', 'El documento adicional se agregó correctamente.');
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            return $this->operationFailed($request, $exception, 'No fue posible agregar el documento adicional.');
        }
    }

    public function storeCertification(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'folio' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'support_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $supportPath = null;

        try {
            if ($request->hasFile('support_file')) {
                $file = $request->file('support_file');
                $supportPath = $file->store("settings/{$request->user()->id}/certifications", 'public');
                $data['support_path'] = $supportPath;
                $data['support_original_name'] = $file->getClientOriginalName();
            }

            unset($data['support_file']);
            $data['user_id'] = $request->user()->id;
            SettingCertification::create($data);

            return back()->with('success', 'La certificación se agregó correctamente.');
        } catch (Throwable $exception) {
            if ($supportPath) {
                Storage::disk('public')->delete($supportPath);
            }

            return $this->operationFailed($request, $exception, 'No fue posible agregar la certificación.');
        }
    }

    public function destroyCertification(Request $request, SettingCertification $certification): RedirectResponse
    {
        abort_unless($certification->user_id === $request->user()->id, 403);

        try {
            $path = $certification->support_path;
            $certification->delete();

            if ($path) {
                Storage::disk('public')->delete($path);
            }

            return back()->with('success', 'La certificación se eliminó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible eliminar la certificación.');
        }
    }

    public function storeRepresentative(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'identification_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'power_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $storedPaths = [];

        try {
            foreach (['identification_file' => 'identification', 'power_file' => 'power'] as $field => $prefix) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $file = $request->file($field);
                $path = $file->store("settings/{$request->user()->id}/representatives", 'public');
                $storedPaths[] = $path;
                $data[$prefix.'_path'] = $path;
                $data[$prefix.'_original_name'] = $file->getClientOriginalName();
                unset($data[$field]);
            }

            $data['user_id'] = $request->user()->id;
            SettingRepresentative::create($data);

            return back()->with('success', 'El representante se agregó correctamente.');
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            return $this->operationFailed($request, $exception, 'No fue posible agregar el representante.');
        }
    }

    public function destroyRepresentative(Request $request, SettingRepresentative $representative): RedirectResponse
    {
        abort_unless($representative->user_id === $request->user()->id, 403);

        try {
            $paths = array_filter([
                $representative->identification_path,
                $representative->power_path,
            ]);

            $representative->delete();

            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }

            return back()->with('success', 'El representante se eliminó correctamente.');
        } catch (Throwable $exception) {
            return $this->operationFailed($request, $exception, 'No fue posible eliminar el representante.');
        }
    }

    private function operationFailed(Request $request, Throwable $exception, string $message): RedirectResponse
    {
        Log::error('Error al actualizar la configuración del usuario.', [
            'user_id' => $request->user()?->id,
            'route' => $request->route()?->getName(),
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        return back()->withInput()->with('error', $message);
    }
}
