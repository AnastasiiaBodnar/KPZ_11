<?php
/*
Template Name: Students Page
*/
get_header();

// Отримання студентів через плагін
$students = do_shortcode('[kpz_students_list]');
?>

<div class="page-container">
    <h1>Список студентів</h1>

    <?php 
    // Виводимо HTML який повертає шорткод
    echo $students; 
    ?>
</div>

<?php get_footer(); ?>
