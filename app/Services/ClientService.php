<?php

namespace App\Services;

use App\Models\Client;
use App\Services\Support\TextNormalizeTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClientService
{
    use TextNormalizeTrait;

    public function getClientesSelect(): \Illuminate\Support\Collection
    {
        $clientCols = array_values(array_filter(['name','nombre','razon_social'], fn($c)=>Schema::hasColumn('clients',$c)));
        $clientDisplayExpr = $clientCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$clientCols)).", CONCAT('ID ', `id`))"
            : "CONCAT('ID ', `id`)";

        return Client::query()
            ->select(['id', DB::raw("$clientDisplayExpr AS display")])
            ->orderByRaw($clientDisplayExpr)
            ->get();
    }

    public function getClientesInfo(): \Illuminate\Support\Collection
    {
        return Client::query()->get();
    }

    public function displayClient(int $id): ?string
    {
        $c = Client::find($id);
        if (!$c) return null;
        foreach (['name','nombre','razon_social'] as $k) if (!empty($c->{$k})) return $c->{$k};
        return "ID {$c->id}";
    }

    public function matchClientId(?string $nombre, ?string $email, ?string $tel): ?int
    {
        $want = ['id','name','nombre','razon_social','email','telefono','phone'];
        $cols = ['id'];
        foreach ($want as $c) if ($c!=='id' && Schema::hasColumn('clients',$c)) $cols[]=$c;
        $clients = Client::query()->select(array_unique($cols))->get();

        $normName  = $this->normalize($nombre ?? '');
        $normEmail = $this->normalize($email ?? '');
        $normTel   = preg_replace('/\D+/', '', (string)$tel);

        $bestId = null; $best=0;
        foreach ($clients as $c) {
            $score = 0;
            $candName = $this->normalize(($c->name ?? null) ?? ($c->nombre ?? null) ?? ($c->razon_social ?? null) ?? '');
            if ($normName && $candName){ similar_text($normName,$candName,$pct); $score += $pct; }

            if (Schema::hasColumn('clients','email')) {
                $candEmail = $this->normalize($c->email ?? '');
                if ($normEmail && $candEmail && $normEmail === $candEmail) $score += 40;
            }

            $candTel = '';
            if (Schema::hasColumn('clients','telefono')) $candTel = preg_replace('/\D+/', '', (string)$c->telefono);
            if (!$candTel && Schema::hasColumn('clients','phone')) $candTel = preg_replace('/\D+/', '', (string)$c->phone);
            if ($normTel && $candTel && str_ends_with($candTel,$normTel)) $score += 25;

            if ($score > $best){ $best=$score; $bestId=$c->id; }
        }
        return $best >= 55 ? $bestId : null;
    }

    public function createOrGetClientId(?string $nombre, ?string $email, ?string $tel, ?string $issuerKind): int
    {
        if ($id = $this->matchClientId($nombre, $email, $tel)) return $id;

        if ($email) {
            $exists = Client::where('email', $email)->first();
            if ($exists) return $exists->id;
        }

        $name = $nombre ?: 'Cliente de PDF';
        $mail = $email ?: $this->genFakeEmail($name);
        $tipo = ($issuerKind === 'dependencia_gobierno_mx') ? 'gobierno' : 'empresa';

        $client = Client::create([
            'nombre'       => $name,
            'email'        => $mail,
            'tipo_cliente' => $tipo,
            'telefono'     => $tel,
            'estatus'      => true,
        ]);

        return $client->id;
    }

    public function genFakeEmail(string $name): string
    {
        do {
            $candidate = $this->genFakeEmailCandidate($name);
        } while (Client::where('email', $candidate)->exists());
        return $candidate;
    }
}
