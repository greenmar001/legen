<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsOrderParamsPluginModel extends waModel
{

    protected $table = 'shop_itemsets_order_params';

    /**
     * Get value of one custom param on order
     * 
     * @param int $order_id
     * @param string $name
     * @return string|null
     */
    public function getOne($order_id, $name)
    {
        $item = $this->getByField(array(
            'order_id' => $order_id,
            'name' => $name
        ));
        return $item ? $item['value'] : null;
    }

    /**
     * Get custom params of order
     * 
     * @param array|int $ids order ID
     * @return array params in key=>value format
     */
    public function get($ids)
    {
        if (!$ids) {
            return array();
        }
        $params = array();
        foreach ($this->getByField('order_id', $ids, true) as $p) {
            $params[$p['order_id']][$p['name']] = $p['value'];
        }
        if (is_numeric($ids)) {
            $params = isset($params[$ids]) ? $params[$ids] : array();
        } else {
            foreach ($ids as $id) {
                if (!isset($params[$id])) {
                    $params[$id] = array();
                }
            }
        }
        return $params;
    }

    /**
     * Set one param to order
     * 
     * @param int $order_id
     * @param string $name
     * @param string $value
     * @return bool
     */
    public function setOne($order_id, $name, $value)
    {
        return $this->set($order_id, array($name => $value), false);
    }

    /**
     * Set custom params to order
     *
     * @param int|array $id order ID
     * @param array|null $params key=>value format of array or null (to delete all params assigned to order)
     * @param bool $delete_old
     * @return bool
     */
    public function set($id, $params = array(), $delete_old = true)
    {
        if ($id) {
            $id = (array) $id;

            // remove if params is null
            if (is_null($params)) {
                return $this->deleteByField(array(
                            'order_id' => $id
                ));
            }

            if (empty($params)) {
                return true;
            }

            // old params (candidate to delete)
            $old_params = $this->get($id);

            // accumulate params to add (new params) and update old params
            $add_params = array();
            foreach ($params as $name => $value) {
                foreach ($id as $order_id) {
                    if (isset($old_params[$order_id][$name])) {
                        if ($value === null) {
                            // delete this param
                            $this->deleteByField(array(
                                'order_id' => $id,
                                'name' => $name
                            ));
                        } else {
                            // update old param
                            $this->updateByField(array('order_id' => $id, 'name' => $name), array('value' => $value));
                        }
                        // remove from candidate to delete
                        unset($old_params[$order_id][$name]);
                    } else {
                        // param to add
                        $add_params[] = array(
                            'order_id' => $order_id,
                            'name' => $name,
                            'value' => $value
                        );
                    }
                }
            }

            if ($delete_old) {
                // delete
                foreach ($old_params as $prms) {
                    foreach ($prms as $name => $value) {
                        $this->deleteByField(array(
                            'order_id' => $id,
                            'name' => $name
                        ));
                    }
                }
            }

            // add new params
            if ($add_params) {
                $this->multipleInsert($add_params);
            }

            return true;
        }
        return false;
    }

}
