<?php
//
// Description
// ===========
// This method will update a tag names in the tutorials.  This can be used to
// merge tags.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to the item is a part of.
// old_tag:         The name of the old tag.
// new_tag:         The new name for the tag.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tutorials_tagUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'tag'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Tag'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'image'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'image-caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image Caption'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.tagUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tutorials');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $tag_type = '';
    if( $args['tag_type'] == '10' ) {
        $tag_type = 'category';
    } elseif( $args['tag_type'] == '40' ) {
        $tag_type = 'group';
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.8', 'msg'=>'Invalid tag type'));
    }


    $updated = 0;
    $fields = array('sequence', 'image', 'image-caption', 'content');
    foreach($fields as $f) {
        if( isset($args[$f]) ) {
            $detail_key = $tag_type . '-' . $f . '-' . $args['tag'];

            //
            // Get the existing tag description
            //
            $strsql = "SELECT detail_value "
                . "FROM ciniki_tutorial_settings "
                . "WHERE ciniki_tutorial_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_tutorial_settings.detail_key = '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'setting');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['setting']) ) {
                $strsql = "INSERT INTO ciniki_tutorial_settings (tnid, detail_key, detail_value, "
                    . "date_added, last_updated) VALUES ("
                    . "' " . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $args[$f]) . "' "
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                    . "";
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tutorials');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tutorials', 
                    'ciniki_tutorial_history', $args['tnid'], 
                    1, 'ciniki_tutorial_settings', $detail_key, 'detail_value', $args[$f]);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.tutorials.setting',
                    'args'=>array('id'=>$detail_key));
            } else {
                $strsql = "UPDATE ciniki_tutorial_settings "
                    . "SET detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$f]) . "', "
                    . "last_updated = UTC_TIMESTAMP() "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tutorials');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tutorials', 
                    'ciniki_tutorial_history', $args['tnid'], 
                    2, 'ciniki_tutorial_settings', $detail_key, 'detail_value', $args[$f]);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.tutorials.setting',
                    'args'=>array('id'=>$detail_key));
            }
            $updated = 1;
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tutorials');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    if( $updated > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'tutorials');
    }

    return array('stat'=>'ok');
}
?>
