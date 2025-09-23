<?php
/**
 * Rooms & Floors Manager
 *
 * Admin UI and storage for maintaining floors and rooms.
 * Floor: id, name, url
 * Room: id, name
 *
 * @package AvailabilityCalendarPlugin
 * @since 1.1.0
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class YCP_Location_Manager {

    const FLOORS_TABLE = 'ycp_floors';
    const ROOMS_TABLE = 'ycp_rooms';
    const CAPABILITY = 'manage_options';
    const NONCE_ACTION = 'ycp_locations_nonce';

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action('admin_menu', [$this, 'add_admin_submenu']);
        add_action('admin_post_ycp_locations_save', [$this, 'handle_save']);
        add_action('admin_post_ycp_locations_delete', [$this, 'handle_delete']);
    }

    /**
     * Ensure tables exist; create on-demand if missing (for sites that updated without re-activation)
     */
    private function ensure_tables(): void {
        global $wpdb;
        $floors = $wpdb->prefix . self::FLOORS_TABLE;
        $rooms = $wpdb->prefix . self::ROOMS_TABLE;
        $has_floors = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $floors)) === $floors;
        $has_rooms = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $rooms)) === $rooms;
        if (!$has_floors || !$has_rooms) {
            self::create_tables();
        }
    }

    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $floors = $wpdb->prefix . self::FLOORS_TABLE;
        $rooms = $wpdb->prefix . self::ROOMS_TABLE;

        $sql = [];
        $sql[] = "CREATE TABLE $floors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            url varchar(500) DEFAULT '' ,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_floor_name (name)
        ) $charset;";

        $sql[] = "CREATE TABLE $rooms (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_room_name (name)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $q) {
            dbDelta($q);
        }
    }

    public function add_admin_submenu(): void {
        add_submenu_page(
            'ycp-professionals',
            __('Rooms & Floors', 'availability-calendar-plugin'),
            __('Rooms & Floors', 'availability-calendar-plugin'),
            self::CAPABILITY,
            'ycp-locations',
            [$this, 'render_locations_page']
        );
    }

    public function render_locations_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('Insufficient permissions.', 'availability-calendar-plugin'));
        }

        $this->ensure_tables();

        global $wpdb;
        $floors_table = $wpdb->prefix . self::FLOORS_TABLE;
        $rooms_table = $wpdb->prefix . self::ROOMS_TABLE;

        $floors = $wpdb->get_results("SELECT * FROM $floors_table ORDER BY name ASC", ARRAY_A) ?: [];
        $rooms = $wpdb->get_results("SELECT * FROM $rooms_table ORDER BY name ASC", ARRAY_A) ?: [];

        $action_url = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Rooms & Floors', 'availability-calendar-plugin'); ?></h1>

            <h2 style="margin-top:20px;"><?php esc_html_e('Floors', 'availability-calendar-plugin'); ?></h2>
            <form method="post" action="<?php echo esc_url($action_url); ?>" style="margin-bottom:20px;">
                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_locations_nonce'); ?>
                <input type="hidden" name="action" value="ycp_locations_save" />
                <input type="hidden" name="entity" value="floor" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="floor_name"><?php esc_html_e('Name', 'availability-calendar-plugin'); ?></label></th>
                        <td><input type="text" name="name" id="floor_name" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="floor_url"><?php esc_html_e('URL', 'availability-calendar-plugin'); ?></label></th>
                        <td><input type="url" name="url" id="floor_url" class="regular-text" placeholder="https://example.com/stock-1" /></td>
                    </tr>
                </table>
                <?php submit_button(__('Add Floor', 'availability-calendar-plugin')); ?>
            </form>

            <?php if (!empty($floors)): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'availability-calendar-plugin'); ?></th>
                        <th><?php esc_html_e('Name', 'availability-calendar-plugin'); ?></th>
                        <th><?php esc_html_e('URL', 'availability-calendar-plugin'); ?></th>
                        <th><?php esc_html_e('Actions', 'availability-calendar-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($floors as $f): $row_form_id = 'ycp-floor-form-' . intval($f['id']); ?>
                    <tr>
                        <td><?php echo intval($f['id']); ?></td>
                        <td>
                            <input type="text" name="name" form="<?php echo esc_attr($row_form_id); ?>" value="<?php echo esc_attr($f['name']); ?>" class="regular-text" style="max-width:260px;" />
                        </td>
                        <td>
                            <input type="url" name="url" form="<?php echo esc_attr($row_form_id); ?>" value="<?php echo esc_attr($f['url']); ?>" class="regular-text" style="max-width:340px;" />
                        </td>
                        <td style="white-space:nowrap;">
                            <form id="<?php echo esc_attr($row_form_id); ?>" method="post" action="<?php echo esc_url($action_url); ?>" style="display:inline;">
                                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_locations_nonce'); ?>
                                <input type="hidden" name="action" value="ycp_locations_save" />
                                <input type="hidden" name="entity" value="floor" />
                                <input type="hidden" name="id" value="<?php echo intval($f['id']); ?>" />
                            </form>
                            <button type="submit" form="<?php echo esc_attr($row_form_id); ?>" class="button button-secondary" style="margin-right:6px; padding: 0 8px; height: 30px; line-height: 28px;"><?php esc_html_e('Update', 'availability-calendar-plugin'); ?></button>
                            <form method="post" action="<?php echo esc_url($action_url); ?>" style="display:inline;">
                                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_locations_nonce'); ?>
                                <input type="hidden" name="action" value="ycp_locations_delete" />
                                <input type="hidden" name="entity" value="floor" />
                                <input type="hidden" name="id" value="<?php echo intval($f['id']); ?>" />
                                <?php submit_button(__('Delete', 'availability-calendar-plugin'), 'secondary small', '', false, ['onclick' => 'return confirm("' . esc_js(__('Delete this floor?', 'availability-calendar-plugin')) . '");']); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <h2 style="margin-top:30px;"><?php esc_html_e('Rooms', 'availability-calendar-plugin'); ?></h2>
            <form method="post" action="<?php echo esc_url($action_url); ?>" style="margin-bottom:20px;">
                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_locations_nonce'); ?>
                <input type="hidden" name="action" value="ycp_locations_save" />
                <input type="hidden" name="entity" value="room" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="room_name"><?php esc_html_e('Name', 'availability-calendar-plugin'); ?></label></th>
                        <td><input type="text" name="name" id="room_name" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(__('Add Room', 'availability-calendar-plugin')); ?>
            </form>

            <?php if (!empty($rooms)): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'availability-calendar-plugin'); ?></th>
                        <th><?php esc_html_e('Name', 'availability-calendar-plugin'); ?></th>
                        <th><?php esc_html_e('Actions', 'availability-calendar-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $r): $row_form_id = 'ycp-room-form-' . intval($r['id']); ?>
                    <tr>
                        <td><?php echo intval($r['id']); ?></td>
                        <td>
                            <input type="text" name="name" form="<?php echo esc_attr($row_form_id); ?>" value="<?php echo esc_attr($r['name']); ?>" class="regular-text" style="max-width:260px;" />
                        </td>
                        <td style="white-space:nowrap;">
                            <form id="<?php echo esc_attr($row_form_id); ?>" method="post" action="<?php echo esc_url($action_url); ?>" style="display:inline;">
                                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_locations_nonce'); ?>
                                <input type="hidden" name="action" value="ycp_locations_save" />
                                <input type="hidden" name="entity" value="room" />
                                <input type="hidden" name="id" value="<?php echo intval($r['id']); ?>" />
                            </form>
                            <button type="submit" form="<?php echo esc_attr($row_form_id); ?>" class="button button-secondary" style="margin-right:6px; padding: 0 8px; height: 30px; line-height: 28px;"><?php esc_html_e('Update', 'availability-calendar-plugin'); ?></button>
                            <form method="post" action="<?php echo esc_url($action_url); ?>" style="display:inline;">
                                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_locations_nonce'); ?>
                                <input type="hidden" name="action" value="ycp_locations_delete" />
                                <input type="hidden" name="entity" value="room" />
                                <input type="hidden" name="id" value="<?php echo intval($r['id']); ?>" />
                                <?php submit_button(__('Delete', 'availability-calendar-plugin'), 'secondary small', '', false, ['onclick' => 'return confirm("' . esc_js(__('Delete this room?', 'availability-calendar-plugin')) . '");']); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('Insufficient permissions.', 'availability-calendar-plugin'));
        }
        if (!isset($_POST['ycp_locations_nonce']) || !wp_verify_nonce($_POST['ycp_locations_nonce'], self::NONCE_ACTION)) {
            wp_die(__('Security check failed.', 'availability-calendar-plugin'));
        }
        $this->ensure_tables();
        $entity = sanitize_text_field($_POST['entity'] ?? '');
        $id = absint($_POST['id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $url = isset($_POST['url']) ? esc_url_raw((string) $_POST['url']) : '';
        if ($name === '') {
            $this->redirect_with_message('ycp-locations', 'error', __('Name is required.', 'availability-calendar-plugin'));
        }

		global $wpdb;
		if ($entity === 'floor') {
			$old_name = '';
			if ($id > 0) {
				$old_row = $wpdb->get_row($wpdb->prepare('SELECT name FROM ' . $wpdb->prefix . self::FLOORS_TABLE . ' WHERE id = %d', $id), ARRAY_A);
				$old_name = isset($old_row['name']) ? (string) $old_row['name'] : '';
				$result = $wpdb->update($wpdb->prefix . self::FLOORS_TABLE, ['name' => $name, 'url' => (string) $url], ['id' => $id], ['%s','%s'], ['%d']);
			} else {
				$result = $wpdb->insert($wpdb->prefix . self::FLOORS_TABLE, ['name' => $name, 'url' => (string) $url], ['%s','%s']);
			}
			if ($result === false) {
				$this->redirect_with_message('ycp-locations', 'error', __('Failed to save floor. Check database tables.', 'availability-calendar-plugin'));
			}
			// Cascade rename into availability when floor name changed
			if ($id > 0 && $old_name !== '' && $old_name !== $name) {
				$this->cascade_rename('floor', $old_name, $name);
			}
		} elseif ($entity === 'room') {
			$old_name = '';
			if ($id > 0) {
				$old_row = $wpdb->get_row($wpdb->prepare('SELECT name FROM ' . $wpdb->prefix . self::ROOMS_TABLE . ' WHERE id = %d', $id), ARRAY_A);
				$old_name = isset($old_row['name']) ? (string) $old_row['name'] : '';
				$result = $wpdb->update($wpdb->prefix . self::ROOMS_TABLE, ['name' => $name], ['id' => $id], ['%s'], ['%d']);
			} else {
				$result = $wpdb->insert($wpdb->prefix . self::ROOMS_TABLE, ['name' => $name], ['%s']);
			}
			if ($result === false) {
				$this->redirect_with_message('ycp-locations', 'error', __('Failed to save room. Check database tables.', 'availability-calendar-plugin'));
			}
			// Cascade rename into availability when room name changed
			if ($id > 0 && $old_name !== '' && $old_name !== $name) {
				$this->cascade_rename('room', $old_name, $name);
			}
		}

        $this->redirect_with_message('ycp-locations', 'updated', __('Saved successfully.', 'availability-calendar-plugin'));
    }

    public function handle_delete(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('Insufficient permissions.', 'availability-calendar-plugin'));
        }
        if (!isset($_POST['ycp_locations_nonce']) || !wp_verify_nonce($_POST['ycp_locations_nonce'], self::NONCE_ACTION)) {
            wp_die(__('Security check failed.', 'availability-calendar-plugin'));
        }
        $this->ensure_tables();
        $entity = sanitize_text_field($_POST['entity'] ?? '');
        $id = absint($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect_with_message('ycp-locations', 'error', __('Invalid ID.', 'availability-calendar-plugin'));
        }

		global $wpdb;
		if ($entity === 'floor') {
			// Fetch the name before deletion for cascading
			$row = $wpdb->get_row($wpdb->prepare('SELECT name FROM ' . $wpdb->prefix . self::FLOORS_TABLE . ' WHERE id = %d', $id), ARRAY_A);
			$old_name = isset($row['name']) ? (string) $row['name'] : '';
			$wpdb->delete($wpdb->prefix . self::FLOORS_TABLE, ['id' => $id], ['%d']);
			if ($old_name !== '') {
				$this->cascade_delete('floor', $old_name);
			}
		} elseif ($entity === 'room') {
			// Fetch the name before deletion for cascading
			$row = $wpdb->get_row($wpdb->prepare('SELECT name FROM ' . $wpdb->prefix . self::ROOMS_TABLE . ' WHERE id = %d', $id), ARRAY_A);
			$old_name = isset($row['name']) ? (string) $row['name'] : '';
			$wpdb->delete($wpdb->prefix . self::ROOMS_TABLE, ['id' => $id], ['%d']);
			if ($old_name !== '') {
				$this->cascade_delete('room', $old_name);
			}
		}
        $this->redirect_with_message('ycp-locations', 'updated', __('Deleted.', 'availability-calendar-plugin'));
    }

	/**
	 * Cascade a rename in Rooms & Floors into the availability table
	 *
	 * @param string $type 'floor'|'room'
	 * @param string $old_name
	 * @param string $new_name
	 */
	private function cascade_rename(string $type, string $old_name, string $new_name): void {
		$type = $type === 'floor' ? 'floor' : 'room';
		global $wpdb;
		$availability_table = $wpdb->prefix . 'ycp_availability';
		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $availability_table)) === $availability_table;
		if (!$exists) {
			return;
		}
		$old_name = trim($old_name);
		$new_name = trim($new_name);
		if ($old_name === '' || $new_name === '' || $old_name === $new_name) {
			return;
		}
		$column = $type === 'floor' ? 'floor' : 'room';
		$sql = "UPDATE $availability_table SET $column = %s WHERE $column = %s";
		$wpdb->query($wpdb->prepare($sql, $new_name, $old_name));
	}

	/**
	 * Cascade a delete in Rooms & Floors into the availability table (null-out references)
	 *
	 * @param string $type 'floor'|'room'
	 * @param string $name
	 */
	private function cascade_delete(string $type, string $name): void {
		$type = $type === 'floor' ? 'floor' : 'room';
		global $wpdb;
		$availability_table = $wpdb->prefix . 'ycp_availability';
		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $availability_table)) === $availability_table;
		if (!$exists) {
			return;
		}
		$name = trim($name);
		if ($name === '') {
			return;
		}
		$column = $type === 'floor' ? 'floor' : 'room';
		$sql = "UPDATE $availability_table SET $column = NULL WHERE $column = %s";
		$wpdb->query($wpdb->prepare($sql, $name));
	}

    private function redirect_with_message(string $page_slug, string $type, string $message): void {
        $url = add_query_arg([
            'page' => $page_slug,
            'ycp_msg' => rawurlencode($message),
            'ycp_msg_type' => $type,
        ], admin_url('admin.php'));
        wp_redirect($url);
        exit;
    }
}


