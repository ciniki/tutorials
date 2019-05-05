<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get tutorials for.
//
// Returns
// -------
//
function ciniki_tutorials_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.tutorials'])
        && (isset($args['permissions']['ciniki.owners'])
            || isset($args['permissions']['ciniki.employees'])
            || isset($args['permissions']['ciniki.resellers'])
            || isset($args['permissions']['ciniki.tutorials'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>1300,
            'label'=>'Tutorials', 
            'edit'=>array('app'=>'ciniki.tutorials.main'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    if( isset($ciniki['tenant']['modules']['ciniki.tutorials'])
        && (isset($args['permissions']['ciniki.owners'])
            || isset($args['permissions']['ciniki.resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>1300, 'label'=>'Tutorials', 'edit'=>array('app'=>'ciniki.tutorials.settings'));
    }

    return $rsp;
}
?>
