<?php
if(function_exists('get_cs_student_houses')){
  $years = get_cs_student_years_ids();  
  if(isset($terms))foreach($terms as $term){
    $years[$term->term_id] = $term->name;
  }
  ?>    <label style="font-weight:bolder">Years:</label><?php
  $values = $task_object->get_years( 'edit' );  
  //from 4 grade 
  $start_grape = date('Y')-CS_STUDENT_YEAR;
  foreach ( $years as $key => $option ) :
    if(in_array($key,$values))$selected_value = true;
    else $selected_value = false;
    if($option<$start_grape)continue;
    ?>
    <label for="<?php echo $key; ?>year" class=" tips">
      <?php echo (date('Y')-$option+4) ?>:
      <input type="checkbox" name="years[<?php echo $key; ?>]" id="<?php echo $key; ?>year" <?php echo checked( $selected_value, true, false ); ?> />
    </label>
  <?php endforeach; 
}