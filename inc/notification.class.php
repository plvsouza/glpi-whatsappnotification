<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

require_once(__DIR__.'/log.class.php');

class PluginWhatsappnotificationNotification {
    static function send($ticket, bool $is_new_ticket = false) {
        try {
            $config = PluginWhatsappnotificationConfig::getConfig();
            $mappings = PluginWhatsappnotificationMapping::getMappings();
            
            // Validate ticket object
            if (!$ticket instanceof Ticket || !$ticket->getID()) {
                throw new Exception("Invalid ticket object");
            }

            $type = $ticket->fields['type'] ?? 0;
            $category = $ticket->fields['itilcategories_id'] ?? 0;
            $key = $type . '_' . $category;
            
            if (!isset($mappings[$key])) {
                return false;
            }

            // Get interaction context safely
            $is_closed = ($ticket->fields['status'] == Ticket::CLOSED);
            $last_interaction = self::getLastInteraction($ticket, $is_closed, $is_new_ticket);
        
            $body = self::buildBody($ticket, $last_interaction, $is_closed, $is_new_ticket);
            $externalKey = 'TICKET_' . $ticket->getID() . '_' . time();

            $data = [
                'url' => $config->fields['api_url'] ?? '',
                'token' => $config->fields['api_token'] ?? '',
                'number' => $mappings[$key],
                'body' => $body,
                'externalkey' => $externalKey,
                'ticket' => $ticket // Add ticket object to data
            ];

            return self::callWhatsAppAPI($data);

        } catch (Exception $e) {
            Toolbox::logInFile('whatsapp-errors', $e->getMessage() . PHP_EOL);
            return false;
        }
    }

    private static function sanitizeContent($content) {
        // Convert HTML entities to actual characters
        $decoded = html_entity_decode($content ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
        // Replace HTML line breaks with newlines
        $with_newlines = str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>'], 
            "\n", 
            $decoded
        );
    
        // Remove all HTML tags
        $stripped = strip_tags($with_newlines);
    
        // Clean up whitespace and newlines
        $cleaned = preg_replace(['/\n+/', '/\s\s+/'], ["\n", ' '], $stripped);
    
        return trim(substr($cleaned, 0, 1000));
    }

    // In notification.class.php

    private static function getLastInteraction(Ticket $ticket, bool $is_closed, bool $is_new_ticket): array {
        global $DB;

        // Para novos tickets
        if ($is_new_ticket) {
            return [
                'type' => 'descricao',
                'content' => self::sanitizeContent($ticket->fields['content']),
                'date' => $ticket->fields['date_creation'],
                'author' => self::getUserName($ticket->fields['users_id_recipient'])
            ];
        }

        $fallback = [
            'type' => 'description',
            'content' => self::sanitizeContent($ticket->fields['content']),
            'date' => $ticket->fields['date_creation'],
            'author' => self::getUserName($ticket->fields['users_id_recipient'])
        ];

        try {
            // 1. Check closing solution first
            if ($is_closed) {
                $solution = new ITILSolution();
                $solutions = $solution->find([
                    'itemtype' => 'Ticket',
                    'items_id' => $ticket->getID()
                ], ['date_creation DESC'], 1);

                if (!empty($solutions)) {
                    $solution = reset($solutions);
                    return [
                        'type' => 'solution',
                        'content' => self::sanitizeContent($solution['content']),
                        'date' => $solution['date_creation'],
                        'author' => self::getUserName($solution['users_id'])
                    ];
                }
            }

            // 2. Get last followup OR task (including private ones)
            $query = "(
                SELECT 'followup' AS type, content, date_creation, users_id 
                FROM glpi_itilfollowups 
                WHERE items_id = {$ticket->getID()} AND itemtype = 'Ticket'
                UNION ALL
                SELECT 'task' AS type, content, date_creation, users_id 
                FROM glpi_tickettasks 
                WHERE tickets_id = {$ticket->getID()}
            ) ORDER BY date_creation DESC LIMIT 1";

            $result = $DB->query($query);

            if ($result && $DB->numrows($result) > 0) {
                $data = $DB->fetchAssoc($result);
                return [
                    'type' => $data['type'],
                    'content' => self::sanitizeContent($data['content']),
                    'date' => $data['date_creation'],
                    'author' => self::getUserName($data['users_id'])
                ];
            }

        } catch (Exception $e) {
            Toolbox::logInFile('whatsapp-errors', 
                "Ticket #{$ticket->getID()} Error: " . $e->getMessage() . PHP_EOL
            );
        }

        // 3. Fallback to last update if exists
        if (!empty($ticket->oldvalues)) {
            return [
                'type' => 'update',
                'content' => self::sanitizeContent($ticket->oldvalues['content'] ?? ''),
                'date' => date('Y-m-d H:i:s'),
                'author' => self::getUserName($_SESSION['glpiID'])
            ];
        }

        // 4. Final fallback to initial description
        return $fallback;
    }

    // Add null-safe getUserName method
    private static function getUserName($user_id) {
        try {
            $user = new User();
            return $user->getFromDB($user_id) ? $user->getFriendlyName() : __('System');
        } catch (Exception $e) {
            return __('System');
        }
    }

    private static function buildBody($ticket, $interaction, $is_closed, $is_new_ticket) {

        global $CFG_GLPI;

        // Determinar rótulo e data conforme o status
        if ($is_new_ticket) {
            $data_label = "Data de abertura";
            $data_value = $ticket->fields['date'];
        } elseif ($is_closed) {
            $data_label = "Data de fechamento";
            $data_value = $interaction['date'] ?? $ticket->fields['closedate'];
        } else {
            $data_label = "Data de atualização";
            $data_value = $ticket->fields['date_mod'];
        }

        $config = PluginWhatsappnotificationConfig::getConfig();

        // Convert HTML entities to actual characters first
        $decoded_content = html_entity_decode($ticket->fields['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
        // Replace all HTML line breaks with newlines
        $with_newlines = str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>'], 
            "\n", 
            $decoded_content
        );
    
        // Remove ALL HTML tags
        $stripped = strip_tags($with_newlines);
    
        // Clean up whitespace and newlines
        $clean_content = preg_replace(['/\n+/', '/\s\s+/'], ["\n", ' '], $stripped);
        $clean_content = trim(substr($clean_content, 0, 1000));

        // URL direta do ticket
        $ticket_url = $CFG_GLPI['url_base'] . '/front/ticket.form.php?id=' . $ticket->getID();
        $html_link = '<a href="' . htmlspecialchars($ticket_url) . '">Ticket #' . $ticket->getID() . '</a>';

        return sprintf(
            "📋 *Chamado #%s - %s*\n" .
            "🔄 Status: %s\n" .
            "📁 Categoria: %s\n" .
            "👤 %s: %s\n" .
            "📅 %s: %s\n" .
            "📝 Detalhes:\n%s\n" .
            "🔗 Link do Chamado: %s",
            $ticket->getID(),
            $is_new_ticket ? 'ABERTO' : ($is_closed ? 'FECHADO' : 'ATUALIZADO'),
            $is_closed ? 'Fechado' : Ticket::getStatus($ticket->fields['status']),
            Dropdown::getDropdownName('glpi_itilcategories', $ticket->fields['itilcategories_id']),
            $is_new_ticket ? 'Aberto Por' : 'Última Atualização Por',
            self::getUserName($is_new_ticket ? $ticket->fields['users_id_recipient'] : $interaction['author']),
            $data_label,
            Html::convDateTime($data_value),
            $interaction['content'],
            $CFG_GLPI['url_base'] . '/front/ticket.form.php?id=' . $ticket->getID()
        );
    }

    private static function callWhatsAppAPI($data) {
        $ticket_id = $data['ticket']->getID();
        $number = preg_replace('/[^0-9]/', '', $data['number']); // Remove all non-numeric chars

        try {
            // Validate inputs
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                throw new Exception(__('Invalid API URL', 'whatsappnotification'));
            }

            // Prepare API request payload EXACTLY as required
            $payload = [
                'body' => substr($data['body'], 0, 1000), // Limit message length
                'number' => $number,
                'externalKey' => $data['externalkey'] // Note exact case sensitivity
            ];

            $jsonPayload = json_encode($payload);
        
            // Verify JSON encoding succeeded
            if ($jsonPayload === false) {
                throw new Exception(__('Failed to encode JSON payload', 'whatsappnotification'));
            }

            $ch = curl_init($data['url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $data['token']
                ],
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_TIMEOUT => 10
            ]);
        
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            // Special handling for 400 errors
            if ($httpCode == 400) {
                $errorDetails = json_decode($response, true) ?? [];
                $errorMessage = $errorDetails['error'] ?? $response;
                throw new Exception(__('API validation failed: ', 'whatsappnotification') . $errorMessage);
            }

            if ($httpCode !== 200) {
                throw new Exception(__('API request failed with status: ', 'whatsappnotification') . $httpCode);
            }

            PluginWhatsappnotificationLog::log(
                $ticket_id,
                $number,
                'success',
                "API Response: " . substr($response, 0, 500)
            );
            return true;
        
        } catch (Exception $e) {
            PluginWhatsappnotificationLog::log(
                $ticket_id,
                $number,
                'error',
                "FAILED: " . $e->getMessage() . "\nPayload: " . ($jsonPayload ?? '')
            );
        
            Session::addMessageAfterRedirect(
                __('WhatsApp notification failed: ', 'whatsappnotification') . $e->getMessage(),
                false,
                ERROR
            );
            return false;
        }
    }

    private static function validatePhoneNumber($number) {
        return preg_match('/^\+?[1-9]\d{7,14}$/', $number);
    }
}
