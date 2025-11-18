<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

/**
 * App\Models\Document
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $section_id
 * @property int|null $subtype_id
 * @property string|null $title
 * @property string|null $description
 * @property string $file_path
 * @property string|null $file_type   // 'foto'|'video'|'documento'
 * @property string|null $mime_type
 * @property \Illuminate\Support\Carbon|null $date
 * @property int|null $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Document forCompany($companyId)
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
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Appended attributes for arrays / JSON.
     * 'url' devuelve la URL pública (disk 'public'), 'filename' nombre del archivo.
     */
    protected $appends = [
        'url',
        'filename',
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

    /* ---------------------------
     | Accessors
     |--------------------------- */

    /**
     * Public URL to the file (disk 'public').
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        // If the file is already a full URL (remote), return as-is.
        if (preg_match('#^https?://#i', $this->file_path)) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Filename extracted from file_path.
     *
     * @return string|null
     */
    public function getFilenameAttribute(): ?string
    {
        if (empty($this->file_path)) return null;
        return basename($this->file_path);
    }

    /* ---------------------------
     | Helpers
     |--------------------------- */

    /**
     * Detect file type by MIME. Use when you only have mime_type.
     *
     * @param  string|null  $mime
     * @return string ('foto'|'video'|'documento')
     */
    public static function detectFileType(?string $mime): string
    {
        if ($mime && str_starts_with($mime, 'image/')) return 'foto';
        if ($mime && str_starts_with($mime, 'video/')) return 'video';
        return 'documento';
    }

    /**
     * Is this document an image?
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->file_type === 'foto' || ($this->mime_type && str_starts_with($this->mime_type, 'image/'));
    }

    /**
     * Is this document a video?
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->file_type === 'video' || ($this->mime_type && str_starts_with($this->mime_type, 'video/'));
    }

    /**
     * Simple scope to filter by company.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @param  int|Company  $company
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($q, $company)
    {
        $companyId = $company instanceof Company ? $company->id : (int) $company;
        return $q->where('company_id', $companyId);
    }

    /* ---------------------------
     | File helpers (optional)
     |--------------------------- */

    /**
     * Delete file from storage (public disk) and optionally the model.
     * Use with caution: this only removes the disk file.
     *
     * @return bool
     */
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
}
