<?php

namespace App\Models\SystemConfig;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class SystemSetting extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'setting_key', 'setting_value', 'setting_type',
        'description', 'is_public', 'updated_by'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper method to get typed value
    public function getTypedValueAttribute()
    {
        switch ($this->setting_type) {
            case 'boolean':
                return filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->setting_value;
            case 'json':
                return json_decode($this->setting_value, true);
            default:
                return $this->setting_value;
        }
    }

    // Static helper to get setting value
    public static function get($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    // Static helper to set setting value
    public static function set($key, $value, $type = 'string')
    {
        if ($type === 'json') {
            $value = is_string($value) ? $value : json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_type' => $type,
                'updated_by' => auth()->id()
            ]
        );
    }
}
