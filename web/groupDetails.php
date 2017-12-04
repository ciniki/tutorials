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
function ciniki_tutorials_web_groupDetails($ciniki, $settings, $tnid, $permalink) {

    $rsp = array('stat'=>'ok', 'group'=>array('image-id'=>'0', 'image-caption'=>'', 'content'=>''));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tutorial_settings', 
        'tnid', $tnid, 'ciniki.tutorials', 'settings', 'group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tutorials.23', 'msg'=>'No settings found, site not configured.'));
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
