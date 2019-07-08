<div class="form-content" style="background-color: #fff; display: flex;">
    <div class="form-items">
        <form class="submit-activity-form" method="post" id="adduser" enctype='multipart/form-data'action="<?=home_url('thanks-for-submit')?>">
            <div class="row">
                <div class="col-xs-12">
                    <input type="hidden" name="submit-activity" value="submit-activity">
                    <input class="form-control activityname" type="text" name="activityname" placeholder="Activity Name" required>
                    <input class="form-control date" type="date" name="date" placeholder="Date" required value=<?=date('Y-m-d');?>  max='<?=date('Y-m-d')?>'>

                    <textarea class="description" rows="4" cols="50" type="text" name="description" placeholder="Description" required></textarea> 
                    
                    <div class="input-container">
                        <input id="passport-img" class="text-input" name="activity_document" type="file" multiple="false" accept="*/*" required />
                        <span class="file-info">Upload a file</span>
                        <button class="browse-btn">BROWSE</button>
                    </div>

                    <input name="submitactivity" type="submit" id="submitactivity" class="submit button" value="SUBMIT FOR APPROVAL" />
                    <input name="action" type="hidden" id="action" value="" />
                </div> 
            </div>
            <?php wp_nonce_field( 'communityservice-submit-activity', 'communityservice-submit-activity-nonce' ); ?>
        </form>
    </div> 
</div>