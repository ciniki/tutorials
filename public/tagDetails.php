<?php
//
// Description
// ===========
// This method will return the list of categories used in the tutorials.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the list from.
// 
// Returns
// -------
//
function ciniki_tutorials_tagDetails($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'tag'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tag'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'private', 'checkAccess');
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.tagDetails'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the settings for the tutorials
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash'); 
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    $tag_type = '';
    if( $args['tag_type'] == '10' ) {
        $tag_type = 'category';
    } elseif( $args['tag_type'] == '40' ) {
        $tag_type = 'group';
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.7', 'msg'=>'Invalid tag type'));
    }

    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tutorial_settings', 
        'tnid', $args['tnid'], 'ciniki.tutorials', 'settings', $tag_type);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    } else {
        $settings = array();
    }
    
    $fields = array('sequence', 'image', 'image-caption', 'content');
    $details = array();
    foreach($fields as $f) {
        $details[$f] = '';
        if( isset($settings[$tag_type . '-' . $f . '-' . $args['tag']]) ) {
            $details[$f] = $settings[$tag_type . '-' . $f . '-' . $args['tag']];
        }
    }

    return array('stat'=>'ok', 'details'=>$details);
}
?>
