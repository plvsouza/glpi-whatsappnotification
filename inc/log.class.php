<?php
class PluginWhatsappnotificationLog extends CommonDBTM {
    static $rightname = 'config';

    static function getTypeName($nb = 0) {
        return __('WhatsApp Log', 'whatsappnotification');
    }

    static function log($ticket_id, $number, $status, $response) {
        $self = new self();
        $input = [
            'date'       => $_SESSION['glpi_currenttime'],
            'ticket_id'  => $ticket_id,
            'number'     => $number,
            'status'     => $status,
            'response'   => $response
        ];
        return $self->add($input);
    }

    static function install(Migration $migration) {
        global $DB;
        
        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `date` TIMESTAMP NOT NULL,
                `ticket_id` INT NOT NULL,
                `number` VARCHAR(20) NOT NULL,
                `status` VARCHAR(50) NOT NULL,
                `response` TEXT NOT NULL,
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
