<?php
// In hook.php
function plugin_whatsappnotification_ticket_add_hook(Ticket $ticket) {
    if (in_array($ticket->fields['status'], [Ticket::INCOMING, Ticket::WAITING])) {
        PluginWhatsappnotificationNotification::send($ticket, true); // Explicit true
    }
}

function plugin_whatsappnotification_ticket_update_hook(Ticket $ticket) {
    $status_changed = isset($ticket->oldvalues['status']);
    if ($ticket->fields['status'] == Ticket::CLOSED || $status_changed) {
        PluginWhatsappnotificationNotification::send($ticket, false); // Explicit false
    }
}
