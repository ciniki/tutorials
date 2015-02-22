<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business.
//
// Returns
// -------
//
function ciniki_tutorials_tutorialGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'tutorial_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tutorial'),
		'steps'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Steps'),
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	if( $args['tutorial_id'] > 0 ) {
		//
		// Get the main tutorialsrmation
		//
		$strsql = "SELECT ciniki_tutorials.id, "
			. "ciniki_tutorials.title, "
			. "ciniki_tutorials.permalink, "
			. "ciniki_tutorials.sequence, "
			. "ciniki_tutorials.flags, "
			. "ciniki_tutorials.primary_image_id, "
			. "ciniki_tutorials.synopsis, "
			. "ciniki_tutorials.content, "
			. "ciniki_tutorials.webflags "
			. "FROM ciniki_tutorials "
			. "WHERE ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_tutorials.id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
			array('container'=>'tutorials', 'fname'=>'id', 'name'=>'tutorial',
				'fields'=>array('id', 'sequence',
					'title', 'permalink', 'flags', 'primary_image_id', 
					'synopsis', 'content', 'webflags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$tutorial = $rc['tutorials'][0]['tutorial'];

		//
		// Get the steps
		//
		if( isset($args['steps']) && $args['steps'] == 'yes' ) {
			$strsql = "SELECT ciniki_tutorial_steps.id, "
				. "ciniki_tutorial_steps.sequence, "
				. "ciniki_tutorial_step_content.code, "
				. "ciniki_tutorial_step_content.title "
				. "FROM ciniki_tutorial_steps "
				. "LEFT JOIN ciniki_tutorial_step_content ON ("
					. "ciniki_tutorial_steps.step_content_id = ciniki_tutorial_step_content.id "
					. "AND ciniki_tutorial_step_content.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. ") " 
				. "WHERE ciniki_tutorial_steps.tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
				. "AND ciniki_tutorial_steps.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "ORDER BY ciniki_tutorial_steps.sequence, ciniki_tutorial_step_content.title "
				. "";
			$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
				array('container'=>'steps', 'fname'=>'id', 'name'=>'step',
					'fields'=>array('id', 'sequence', 'code', 'title')),
					));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['steps']) ) {
				$tutorial['steps'] = $rc['steps'];
			} else {
				$tutorial['steps'] = array();
			}
			$num_steps = 0;
			foreach($tutorial['steps'] as $sid => $step) {
				$num_steps++;
				$tutorial['steps'][$sid]['step']['number'] = $num_steps;
			}
			$tutorial['num_steps'] = $num_steps;
		}
		//
		// Get the categories and tags for the post
		//
		$strsql = "SELECT tag_type, tag_name AS lists "
			. "FROM ciniki_tutorial_tags "
			. "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY tag_type, tag_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
			array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
				'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			foreach($rc['tags'] as $tags) {
				if( $tags['tags']['tag_type'] == 10 ) {
					$tutorial['categories'] = $tags['tags']['lists'];
				}
			}
		}

	} else {
		$tutorial = array('id'=>'0', 
			'title'=>'',
			'permalink'=>'',
			'flags'=>'0',
			'primary_image_id'=>'0',
			'synopsis'=>'',
			'content'=>'',
			'webflags'=>'1',
			);
	}

	$rsp = array('stat'=>'ok', 'tutorial'=>$tutorial);

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
