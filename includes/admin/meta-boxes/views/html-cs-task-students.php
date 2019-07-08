<?php
$args = array(
    'type'=>'cs-activity',
    'parent'=>$task_object->get_id()
);
$activities = cs_get_activities($args);

foreach($activities as $activity){
    $activity_link = admin_url( 'post.php?post=' . $activity->get_id() . '&action=edit' );
    ?>
    <div class="">
        <a href="<?=$activity_link;?>">Activity #<?=$activity->get_id()?></a>
        <span></span>
        <span><?=$activity->get_status()?></span>
    </div>
    <?php
}