<?php
include('../../../inc/includes.php');

Html::header(__('WhatsApp Notification Config', 'whatsappnotification'), $_SERVER['PHP_SELF'], 'config', 'plugin');

$config = new PluginWhatsappnotificationConfig();
$mapping = new PluginWhatsappnotificationMapping();

// Load configuration safely
$config_data = [];
if ($config->getFromDB(1)) {
    $config_data = $config->fields;
}

// Handle form submissions
// Check CSRF token for destructive actions
if (isset($_POST['update_config'])) {
    Session::checkRight("config", UPDATE);
    $config->update($_POST);
    Session::addMessageAfterRedirect(__('Configura&ccedil;&atilde;o atualizada com sucesso', 'whatsappnotification'));
    Html::back();
}

if (isset($_POST['add_mapping'])) {
    Session::checkRight("config", UPDATE);
    if ($mapping->add($_POST)) {
        Session::addMessageAfterRedirect(__('Mapeamento adicionado com sucesso', 'whatsappnotification'));
    }
    Html::back();
}

if (isset($_POST['delete_mapping'])) {
    Session::checkRight("config", UPDATE);
    if ($mapping->delete($_POST)) {
        Session::addMessageAfterRedirect(__('Mapeamento exclu&iacute;do com sucesso', 'whatsappnotification'));
    }
    Html::back();
}

// Main configuration form
echo "<div class='center'>";
echo "<form method='post' action=''>";
echo "<input type='hidden' name='id' value='1'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>".__('Configura&ccedil;&atilde;o da API', 'whatsappnotification')."</th></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>".__('API URL', 'whatsappnotification')."</td>";
echo "<td><input type='url' name='api_url' value='".($config_data['api_url'] ?? '')."' required style='width: 300px'></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>".__('API Token', 'whatsappnotification')."</td>";
echo "<td><input type='password' name='api_token' value='".($config_data['api_token'] ?? '')."' required style='width: 300px'></td>";
echo "</tr>";

echo "<tr class='tab_bg_2'>";
echo "<td colspan='2' class='center'>";
echo "<input type='submit' name='update_config' value='"._sx('button', 'Save')."' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";

// Mapping configuration
echo "<div class='center'>";
echo "<form method='post' action=''>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='4'>".__('Configura&ccedil;&atilde;o de mapeamento de n&uacute;meros', 'whatsappnotification')."</th></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>".__('Tipo de Ticket', 'whatsappnotification')."</td>";
echo "<td>";
Ticket::dropdownType('type', ['value' => $_POST['type'] ?? '']);
echo "</td>";

echo "<td>".__('Categoria', 'whatsappnotification')."</td>";
echo "<td>";
ITILCategory::dropdown(['name' => 'category', 'value' => $_POST['category'] ?? 0]);
echo "</td></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>".__('N&uacute;mero do WhatsApp', 'whatsappnotification')."</td>";
echo "<td colspan='3'>";
echo "<input type='tel' name='number' pattern='[0-9]{11,15}' required 
      placeholder='5511999999999 (somente n&uacute;meros)'>";
echo "<br><small>".__('Deve incluir c&oacute;digo de pa&iacute;s (por exemplo, 55 para o Brasil) sem o sinal de +', 'whatsappnotification')."</small>";
echo "</td></tr>";

echo "<tr class='tab_bg_2'>";
echo "<td colspan='4' class='center'>";
echo "<input type='submit' name='add_mapping' value='".__('Adicionar Mapeamento')."' class='submit'>";
echo "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";

// Display mappings section
echo '<div class="card">';
echo '<div class="card-header"><h3>'.__('Mapeamentos de n&uacute;meros', 'whatsappnotification').'</h3></div>';
echo '<div class="card-body">';

$mapping = new PluginWhatsappnotificationMapping();
$all_mappings = $mapping->find([], ['type ASC', 'category ASC']); // Get ALL mappings
// $all_mappings = $mapping->find([], 0, 0);

if (!empty($all_mappings)) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover">';
    echo '<thead class="thead-light">';
    echo '<tr>';
    echo '<th>'.__('Tipo').'</th>';
    echo '<th>'.__('Categoria').'</th>';
    echo '<th>'.__('N&uacute;mero do WhatsApp').'</th>';
    echo '<th>'.__('A&ccedil;&otilde;es').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($all_mappings as $map) {
        echo '<tr>';
        // Type Column
        echo '<td>'.Ticket::getTicketTypeName($map['type']).'</td>';
        
        // Category Column
        echo '<td>'.Dropdown::getDropdownName('glpi_itilcategories', $map['category']).'</td>';
        
        // Phone Number (Sanitized)
        echo '<td>'.preg_replace('/[^0-9+]/', '', $map['number']).'</td>';
        
        // Delete Button (Alternative Approach)
        echo '<td>';
        echo '<form method="post" style="display: inline-block;">';
        echo '<input type="hidden" name="id" value="'.$map['id'].'">';
        echo '<button type="submit" 
                    name="delete_mapping" 
                    class="btn btn-danger btn-sm"
                    onclick="return confirm(\''.__('Confirm deletion?').'\')">
                '.__('Delete').'
            </button>';
        Html::closeForm();
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info">'.__('Nenhum mapeamento encontrado').'</div>';
}

echo '</div></div>'; // Close card-body and card

Html::footer();
