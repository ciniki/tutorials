<?php
//
// Description
// ===========
// This method will list the art catalog items sorted by category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the list from.
// section:			(optional) How the list should be sorted and organized.
//
//					- category
//					- media
//					- location
//					- year
//					- list
//
// name:			(optional) The name of the section to get restrict the list.  This
//					can only be specified if the section is also specified.  If the section
//					is category, then the name will restrict the results to the cateogry of
//					this name.
//
// type:			(optional) Only list items of a specific type. Valid types are:
//
//					- painting
//					- photograph
//					- jewelry
//					- sculpture
//					- fibreart
//					- clothing
//
// limit:			(optional) Limit the number of results.
// 
// Returns
// -------
//
function ciniki_tutorials_downloadPDF($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		// PDF options
//        'output'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Output Type'), 
        'layout'=>array('required'=>'no', 'blank'=>'no', 'default'=>'list', 'name'=>'Layout',
			'validlist'=>array('single', 'double', 'triple')), 
        'coverpage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Cover Page'), 
        'toc'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Table of Contents'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Title'), 
        'tutorials'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Tutorials'), // List of tutorials to include
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['business_id'], 'ciniki.tutorials.downloadPDF'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

	//
	// Load the list of tutorials organized by category
	//
	$strsql = "SELECT ciniki_tutorials.id, "
		. "IFNULL(ciniki_tutorial_tags.tag_name, '') AS category, "
		. "IFNULL(ciniki_tutorial_settings.detail_value, 99) AS catsequence, "
		. "ciniki_tutorials.title, "
		. "ciniki_tutorials.sequence, "
		. "ciniki_tutorials.primary_image_id, "
		. "ciniki_tutorials.content "
		. "FROM ciniki_tutorials "
		. "LEFT JOIN ciniki_tutorial_tags ON ("
			. "ciniki_tutorials.id = ciniki_tutorial_tags.tutorial_id "
			. "AND ciniki_tutorial_tags.tag_type = 10 "
			. "AND ciniki_tutorial_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_tutorial_settings ON ("
			. "CONCAT_WS('-', 'category', 'sequence', ciniki_tutorial_tags.permalink) = ciniki_tutorial_settings.detail_key "
			. "AND ciniki_tutorial_settings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_tutorials.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['tutorials']) . ") "
		. "ORDER BY catsequence, category, title, ciniki_tutorials.id "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tutorials', array(
		array('container'=>'categories', 'fname'=>'category',
			'fields'=>array('name'=>'category')),
		array('container'=>'tutorials', 'fname'=>'id',
			'fields'=>array('id', 'title', 'sequence', 'image_id'=>'primary_image_id', 'content')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2259', 'msg'=>'Unable to find tutorials'));
	} else {
		$categories = $rc['categories'];
	}
	
	//
	// Load the list of tutorials, and their steps
	//
	$strsql = "SELECT ciniki_tutorials.id, "
		. "ciniki_tutorial_steps.id AS step_id, "
		. "ciniki_tutorial_step_content.code, "
		. "ciniki_tutorial_step_content.title AS step_title, "
		. "ciniki_tutorial_step_content.image_id AS step_image_id, "
		. "ciniki_tutorial_step_content.content AS step_content "
		. "FROM ciniki_tutorials "
		. "LEFT JOIN ciniki_tutorial_steps ON ("
			. "ciniki_tutorials.id = ciniki_tutorial_steps.tutorial_id "
			. "AND ciniki_tutorial_steps.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_tutorial_step_content ON ("
			. "ciniki_tutorial_steps.step_content_id = ciniki_tutorial_step_content.id "
			. "AND ciniki_tutorial_step_content.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_tutorials.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_tutorials.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['tutorials']) . ") "
		. "ORDER BY ciniki_tutorials.id, ciniki_tutorial_steps.sequence, ciniki_tutorial_step_content.title "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tutorials', array(
		array('container'=>'tutorials', 'fname'=>'id',
			'fields'=>array('id')),
		array('container'=>'steps', 'fname'=>'step_id',
			'fields'=>array('id'=>'step_id', 'code', 'title'=>'step_title', 'image_id'=>'step_image_id', 'content'=>'step_content')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['tutorials']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2259', 'msg'=>'Unable to find tutorials'));
	} else {
		foreach($categories as $cid => $cat) {
			foreach($cat['tutorials'] as $tid => $tutorial) {
				if( isset($rc['tutorials'][$tid]['steps']) ) {
					$categories[$cid]['tutorials'][$tid]['steps'] = $rc['tutorials'][$tid]['steps'];
				}
			}
		}
	}

	if( count($categories) < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2260', 'msg'=>'Unable to find tutorials'));
	}
	
	//
	// Check for coverpage settings
	//
	if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
		$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tutorial_settings', 'business_id', $args['business_id'], 'ciniki.tutorials', 'settings', 'coverpage');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['settings']['coverpage-image']) ) {
			$args['coverpage-image'] = $rc['settings']['coverpage-image'];
		}
	}

	//
	// Generate PDF
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'templates', $args['layout']);
	$function = 'ciniki_tutorials_templates_' . $args['layout'];
	$rc = $function($ciniki, $args['business_id'], $categories, $args);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}

	if( isset($args['title']) && $args['title'] != '' ) {
		$filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $args['title']));
	} else {
		foreach($categories as $cat) {
			foreach($cat['tutorials'] as $tutorial) {
				$filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $tutorial['title']));
				break;
			}
			break;
		}
	}
	if( isset($rc['pdf']) ) {
		$rc['pdf']->Output($filename . '.pdf', 'D');
	}

	return array('stat'=>'exit');
}
?>
