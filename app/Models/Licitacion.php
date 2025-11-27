<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Licitacion extends Model
{
    use HasFactory;

    /**
     * Nombre explícito de la tabla en la BD
     */
    protected $table = 'licitaciones';

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'titulo',
        'descripcion',

        // ✅ compatibilidad: primera fecha
        'fecha_convocatoria',
        // ✅ nuevo: múltiples fechas seleccionadas
        'fechas_convocatoria',

        // ✅ modalidades: presencial | en_linea | mixta
        'modalidad',
        'recordatorio_emails',

        'fecha_junta_aclaraciones',
        'fecha_limite_preguntas',
        'lugar_junta',
        'link_junta',

        'fecha_apertura_propuesta',

        // ✅ NUEVO: fecha del acta de apertura
        'fecha_acta_apertura',

        'requiere_muestras',
        'fecha_entrega_muestras',
        'lugar_entrega_muestras',
        'resultado',
        'observaciones_fallo',
        'fecha_fallo',
        'fecha_presentacion_fallo',
        'lugar_presentacion_fallo',
        'docs_presentar_fallo',
        'fecha_emision_contrato',
        'fecha_fianza',

        // 🔀 NUEVOS CAMPOS CONTRATO / FIANZA
        'tipo_fianza',
        'observaciones_contrato',
        'fechas_cobro',

        'estatus',
        'current_step',
        'created_by',
    ];

    /**
     * Casts de tipos para fechas / booleanos
     */
    protected $casts = [
        'fecha_convocatoria'        => 'date',

        // ✅ JSON <-> array
        'fechas_convocatoria'       => 'array',
        'recordatorio_emails'       => 'array',

        // ✅ NUEVO: cast de fecha del acta
        'fecha_acta_apertura'       => 'date',

        'fecha_junta_aclaraciones'  => 'datetime',
        'fecha_limite_preguntas'    => 'datetime',
        'fecha_apertura_propuesta'  => 'datetime',
        'fecha_entrega_muestras'    => 'datetime',
        'fecha_fallo'               => 'datetime',
        'fecha_presentacion_fallo'  => 'datetime',
        'fecha_emision_contrato'    => 'date',
        'fecha_fianza'              => 'date',

        // 💰 NUEVO: fechas de cobro como array (JSON)
        'fechas_cobro'              => 'array',

        'requiere_muestras'         => 'boolean',
    ];

    /**
     * Usuario creador de la licitación
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Archivos asociados (convocatoria, actas, fallo, contrato, etc.)
     */
    public function archivos()
    {
        return $this->hasMany(LicitacionArchivo::class);
    }

    /**
     * Preguntas realizadas por los usuarios sobre la licitación
     */
    public function preguntas()
    {
        return $this->hasMany(LicitacionPregunta::class);
    }

    /**
     * Eventos de agenda asociados (junta, apertura, muestras, fallo, fianza, cobros, etc.)
     */
    public function eventos()
    {
        return $this->hasMany(LicitacionEvento::class);
    }

    /**
     * Checklist de compras (varios ítems)
     */
    public function checklistCompras()
    {
        return $this->hasMany(LicitacionChecklistCompra::class);
    }

    /**
     * Checklist de facturación (un registro por licitación)
     */
    public function checklistFacturacion()
    {
        return $this->hasOne(LicitacionChecklistFacturacion::class);
    }

    /**
     * Información contable de la licitación
     */
    public function contabilidad()
    {
        return $this->hasOne(LicitacionContabilidad::class);
    }

    /**
     * Helper opcional: regresa siempre un array ordenado de fechas de convocatoria
     */
    public function getFechasConvocatoriaOrdenadasAttribute(): array
    {
        $fechas = $this->fechas_convocatoria ?? [];
        sort($fechas);
        return $fechas;
    }

    /**
     * Helper opcional: fechas de cobro ordenadas (por si quieres usarlas en vistas)
     */
    public function getFechasCobroOrdenadasAttribute(): array
    {
        $fechas = $this->fechas_cobro ?? [];
        sort($fechas);
        return $fechas;
    }
}
