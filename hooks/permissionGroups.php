<?php
//
// Description
// -----------
// Return the list of available permission groups
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_tutorials_hooks_permissionGroups(&$ciniki, $tnid, $args) {

    return array('stat'=>'ok', 'permission_groups'=>array('ciniki.tutorials'=>array('name'=>'Tutorials')));
}
?>
