<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}
global $gmCore, $gmProcessor;
?>
<div class="list-group-item">
    <div class="well well-lg text-center">
        <?php if('duplicates' === $gmCore->_get('gmedia__in')){
            echo '<h4>' . __('No duplicates in Gmedia Library.', 'grand-media') . '</h4>';
        } else{
            echo '<h4>' . __('No items to show.', 'grand-media') . '</h4>';
            if(gm_user_can('upload') && !$gmProcessor->gmediablank){
                $args = array('page' => 'GrandMedia_AddMedia');
                if($gmProcessor->edit_term){
                    $taxterm          = $gmProcessor->taxterm;
                    $args[ $taxterm ] = $gmProcessor->edit_term;
                }
                ?>
                <p>
                    <a href="<?php echo gm_get_admin_url($args, array(), true); ?>" class="btn btn-success">
                        <span class="glyphicon glyphicon-plus"></span> <?php _e('Add Media', 'grand-media'); ?>
                    </a>
                </p>
            <?php }
        } ?>
    </div>
</div>
