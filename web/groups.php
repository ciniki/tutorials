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
function ciniki_tutorials_web_groups($ciniki, $settings, $tnid, $args) {

    $strsql = "SELECT DISTINCT ciniki_tutorial_tags.tag_name, "
        . "ciniki_tutorial_tags.permalink, "
        . "IFNULL(ciniki_tutorial_settings.detail_value, 99) AS tag_sequence "
        . "FROM ciniki_tutorial_tags "
        . "INNER JOIN ciniki_tutorials ON ("
            . "ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
            . "AND (ciniki_tutorials.webflags&0x01) = 0x01 "
            . "AND ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_tutorial_settings ON ("
            . "ciniki_tutorial_settings.detail_key = CONCAT('group-sequence-', ciniki_tutorial_tags.permalink) "
            . "AND ciniki_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tutorial_tags.tag_type = 40 "
        . "ORDER BY tag_sequence+0, tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'groups', 'fname'=>'tag_name',
            'fields'=>array('name'=>'tag_name', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return $rc;
}
?>
