<?php
define('PLUGIN_WHATSAPPNOTIFICATION_VERSION', '1.1.02');
define('PLUGIN_WHATSAPPNOTIFICATION_MIN_GLPI', '10.0.18');
define('PLUGIN_WHATSAPPNOTIFICATION_MAX_GLPI', '10.0.99');

function plugin_init_whatsappnotification() {
    global $PLUGIN_HOOKS;
    
    $PLUGIN_HOOKS['csrf_compliant']['whatsappnotification'] = true;
    $PLUGIN_HOOKS['config_page']['whatsappnotification'] = 'front/config.form.php';
    
    // Update config_page hook
    // $PLUGIN_HOOKS['config_page']['whatsappnotification'] = 'index.php';

    // Register hooks
    $PLUGIN_HOOKS['item_add']['whatsappnotification'] = [
//        'Ticket' => 'plugin_whatsappnotification_ticket_hook',
        'Ticket' => 'plugin_whatsappnotification_ticket_add_hook'
    ];
    $PLUGIN_HOOKS['item_update']['whatsappnotification'] = [
//        'Ticket' => 'plugin_whatsappnotification_ticket_hook',
        'Ticket' => 'plugin_whatsappnotification_ticket_update_hook'
    ];
    $PLUGIN_HOOKS['add_css']['whatsappnotification'] = [
        'css/custom.css' => 'all'
    ];
    $PLUGIN_HOOKS['menu_toadd']['whatsappnotification'] = [
        'config' => 'PluginWhatsappnotificationConfig'
    ];

    Plugin::registerClass('PluginWhatsappnotificationConfig');
    Plugin::registerClass('PluginWhatsappnotificationMapping');

    
}

function plugin_version_whatsappnotification() {
    return [
        'name'           => 'WhatsApp Notification',
        'version'        => PLUGIN_WHATSAPPNOTIFICATION_VERSION,
        'author'         => 'Your Name',
        'license'        => 'GPLv3+',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_WHATSAPPNOTIFICATION_MIN_GLPI,
                'max' => PLUGIN_WHATSAPPNOTIFICATION_MAX_GLPI,
            ]
        ]
    ];
}

function plugin_whatsappnotification_ticket_hook(Ticket $ticket) {
    $status = $ticket->fields['status'];
    if (in_array($status, [Ticket::INCOMING, Ticket::WAITING, Ticket::CLOSED])) {
        PluginWhatsappnotificationNotification::send($ticket);
    }
}

function plugin_whatsappnotification_getMenuContent() {
    $menu = [];
    
    if (Session::haveRight('plugin_whatsappnotification', READ)) {
        $menu['title'] = __('WhatsApp Notifications', 'whatsappnotification');
        $menu['page']  = Plugin::getWebDir('whatsappnotification').'/front/config.form.php';
        $menu['icon']  = 'fas fa-comment-alt';
    }
    
    return $menu;
}

function plugin_whatsappnotification_install() {
    $migration = new Migration(PLUGIN_WHATSAPPNOTIFICATION_VERSION);
    // Add performance indexes to core GLPI tables
    $migration->addKey('glpi_ticketfollowups', 'tickets_id');
    $migration->addKey('glpi_tickettasks', 'tickets_id');
    
    // Install config table
    PluginWhatsappnotificationConfig::install($migration);
    // Install mapping table
    PluginWhatsappnotificationMapping::install($migration);
    // Install log table
    PluginWhatsappnotificationLog::install($migration);
    // Install profiles
    PluginWhatsappnotificationProfile::install($migration);
    
    // Create default configuration
    $config = new PluginWhatsappnotificationConfig();
    $config->add([
    //    'id'        => 1,
        'api_url'   => '',
        'api_token' => ''
    ]);
    
    return true;
}

function plugin_whatsappnotification_uninstall() {
    $migration = new Migration(PLUGIN_WHATSAPPNOTIFICATION_VERSION);
    
    // Remove tables
    PluginWhatsappnotificationConfig::uninstall($migration);
    PluginWhatsappnotificationMapping::uninstall($migration);
    PluginWhatsappnotificationLog::uninstall($migration);
    PluginWhatsappnotificationProfile::uninstall($migration);
    
    return true;
}
function plugin_whatsappnotification_check_config() {
    return true;
}
