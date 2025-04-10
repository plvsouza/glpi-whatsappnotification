<?php
class PluginWhatsappnotificationMapping extends CommonDBTM {
    static $rightname = 'config';

    static function getTypeName($nb = 0) {
        return __('WhatsApp Number Mapping');
    }

    static function getMappings() {
        global $DB;
        
        $mappings = [];
        $iterator = $DB->request([
            'FROM' => self::getTable()
        ]);
        
        foreach ($iterator as $data) {
            $key = $data['type'] . '_' . $data['category'];
            $mappings[$key] = $data['number'];
        }
        
        return $mappings;
    }

    static function install(Migration $migration) {
        global $DB;
        
        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `type` INT NOT NULL,
                `category` INT NOT NULL,
                `number` VARCHAR(20) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $DB->query($query);
        }
    }

    static function uninstall(Migration $migration) {
        global $DB;
        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $migration->dropTable($table);
        }
    }
}
