<?php

namespace App\Models\Media;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    //
    protected $fillable = [
        'filename', 'original_name', 'file_path', 'file_size',
        'mime_type', 'file_type', 'uploaded_by', 'related_type',
        'related_id', 'is_public', 'uploaded_at'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Polymorphic relationship for related entity
    public function related()
    {
        return $this->morphTo();
    }

    // Helper method to get file URL
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    // Helper method to format file size
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}
