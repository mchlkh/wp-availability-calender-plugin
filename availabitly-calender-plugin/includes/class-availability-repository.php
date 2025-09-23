<?php
/**
 * Availability Repository for per-day room/floor storage
 *
 * @package AvailabilityCalendarPlugin
 * @since 1.1.0
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YCP_Availability_Repository
 *
 * Manages the `ycp_availability` table and related operations.
 */
class YCP_Availability_Repository {

    /**
     * Database table name suffix
     */
    const TABLE_NAME = 'ycp_availability';

    /**
     * Create availability table if not exists
     */
    public static function create_table(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            professional_id mediumint(9) NOT NULL,
            date date NOT NULL,
            room varchar(100) NULL,
            floor varchar(100) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_professional_date (professional_id, date),
            KEY professional_id_idx (professional_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Ensure availability table exists
     */
    private function ensure_table_exists(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name;
        if (!$exists) {
            self::create_table();
        }
    }

    /**
     * Upsert a single availability row and ensure available_dates contains the date
     */
    public function upsert_availability(int $professional_id, string $date, ?string $room, ?string $floor): void {
        global $wpdb;

        if (!$this->is_valid_date($date)) {
            return;
        }

        $availability_table = $wpdb->prefix . self::TABLE_NAME;
        $professionals_table = $wpdb->prefix . 'ycp_professionals';

        $this->ensure_table_exists();

        $clean_room = isset($room) ? sanitize_text_field($room) : null;
        $clean_floor = isset($floor) ? sanitize_text_field($floor) : null;

        // Replace ensures unique(professional_id, date) updated
        $wpdb->replace(
            $availability_table,
            [
                'professional_id' => $professional_id,
                'date' => $date,
                'room' => $clean_room,
                'floor' => $clean_floor,
            ],
            ['%d', '%s', '%s', '%s']
        );

        // Ensure available_dates CSV contains the date
        $current_csv = (string) $wpdb->get_var($wpdb->prepare("SELECT available_dates FROM $professionals_table WHERE id = %d", $professional_id));
        $dates = $this->csv_to_array($current_csv);
        if (!in_array($date, $dates, true)) {
            $dates[] = $date;
            $new_csv = $this->build_available_dates_csv($dates);
            $wpdb->update(
                $professionals_table,
                ['available_dates' => $new_csv],
                ['id' => $professional_id],
                ['%s'],
                ['%d']
            );
        }
    }

    /**
     * Get room/floor for a specific day
     *
     * @return array{room:?string,floor:?string}
     */
    public function get_day_details(int $professional_id, string $date): array {
        global $wpdb;
        $availability_table = $wpdb->prefix . self::TABLE_NAME;
        if (!$this->is_valid_date($date)) {
            return ['room' => null, 'floor' => null];
        }
        $this->ensure_table_exists();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT room, floor FROM $availability_table WHERE professional_id = %d AND date = %s", $professional_id, $date),
            ARRAY_A
        );
        return [
            'room' => isset($row['room']) ? (string) $row['room'] : null,
            'floor' => isset($row['floor']) ? (string) $row['floor'] : null,
        ];
    }

    /**
     * Convert CSV dates to array
     *
     * @param string $csv
     * @return array<int,string>
     */
    private function csv_to_array(string $csv): array {
        if ($csv === '') { return []; }
        $parts = array_map('trim', explode(',', $csv));
        return array_values(array_filter($parts, function(string $d): bool { return $this->is_valid_date($d); }));
    }

    /**
     * Replace all availability rows for a professional and rebuild available_dates
     *
     * @param int $professional_id
     * @param array<int, array{date:string, room:?string, floor:?string}> $entries
     */
    public function replace_professional_availability(int $professional_id, array $entries): void {
        global $wpdb;

        $availability_table = $wpdb->prefix . self::TABLE_NAME;
        $professionals_table = $wpdb->prefix . 'ycp_professionals';

        $this->ensure_table_exists();

        // Delete previous availability rows for this professional
        $wpdb->delete($availability_table, ['professional_id' => $professional_id], ['%d']);

        $valid_dates = [];

        foreach ($entries as $entry) {
            $date = isset($entry['date']) ? trim((string) $entry['date']) : '';
            if (!$this->is_valid_date($date)) {
                continue;
            }

            // If we receive IDs, translate to names for back-compat storage
            $room = null;
            $floor = null;
            if (isset($entry['room_id']) && is_numeric($entry['room_id'])) {
                $room_id = (int) $entry['room_id'];
                $room = $this->get_room_name_by_id($room_id);
            }
            if (isset($entry['floor_id']) && is_numeric($entry['floor_id'])) {
                $floor_id = (int) $entry['floor_id'];
                $floor = $this->get_floor_name_by_id($floor_id);
            }

            $wpdb->insert(
                $availability_table,
                [
                    'professional_id' => $professional_id,
                    'date' => $date,
                    'room' => $room,
                    'floor' => $floor,
                ],
                ['%d', '%s', '%s', '%s']
            );

            $valid_dates[] = $date;
        }

        // Build available_dates CSV and update professionals table (back-compat)
        $available_dates_csv = $this->build_available_dates_csv($valid_dates);
        $wpdb->update(
            $professionals_table,
            ['available_dates' => $available_dates_csv],
            ['id' => $professional_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Look up floor name by id
     */
    private function get_floor_name_by_id(int $floor_id): ?string {
        global $wpdb;
        $table = $wpdb->prefix . 'ycp_floors';
        $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $table WHERE id = %d", $floor_id));
        return $name !== null ? (string) $name : null;
    }

    /**
     * Look up room name by id
     */
    private function get_room_name_by_id(int $room_id): ?string {
        global $wpdb;
        $table = $wpdb->prefix . 'ycp_rooms';
        $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $table WHERE id = %d", $room_id));
        return $name !== null ? (string) $name : null;
    }

    /**
     * Build a comma-separated dates string sorted ascending and unique
     *
     * @param array<int, string> $dates
     */
    private function build_available_dates_csv(array $dates): string {
        $dates = array_filter(array_map('trim', $dates), function(string $d): bool {
            return $this->is_valid_date($d);
        });
        $dates = array_values(array_unique($dates));
        sort($dates);
        return implode(',', $dates);
    }

    /**
     * Validate Y-m-d date
     */
    private function is_valid_date(string $date): bool {
        if ($date === '') {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    /**
     * Sync availability rows to a set of dates: ensure rows exist for provided dates (null details),
     * and delete rows not in the set. Also updates available_dates CSV accordingly.
     *
     * @param int $professional_id
     * @param array<int,string> $dates
     */
    public function sync_dates(int $professional_id, array $dates): void {
        global $wpdb;

        $availability_table = $wpdb->prefix . self::TABLE_NAME;
        $professionals_table = $wpdb->prefix . 'ycp_professionals';

        $this->ensure_table_exists();

        // Normalize dates
        $normalized = [];
        foreach ($dates as $d) {
            $d = trim((string) $d);
            if ($this->is_valid_date($d)) {
                $normalized[$d] = true;
            }
        }
        $dates = array_keys($normalized);

        // Delete rows not in set
        $placeholders = implode(',', array_fill(0, count($dates), '%s'));
        if (!empty($dates)) {
            $sql = $wpdb->prepare(
                "DELETE FROM $availability_table WHERE professional_id = %d AND date NOT IN ($placeholders)",
                array_merge([$professional_id], $dates)
            );
            $wpdb->query($sql);
        } else {
            // If no dates selected, remove all rows
            $wpdb->delete($availability_table, ['professional_id' => $professional_id], ['%d']);
        }

        // Ensure rows exist for provided dates without overwriting existing room/floor
        foreach ($dates as $d) {
            $query = $wpdb->prepare(
                "INSERT INTO $availability_table (professional_id, date) VALUES (%d, %s)
                 ON DUPLICATE KEY UPDATE professional_id = professional_id",
                $professional_id,
                $d
            );
            $wpdb->query($query);
        }

        // Update available_dates CSV
        $csv = $this->build_available_dates_csv($dates);
        $wpdb->update(
            $professionals_table,
            ['available_dates' => $csv],
            ['id' => $professional_id],
            ['%s'],
            ['%d']
        );
    }
}




