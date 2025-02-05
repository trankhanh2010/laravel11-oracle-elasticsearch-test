<?php

namespace App\Http\Controllers\Api\CacheControllers;

use App\Http\Controllers\BaseControllers\BaseApiCacheController;
use App\Models\HIS\PatientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PatientTypeController extends BaseApiCacheController
{
    public function __construct(Request $request){
        parent::__construct($request); // Gọi constructor của BaseController
        $this->patient_type = new PatientType();

        // Kiểm tra tên trường trong bảng
        if ($this->order_by != null) {
            foreach ($this->order_by as $key => $item) {
                if (!$this->patient_type->getConnection()->getSchemaBuilder()->hasColumn($this->patient_type->getTable(), $key)) {
                    unset($this->order_by_request[camelCaseFromUnderscore($key)]);       
                    unset($this->order_by[$key]);               
                }
            }
            $this->order_by_tring = arrayToCustomString($this->order_by);
        }
    }




    /// Kiểm tra lại trong model appends, bỏ appends, dùng join, không nhận được id do có parent cùng loại 





    public function patient_type($id = null)
    {
        $keyword = $this->keyword;
        if (($keyword != null) || ($this->is_addition !== null)) {
            $param = [
                'base_patient_type',
                'other_pay_source'
            ];
            $data = $this->patient_type;
            $data = $data->where(function ($query) use ($keyword){
                $query = $query
                ->where(DB::connection('oracle_his')->raw('patient_type_code'), 'like', $keyword . '%')
                ->orWhere(DB::connection('oracle_his')->raw('patient_type_name'), 'like', $keyword . '%');
            });
        if ($this->is_active !== null) {
            $data = $data->where(function ($query) {
                $query = $query->where(DB::connection('oracle_his')->raw('is_active'), $this->is_active);
            });
        } 
        if ($this->is_addition !== null) {
            $data = $data->where(function ($query) {
                $query = $query->where(DB::connection('oracle_his')->raw('is_addition'), $this->is_addition);
            });
        } 
            $count = $data->count();
            if ($this->order_by != null) {
                foreach ($this->order_by as $key => $item) {
                    $data->orderBy($key, $item);
                }
            }
            $data = $data
                ->skip($this->start)
                ->take($this->limit)
                ->with($param)
                ->get();
        } else {
            if ($id == null) {
                $name = $this->patient_type_name. '_start_' . $this->start . '_limit_' . $this->limit. $this->order_by_tring. '_is_active_' . $this->is_active;
                $param = [
                    'base_patient_type',
                    'other_pay_source'
                ];
            } else {
                if (!is_numeric($id)) {
                    return return_id_error($id);
                }
                $data = $this->patient_type->find($id);
                if ($data == null) {
                    return return_not_record($id);
                }
                $name = $this->patient_type_name . '_' . $id. '_is_active_' . $this->is_active;
                $param = [
                    'base_patient_type',
                    'other_pay_source'
                ];
            }
            $data = get_cache_full($this->patient_type, $param, $name, $id, $this->time, $this->start, $this->limit, $this->order_by, $this->is_active);
        }
        $param_return = [
            'start' => $this->start,
            'limit' => $this->limit,
            'count' => $count ?? $data['count'],
            'is_active' => $this->is_active,
            'keyword' => $this->keyword,
            'order_by' => $this->order_by_request
        ];
        return return_data_success($param_return, $data ?? $data['data']);
    }

    // public function patient_type_is_addition()
    // {
    //     $data = Cache::remember($this->patient_type_name.'_is_addition', $this->time, function () {
    //         return $this->patient_type->where('is_addition', '=', 1)->get();
    //     });

    //     $count = $data->count();
    //     $param_return = [
    //         'start' => null,
    //         'limit' => null,
    //         'count' => $count
    //     ];
    //     return return_data_success($param_return, $data);
    // }
}
