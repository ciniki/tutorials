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
function ciniki_tutorials_web_tutorialList($ciniki, $settings, $business_id, $args) {

    if( isset($args['group']) && $args['group'] != '' ) {
        $strsql = "SELECT ciniki_tutorials.id, "
            . "ciniki_tutorials.title, "
            . "ciniki_tutorials.permalink, "
            . "ciniki_tutorials.primary_image_id, "
            . "ciniki_tutorials.synopsis, "
            . "IFNULL(t2.tag_name, ' ') AS category, "
            . "'yes' AS is_details, "
            . "IFNULL(ciniki_tutorial_settings.detail_value, '99') AS cat_sequence "
            . "FROM ciniki_tutorial_tags AS t1 "
            . "INNER JOIN ciniki_tutorials ON ("
                . "t1.tutorial_id = ciniki_tutorials.id "
                . "AND (ciniki_tutorials.webflags&0x01) = 0x01 "
                . "AND ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_tags AS t2 ON ("
                . "ciniki_tutorials.id = t2.tutorial_id "
                . "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND t2.tag_type = 10 "
                . ") "
            . "LEFT JOIN ciniki_tutorial_settings ON ("
                . "ciniki_tutorial_settings.detail_key = CONCAT('category-sequence-', t2.permalink) "
                . "AND t2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE t1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND t1.tag_type = 40 "
            . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['group']) . "' "
            . "ORDER BY cat_sequence, category, ciniki_tutorials.sequence, ciniki_tutorials.title "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'categories', 'fname'=>'category',
                'fields'=>array('id', 'name'=>'category')),
            array('container'=>'list', 'fname'=>'id', 
                'fields'=>array('id', 'title', 'permalink', 'sequence'=>'cat_sequence', 'image_id'=>'primary_image_id',
                    'synopsis', 'is_details')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    } else {
        $strsql = "SELECT ciniki_tutorials.id, "
            . "ciniki_tutorials.title, "
            . "ciniki_tutorials.permalink, "
            . "ciniki_tutorials.primary_image_id, "
            . "ciniki_tutorials.synopsis, "
            . "IFNULL(ciniki_tutorial_tags.tag_name, ' ') AS category, "
            . "'yes' AS is_details, "
            . "IFNULL(ciniki_tutorial_settings.detail_value, '99') AS cat_sequence "
            . "FROM ciniki_tutorials "
            . "LEFT JOIN ciniki_tutorial_tags ON ("
                . "ciniki_tutorials.id = ciniki_tutorial_tags.tutorial_id "
                . "AND ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_tutorial_tags.tag_type = '10' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_settings ON ("
                . "ciniki_tutorial_settings.detail_key = CONCAT('category-sequence-', ciniki_tutorial_tags.permalink) "
                . "AND ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_tutorial_tags.tag_type = '10' "
                . ") "
            . "WHERE ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_tutorials.webflags&0x01) = 0x01 "
            . "ORDER BY cat_sequence, category, ciniki_tutorials.sequence, ciniki_tutorials.title "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'categories', 'fname'=>'category',
                'fields'=>array('id', 'name'=>'category')),
            array('container'=>'list', 'fname'=>'id', 
                'fields'=>array('id', 'title', 'permalink', 'sequence'=>'cat_sequence', 'image_id'=>'primary_image_id',
                    'synopsis', 'is_details')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return $rc;
}
?>
