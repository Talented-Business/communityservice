<?php
    $value = $task_object->get_duties( 'edit' );
?>
<textarea name="duties" cols="100" rows="3" placeholder="<?php printf( esc_attr__( 'Enter duties', 'communityservice' ) ); ?>">
  <?= $value ?>
</textarea>