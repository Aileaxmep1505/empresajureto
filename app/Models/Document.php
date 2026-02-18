<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;

/**
 * App\Models\Document
 *
 * + Ficticio ligado al documento:
 * - ficticio_file_path
 * - ficticio_filename
 * - ficticio_mime_type
 * - ficticio_uploaded_by
 */
class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'company_id',
        'section_id',
        'subtype_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'mime_type',
        'date',
        'uploaded_by',

        // ✅ FICTICIO
        'ficticio_file_path',
        'ficticio_filename',
        'ficticio_mime_type',
        'ficticio_uploaded_by',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'filename',
        'ficticio_url',
    ];

    /* ---------------------------
     | Relationships
     |--------------------------- */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function section()
    {
        return $this->belongsTo(DocumentSection::class, 'section_id');
    }

    public function subtype()
    {
        return $this->belongsTo(DocumentSubtype::class, 'subtype_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function ficticioUploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'ficticio_uploaded_by');
    }

    /* ---------------------------
     | Accessors
     |--------------------------- */

    public function getUrlAttribute(): ?string
    {
        if (empty($this->file_path)) return null;

        if (preg_match('#^https?://#i', $this->file_path)) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getFilenameAttribute(): ?string
    {
        if (empty($this->file_path)) return null;
        return basename($this->file_path);
    }

    public function getFicticioUrlAttribute(): ?string
    {
        if (empty($this->ficticio_file_path)) return null;

        if (preg_match('#^https?://#i', $this->ficticio_file_path)) {
            return $this->ficticio_file_path;
        }

        return Storage::disk('public')->url($this->ficticio_file_path);
    }

    /* ---------------------------
     | Helpers
     |--------------------------- */

    public static function detectFileType(?string $mime): string
    {
        if ($mime && str_starts_with($mime, 'image/')) return 'foto';
        if ($mime && str_starts_with($mime, 'video/')) return 'video';
        return 'documento';
    }

    public function isImage(): bool
    {
        return $this->file_type === 'foto' || ($this->mime_type && str_starts_with($this->mime_type, 'image/'));
    }

    public function isVideo(): bool
    {
        return $this->file_type === 'video' || ($this->mime_type && str_starts_with($this->mime_type, 'video/'));
    }

    public function hasFicticio(): bool
    {
        return !empty($this->ficticio_file_path);
    }

    public function scopeForCompany($q, $company)
    {
        $companyId = $company instanceof Company ? $company->id : (int) $company;
        return $q->where('company_id', $companyId);
    }

    public function deleteFileFromDisk(): bool
    {
        if (!$this->file_path) return true;
        try {
            return Storage::disk('public')->delete($this->file_path);
        } catch (\Throwable $e) {
            \Log::error('deleteFileFromDisk error: '.$e->getMessage(), ['document_id' => $this->id]);
            return false;
        }
    }

    public function deleteFicticioFromDisk(): bool
    {
        if (!$this->ficticio_file_path) return true;
        try {
            return Storage::disk('public')->delete($this->ficticio_file_path);
        } catch (\Throwable $e) {
            \Log::error('deleteFicticioFromDisk error: '.$e->getMessage(), ['document_id' => $this->id]);
            return false;
        }
    }
}
