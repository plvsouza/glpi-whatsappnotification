<?php
class PluginWhatsappnotificationConfig extends CommonDBTM {
    static protected $notable = false;
    
    static function getTypeName($nb = 0) {
        return __('WhatsApp Configuration');
    }

    static function getConfig() {
        $config = new self();
        $config->getFromDB(1);
        return $config;
    }

    function getForm() {
        $config = $this->getConfig();
        
        $form = [
            'action' => Plugin::getWebDir('whatsappnotification').'/front/config.form.php',
            'inputs' => [
                [
                    'type'  => 'text',
                    'name'  => 'api_url',
                    'label' => __('API URL'),
                    'value' => $config->fields['api_url'] ?? ''
                ],
                [
                    'type'  => 'password',
                    'name'  => 'api_token',
                    'label' => __('API Token'),
                    'value' => $config->fields['api_token'] ?? ''
                ]
            ],
            'submit' => __('Save')
        ];
        
        return $form;
    }

    static function install(Migration $migration) {
        global $DB;
        
        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `api_url` VARCHAR(255) NOT NULL,
                `api_token` VARCHAR(255) NOT NULL,
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
