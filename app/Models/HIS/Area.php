<?php

namespace App\Models\HIS;

use App\Traits\dinh_dang_ten_truong;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Area extends Model
{
    use HasFactory, dinh_dang_ten_truong, Searchable;
    protected $connection = 'oracle_his';
    protected $table = 'HIS_Area';
    protected $fillable = [
        'create_time',
        'modify_time',
        'creator',
        'modifier',
        'app_creator',
        'app_modifier',
        'is_active',
        'is_delete',
        'area_code',
        'area_name',
        'department_id',
        'patient_type_id',

    ];
    // Đặt thuộc tính $timestamps thành false để tắt tự động thêm created_at và updated_at
    public $timestamps = false;
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'area_name' => $this->area_name,
            'area_code' => $this->area_code,
        ];
    }
    public function department()
    {
        return $this->hasOne(Department::class);
    }
}
