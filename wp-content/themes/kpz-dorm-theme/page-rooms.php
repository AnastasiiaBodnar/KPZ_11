<?php
/*
 Template Name: Rooms Page
*/
get_header();
?>

<h1 class="kpz-fade-in"> Кімнати гуртожитку</h1>

<div class="kpz-fade-in">
    <?php 
    echo do_shortcode('[kpz_rooms_list]'); 
    ?>
</div>

<?php get_footer(); ?>