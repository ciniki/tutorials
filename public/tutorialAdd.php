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
function ciniki_tutorials_tutorialAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Permalink'), 
        'sequence'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Sequence'), 
        'flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Flags'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        'synopsis'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Synopsis'),
        'content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Content'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Web Flags'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        'groups'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Groups'),
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.tutorialAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    } 

    //
    // Determine the permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);

        //
        // Check through all the groups to see if the permalink already exists for one of the groups
        //
        $groups = array();
        if( ($ciniki['business']['modules']['ciniki.tutorials']['flags']&0x04) > 0 ) {
            if( isset($args['groups']) ) {
                $groups = $args['groups'];
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
                . "WHERE ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_tutorial_tags.tag_type = 40 "
                . "AND ciniki_tutorial_tags.tag_name IN (" . ciniki_core_dbQuoteList($ciniki, $groups) . ") "
                . "AND ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
                . "AND ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_tutorials.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'tutorial');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['num_rows'] > 0 ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2331', 'msg'=>'You already have tutorial with this title, please choose another title.'));
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
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
                . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'tutorial');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['num_rows'] > 0 ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2241', 'msg'=>'You already have tutorial with this title, please choose another title.'));
            }
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
    // Add the tutorial to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.tutorials.tutorial', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tutorials');
        return $rc;
    }
    $tutorial_id = $rc['id'];

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.tutorials', 'tag', $args['business_id'],
            'ciniki_tutorial_tags', 'ciniki_tutorial_history',
            'tutorial_id', $tutorial_id, 10, $args['categories']);
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
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.tutorials', 'tag', $args['business_id'],
            'ciniki_tutorial_tags', 'ciniki_tutorial_history',
            'tutorial_id', $tutorial_id, 40, $args['groups']);
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

    return array('stat'=>'ok', 'id'=>$tutorial_id);
}
?>
