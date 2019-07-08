<div class="book-content">
    <?php 
        if(count($activities)>0){
            if(is_callable(array('WPBMap','addAllMappedShortcodes')))WPBMap::addAllMappedShortcodes();
            $activity = $activities[0];
            $post = get_post($activity->get_id());
            $task_id =  $activity->get_parent_id();
            if($task_id>0)$image_src = get_the_post_thumbnail_url($task_id);
            if(!isset($image_src) || !$image_src) $image_src = home_url('/')."wp-content/uploads/2019/02/christian-brothers-logo-150x150.png";
            ?>
                <img src="<?=$image_src?>"/>
                <h3 class="activity-title"><?=$activity->get_name()?></h3>
                <div class="activity-content">
                    <?php 
                        $content = $activity->get_description();
                        $content = apply_filters('the_content', $content);
                        $content = str_replace(']]>', ']]&gt;', $content);
                        echo $content;       
                    ?>
                </div>
                <p class="activity-date">Completed <?= date("jS F Y",strtotime($activity->get_activity_date()))?></p>
            <?php
        }else{

        }
    ?>
</div>
<span class="page-number"></span>