<?php
/**
 * Plugin Name:       Guru Table Pro
 * Description:       Create and manage dynamic, editable tables via shortcodes. Admins can add/remove rows and columns inline.
 * Version:1.2
 * Author:Alireza Fatemi
 * Author URI:https://alirezafatemi.ir
 * Plugin URI:https://github.com/deveguru
 */

if (!defined('ABSPATH')) {
    exit;
}

class Guru_Table_Pro {

    private $option_name = 'guru_tables_data';

    public function __construct() {
        for ($i = 1; $i <= 9; $i++) {
            add_shortcode('guru_table' . $i, array($this, 'render_table_shortcode'));
        }
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        add_action('wp_ajax_guru_table_save_cell', array($this, 'ajax_save_cell'));
        add_action('wp_ajax_guru_table_add_row', array($this, 'ajax_add_row'));
        add_action('wp_ajax_guru_table_remove_row', array($this, 'ajax_remove_row'));
        add_action('wp_ajax_guru_table_add_column', array($this, 'ajax_add_column'));
        add_action('wp_ajax_guru_table_remove_column', array($this, 'ajax_remove_column'));
        add_action('wp_head', array($this, 'add_inline_styles'));
        add_action('wp_footer', array($this, 'add_inline_scripts'));
    }

    public function register_assets() {
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'guru_table_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('guru_table_nonce')
        ));
    }

    public function add_inline_styles() {
        ?>
        <style type="text/css">
            .guru-table-app * { direction: ltr !important; box-sizing: border-box !important; }
            .guru-table-container { font-family: Arial, sans-serif !important; max-width: 100% !important; margin: 20px auto !important; padding: 20px !important; }
            .guru-table-header { margin-bottom: 20px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap; gap: 10px; }
            .guru-table-header h2 { color: #2e7d32 !important; margin: 0 !important; font-size: 24px; }
            .guru-table-controls button { background-color: #2e7d32 !important; color: white !important; border: none !important; padding: 10px 15px !important; border-radius: 4px !important; cursor: pointer !important; font-weight: bold !important; font-size: 14px !important; }
            .guru-table-controls button:hover { background-color: #1b5e20 !important; }
            .guru-table-wrapper { overflow-x: auto !important; border: 1px solid #ddd !important; border-radius: 8px !important; background: white !important; }
            .guru-table { width: 100% !important; border-collapse: collapse !important; }
            .guru-table th, .guru-table td { padding: 8px !important; border: 1px solid #ddd !important; text-align: center !important; font-size: 13px !important; position: relative !important; }
            .guru-table thead th { background-color: #2e7d32 !important; color: white !important; padding: 15px 8px !important; font-weight: bold !important; border-bottom: 2px solid #1b5e20 !important; }
            .guru-table tbody tr:nth-child(even) { background-color: #f9f9f9 !important; }
            .guru-table tbody tr:hover { background-color: #e8f5e9 !important; }
            .editable-cell, .editable-header, .editable-title, .editable-text { cursor: pointer !important; min-height: 20px !important; border-radius: 3px !important; }
            .editable-title { display: inline-block; padding: 5px 10px !important; border: 1px dashed #ccc !important; }
            .editable-text { text-align: center !important; white-space: pre-wrap; width: 100% !important; padding: 15px !important; margin: 15px 0 !important; background-color: #f9f9f9 !important; border: 1px dashed #ccc !important; line-height: 1.6 !important; font-size: 14px !important; }
            .editable-cell:hover, .editable-header:hover, .editable-title:hover, .editable-text:hover { background-color: #f0f8ff !important; box-shadow: 0 0 0 2px #2e7d32 inset; border-color: transparent !important; }
            .editing { padding: 0 !important; }
            .editable-input { width: 100% !important; padding: 8px !important; border: 2px solid #2e7d32 !important; border-radius: 3px !important; font-size: 13px !important; }
            .editable-textarea { width: 100% !important; min-height: 100px !important; padding: 10px !important; border: 2px solid #2e7d32 !important; border-radius: 3px !important; font-size: 14px !important; line-height: 1.6 !important; }
            h2 .editable-input { font-size: 24px; padding: 0 5px; font-weight: bold; color: #2e7d32; }
            .action-buttons { width: 100px; }
            .remove-btn { background: #f44336 !important; color: white !important; border: none !important; border-radius: 50% !important; cursor: pointer !important; width: 22px !important; height: 22px !important; font-weight: bold !important; line-height: 22px !important; text-align: center !important; }
            .remove-btn:hover { background: #da190b !important; }
            .remove-col-btn { position: absolute; top: 2px; right: 2px; }
            .loading-overlay { display: none !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background-color: rgba(255, 255, 255, 0.8) !important; z-index: 10000 !important; justify-content: center !important; align-items: center !important; }
            .loading-spinner { border: 5px solid #f3f3f3 !important; border-top: 5px solid #2e7d32 !important; border-radius: 50% !important; width: 50px !important; height: 50px !important; animation: guru-spin 1s linear infinite !important; }
            @keyframes guru-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .message-bar { display: none; color: white !important; padding: 10px !important; border-radius: 4px !important; margin-bottom: 15px !important; text-align: center !important; }
            .message-bar.success { background-color: #4caf50 !important; }
            .message-bar.error { background-color: #f44336 !important; }
        </style>
        <?php
    }

    public function add_inline_scripts() {
        if (!current_user_can('manage_options')) return;
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function initGuruTable(container) {
                if (container.data('initialized')) return;
                container.data('initialized', true);
                var editingCell = null;
                var originalValue = '';
                var tableId = container.data('table-id');
                function showLoading() { container.find(".loading-overlay").css("display", "flex"); }
                function hideLoading() { container.find(".loading-overlay").hide(); }
                function showMessage(message, type) {
                    var bar = container.find('.message-bar');
                    bar.removeClass('success error').addClass(type).text(message).fadeIn();
                    setTimeout(function() { bar.fadeOut(); }, 3000);
                }
                function doAjax(action, data, successCallback) {
                    data.action = 'guru_table_' + action;
                    data.nonce = guru_table_vars.nonce;
                    data.table_id = tableId;
                    showLoading();
                    $.ajax({
                        url: guru_table_vars.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        success: function(response) {
                            if (response.success) {
                                if (successCallback) successCallback(response.data);
                                showMessage(response.data.message || 'Operation successful!', 'success');
                            } else {
                                showMessage('Error: ' + (response.data || 'Unknown error'), 'error');
                            }
                        },
                        error: function() { showMessage('AJAX Error: Could not connect to the server.', 'error'); },
                        complete: function() { hideLoading(); }
                    });
                }
                function startEdit(cell) {
                    if (editingCell) return;
                    editingCell = cell;
                    originalValue = cell.text().trim();
                    cell.addClass('editing');
                    var editor;
                    if (cell.hasClass('editable-text')) {
                        editor = $('<textarea class="editable-textarea"></textarea>');
                    } else {
                        editor = $('<input type="text" class="editable-input">');
                    }
                    editor.val(originalValue);
                    cell.html(editor);
                    editor.focus();
                    editor.on('blur', saveEdit);
                    editor.on('keydown', function(e) {
                        if (e.key === 'Escape') {
                            e.preventDefault();
                            cancelEdit();
                        }
                    });
                }
                function saveEdit() {
                    if (!editingCell) return;
                    var input = editingCell.find('input, textarea');
                    if (input.length === 0) return;
                    var newValue = input.val().trim();
                    if (newValue === originalValue) {
                        cancelEdit();
                        return;
                    }
                    var data = {};
                    if (editingCell.is('th')) {
                        data.col_id = editingCell.data('col-id');
                        data.value = newValue;
                        data.type = 'header';
                    } else if (editingCell.hasClass('editable-title')) {
                        data.value = newValue;
                        data.type = 'title';
                    } else if (editingCell.hasClass('editable-text')) {
                        data.type = editingCell.data('type');
                        data.value = newValue;
                    } else {
                        data.row_index = editingCell.closest('tr').data('row-index');
                        data.col_id = editingCell.data('col-id');
                        data.value = newValue;
                        data.type = 'cell';
                    }
                    doAjax('save_cell', data, function(responseData) {
                        editingCell.text(newValue).removeClass('editing');
                        editingCell = null;
                    });
                }
                function cancelEdit() {
                    if (!editingCell) return;
                    editingCell.text(originalValue).removeClass('editing');
                    editingCell = null;
                }
                container.on('click', '.editable-cell, .editable-header, .editable-title, .editable-text', function() {
                    if ($(this).hasClass('editing')) return;
                    if (editingCell && !editingCell.is($(this))) saveEdit();
                    startEdit($(this));
                });
                container.on('click', '.add-row-btn', function() {
                    doAjax('add_row', {}, function(data) { container.find('tbody').append(data.row_html); });
                });
                container.on('click', '.add-col-btn', function() {
                    doAjax('add_column', {}, function() { location.reload(); });
                });
                container.on('click', '.remove-row-btn', function() {
                    if (!confirm('Are you sure you want to delete this row?')) return;
                    var row = $(this).closest('tr');
                    var rowIndex = row.data('row-index');
                    doAjax('remove_row', { row_index: rowIndex }, function() { row.fadeOut(function() { $(this).remove(); }); });
                });
                container.on('click', '.remove-col-btn', function(e) {
                    e.stopPropagation();
                    if (!confirm('Are you sure you want to delete this column?')) return;
                    var colId = $(this).closest('th').data('col-id');
                    doAjax('remove_column', { col_id: colId }, function() { location.reload(); });
                });
            }
            $('.guru-table-app').each(function() { initGuruTable($(this)); });
        });
        </script>
        <?php
    }

    private function get_table_data($table_id) {
        $all_tables = get_option($this->option_name, array());
        if (!isset($all_tables[$table_id])) {
            $default_col_id = 'col_' . uniqid();
            return array(
                'title'   => 'Editable Table (' . $table_id . ')',
                'description_top' => 'Click to edit top description.',
                'headers' => array(array('id' => $default_col_id, 'label' => 'Header 1')),
                'rows'    => array(array($default_col_id => 'Sample Data')),
                'description_bottom' => 'Click to edit bottom description.'
            );
        }
        return wp_parse_args($all_tables[$table_id], array(
            'description_top' => '',
            'description_bottom' => ''
        ));
    }

    private function save_table_data($table_id, $data) {
        $all_tables = get_option($this->option_name, array());
        $all_tables[$table_id] = $data;
        return update_option($this->option_name, $all_tables);
    }

    public function ajax_save_cell() {
        check_ajax_referer('guru_table_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
        $table_id = sanitize_key($_POST['table_id']);
        $value = sanitize_textarea_field(stripslashes($_POST['value']));
        $type = sanitize_text_field($_POST['type']);
        $data = $this->get_table_data($table_id);

        if ($type === 'title') {
            $data['title'] = $value;
        } elseif ($type === 'header') {
            $col_id = sanitize_text_field($_POST['col_id']);
            foreach ($data['headers'] as &$header) {
                if ($header['id'] === $col_id) {
                    $header['label'] = $value;
                    break;
                }
            }
        } elseif ($type === 'description_top') {
            $data['description_top'] = $value;
        } elseif ($type === 'description_bottom') {
            $data['description_bottom'] = $value;
        } else {
            $row_index = intval($_POST['row_index']);
            $col_id = sanitize_text_field($_POST['col_id']);
            if (isset($data['rows'][$row_index])) {
                $data['rows'][$row_index][$col_id] = $value;
            }
        }

        if ($this->save_table_data($table_id, $data)) {
            wp_send_json_success(['message' => 'Updated successfully.']);
        } else {
            wp_send_json_error('Failed to save data.');
        }
    }

    public function ajax_add_row() {
        check_ajax_referer('guru_table_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
        $table_id = sanitize_key($_POST['table_id']);
        $data = $this->get_table_data($table_id);
        $new_row = array();
        foreach($data['headers'] as $header) { $new_row[$header['id']] = ''; }
        $data['rows'][] = $new_row;
        $new_row_index = count($data['rows']) - 1;
        if ($this->save_table_data($table_id, $data)) {
            ob_start();
            $this->render_table_row($new_row, $new_row_index, $data['headers'], true);
            $row_html = ob_get_clean();
            wp_send_json_success(['message' => 'Row added.', 'row_html' => $row_html]);
        } else {
            wp_send_json_error('Failed to add row.');
        }
    }
    
    public function ajax_remove_row() {
        check_ajax_referer('guru_table_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
        $table_id = sanitize_key($_POST['table_id']);
        $row_index = intval($_POST['row_index']);
        $data = $this->get_table_data($table_id);
        if (isset($data['rows'][$row_index])) {
            array_splice($data['rows'], $row_index, 1);
        }
        if ($this->save_table_data($table_id, $data)) {
            wp_send_json_success(['message' => 'Row removed.']);
        } else {
            wp_send_json_error('Failed to remove row.');
        }
    }

    public function ajax_add_column() {
        check_ajax_referer('guru_table_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
        $table_id = sanitize_key($_POST['table_id']);
        $data = $this->get_table_data($table_id);
        $new_col_id = 'col_' . uniqid();
        $data['headers'][] = ['id' => $new_col_id, 'label' => 'New Column'];
        foreach ($data['rows'] as &$row) { $row[$new_col_id] = ''; }
        if ($this->save_table_data($table_id, $data)) {
            wp_send_json_success(['message' => 'Column added.']);
        } else {
            wp_send_json_error('Failed to add column.');
        }
    }

    public function ajax_remove_column() {
        check_ajax_referer('guru_table_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied.');
        $table_id = sanitize_key($_POST['table_id']);
        $col_id = sanitize_text_field($_POST['col_id']);
        $data = $this->get_table_data($table_id);
        $data['headers'] = array_values(array_filter($data['headers'], function($h) use ($col_id) { return $h['id'] !== $col_id; }));
        foreach ($data['rows'] as &$row) { unset($row[$col_id]); }
        if ($this->save_table_data($table_id, $data)) {
            wp_send_json_success(['message' => 'Column removed.']);
        } else {
            wp_send_json_error('Failed to remove column.');
        }
    }

    private function render_table_row($row, $row_index, $headers, $is_admin) {
        ?>
        <tr data-row-index="<?php echo esc_attr($row_index); ?>">
            <?php foreach ($headers as $header): ?>
                <td class="<?php echo $is_admin ? 'editable-cell' : ''; ?>" data-col-id="<?php echo esc_attr($header['id']); ?>"><?php echo esc_html($row[$header['id']] ?? ''); ?></td>
            <?php endforeach; ?>
            <?php if ($is_admin): ?>
                <td class="action-buttons"><button type="button" class="remove-btn remove-row-btn" title="Remove Row">&times;</button></td>
            <?php endif; ?>
        </tr>
        <?php
    }

    public function render_table_shortcode($atts, $content = null, $tag = '') {
        $table_id = sanitize_key($tag);
        $data = $this->get_table_data($table_id);
        $is_admin = current_user_can('manage_options');
        ob_start();
        ?>
        <div id="guru-table-app-<?php echo esc_attr($table_id); ?>" class="guru-table-app" data-table-id="<?php echo esc_attr($table_id); ?>">
            <div class="guru-table-container">
                <div class="guru-table-header">
                    <h2>
                        <?php if ($is_admin): ?>
                            Guru Table Editor: <span class="editable-title" style="color: #4caf50;"><?php echo esc_html($data['title']); ?></span>
                        <?php else: ?>
                            <?php echo esc_html($data['title']); ?>
                        <?php endif; ?>
                    </h2>
                    <?php if ($is_admin): ?>
                        <div class="guru-table-controls">
                            <button type="button" class="add-row-btn">Add Row</button>
                            <button type="button" class="add-col-btn">Add Column</button>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($is_admin): ?>
                    <div class="message-bar"></div>
                <?php endif; ?>

                <?php if ($is_admin || !empty(trim($data['description_top']))): ?>
                    <div class="editable-text" data-type="description_top">
                        <?php echo $is_admin ? esc_html($data['description_top']) : nl2br(esc_html($data['description_top'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="guru-table-wrapper">
                    <table class="guru-table">
                        <thead>
                            <tr>
                                <?php foreach ($data['headers'] as $header): ?>
                                    <th class="<?php echo $is_admin ? 'editable-header' : ''; ?>" data-col-id="<?php echo esc_attr($header['id']); ?>">
                                        <?php echo esc_html($header['label']); ?>
                                        <?php if ($is_admin): ?>
                                            <button type="button" class="remove-btn remove-col-btn" title="Remove Column">&times;</button>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <?php if ($is_admin): ?>
                                    <th class="action-buttons">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data['rows']) && is_array($data['rows'])): ?>
                                <?php foreach ($data['rows'] as $index => $row): ?>
                                    <?php $this->render_table_row($row, $index, $data['headers'], $is_admin); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($is_admin || !empty(trim($data['description_bottom']))): ?>
                    <div class="editable-text" data-type="description_bottom">
                         <?php echo $is_admin ? esc_html($data['description_bottom']) : nl2br(esc_html($data['description_bottom'])); ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                    <div class="loading-overlay"><div class="loading-spinner"></div></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Guru_Table_Pro();
