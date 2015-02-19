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
// business_id:		The ID of the business to get the list from.
// 
// Returns
// -------
//
function ciniki_tutorials_categoryDetails($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'category'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.categoryDetails'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the settings for the tutorials
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tutorial_settings', 
		'business_id', $args['business_id'], 'ciniki.tutorials', 'settings', 'category');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$settings = $rc['settings'];
	} else {
		$settings = array();
	}
	
	$fields = array('sequence');
	$details = array();
	foreach($fields as $f) {
		$details[$f] = '';
		if( isset($settings['category-' . $f . '-' . $args['category']]) ) {
			$details[$f] = $settings['category-' . $f . '-' . $args['category']];
		}
	}

	return array('stat'=>'ok', 'details'=>$details);
}
?>
