<?php 
    // Template for my form shortcode 
    $current_user_id = get_current_user_id();
    $current_student_year = get_user_meta($current_user_id,'years');
    $year = wp_get_terms_for_user( $current_user_id, 'user-group' );
    if(is_array($year))$year = $year[0]->term_id;
    $activityParams = array(
        'posts_per_page' => 20,
        'post_type' => 'cs-activity'
    );

    
    $cs_activity_query = new WP_Query($activityParams); // (2)
?>

<div class="form-content activities-form">
    <div class="card-title">
        <h5 class="activity">Activities</h5>
        <h5 class="status">Status</h5>
    </div>

	<!-- The Modal -->
	<div id="date-modal" class="modal" style="display: none;position: fixed;z-index: 9999; padding-top: 100px; left: 0; top: 0;width: 100%;height: 100%;overflow: auto; background-color: rgb(0,0,0); background-color: rgba(0,0,0,0.4);">
	  <div class="modal-content" style="background-color: #fefefe;  margin: auto;  padding: 20px;  border: 1px solid #888;  width: 60%; align-items: center;">
        <form id="submit-activity">
            <p style="text-align: center;">Please input the completed date..</p>
            <input type="date" id="modal-date" name="date" value="<?=date('Y-m-d')?>" style="margin: 5px; width: 180px;"  max='<?=date('Y-m-d')?>'>
            <input type="hidden" id="task_id" name="task_id" value="<?=date('Y-m-d')?>">
            <input type="hidden" id="submit-activity" name="submit-activity" value="submit-activity-ajax"?>
            <?php wp_nonce_field( 'communityservice-submit-activity', 'communityservice-submit-activity-nonce' ); ?>
            <div>
                <button class="ib-submit" onclick="closeModal()" type='button'>Close</button>
                <button class="ib-close" onclick="submitBtnClicked()" style="margin-left: 20px;" type='button'>Submit</button>
            </div>
        </form>
	  </div>
	</div>
	<?php if (count($tasks->ids)>0) : ?>
    <?php 
        foreach ( $tasks->ids as $task_id ) {
            $GLOBALS['post'] = get_post( $task_id ); // WPCS: override ok.
            setup_postdata( $GLOBALS['post'] );
            $activity_status_text = cs_activity_exists_by_task(get_the_ID(),$current_user_id);
    ?>
	    <div class="form-item">
	        <p><a href="<?=get_permalink(get_the_ID());?>"><?php the_title(); ?></a></p>
	        <div class="form-button">
                <button class="ibtn
                    <?php if($activity_status_text == "Submit for Approval")
                        echo    'no-activity';
                    ?>
                    " id="task_<?=get_the_ID()?>" onclick="submitInternalActivity('<?=$activity_status_text; ?>', '<?php the_title(); ?>', '<?php the_ID(); ?>')" 
                ><?=$activity_status_text; ?></button>
	        </div>
	    </div>
    <?php } ?>
    <?php communityservice_pagination();?>
    <?php wp_reset_postdata(); ?>
	<?php else:  ?>
	    <p>
	         <?php _e( 'No Activities' ); ?>
	    </p>
	<?php endif; ?>
</div>