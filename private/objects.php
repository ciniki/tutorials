<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tutorials_objects($ciniki) {
    
    $objects = array();
    $objects['tutorial'] = array(
        'name'=>'Tutorial',
        'sync'=>'yes',
        'table'=>'ciniki_tutorials',
        'fields'=>array(
            'title'=>array(),
            'permalink'=>array(),
            'sequence'=>array(),
            'flags'=>array(),
            'primary_image_id'=>array('ref'=>'ciniki.images.image'),
            'synopsis'=>array(),
            'content'=>array(),
            'webflags'=>array(),
            ),
        'history_table'=>'ciniki_tutorial_history',
        );
    $objects['step'] = array(
        'name'=>'Tutorial Step',
        'sync'=>'yes',
        'table'=>'ciniki_tutorial_steps',
        'fields'=>array(
            'tutorial_id'=>array('ref'=>'ciniki.tutorials.tutorial'),
            'step_content_id'=>array('ref'=>'ciniki.tutorials.step_content'),
            'sequence'=>array(),
            ),
        'history_table'=>'ciniki_tutorial_history',
        );
    $objects['step_content'] = array(
        'name'=>'Tutorial Step Content',
        'sync'=>'yes',
        'table'=>'ciniki_tutorial_step_content',
        'fields'=>array(
            'code'=>array(),
            'title'=>array(),
            'image_id'=>array(),
            'content'=>array(),
            ),
        'history_table'=>'ciniki_tutorial_history',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'sync'=>'yes',
        'table'=>'ciniki_tutorial_tags',
        'fields'=>array(
            'tutorial_id'=>array('ref'=>'ciniki.tutorials.tutorial'),
            'tag_type'=>array(),
            'tag_name'=>array(),
            'permalink'=>array(),
            ),
        'history_table'=>'ciniki_tutorial_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Tutorial Settings',
        'table'=>'ciniki_tutorial_settings',
        'history_table'=>'ciniki_tutorial_history',
        );
    
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
