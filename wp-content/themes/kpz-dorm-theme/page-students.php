<?php
/*
Template Name: Students Page
*/
get_header();

$students = do_shortcode('[kpz_students_list]');
?>

<div class="page-container">
    <h1>Список студентів</h1>

    <?php 
    echo $students; 
    ?>
</div>

<?php get_footer(); ?>
