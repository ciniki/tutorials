<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to update the tutorial for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tutorials_tutorialStepUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'step_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Tutorial'), 
        'step_content_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Content'), 
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Code'), 
		'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
		'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialStepUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the existing step details 
	//
	$strsql = "SELECT id, tutorial_id, step_content_id, sequence, uuid "
		. "FROM ciniki_tutorial_steps "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2248', 'msg'=>'Step not found'));
	}
	$item = $rc['item'];

	//
	// Grab the old sequence
	//
	if( isset($args['sequence']) ) {
		$old_sequence = $item['sequence'];
		$tutorial_id = $item['tutorial_id'];
	}

	//
	// Check the step content code does not already exist
	//
	if( isset($args['step_content_id']) && $args['step_content_id'] > 0 
		&& isset($args['code']) && $args['code'] != '' 
		) {
		$strsql = "SELECT id, code, title "
			. "FROM ciniki_tutorial_step_content "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND code = '" . ciniki_core_dbQuote($ciniki, $args['code']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['step_content_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'items');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2247', 'msg'=>'The code already exists.'));
		}
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tutorials');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if the content should be updated
	//
	if( isset($args['step_content_id']) && $args['step_content_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectGet');
		$rc = ciniki_core_objectGet($ciniki, $args['business_id'], 'ciniki.tutorials.step_content', $args['step_content_id']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
			return $rc;
		}
		$item = $rc['object'];

		$update_args = array();
		if( isset($args['code']) && $args['code'] != $item['code'] ) {
			$update_args['code'] = $args['code'];
		}
		if( isset($args['title']) && $args['title'] != $item['title'] ) {
			$update_args['title'] = $args['title'];
		}
		if( isset($args['image_id']) && $args['image_id'] != $item['image_id'] ) {
			$update_args['image_id'] = $args['image_id'];
		}
		if( isset($args['content']) && $args['content'] != $item['content'] ) {
			$update_args['content'] = $args['content'];
		}
		if( count($update_args) > 0 ) {
			//
			// Update the step content to the database
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
			$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.tutorials.step_content', $args['step_content_id'], $update_args, 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
				return $rc;
			}
		}
	} elseif( isset($args['code']) || isset($args['title']) || isset($args['image_id']) || isset($args['content']) ) {
		//
		// Add the step content to the database
		//
		$add_args = array(
			'code'=>(isset($args['code'])?$args['code']:''),
			'title'=>(isset($args['title'])?$args['title']:''),
			'image_id'=>(isset($args['image_id'])?$args['image_id']:'0'),
			'content'=>(isset($args['content'])?$args['content']:''),
			);
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.tutorials.step_content', $add_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
			return $rc;
		}
		$args['step_content_id'] = $rc['id'];
	}	

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.tutorials.step', $args['step_id'], $args);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
		return $rc;
	}

	//
	// Update any sequences
	//
	if( isset($args['sequence']) && $tutorial_id > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'private', 'tutorialUpdateSequences');
		$rc = ciniki_tutorials_tutorialUpdateSequences($ciniki, $args['business_id'], 
			$tutorial_id, $args['sequence'], $old_sequence);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
			return $rc;
		}
	}

	//
	// Commit the changes to the database
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tutorials');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'tutorials');

	return array('stat'=>'ok');
}
?>
