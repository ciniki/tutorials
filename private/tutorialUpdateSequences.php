<?php
//
// Description
// ===========
// This function will update the sequences for tutorialStepss.
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_tutorials_tutorialUpdateSequences($ciniki, $business_id, $tutorial_id, $new_seq, $old_seq) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

	//
	// Get the sequences
	//
	$strsql = "SELECT id, sequence AS number "
		. "FROM ciniki_tutorial_steps "
		. "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $tutorial_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	// Use the last_updated to determine which is in the proper position for duplicate numbers
	if( $new_seq < $old_seq || $old_seq == -1) {
		$strsql .= "ORDER BY sequence, last_updated DESC";
	} else {
		$strsql .= "ORDER BY sequence, last_updated ";
	}
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'sequence');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
		return $rc;
	}
	$cur_number = 1;
	if( isset($rc['rows']) ) {
		$sequences = $rc['rows'];
		foreach($sequences as $sid => $seq) {
			//
			// If the number is not where it's suppose to be, change
			//
			if( $cur_number != $seq['number'] ) {
				$strsql = "UPDATE ciniki_tutorial_steps SET "
					. "sequence = '" . ciniki_core_dbQuote($ciniki, $cur_number) . "' "
					. ", last_updated = UTC_TIMESTAMP() "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND id = '" . ciniki_core_dbQuote($ciniki, $seq['id']) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tutorials');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
				}
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tutorials', 
					'ciniki_tutorials_history', $business_id, 
					2, 'ciniki_tutorial_steps', $seq['id'], 'sequence', $cur_number);
				$ciniki['syncqueue'][] = array('push'=>'ciniki.tutorials.step', 
					'args'=>array('id'=>$seq['id']));
				
			}
			$cur_number++;
		}
	}
	
	return array('stat'=>'ok');
}
?>
