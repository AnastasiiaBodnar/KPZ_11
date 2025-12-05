<?php
/*
Plugin Name: KPZ Dorm Students CRUD
Description: CRUD для студентів гуртожитку + автоматичне оновлення зайнятих місць у кімнатах
Version: 4.0
Author: Anastasiia Bodnar
*/

global $wpdb;

/* -------------------------
   CREATE TABLE
--------------------------- */
function kpz_create_students_table() {
    global $wpdb;
    $table = $wpdb->prefix . "kpz_students";
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id INT NOT NULL AUTO_INCREMENT,
        last_name VARCHAR(50) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        patronymic VARCHAR(50) DEFAULT '',
        room_id INT DEFAULT NULL,
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
        wp_die("Недостатньо прав");
    }

    /* -------------------------
       DELETE STUDENT
    --------------------------- */
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {

        $id = intval($_GET['id']);

        // дізнаємося, у якій кімнаті він був
        $old_room = $wpdb->get_var($wpdb->prepare(
            "SELECT room_id FROM $students_table WHERE id=%d",
            $id
        ));

        // якщо була кімната — звільняємо місце
        if ($old_room) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $rooms_table SET occupied = occupied - 1 
                 WHERE id = %d AND occupied > 0",
                $old_room
            ));
        }

        // видаляємо студента
        $wpdb->delete($students_table, ['id' => $id], ['%d']);

        echo "<div class='notice notice-success'><p>Студента видалено</p></div>";
    }


    /* -------------------------
       CREATE / UPDATE STUDENT
    --------------------------- */
    if (isset($_POST['kpz_submit'])) {

        $id = intval($_POST['id']);
        $last = sanitize_text_field($_POST['last_name']);
        $first = sanitize_text_field($_POST['first_name']);
        $patr = sanitize_text_field($_POST['patronymic']);
        $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
        $course = intval($_POST['course']);

        $data = [
            'last_name' => $last,
            'first_name' => $first,
            'patronymic' => $patr,
            'room_id' => $room_id,
            'course' => $course
        ];

        $format = ['%s','%s','%s','%d','%d'];

        /* CREATE */
        if ($id == 0) {

            // перевіряємо чи є місце
            if ($room_id) {
                $free = $wpdb->get_row($wpdb->prepare(
                    "SELECT capacity, occupied FROM $rooms_table WHERE id=%d",
                    $room_id
                ));
                if ($free->occupied >= $free->capacity) {
                    echo "<div class='notice notice-error'><p>Немає вільних місць у цій кімнаті!</p></div>";
                } else {
                    $wpdb->insert($students_table, $data, $format);

                    // зайняти місце
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $rooms_table SET occupied = occupied + 1 WHERE id=%d",
                        $room_id
                    ));

                    echo "<div class='notice notice-success'><p>Студента додано!</p></div>";
                }
            } else {
                $wpdb->insert($students_table, $data, $format);
                echo "<div class='notice notice-success'><p>Студента додано без кімнати!</p></div>";
            }

        } 
        
        /* UPDATE */
        else {

            // стара кімната
            $old_room = $wpdb->get_var($wpdb->prepare(
                "SELECT room_id FROM $students_table WHERE id=%d",
                $id
            ));

            // оновлюємо
            $wpdb->update($students_table, $data, ['id'=>$id], $format, ['%d']);

            // якщо змінилася кімната
            if ($old_room != $room_id) {

                // звільняємо стару
                if ($old_room) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $rooms_table SET occupied = occupied - 1 
                         WHERE id=%d AND occupied > 0",
                        $old_room
                    ));
                }

                // займаємо нову
                if ($room_id) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $rooms_table SET occupied = occupied + 1 
                         WHERE id=%d",
                        $room_id
                    ));
                }
            }

            echo "<div class='notice notice-success'><p>Студента оновлено!</p></div>";
        }
    }


    /* -------------------------
       EDIT MODE
    --------------------------- */
    $edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit') {
        $edit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $students_table WHERE id=%d",
            intval($_GET['id'])
        ));
    }

    /* -------------------------
       GET ROOMS LIST
    --------------------------- */
    $rooms = $wpdb->get_results("SELECT * FROM $rooms_table ORDER BY room_number ASC");


    ?>
    <div class="wrap">
        <h1>Студенти гуртожитку</h1>
        <form method="post" action="?page=kpz_students">
            <input type="hidden" name="id" value="<?php echo $edit ? $edit->id : 0; ?>">

            <table class="form-table">
                <tr>
                    <th>Прізвище</th>
                    <td><input type="text" name="last_name" value="<?php echo $edit->last_name ?? '' ?>" required></td>
                </tr>

                <tr>
                    <th>Ім'я</th>
                    <td><input type="text" name="first_name" value="<?php echo $edit->first_name ?? '' ?>" required></td>
                </tr>

                <tr>
                    <th>По батькові</th>
                    <td><input type="text" name="patronymic" value="<?php echo $edit->patronymic ?? '' ?>"></td>
                </tr>

                <tr>
                    <th>Кімната</th>
                    <td>
                        <select name="room_id">
                            <option value="">— Без кімнати —</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room->id ?>"
                                    <?php selected($edit ? $edit->room_id : '', $room->id); ?>>
                                    Кімната <?php echo $room->room_number ?>
                                    (<?php echo $room->occupied ?>/<?php echo $room->capacity ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>Курс</th>
                    <td>
                        <select name="course">
                            <?php for($i=1;$i<=4;$i++): ?>
                                <option value="<?php echo $i ?>" <?php selected($edit->course ?? 1, $i); ?>>
                                    <?php echo $i ?> курс
                                </option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <button class="button button-primary" name="kpz_submit">Зберегти</button>
        </form>


        <hr>

        <h2>Список студентів</h2>
        <?php
        $students = $wpdb->get_results(
            "SELECT s.*, r.room_number 
             FROM $students_table s
             LEFT JOIN $rooms_table r ON s.room_id = r.id
             ORDER BY s.last_name ASC"
        );
        ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ПІБ</th>
                    <th>Кімната</th>
                    <th>Курс</th>
                    <th>Дії</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($students as $s): ?>
                <tr>
                    <td><?php echo $s->id ?></td>
                    <td><?php echo $s->last_name . " " . $s->first_name ?></td>
                    <td><?php echo $s->room_number ?: "—" ?></td>
                    <td><?php echo $s->course ?></td>
                    <td>
                        <a href="?page=kpz_students&action=edit&id=<?php echo $s->id ?>" class="button">✏️</a>
                        <a 
                            href="?page=kpz_students&action=delete&id=<?php echo $s->id ?>" 
                            class="button button-danger"
                            onclick="return confirm('Видалити?')"
                        >🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}
?>
