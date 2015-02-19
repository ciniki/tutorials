<?php
//
// Description
// ===========
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
function ciniki_tutorials_tutorialList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        'categories'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Categories'), 
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	if( isset($args['category']) && $args['category'] != '' ) {
		$strsql = "SELECT ciniki_tutorials.id, ciniki_tutorials.title "	
			. "FROM ciniki_tutorial_tags "
			. "LEFT JOIN ciniki_tutorials ON ("
				. "ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
				. "AND ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_tutorial_tags.tag_type = 10 "
			. "AND ciniki_tutorial_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "ORDER BY ciniki_tutorials.title "
			. "";
	} else {
		$strsql = "SELECT ciniki_tutorials.id, ciniki_tutorials.title, tag_name "	
			. "FROM ciniki_tutorials "
			. "LEFT JOIN ciniki_tutorial_tags ON ("
				. "ciniki_tutorials.id = ciniki_tutorial_tags.tutorial_id "
				. "AND ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "HAVING ISNULL(tag_name) "
			. "ORDER BY title "
			. "";
	}
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
		array('container'=>'tutorials', 'fname'=>'id', 'name'=>'tutorial',
			'fields'=>array('id', 'title')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp = array('stat'=>'ok');
	if( !isset($rc['tutorials']) ) {
		$rsp['tutorials'] = array();
	} else {
		$rsp['tutorials'] = $rc['tutorials'];
	}

	//
	// Check if we should return categories as well
	//
	if( isset($args['categories']) && $args['categories'] == 'yes' ) {
		$strsql = "SELECT ciniki_tutorial_tags.tag_name, "
			. "ciniki_tutorial_tags.permalink, "
			. "COUNT(ciniki_tutorials.id) AS num_tutorials "
			. "FROM ciniki_tutorial_tags "
			. "LEFT JOIN ciniki_tutorials ON ("
				. "ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
				. "AND ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "GROUP BY tag_name "
			. "ORDER BY tag_name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
			array('container'=>'categories', 'fname'=>'tag_name', 'name'=>'category',
				'fields'=>array('name'=>'tag_name', 'permalink', 'num_tutorials')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['categories']) ) {
			$rsp['categories'] = array();
		} else {
			$rsp['categories'] = $rc['categories'];
		}
	}

	return $rsp;
}
?>
