<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to update the tutorial for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tutorials_tutorialUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tutorial_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tutorial'), 
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Flags'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        'groups'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Groups'),
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.tutorialUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the existing tutorial details 
    //
    $strsql = "SELECT id, uuid, permalink "
        . "FROM ciniki_tutorials "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.17', 'msg'=>'Tutorial not found'));
    }
    $item = $rc['item'];

//  if( (!isset($args['permalink']) || $args['permalink'] == '') && isset($args['title']) ) {
    if( isset($args['title']) || isset($args['groups']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        if( isset($args['title']) ) {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
        } else {
            $args['permalink'] = $item['permalink'];
        }

        //
        // Check through all the groups to see if the permalink already exists for one of the groups
        //
        $groups = array();
        if( ($ciniki['tenant']['modules']['ciniki.tutorials']['flags']&0x04) > 0 ) {
            if( isset($args['groups']) ) {
                $groups = $args['groups'];
            } else {    
                $strsql = "SELECT DISTINCT tag_name "
                    . "FROM ciniki_tutorial_tags "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND tag_type = '40' "
                    . "AND tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
                $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.tutorials', 'groups', 'tag_name');
                if( $rc['stat'] != 'ok' ) { 
                    return $rc;
                }
                $groups = $rc['groups'];
            }
        }

        //
        // Make sure the permalink is unique within the groups
        //
        if( count($groups) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
            $strsql = "SELECT ciniki_tutorials.id, "
                . "ciniki_tutorials.title, "
                . "ciniki_tutorials.permalink "
                . "FROM ciniki_tutorial_tags, ciniki_tutorials "
                . "WHERE ciniki_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_tutorial_tags.tag_type = 40 "
                . "AND ciniki_tutorial_tags.tag_name IN (" . ciniki_core_dbQuoteList($ciniki, $groups) . ") "
                . "AND ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
                . "AND ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_tutorials.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
                . "AND ciniki_tutorials.id <> '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'tutorial');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['num_rows'] > 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.18', 'msg'=>'You already have tutorial with this title, please choose another title.'));
            }
        }
        //
        // No groups, do a basic permalink check
        //
        else {
            //
            // Make sure the permalink is unique
            //
            $strsql = "SELECT id, title, permalink "
                . "FROM ciniki_tutorials "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
                . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'tutorial');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['num_rows'] > 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.19', 'msg'=>'You already have tutorial with this title, please choose another title.'));
            }
        }
    }

    if( isset($args['permalink']) && $item['permalink'] == $args['permalink'] ) {
        unset($args['permalink']);
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
    // Update the tutorial in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.tutorials.tutorial', $args['tutorial_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
        return $rc;
    }

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.tutorials', 'tag', $args['tnid'],
            'ciniki_tutorial_tags', 'ciniki_tutorial_history',
            'tutorial_id', $args['tutorial_id'], 10, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
            return $rc;
        }
    }

    //
    // Update the groups
    //
    if( isset($args['groups']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.tutorials', 'tag', $args['tnid'],
            'ciniki_tutorial_tags', 'ciniki_tutorial_history',
            'tutorial_id', $args['tutorial_id'], 40, $args['groups']);
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

    return array('stat'=>'ok');
}
?>
