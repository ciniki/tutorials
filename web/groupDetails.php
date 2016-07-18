<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tutorials_web_groupDetails($ciniki, $settings, $business_id, $permalink) {

    $rsp = array('stat'=>'ok', 'group'=>array('image-id'=>'0', 'image-caption'=>'', 'content'=>''));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tutorial_settings', 
        'business_id', $business_id, 'ciniki.tutorials', 'settings', 'group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2333', 'msg'=>'No settings found, site not configured.'));
    }
    $settings = $rc['settings'];

    if( isset($settings['group-image-' . $permalink]) ) {
        $rsp['group']['image'] = $settings['group-image-' . $permalink];
    }
    if( isset($settings['group-image-caption-' . $permalink]) ) {
        $rsp['group']['image-caption'] = $settings['group-image-caption-' . $permalink];
    }
    if( isset($settings['group-content-' . $permalink]) ) {
        $rsp['group']['content'] = $settings['group-content-' . $permalink];
    }

    return $rsp;
}
?>
