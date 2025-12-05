<?php
/*
Plugin Name: KPZ Dorm Rooms CRUD
Description: CRUD для кімнат гуртожитку (Create, Read, Update, Delete)
Author: Anastasiia Bodnar
*/

global $wpdb;
$table_name = $wpdb->prefix . "kpz_rooms";

function kpz_create_rooms_table() {
    global $wpdb;
    $table = $wpdb->prefix . "kpz_rooms";
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id INT NOT NULL AUTO_INCREMENT,
        room_number VARCHAR(20) NOT NULL UNIQUE,
        capacity INT NOT NULL,
        occupied INT NOT NULL DEFAULT 0,
        PRIMARY KEY(id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE table_name = '$table' AND column_name = 'occupied'");
    
    if(empty($row)) {
        $wpdb->query("ALTER TABLE $table ADD occupied INT NOT NULL DEFAULT 0 AFTER capacity");
    }
}
register_activation_hook(__FILE__, 'kpz_create_rooms_table');


function kpz_rooms_menu() {
    add_menu_page(
        "Кімнати гуртожитку",
        "Кімнати",
        "manage_options",
        "kpz_rooms",
        "kpz_rooms_page",
        "dashicons-admin-home",
        7
    );
}
add_action("admin_menu", "kpz_rooms_menu");


function kpz_rooms_page() {
    global $wpdb;
    $table = $wpdb->prefix . "kpz_rooms";
    $students_table = $wpdb->prefix . "kpz_students";

    if (!current_user_can('manage_options')) {
        wp_die(__('У вас немає прав для доступу до цієї сторінки.'));
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'sync') {
        $wpdb->query("UPDATE $table SET occupied = 0");
        
        $wpdb->query("
            UPDATE $table r 
            SET occupied = (
                SELECT COUNT(*) 
                FROM $students_table s 
                WHERE s.room_id = r.id
            )
        ");
        
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_room_' . intval($_GET['id']))) {
            wp_die('Помилка безпеки');
        }
        
        $deleted = $wpdb->delete($table, ['id' => intval($_GET['id'])], ['%d']);
        
        if ($deleted !== false) {
            echo "<div class='notice notice-success is-dismissible'><p>✓ Кімнату успішно видалено.</p></div>";
        } else {
            echo "<div class='notice notice-error is-dismissible'><p>✗ Помилка при видаленні кімнати.</p></div>";
        }
    }

    if (isset($_POST['kpz_submit'])) {
        if (!isset($_POST['kpz_room_nonce']) || !wp_verify_nonce($_POST['kpz_room_nonce'], 'kpz_room_action')) {
            wp_die('Помилка безпеки');
        }

        $errors = array();
        
        $room_number = sanitize_text_field($_POST['room_number']);
        if (empty($room_number)) {
            $errors[] = "Номер кімнати обов'язковий";
        }
        
        $capacity = intval($_POST['capacity']);
        if ($capacity < 1 || $capacity > 10) {
            $errors[] = "Кількість місць має бути від 1 до 10";
        }

        if (empty($errors)) {
            $data = [
                'room_number' => $room_number,
                'capacity' => $capacity
            ];

            $room_id = intval($_POST['id']);

            if ($room_id == 0) {
                $inserted = $wpdb->insert($table, $data, ['%s', '%d']);
                
                if ($inserted !== false) {
                    echo "<div class='notice notice-success is-dismissible'><p>✓ Кімнату успішно додано! (ID: " . $wpdb->insert_id . ")</p></div>";
                } else {
                    if (strpos($wpdb->last_error, 'Duplicate') !== false) {
                        echo "<div class='notice notice-error is-dismissible'><p>✗ Кімната з таким номером вже існує!</p></div>";
                    } else {
                        echo "<div class='notice notice-error is-dismissible'><p>✗ Помилка при додаванні: " . $wpdb->last_error . "</p></div>";
                    }
                }
            } else {
                $updated = $wpdb->update(
                    $table, 
                    $data, 
                    ['id' => $room_id],
                    ['%s', '%d'],
                    ['%d']
                );
                
                if ($updated !== false) {
                    echo "<div class='notice notice-success is-dismissible'><p>✓ Дані кімнати успішно оновлено!</p></div>";
                } else {
                    echo "<div class='notice notice-error is-dismissible'><p>✗ Помилка при оновленні: " . $wpdb->last_error . "</p></div>";
                }
            }
        } else {
            echo "<div class='notice notice-error is-dismissible'><p>✗ Помилки валідації:<br>" . implode('<br>', $errors) . "</p></div>";
        }
    }

    $edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['id'])));
        
        if (!$edit) {
            echo "<div class='notice notice-error is-dismissible'><p>Кімнату не знайдено!</p></div>";
        }
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-admin-home" style="font-size: 28px; width: 28px; height: 28px;"></span>
            Управління кімнатами гуртожитку
        </h1>
    
        
        <?php if ($edit): ?>
            <a href="?page=kpz_rooms" class="page-title-action">← Повернутися до списку</a>
        <?php endif; ?>
        
        <hr class="wp-header-end">

        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2><?php echo $edit ? " Редагувати кімнату" : " Додати нову кімнату"; ?></h2>

            <form method="post" action="?page=kpz_rooms">
                <?php wp_nonce_field('kpz_room_action', 'kpz_room_nonce'); ?>
                
                <input type="hidden" name="id" value="<?php echo $edit ? esc_attr($edit->id) : 0; ?>">

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="room_number">Номер кімнати <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="room_number" 
                                    id="room_number" 
                                    class="regular-text" 
                                    required 
                                    value="<?php echo $edit ? esc_attr($edit->room_number) : ''; ?>"
                                    placeholder="Наприклад: 101, 203-А, 305"
                                >
                                <p class="description">Унікальний номер кімнати</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="capacity">Кількість місць <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input 
                                    type="number" 
                                    name="capacity" 
                                    id="capacity" 
                                    min="1" 
                                    max="10"
                                    required 
                                    value="<?php echo $edit ? esc_attr($edit->capacity) : 1; ?>"
                                    style="width: 100px;"
                                >
                                <p class="description">Кількість місць у кімнаті (1-10)</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" name="kpz_submit" class="button button-primary">
                        <?php echo $edit ? ' Зберегти зміни' : ' Додати кімнату'; ?>
                    </button>
                    
                    <?php if ($edit): ?>
                        <a href="?page=kpz_rooms" class="button">Скасувати</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <hr style="margin: 30px 0;">
        
        <h2> Список кімнат</h2>

        <?php
        $per_page = 15;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $rooms = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY room_number ASC LIMIT %d OFFSET %d", 
            $per_page, 
            $offset
        ));

        if ($rooms):
            $total_capacity = $wpdb->get_var("SELECT SUM(capacity) FROM $table");
            $total_occupied = $wpdb->get_var("SELECT SUM(occupied) FROM $table");
        ?>
        
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column" style="width: 60px;">ID</th>
                    <th scope="col" class="manage-column">Номер кімнати</th>
                    <th scope="col" class="manage-column" style="width: 150px;">Кількість місць</th>
                    <th scope="col" class="manage-column" style="width: 150px;">Зайнято</th>
                    <th scope="col" class="manage-column" style="width: 180px;">Дії</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($rooms as $r): ?>
                <tr>
                    <td><strong><?php echo esc_html($r->id); ?></strong></td>
                    <td>
                        <strong style="font-size: 15px;">
                             Кімната <?php echo esc_html($r->room_number); ?>
                        </strong>
                    </td>
                    <td>
                        <?php echo esc_html($r->capacity); ?> 
                        <?php echo $r->capacity == 1 ? 'місце' : ($r->capacity < 5 ? 'місця' : 'місць'); ?>
                    </td>
                    <td>
                        <?php 
                        $occupied = isset($r->occupied) ? $r->occupied : 0;
                        $percent = $r->capacity > 0 ? round(($occupied / $r->capacity) * 100) : 0;
                        $color = $percent >= 100 ? '#d63638' : ($percent >= 70 ? '#dba617' : '#00a32a');
                        ?>
                        <strong style="color: <?php echo $color; ?>">
                            <?php echo $occupied; ?> / <?php echo $r->capacity; ?>
                        </strong>
                        (<?php echo $percent; ?>%)
                    </td>
                    <td>
                        <a href="?page=kpz_rooms&action=edit&id=<?php echo $r->id; ?>" 
                           class="button button-small">
                             Редагувати
                        </a>
                        
                        <a href="?page=kpz_rooms&action=delete&id=<?php echo $r->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_room_' . $r->id); ?>" 
                           class="button button-small button-link-delete"
                           onclick="return confirm('Ви впевнені, що хочете видалити кімнату <?php echo esc_js($r->room_number); ?>?');"
                           style="color: #b32d2e;">
                             Видалити
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        if ($total > $per_page):
            $total_pages = ceil($total / $per_page);
        ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '« Попередня',
                    'next_text' => 'Наступна »',
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <div class="notice notice-info">
                <p> Кімнат ще немає. Додайте першу кімнату за допомогою форми вище!</p>
            </div>
        <?php endif; ?>

    </div>

    <style>
        .card { padding: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .button-link-delete:hover { color: #a00 !important; }
        .dashicons { vertical-align: middle; }
    </style>
    <?php
}


function kpz_rooms_shortcode() {
    global $wpdb;
    $table = $wpdb->prefix . "kpz_rooms";

    $rooms = $wpdb->get_results("SELECT * FROM $table ORDER BY room_number ASC");

    if (empty($rooms)) {
        return '<p>Список кімнат порожній.</p>';
    }

    $html = '<div class="kpz-rooms-list kpz-fade-in">';
    $html .= '<h3> Кімнати гуртожитку</h3>';
    $html .= '<ul class="kpz-rooms-ul">';

    foreach ($rooms as $r) {
        $places_text = $r->capacity == 1 ? 'місце' : ($r->capacity < 5 ? 'місця' : 'місць');
        $occupied = isset($r->occupied) ? $r->occupied : 0;
        
        $html .= sprintf(
            '<li class="kpz-room-item"> Кімната <strong>%s</strong> — %d %s (зайнято: %d)</li>',
            esc_html($r->room_number),
            esc_html($r->capacity),
            $places_text,
            esc_html($occupied)
        );
    }

    $html .= '</ul></div>';
    
    $html .= '<style>
        .kpz-rooms-list { margin: 20px 0; }
        .kpz-rooms-ul { list-style: none; padding: 0; }
        .kpz-room-item { 
            padding: 15px; 
            margin: 10px 0; 
            background: #f9f9f9; 
            border-left: 4px solid #0073aa;
            transition: all 0.3s ease;
        }
        .kpz-room-item:hover { 
            background: #f0f0f1; 
            transform: translateX(5px);
        }
        
        /* Анімація появи */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .kpz-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .kpz-room-item {
            animation: fadeIn 0.8s ease-out backwards;
        }
        
        .kpz-room-item:nth-child(1) { animation-delay: 0.1s; }
        .kpz-room-item:nth-child(2) { animation-delay: 0.2s; }
        .kpz-room-item:nth-child(3) { animation-delay: 0.3s; }
        .kpz-room-item:nth-child(4) { animation-delay: 0.4s; }
        .kpz-room-item:nth-child(5) { animation-delay: 0.5s; }
    </style>';

    return $html;
}
add_shortcode("kpz_rooms_list", "kpz_rooms_shortcode");
?>