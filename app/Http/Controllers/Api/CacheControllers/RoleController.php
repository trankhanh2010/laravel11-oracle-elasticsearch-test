<?php

namespace App\Http\Controllers\Api\CacheControllers;

use App\Http\Controllers\BaseControllers\BaseApiCacheController;
use App\Models\ACS\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends BaseApiCacheController
{
    public function __construct(Request $request)
    {
        parent::__construct($request); // Gọi constructor của BaseController
        $this->role = new Role();

        // Kiểm tra tên trường trong bảng
        if ($this->order_by != null) {
            foreach ($this->order_by as $key => $item) {
                if (!$this->role->getConnection()->getSchemaBuilder()->hasColumn($this->role->getTable(), $key)) {
                    unset($this->order_by_request[camelCaseFromUnderscore($key)]);       
                    unset($this->order_by[$key]);               
                }
            }
            $this->order_by_tring = arrayToCustomString($this->order_by);
        }
    }
    public function role($id = null)
    {
        $keyword = $this->keyword;
        if ($keyword != null) {
            $param = [
                'modules:id,module_name'
            ];
            $data = $this->role;
            $data = $data->where(function ($query) use ($keyword){
                $query = $query
                ->where(DB::connection('oracle_his')->raw('role_name'), 'like', $keyword . '%')
                ->orWhere(DB::connection('oracle_his')->raw('role_code'), 'like', $keyword . '%');
            });
        if ($this->is_active !== null) {
            $data = $data->where(function ($query) {
                $query = $query->where(DB::connection('oracle_his')->raw('acs_role.is_active'), $this->is_active);
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
                $name = $this->role_name . '_start_' . $this->start . '_limit_' . $this->limit . $this->order_by_tring. '_is_active_' . $this->is_active;
                $param = [
                ];
            } else {
                if (!is_numeric($id)) {
                    return return_id_error($id);
                }
                $data = $this->role->find($id);
                if ($data == null) {
                    return return_not_record($id);
                }
                $name =  $this->role_name . '_' . $id. '_is_active_' . $this->is_active;
                $param = [
                    'modules'
                ];
            }
            $model = $this->role;
            $data = get_cache_full($model, $param, $name, $id, $this->time, $this->start, $this->limit, $this->order_by, $this->is_active);
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
}
