<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to get the list from.
// 
// Returns
// -------
//
function ciniki_tutorials_tutorialCategories($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'category', 'name'=>'Type'),
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialCategories'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    $strsql = "SELECT DISTINCT ciniki_tutorial_tags.tag_name, "
        . "ciniki_tutorial_tags.permalink, "
        . "COUNT(ciniki_tutorials.id) AS num_tutorials "
        . "FROM ciniki_tutorial_tags "
        . "LEFT JOIN ciniki_tutorials ON ("
            . "ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
            . "AND ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY tag_name "
        . "GROUP BY tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
        array('container'=>'categories', 'fname'=>'tag_name', 'name'=>'category',
            'fields'=>array('tag_name', 'permalink', 'num_tutorials')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }
    return array('stat'=>'ok', 'categories'=>$rc['categories']);
}
?>
