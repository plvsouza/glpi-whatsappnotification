class PluginWhatsappnotificationUtility {
    static function getUserName($user_id) {
        $user = new User();
        if ($user->getFromDB($user_id)) {
            return $user->getFriendlyName();
        }
        return __('System', 'whatsappnotification');
    }
}
