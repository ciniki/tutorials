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
function ciniki_tutorials_objects($ciniki) {
	
	$objects = array();
	$objects['tutorial'] = array(
		'name'=>'Tutorials',
		'sync'=>'yes',
		'table'=>'ciniki_tutorials',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'synopsis'=>array(),
			'description'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			),
		'history_table'=>'ciniki_tutorial_history',
		);
	$objects['step'] = array(
		'name'=>'Tutorial Step',
		'sync'=>'yes',
		'table'=>'ciniki_tutorial_steps',
		'fields'=>array(
			'tutorial_id'=>array('ref'=>'ciniki.tutorials.tutorial'),
			'sequence'=>array(),
			'title'=>array(),
			'description'=>array(),
			),
		'history_table'=>'ciniki_tutorial_history',
		);
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
		'table'=>'ciniki_tutorial_images',
		'fields'=>array(
			'tutorial_id'=>array('ref'=>'ciniki.tutorials.tutorial'),
			'step_id'=>array('ref'=>'ciniki.tutorials.step'),
			'name'=>array(),
			'permalink'=>array(),
			'sequence'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_tutorial_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
