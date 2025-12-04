<?php
/*
Plugin Name: KPZ Dorm Students CRUD
Description: CRUD for dormitory students (Create, Read, Update, Delete)
Version: 1.0
Author: Anastasiia Bodnar
*/

global $wpdb;
$table_name = $wpdb->prefix . "kpz_students";

/* -------------------------
   CREATE TABLE ON ACTIVATE
--------------------------- */
function kpz_create_students_table() {
    global $wpdb;

    $table = $wpdb->prefix . "kpz_students";
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        room VARCHAR(20) NOT NULL,
        course INT NOT NULL,
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
    $table = $wpdb->prefix . "kpz_students";

    // Delete
    if (isset($_GET['delete'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
        echo "<div class='updated'><p>Студента видалено.</p></div>";
    }

    // Save / Update
    if (isset($_POST['kpz_submit'])) {

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'room' => sanitize_text_field($_POST['room']),
            'course' => intval($_POST['course'])
        ];

        if ($_POST['id'] == "0") {
            $wpdb->insert($table, $data);
            echo "<div class='updated'><p>Додано нового студента!</p></div>";
        } else {
            $wpdb->update($table, $data, ['id' => intval($_POST['id'])]);
            echo "<div class='updated'><p>Дані студента оновлено!</p></div>";
        }
    }

    // Edit mode
    $edit = null;
    if (isset($_GET['edit'])) {
        $edit = $wpdb->get_row("SELECT * FROM $table WHERE id=" . intval($_GET['edit']));
    }

    ?>
    <div class="wrap">
        <h1>Студенти гуртожитку</h1>

        <h2><?php echo $edit ? "Редагувати студента" : "Додати студента"; ?></h2>

        <form method="post">
            <input type="hidden" name="id" value="<?php echo $edit ? $edit->id : 0; ?>">

            <table class="form-table">
                <tr>
                    <th>Ім'я студента</th>
                    <td><input type="text" name="name" required value="<?php echo $edit ? $edit->name : ""; ?>"></td>
                </tr>

                <tr>
                    <th>Номер кімнати</th>
                    <td><input type="text" name="room" required value="<?php echo $edit ? $edit->room : ""; ?>"></td>
                </tr>

                <tr>
                    <th>Курс</th>
                    <td><input type="number" name="course" min="1" max="6" required value="<?php echo $edit ? $edit->course : ""; ?>"></td>
                </tr>
            </table>

            <button type="submit" name="kpz_submit" class="button button-primary">Зберегти</button>
        </form>

        <hr>

        <h2>Список студентів</h2>

        <?php
        $students = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

        if ($students):
        ?>
        <table class="widefat">
            <thead>
            <tr>
                <th>ID</th>
                <th>Ім'я</th>
                <th>Кімната</th>
                <th>Курс</th>
                <th>Дії</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?php echo $s->id; ?></td>
                    <td><?php echo $s->name; ?></td>
                    <td><?php echo $s->room; ?></td>
                    <td><?php echo $s->course; ?></td>

                    <td>
                        <a href="?page=kpz_students&edit=<?php echo $s->id; ?>" class="button">Редагувати</a>
                        <a href="?page=kpz_students&delete=<?php echo $s->id; ?>"
                           class="button button-danger"
                           onclick="return confirm('Видалити студента?');">Видалити</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
            <p>Студентів немає.</p>
        <?php endif; ?>

    </div>
    <?php
}


/* -------------------------
   SHORTCODE FOR FRONTEND
--------------------------- */
function kpz_students_shortcode() {
    global $wpdb;
    $table = $wpdb->prefix . "kpz_students";

    $students = $wpdb->get_results("SELECT * FROM $table");

    $html = "<h3>Список студентів</h3><ul>";

    foreach ($students as $s) {
        $html .= "<li>$s->name — кімната $s->room, $s->course курс</li>";
    }

    $html .= "</ul>";

    return $html;
}

add_shortcode("kpz_students_list", "kpz_students_shortcode");
