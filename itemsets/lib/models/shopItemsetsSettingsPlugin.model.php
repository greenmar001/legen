<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopItemsetsSettingsPluginModel extends waModel
{

    protected $table = 'shop_itemsets_settings';

    /**
     * Get settings by field
     * 
     * @param string $field
     * @return array
     */
    public function get($field)
    {
        $settings = array();
        $sql = "SELECT * FROM {$this->table} WHERE field = '" . $this->escape($field) . "'";
        $result = $this->query($sql);
        if ($result) {
            foreach ($result as $r) {
                if (!empty($r['text'])) {
                    $r['value'] = $r['text'];
                }
                if (isset($settings[$r['ext']])) {
                    $settings[$r['ext']] = (array) $settings[$r['ext']];
                    $settings[$r['ext']][] = $r['value'];
                } else {
                    $settings[$r['ext']] = $r['value'];
                }
            }
        }
        return $settings;
    }

    /**
     * Save settings 
     * 
     * @param string $field
     * @param array $settings
     * @return boolean
     */
    public function save($field, $settings)
    {
        $query = array();
        if ($settings) {
            foreach ($settings as $k => $v) {
                if (!is_array($v)) {
                    if (mb_strlen($v, 'UTF-8') > 50) {
                        $query[] = "('" . $this->escape($field) . "', '" . $this->escape($k) . "', '', '" . $this->escape($v) . "')";
                    } else {
                        $query[] = "('" . $this->escape($field) . "', '" . $this->escape($k) . "', '" . $this->escape($v) . "', '')";
                    }
                } else {
                    foreach ($v as $val) {
                        if (mb_strlen($val, 'UTF-8') > 50) {
                            $query[] = "('" . $this->escape($field) . "', '" . $this->escape($k) . "','', '" . $this->escape($val) . "')";
                        } else {
                            $query[] = "('" . $this->escape($field) . "', '" . $this->escape($k) . "', '" . $this->escape($val) . "', '')";
                        }
                    }
                }
            }
        }
        if ($query) {
            $sql = "INSERT IGNORE INTO {$this->table} (field, ext, value, text) VALUES " . implode(",", $query);
            return $this->exec($sql);
        }
        return true;
    }

}
