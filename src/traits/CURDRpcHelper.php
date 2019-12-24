<?php

namespace suframe\think\traits;

use think\facade\Db;
use think\Model;

trait CURDRpcHelper
{

    /**
     * @return mixed|Model
     */
    private function getManageModel()
    {
    }

    /**
     * @param array $param
     * @param array $cond
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function ajaxSearch($param = [], $cond = [])
    {
        $whereType = $cond['whereType'] ?? [];
        $tableFields = $cond['tableFields'] ?? [];
        return $this->parseSearchWhere($this->getManageModel(), $param, $whereType, $tableFields)->toArray();
    }

    public function findOne($id, $cond = [], $ext = [])
    {
        $rs = $this->getManageModel()::find($id);
        if (!$rs) {
            return [];
        }
        return $rs->toArray();
    }

    public function update($data, $ext = [])
    {
        $info = $this->getUpdateInfo($data);
        if (!$info) {
            $class = $this->getManageModel();
            $info = new $class;
        }
        Db::startTrans();
        try {
            $post = $this->beforeSave($info, $data, $ext);
            $rs = $info->save($post);
            Db::commit();
            return $rs;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return false;
    }

    public function delete($id, $ext = [])
    {
        $model = $this->getManageModel()::find($id);
        if (!$model) {
            return false;
        }
        Db::startTrans();
        try {
            $this->beforeDelete($model, $ext);
            $rs = $model->delete();
            Db::commit();
            return $rs;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return false;
    }

    /**
     * 默认查询条件
     * @param Model|string $model
     * @param array $params
     * @param array $whereType
     * @param array $tableFields
     * @return mixed|Model
     * @throws \think\db\exception\DbException
     */
    protected function parseSearchWhere($model, $params = [], $whereType = [], $tableFields = [])
    {
        if (is_string($model)) {
            $model = $model::where(1, 1);
        }
        $defaultParams = ['pageSize', 'sort', 'sortType'];

        if (!$tableFields) {
            $tableFields = $model->getTableFields();
        }

        foreach ($params as $key => $item) {
            if (!in_array($key, $tableFields)) {
                unset($params[$key]);
            }
        }
        //分页
        $pageSize = $params['pageSize'] ?? 10;
        $pageSize = intval($pageSize);
        if ($pageSize < 1) {
            $pageSize = 10;
        }
        if ($pageSize > 1000) {
            $pageSize = 1000;
        }

        //排序
        if (isset($params['sort'])) {
            $order = $params['sortType'] ?? 'desc';
            $model->order($params['sort'], $order === 'asc' ? 'asc' : 'desc');
        } else {
            if ($pk = $model->getPk()) {
                $model->order($pk, 'desc');
            }
        }
        foreach ($defaultParams as $defaultParam) {
            if (isset($params[$defaultParam])) {
                unset($params[$defaultParam]);
            }
        }

        foreach ($params as $key => $param) {
            if (!$param) {
                continue;
            }
            if (is_array($param)) {
                $type = 'in';
            } else {
                $type = 'eq';
            }
            if (isset($whereType[$key])) {
                $type = $whereType[$key];
            }
            switch ($type) {
                case 'eq':
                    $model->where($key, $param);
                    break;
                case 'neq':
                    $model->where($key, '<>', $param);
                    break;
                case 'gt':
                    $model->where($key, '>', $param);
                    break;
                case 'gtn':
                    $model->where($key, '>=', $param);
                    break;
                case 'lt':
                    $model->where($key, '<', $param);
                    break;
                case 'ltn':
                    $model->where($key, '<=', $param);
                    break;
                case 'in':
                    $model->whereIn($key, $param);
                    break;
                case 'notIn':
                    $model->whereNotIn($key, $param);
                    break;
                case 'like':
                    $model->whereLike($key, "%{$param}%");
                    break;
                case 'notLike':
                    $model->whereNotLike($key, "%{$param}%");
                    break;
                case 'betweenTime':
                    $model->whereBetweenTime($key, $param[0], $param[1]);
                    break;
                case 'between':
                    $model->whereBetween($key, $param);
                    break;
            }
        }
        return $model->paginate($pageSize);
    }

    /**
     * @param array $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUpdateInfo($param = [])
    {
        if (!$param) {
            return [];
        }
        if (is_int($param)) {
            $id = $param;
        } else {
            $id = $param['id'] ?? 0;
            if (!$id) {
                return [];
            }
        }
        return $this->getManageModel()::find($id);
    }

    /**
     * @param $info
     * @param $post
     * @param array $ext
     * @return mixed
     */
    private function beforeSave($info, $post, $ext = [])
    {
        return $post;
    }

    /**
     * @param \think\Model $model
     * @param array $ext
     */
    private function beforeDelete($model, $ext = []){}

}