<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business.
//
// Returns
// -------
//
function ciniki_tutorials_tutorialStepGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'step_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Step'),
        'tutorial_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tutorial'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'private', 'checkAccess');
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialStepGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    if( isset($args['step_id']) && $args['step_id'] > 0 ) {
        $strsql = "SELECT ciniki_tutorial_steps.id, "
            . "ciniki_tutorial_steps.step_content_id, "
            . "ciniki_tutorial_steps.sequence, "
            . "IFNULL(ciniki_tutorial_step_content.code, '') AS code, "
            . "IFNULL(ciniki_tutorial_step_content.title, '') AS title, "
            . "IFNULL(ciniki_tutorial_step_content.image_id, 0) AS image_id, "
            . "IFNULL(ciniki_tutorial_step_content.content, '') AS content "
            . "FROM ciniki_tutorial_steps "
            . "LEFT JOIN ciniki_tutorial_step_content ON ("
                . "ciniki_tutorial_steps.step_content_id = ciniki_tutorial_step_content.id "
                . "AND ciniki_tutorial_step_content.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_tutorial_steps.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_tutorial_steps.id = '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'steps', 'fname'=>'id', 'name'=>'step',
                'fields'=>array('id', 'step_content_id', 'sequence',
                    'code', 'title', 'image_id', 'content')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $step = $rc['steps'][0]['step'];
    } else {
        $strsql = "SELECT MAX(sequence) AS sequence "
            . "FROM ciniki_tutorial_steps "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['sequence']) ) {
            $sequence = $rc['max']['sequence'] + 1;
        } else {
            $sequence = 1;
        }
        $step = array('id'=>'0', 
            'step_content_id'=>'0',
            'sequence'=>$sequence,
            'code'=>'',
            'title'=>'',
            'flags'=>'0',
            'image_id'=>'0',
            'content'=>'',
            );
    }

    return array('stat'=>'ok', 'step'=>$step);
}
?>
