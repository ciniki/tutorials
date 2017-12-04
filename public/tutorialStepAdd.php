<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_tutorials_tutorialStepAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tutorial_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Tutorial'), 
        'step_content_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Content'), 
        'sequence'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Sequence'),
        'code'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Code'), 
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
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'private', 'checkAccess');
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.tutorialStepAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    } 

    //
    // Check the sequence
    //
    if( !isset($args['sequence']) || $args['sequence'] == '' || $args['sequence'] == '0' ) {
        $strsql = "SELECT MAX(sequence) AS max_sequence "
            . "FROM ciniki_tutorial_steps "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'seq');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['seq']) && isset($rc['seq']['max_sequence']) ) {
            $args['sequence'] = $rc['seq']['max_sequence'] + 1;
        } else {
            $args['sequence'] = 1;
        }
    }

    //
    // Check the step content code does not already exist
    //
    if( isset($args['step_content_id']) && $args['step_content_id'] == 0 
        && isset($args['code']) && $args['code'] != '' 
        ) {
        $strsql = "SELECT id, code, title "
            . "FROM ciniki_tutorial_step_content "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND code = '" . ciniki_core_dbQuote($ciniki, $args['code']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'items');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.12', 'msg'=>'The code already exists.'));
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

    if( isset($args['step_content_id']) && $args['step_content_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectGet');
        $rc = ciniki_core_objectGet($ciniki, $args['tnid'], 'ciniki.tutorials.step_content', $args['step_content_id']);
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
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.tutorials.step_content', $args['step_content_id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
                return $rc;
            }
        }
    } else {
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
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.tutorials.step_content', $add_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
            return $rc;
        }
        $args['step_content_id'] = $rc['id'];
    }

    //
    // Add the step to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.tutorials.step', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
        return $rc;
    }
    $step_id = $rc['id'];

    //
    // Update any sequences
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'private', 'tutorialUpdateSequences');
        $rc = ciniki_tutorials_tutorialUpdateSequences($ciniki, $args['tnid'], 
            $args['tutorial_id'], $args['sequence'], -1);
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'tutorials');

    return array('stat'=>'ok', 'id'=>$step_id);
}
?>
