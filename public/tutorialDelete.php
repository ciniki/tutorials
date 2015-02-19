<?php
//
// Description
// -----------
// This method will delete a tutorial from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the tutorial is attached to.
// tutorial_id:			The ID of the tutorial to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_tutorials_tutorialDelete(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'tutorial_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Tutorial'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'private', 'checkAccess');
	$ac = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialDelete');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Get the uuid of the tutorial to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_tutorials "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2215', 'msg'=>'The tutorial does not exist'));
	}
	$item = $rc['item'];

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tutorials');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Remove the steps but not the content
	//
	$strsql = "SELECT id, uuid FROM ciniki_tutorial_steps "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'step');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$steps = $rc['rows'];
		
		foreach($steps as $iid => $step) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.tutorials.step', 
				$step['id'], $step['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
				return $rc;	
			}
		}
	}

	//
	// Remove the tutorial
	//
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.tutorials.tutorial', 
		$args['tutorial_id'], $item['uuid'], 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
		return $rc;
	}

	//
	// Commit the transaction
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
