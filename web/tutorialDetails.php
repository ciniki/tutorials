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
function ciniki_tutorials_web_tutorialDetails($ciniki, $settings, $tnid, $permalink) {

    $modules = array();
    if( isset($ciniki['tenant']['modules']) ) {
        $modules = $ciniki['tenant']['modules'];
    }

    $strsql = "SELECT ciniki_tutorials.id, "
        . "ciniki_tutorial_steps.id AS step_id, "
        . "ciniki_tutorials.title, "
        . "ciniki_tutorials.permalink, "
        . "ciniki_tutorials.synopsis, "
        . "ciniki_tutorials.content, "
        . "ciniki_tutorials.primary_image_id, "
        . "ciniki_tutorial_steps.sequence, "
        . "ciniki_tutorial_step_content.image_id, "
        . "ciniki_tutorial_step_content.title AS step_title, "
        . "ciniki_tutorial_step_content.content AS step_content, "
        . "'yes' as is_details, "
        . "UNIX_TIMESTAMP(ciniki_tutorial_steps.last_updated) AS step_last_updated "
        . "FROM ciniki_tutorials "
        . "LEFT JOIN ciniki_tutorial_steps ON ("
            . "ciniki_tutorials.id = ciniki_tutorial_steps.tutorial_id "
            . "AND ciniki_tutorial_steps.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_tutorial_step_content ON ("
            . "ciniki_tutorial_steps.step_content_id = ciniki_tutorial_step_content.id "
            . "AND ciniki_tutorial_step_content.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tutorials.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "AND (ciniki_tutorials.webflags&0x01) = 1 "
        . "ORDER BY ciniki_tutorial_steps.tutorial_id, ciniki_tutorial_steps.sequence, ciniki_tutorial_step_content.title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
        array('container'=>'tutorials', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'permalink', 'primary_image_id', 'synopsis', 'content', 'is_details')),
        array('container'=>'steps', 'fname'=>'step_id', 
            'fields'=>array('image_id', 'title'=>'step_title', 
                'description'=>'step_content', 
                'last_updated'=>'step_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tutorials']) || count($rc['tutorials']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.tutorials.24', 'msg'=>"I'm sorry, but we can't find the tutorial you requested."));
    }
    $tutorial = array_pop($rc['tutorials']);

    //
    // Get the categories and tags for the tutorial
    //
    $strsql = "SELECT id, tag_type, tag_name, permalink "
        . "FROM ciniki_tutorial_tags "
        . "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $tutorial['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY tag_type, tag_name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tutorials', array(
        array('container'=>'types', 'fname'=>'tag_type',
            'fields'=>array('type'=>'tag_type')),
        array('container'=>'tags', 'fname'=>'id',
            'fields'=>array('id', 'name'=>'tag_name', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['types']) ) {
        foreach($rc['types'] as $type) {
            if( $type['type'] == 10 ) {
                $tutorial['categories'] = $type['tags'];
            } elseif( $type['type'] == 20 ) {
                $tutorial['tags'] = $type['tags'];
            }
        }
    }

    return array('stat'=>'ok', 'tutorial'=>$tutorial);
}
?>
