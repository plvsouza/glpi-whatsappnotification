<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginWhatsappnotificationProfile extends Profile {
    static $rightname = 'plugin_whatsappnotification';

    // Add this method to fix the error
    static function uninstallProfile() {
        $right = new ProfileRight();
        $right->deleteByCriteria(['name' => self::$rightname]);
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return __('WhatsApp Notification', 'whatsappnotification');
        }
        return '';
    }

    function showForm($ID, $options = []) {
        global $DB;

        $profile = new Profile();
        $profile->getFromDB($ID);
        
        echo "<div class='firstbloc'>";
        echo "<form method='post' action='".$profile->getFormURL()."'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>".__('WhatsApp Notification Access', 'whatsappnotification')."</th></tr>";

        $rights = [
            ['rights' => READ, 'label' => __('Read')],
            ['rights' => UPDATE, 'label' => __('Update')]
        ];
        
        echo "<tr class='tab_bg_2'>";
        echo "<td>".__('Permissions')."</td>";
        echo "<td>";
        $profile->displayRightsChoiceMatrix($rights, [
            'default_class' => 'tab_bg_2',
            'title' => __('General rights')
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='hidden' name='id' value='$ID'>";
        echo "<input type='submit' name='update' value='"._sx('button', 'Save')."' class='submit'>";
        echo "</td></tr>";
        
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    static function createAdminAccess($ID) {
        self::addDefaultProfileInfos($ID, [self::$rightname => ALLSTANDARDRIGHT]);
    }

    static function addDefaultProfileInfos($profiles_id, $rights) {
        $profileRight = new ProfileRight();
        foreach ($rights as $name => $right) {
            if (!countElementsInTable('glpi_profilerights', 
                ['profiles_id' => $profiles_id, 'name' => $name])) {
                $profileRight->add([
                    'profiles_id' => $profiles_id,
                    'name'        => $name,
                    'rights'      => $right
                ]);
            }
        }
    }

    static function install(Migration $migration) {
        global $DB;
        
        foreach (['Profile'] as $itemtype) {
            ProfileRight::addProfileRights([self::$rightname]);
        }
    }
    
    static function uninstall(Migration $migration) {
        // Corrected uninstall method
        self::uninstallProfile();
        ProfileRight::deleteProfileRights([self::$rightname]);
    }
}
