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

	$strsql = "SELECT ciniki_tutorials.id, "
		. "ciniki_tutorials.title, "
		. "ciniki_tutorials.permalink, "
		. "ciniki_tutorials.primary_image_id, "
		. "ciniki_tutorials.synopsis, "
		. "IFNULL(ciniki_tutorial_tags.tag_name, ' ') AS category, "
		. "'yes' AS is_details, "
		. "IFNULL(ciniki_tutorial_settings.detail_value, '99') AS sequence "
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
		. "ORDER BY sequence, category, ciniki_tutorials.title "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'categories', 'fname'=>'category',
			'fields'=>array('id', 'name'=>'category')),
		array('container'=>'list', 'fname'=>'id', 
			'fields'=>array('id', 'title', 'permalink', 'sequence', 'image_id'=>'primary_image_id',
				'synopsis', 'is_details')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
