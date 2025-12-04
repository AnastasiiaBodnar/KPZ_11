<?php
/*
Plugin Name: KPZ Dorm Students CRUD
Description: CRUD для студентів гуртожитку (Create, Read, Update, Delete)
Version: 3.1
Author: Anastasiia Bodnar
*/

global $wpdb;

/* -------------------------
   CREATE TABLES
--------------------------- */
function kpz_create_students_table() {
    global $wpdb;
    $table = $wpdb->prefix . "kpz_students";
    $charset = $wpdb->get_charset_collate();

    // Видаляємо стару таблицю якщо вона існує (тільки при активації)
    $wpdb->query("DROP TABLE IF EXISTS $table");

    $sql = "CREATE TABLE $table (
        id INT NOT NULL AUTO_INCREMENT,
        last_name VARCHAR(50) NOT NULL DEFAULT '',
        first_name VARCHAR(50) NOT NULL DEFAULT '',
        patronymic VARCHAR(50) DEFAULT '',
        room_id INT,
        course INT NOT NULL DEFAULT 1,
        PRIMARY KEY(id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'kpz_create_students_table');


/* -------------------------
   ADMIN MENU
--------------------------- */
function kpz_students_menu() {
    add_menu_page(
        "Студенти гуртожитку",
        "Студенти",
        "manage_options",
        "kpz_students",
        "kpz_students_page",
        "dashicons-id-alt",
        6
    );
}
add_action("admin_menu", "kpz_students_menu");


/* -------------------------
   ADMIN PAGE (CRUD)
--------------------------- */
function kpz_students_page() {
    global $wpdb;
    $students_table = $wpdb->prefix . "kpz_students";
    $rooms_table = $wpdb->prefix . "kpz_rooms";
    
    if (!current_user_can('manage_options')) {
        wp_die(__('У вас немає прав для доступу до цієї сторінки.'));
    }

    // DELETE
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_student_' . intval($_GET['id']))) {
            wp_die('Помилка безпеки');
        }
        
        $deleted = $wpdb->delete($students_table, ['id' => intval($_GET['id'])], ['%d']);
        
        if ($deleted !== false) {
            echo "<div class='notice notice-success is-dismissible'><p>✓ Студента успішно видалено.</p></div>";
        } else {
            echo "<div class='notice notice-error is-dismissible'><p>✗ Помилка при видаленні студента.</p></div>";
        }
    }

    // CREATE / UPDATE
    if (isset($_POST['kpz_submit'])) {
        if (!isset($_POST['kpz_student_nonce']) || !wp_verify_nonce($_POST['kpz_student_nonce'], 'kpz_student_action')) {
            wp_die('Помилка безпеки');
        }

        $errors = array();
        
        $last_name = sanitize_text_field($_POST['last_name']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $patronymic = sanitize_text_field($_POST['patronymic']);
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $course = intval($_POST['course']);
        
        // Валідація
        if (empty($last_name) || mb_strlen($last_name) < 2) {
            $errors[] = "Прізвище повинно містити мінімум 2 символи";
        }
        
        if (empty($first_name) || mb_strlen($first_name) < 2) {
            $errors[] = "Ім'я повинно містити мінімум 2 символи";
        }
        
        if ($course < 1 || $course > 4) {
            $errors[] = "Курс має бути від 1 до 4";
        }

        if (empty($errors)) {
            $data = [
                'last_name' => $last_name,
                'first_name' => $first_name,
                'patronymic' => $patronymic,
                'room_id' => $room_id,
                'course' => $course
            ];

            $format = ['%s', '%s', '%s', '%d', '%d'];
            
            // Якщо room_id null, змінюємо формат
            if ($room_id === null) {
                $data['room_id'] = null;
                $format = ['%s', '%s', '%s', null, '%d'];
            }

            $student_id = intval($_POST['id']);

            if ($student_id == 0) {
                // CREATE
                $inserted = $wpdb->insert($students_table, $data, $format);
                
                if ($inserted !== false) {
                    echo "<div class='notice notice-success is-dismissible'><p>✓ Студента успішно додано! (ID: " . $wpdb->insert_id . ")</p></div>";
                } else {
                    echo "<div class='notice notice-error is-dismissible'><p>✗ Помилка при додаванні: " . $wpdb->last_error . "</p></div>";
                }
            } else {
                // UPDATE
                $updated = $wpdb->update($students_table, $data, ['id' => $student_id], $format, ['%d']);
                
                if ($updated !== false) {
                    echo "<div class='notice notice-success is-dismissible'><p>✓ Дані студента успішно оновлено!</p></div>";
                } else {
                    echo "<div class='notice notice-error is-dismissible'><p>✗ Помилка при оновленні: " . $wpdb->last_error . "</p></div>";
                }
            }
        } else {
            echo "<div class='notice notice-error is-dismissible'><p>✗ Помилки валідації:<br>" . implode('<br>', $errors) . "</p></div>";
        }
    }

    // EDIT MODE
    $edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $students_table WHERE id = %d", intval($_GET['id'])));
        
        if (!$edit) {
            echo "<div class='notice notice-error is-dismissible'><p>Студента не знайдено!</p></div>";
        }
    }

    // Отримуємо список кімнат
    $rooms = $wpdb->get_results("SELECT * FROM $rooms_table ORDER BY room_number ASC");

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-id-alt" style="font-size: 28px; width: 28px; height: 28px;"></span>
            Управління студентами гуртожитку
        </h1>
        
        <?php if ($edit): ?>
            <a href="?page=kpz_students" class="page-title-action">← Повернутися до списку</a>
        <?php endif; ?>
        
        <hr class="wp-header-end">

        <!-- ФОРМА ДОДАВАННЯ/РЕДАГУВАННЯ -->
        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2><?php echo $edit ? "✏️ Редагувати студента" : "➕ Додати нового студента"; ?></h2>

            <form method="post" action="?page=kpz_students">
                <?php wp_nonce_field('kpz_student_action', 'kpz_student_nonce'); ?>
                
                <input type="hidden" name="id" value="<?php echo $edit ? esc_attr($edit->id) : 0; ?>">

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="last_name">Прізвище <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="last_name" 
                                    id="last_name" 
                                    class="regular-text" 
                                    required 
                                    value="<?php echo $edit ? esc_attr($edit->last_name) : ''; ?>"
                                    placeholder="Наприклад: Шевченко"
                                >
                                <p class="description">Прізвище студента</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="first_name">Ім'я <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="first_name" 
                                    id="first_name" 
                                    class="regular-text" 
                                    required 
                                    value="<?php echo $edit ? esc_attr($edit->first_name) : ''; ?>"
                                    placeholder="Наприклад: Тарас"
                                >
                                <p class="description">Ім'я студента</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="patronymic">По батькові</label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="patronymic" 
                                    id="patronymic" 
                                    class="regular-text" 
                                    value="<?php echo $edit ? esc_attr($edit->patronymic) : ''; ?>"
                                    placeholder="Наприклад: Григорович"
                                >
                                <p class="description">По батькові (необов'язково)</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="room_id">Кімната</label>
                            </th>
                            <td>
                                <select name="room_id" id="room_id" class="regular-text">
                                    <option value="">-- Без кімнати --</option>
                                    <?php if ($rooms): ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room->id; ?>" 
                                                <?php selected($edit ? $edit->room_id : 0, $room->id); ?>>
                                                Кімната <?php echo esc_html($room->room_number); ?> 
                                                (<?php echo $room->capacity; ?> 
                                                <?php echo $room->capacity == 1 ? 'місце' : ($room->capacity < 5 ? 'місця' : 'місць'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Немає доступних кімнат</option>
                                    <?php endif; ?>
                                </select>
                                <p class="description">
                                    Оберіть кімнату, де проживає студент
                                    <?php if (!$rooms): ?>
                                        <br><strong style="color: #dc3232;">⚠️ Спочатку додайте кімнати в розділі "Кімнати"</strong>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="course">Курс <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <select name="course" id="course" required>
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php selected($edit ? $edit->course : 1, $i); ?>>
                                            <?php echo $i; ?> курс
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <p class="description">Курс навчання студента (1-6)</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" name="kpz_submit" class="button button-primary">
                        <?php echo $edit ? '💾 Зберегти зміни' : '➕ Додати студента'; ?>
                    </button>
                    
                    <?php if ($edit): ?>
                        <a href="?page=kpz_students" class="button">Скасувати</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <!-- СПИСОК СТУДЕНТІВ (READ) -->
        <hr style="margin: 30px 0;">
        
        <h2>📋 Список студентів</h2>

        <?php
        // Пагінація
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $students_table");
        
        // JOIN з таблицею кімнат
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, r.room_number 
             FROM $students_table s 
             LEFT JOIN $rooms_table r ON s.room_id = r.id 
             ORDER BY s.last_name ASC, s.first_name ASC 
             LIMIT %d OFFSET %d", 
            $per_page, 
            $offset
        ));

        if ($students):
        ?>
        
        <p>Всього студентів: <strong><?php echo $total; ?></strong></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column" style="width: 50px;">ID</th>
                    <th scope="col" class="manage-column">ПІБ</th>
                    <th scope="col" class="manage-column" style="width: 100px;">Кімната</th>
                    <th scope="col" class="manage-column" style="width: 80px;">Курс</th>
                    <th scope="col" class="manage-column" style="width: 180px;">Дії</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><strong><?php echo esc_html($s->id); ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($s->last_name); ?></strong> 
                        <?php echo esc_html($s->first_name); ?>
                        <?php if ($s->patronymic): ?>
                            <?php echo esc_html($s->patronymic); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s->room_number): ?>
                            <span class="dashicons dashicons-admin-home" style="color: #2271b1;"></span>
                            <?php echo esc_html($s->room_number); ?>
                        <?php else: ?>
                            <span style="color: #999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($s->course); ?> курс</td>
                    <td>
                        <a href="?page=kpz_students&action=edit&id=<?php echo $s->id; ?>" 
                           class="button button-small">
                            ✏️ Редагувати
                        </a>
                        
                        <a href="?page=kpz_students&action=delete&id=<?php echo $s->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_student_' . $s->id); ?>" 
                           class="button button-small button-link-delete"
                           onclick="return confirm('Ви впевнені, що хочете видалити студента <?php echo esc_js($s->last_name . ' ' . $s->first_name); ?>?');"
                           style="color: #b32d2e;">
                            🗑️ Видалити
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        // Пагінація
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
                <p>📭 Студентів ще немає. Додайте першого студента за допомогою форми вище!</p>
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


/* -------------------------
   SHORTCODE FOR FRONTEND
--------------------------- */
function kpz_students_shortcode() {
    global $wpdb;
    $students_table = $wpdb->prefix . "kpz_students";
    $rooms_table = $wpdb->prefix . "kpz_rooms";

    $students = $wpdb->get_results(
        "SELECT s.*, r.room_number 
         FROM $students_table s 
         LEFT JOIN $rooms_table r ON s.room_id = r.id 
         ORDER BY s.course, s.last_name"
    );

    if (empty($students)) {
        return '<p>Список студентів порожній.</p>';
    }

    $html = '<div class="kpz-students-list">';
    $html .= '<h3>📋 Список студентів гуртожитку</h3>';
    $html .= '<table class="kpz-table">';
    $html .= '<thead><tr><th>ПІБ</th><th>Кімната</th><th>Курс</th></tr></thead>';
    $html .= '<tbody>';

    foreach ($students as $s) {
        $full_name = trim($s->last_name . ' ' . $s->first_name . ' ' . $s->patronymic);
        $room_display = $s->room_number ? $s->room_number : '—';
        
        $html .= sprintf(
            '<tr><td>%s</td><td>%s</td><td>%d курс</td></tr>',
            esc_html($full_name),
            esc_html($room_display),
            esc_html($s->course)
        );
    }

    $html .= '</tbody></table></div>';
    
    $html .= '<style>
        .kpz-students-list { margin: 20px 0; }
        .kpz-table { width: 100%; border-collapse: collapse; }
        .kpz-table th, .kpz-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .kpz-table th { background: #f5f5f5; font-weight: bold; }
        .kpz-table tr:hover { background: #f9f9f9; }
    </style>';

    return $html;
}
add_shortcode("kpz_students_list", "kpz_students_shortcode");