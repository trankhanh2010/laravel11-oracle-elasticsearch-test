<?php

namespace App\Http\Controllers\Api\CacheControllers;

use App\Http\Controllers\BaseControllers\BaseApiCacheController;
use Illuminate\Http\Request;
use App\Models\HIS\DataStore;
use App\Events\Cache\DeleteCache;
use App\Http\Requests\DataStore\CreateDataStoreRequest;
use App\Http\Requests\DataStore\UpdateDataStoreRequest;
use App\Models\HIS\Room;
use Illuminate\Support\Facades\DB;

class DataStoreController extends BaseApiCacheController
{
    public function __construct(Request $request){
        parent::__construct($request); // Gọi constructor của BaseController
        $this->data_store = new DataStore();
        $this->room = new Room();

        // Kiểm tra tên trường trong bảng
        if ($this->order_by != null) {
            foreach ($this->order_by as $key => $item) {
                if (!$this->data_store->getConnection()->getSchemaBuilder()->hasColumn($this->data_store->getTable(), $key)) {
                    unset($this->order_by_request[camelCaseFromUnderscore($key)]);       
                    unset($this->order_by[$key]);               
                }
            }
            $this->order_by_tring = arrayToCustomString($this->order_by);
        }
    }
    
    public function data_store($id = null)
    {
        $keyword = $this->keyword;
        if ($keyword != null) {
            $param = [
                'room:id,department_id',
                'room.department:id,department_name,department_code',
                'stored_room:id',
                'stored_department:id,department_name,department_code',
                'parent:id,data_store_code,data_store_name',
            ];
            $data = $this->data_store;
            $data = $data->where(function ($query) use ($keyword){
                $query = $query
                ->where(DB::connection('oracle_his')->raw('data_store_code'), 'like', $keyword . '%')
                ->orWhere(DB::connection('oracle_his')->raw('data_store_name'), 'like', $keyword . '%');
            });
        if ($this->is_active !== null) {
            $data = $data->where(function ($query) {
                $query = $query->where(DB::connection('oracle_his')->raw('his_data_store.is_active'), $this->is_active);
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
                $name = $this->data_store_name. '_start_' . $this->start . '_limit_' . $this->limit. $this->order_by_tring. '_is_active_' . $this->is_active;
                $param = [
                    'room:id,department_id',
                    'room.department:id,department_name,department_code',
                    'stored_room:id',
                    'stored_department:id,department_name,department_code',
                    'parent:id,data_store_code,data_store_name',
                ];
            } else {
                if (!is_numeric($id)) {
                    return return_id_error($id);
                }
                $data = $this->data_store->find($id);
                if ($data == null) {
                    return return_not_record($id);
                }
                $name = $this->data_store_name . '_' . $id. '_is_active_' . $this->is_active;
                $param = [
                    'room',
                    'room.department',
                    'stored_room',
                    'stored_department',
                    'parent',
                ];
            }
            $data = get_cache_full($this->data_store, $param, $name, $id, $this->time, $this->start, $this->limit, $this->order_by, $this->is_active);
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

    public function data_store_create(CreateDataStoreRequest $request)
    {
        // Start transaction
        DB::connection('oracle_his')->beginTransaction();
        try {
            $room = $this->room::create([
                'create_time' => now()->format('Ymdhis'),
                'modify_time' => now()->format('Ymdhis'),
                'creator' => get_loginname_with_token($request->bearerToken(), $this->time),
                'modifier' => get_loginname_with_token($request->bearerToken(), $this->time),
                'app_creator' => $this->app_creator,
                'app_modifier' => $this->app_modifier,
                'department_id' => $request->department_id,
                'room_type_id' => $request->room_type_id
            ]);
            $data = $this->data_store::create([
                'create_time' => now()->format('Ymdhis'),
                'modify_time' => now()->format('Ymdhis'),
                'creator' => get_loginname_with_token($request->bearerToken(), $this->time),
                'modifier' => get_loginname_with_token($request->bearerToken(), $this->time),
                'app_creator' => $this->app_creator,
                'app_modifier' => $this->app_modifier,
                'data_store_code' => $request->data_store_code,
                'data_store_name' => $request->data_store_name,
                'parent_id' => $request->parent_id,
                'stored_department_id' => $request->stored_department_id,
                'stored_room_id' => $request->stored_room_id,
                'treatment_end_type_ids' => $request->treatment_end_type_ids,
                'treatment_type_ids' => $request->treatment_type_ids,
                'room_id' => $room->id,
            ]);
            DB::connection('oracle_his')->commit();
            // Gọi event để xóa cache
            event(new DeleteCache($this->data_store_name));
            return return_data_create_success(['data' => $data, 'room' => $room]);
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::connection('oracle_his')->rollBack();
            return return_data_fail_transaction();
        }
    }

    public function data_store_update(UpdateDataStoreRequest $request, $id)
    {
        if (!is_numeric($id)) {
            return return_id_error($id);
        }
        $data = $this->data_store->find($id);
        if ($data == null) {
            return return_not_record($id);
        }
        $room = $this->room->find($data->room_id);
        if ($room == null) {
            return return_not_record($data->room_id);
        }
        // Start transaction
        DB::connection('oracle_his')->beginTransaction();
        try {
            $room_update = [
                'modify_time' => now()->format('Ymdhis'),
                'modifier' => get_loginname_with_token($request->bearerToken(), $this->time),
                'app_modifier' => $this->app_modifier,
                'room_type_id' => $request->room_type_id,
                'is_active' => $request->is_active,

            ];
            $data_update = [
                'modify_time' => now()->format('Ymdhis'),
                'modifier' => get_loginname_with_token($request->bearerToken(), $this->time),
                'app_modifier' => $this->app_modifier,
                'data_store_name' => $request->data_store_name,
                'parent_id' => $request->parent_id,
                'stored_department_id' => $request->stored_department_id,
                'stored_room_id' => $request->stored_room_id,
                'treatment_end_type_ids' => $request->treatment_end_type_ids,
                'treatment_type_ids' => $request->treatment_type_ids,
                'is_active' => $request->is_active,

            ];
            $room->fill($room_update);
            $room->save();
            $data->fill($data_update);
            $data->save();
            DB::connection('oracle_his')->commit();
            // Gọi event để xóa cache
            event(new DeleteCache($this->data_store_name));
            return return_data_update_success($data);
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::connection('oracle_his')->rollBack();
            return return_data_fail_transaction();
        }
    }

    public function data_store_delete(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return return_id_error($id);
        }
        $data = $this->data_store->find($id);
        if ($data == null) {
            return return_not_record($id);
        }
        $room = $this->room->find($data->room_id);
        if ($room == null) {
            return return_not_record($data->room_id);
        }
        // Start transaction
        DB::connection('oracle_his')->beginTransaction();
        try {
            $data->delete();
            $room->delete();
            DB::connection('oracle_his')->commit();
            // Gọi event để xóa cache
            event(new DeleteCache($this->data_store_name));
            return return_data_delete_success();
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::connection('oracle_his')->rollBack();
            return return_data_fail_transaction();
        }
    }


}
