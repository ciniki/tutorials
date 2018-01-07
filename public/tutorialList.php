<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the list from.
// 
// Returns
// -------
//
function ciniki_tutorials_tutorialList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'group'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Group'),
        'allcategories'=>array('required'=>'no', 'blank'=>'no', 'name'=>'All Categories'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        'categories'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Categories'), 
        'groups'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Groups'), 
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
    $rc = ciniki_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.tutorialList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    if( isset($args['allcategories']) && $args['allcategories'] == 'yes' ) {
        $strsql = "SELECT ciniki_tutorials.id, "
            . "ciniki_tutorials.title, "
            . "ciniki_tutorials.permalink, "
            . "ciniki_tutorials.sequence, "
            . "ciniki_tutorials.primary_image_id, "
            . "ciniki_tutorials.synopsis, "
            . "IFNULL(t2.tag_name, ' ') AS category, "
            . "'yes' AS is_details, "
            . "IFNULL(ciniki_tutorial_settings.detail_value, '99') AS cat_sequence "
            . "FROM ciniki_tutorials "
            . "";
        if( isset($args['group']) && $args['group'] != '' ) {
            $strsql .= "INNER JOIN ciniki_tutorial_tags AS t1 ON ("
                . "ciniki_tutorials.id = t1.tutorial_id "
                . "AND t1.tag_type = '40' "
                . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['group']) . "' "
                . "AND t1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
        $strsql .= "LEFT JOIN ciniki_tutorial_tags AS t2 ON ("
                . "ciniki_tutorials.id = t2.tutorial_id "
                . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND t2.tag_type = '10' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_settings ON ("
                . "ciniki_tutorial_settings.detail_key = CONCAT('category-sequence-', t2.permalink) "
                . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND t2.tag_type = '10' "
                . ") "
            . "WHERE ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//            . "AND (ciniki_tutorials.webflags&0x01) = 0x01 "
            . "ORDER BY cat_sequence, category, ciniki_tutorials.sequence, ciniki_tutorials.title "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
                'fields'=>array('name'=>'category')),
            array('container'=>'tutorials', 'fname'=>'id', 'name'=>'tutorial',
                'fields'=>array('id', 'title', 'sequence')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp = array('stat'=>'ok');
        if( !isset($rc['categories']) ) {
            $rsp['categories'] = array();
        } else {
            $rsp['categories'] = $rc['categories'];
        }
        return $rsp;
    }

    if( isset($args['category']) && $args['category'] != '' ) {
        $strsql = "SELECT ciniki_tutorials.id, "
            . "ciniki_tutorials.sequence, "
            . "IF((webflags&0x01)=1, 'published', 'unpublished') AS publishedstatus, "
            . "ciniki_tutorials.title, "    
            . "IFNULL(t2.tag_name, '') AS tags "
            . "FROM ciniki_tutorial_tags AS t1 "
            . "LEFT JOIN ciniki_tutorials ON ("
                . "t1.tutorial_id = ciniki_tutorials.id "
                . "AND ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_tags AS t2 ON ("
                . "ciniki_tutorials.id = t2.tutorial_id "
                . "AND t2.tag_type = 40 "
                . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE t1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND t1.tag_type = 10 "
            . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "ORDER BY publishedstatus, ciniki_tutorials.sequence, ciniki_tutorials.title "
            . "";
        if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'status', 'fname'=>'publishedstatus', 'name'=>'status',
                'fields'=>array('publishedstatus')),
            array('container'=>'tutorials', 'fname'=>'id', 'name'=>'tutorial',
                'fields'=>array('id', 'title', 'sequence', 'tags'),
                'dlists'=>array('tags'=>', ')),
            ));
    } 
    
    elseif( isset($args['group']) && $args['group'] != '' ) {
        $strsql = "SELECT ciniki_tutorials.id, "
            . "ciniki_tutorials.sequence, "
            . "IF((webflags&0x01)=1, 'published', 'unpublished') AS publishedstatus, "
            . "ciniki_tutorials.title, "    
            . "IFNULL(t2.tag_name, '') AS tags, "
            . "IFNULL(ciniki_tutorial_settings.detail_value, 99) AS catsequence "
            . "FROM ciniki_tutorial_tags AS t1 "
            . "LEFT JOIN ciniki_tutorials ON ("
                . "t1.tutorial_id = ciniki_tutorials.id "
                . "AND ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_tags AS t2 ON ("
                . "ciniki_tutorials.id = t2.tutorial_id "
                . "AND t2.tag_type = 10 "
                . "AND t2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_settings ON ("
                . "CONCAT_WS('-', 'category', 'sequence', t2.permalink) = ciniki_tutorial_settings.detail_key "
                . "AND ciniki_tutorial_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE t1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND t1.tag_type = 40 "
            . "AND t1.permalink = '" . ciniki_core_dbQuote($ciniki, $args['group']) . "' "
            . "ORDER BY publishedstatus, catsequence, ciniki_tutorials.sequence, ciniki_tutorials.title "
            . "";
        if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'status', 'fname'=>'publishedstatus', 'name'=>'status',
                'fields'=>array('publishedstatus')),
            array('container'=>'tutorials', 'fname'=>'id', 'name'=>'tutorial',
                'fields'=>array('id', 'title', 'sequence', 'tags'),
                'dlists'=>array('tags'=>', ')),
            ));
    } 
    
    else {
        $strsql = "SELECT ciniki_tutorials.id, "
            . "ciniki_tutorials.sequence, "
            . "ciniki_tutorials.title, "
            . "IF((webflags&0x01)=1, 'published', 'unpublished') AS publishedstatus, "
            . "ciniki_tutorial_tags.tag_name "  
            . "FROM ciniki_tutorials "
            . "LEFT JOIN ciniki_tutorial_tags ON ("
                . "ciniki_tutorials.id = ciniki_tutorial_tags.tutorial_id "
                . "AND ciniki_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "HAVING ISNULL(tag_name) "
            . "ORDER BY publishedstatus, ciniki_tutorials.sequence, title "
            . "";
        if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'status', 'fname'=>'publishedstatus', 'name'=>'status',
                'fields'=>array('publishedstatus')),
            array('container'=>'tutorials', 'fname'=>'id', 'name'=>'tutorial',
                'fields'=>array('id', 'title', 'sequence')),
            ));
    }

    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = array('stat'=>'ok');
    if( !isset($rc['status']) ) {
        $rsp['published'] = array();
        $rsp['unpublished'] = array();
    } else {
        foreach($rc['status'] as $status) {
            if( $status['status']['publishedstatus'] == 'published' && isset($status['status']['tutorials']) ) {
                $rsp['published'] = $status['status']['tutorials'];
            }
            if( $status['status']['publishedstatus'] == 'unpublished' && isset($status['status']['tutorials']) ) {
                $rsp['unpublished'] = $status['status']['tutorials'];
            }
        }
    }

    //
    // Check if we should return categories as well
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT ciniki_tutorial_tags.tag_name, "
            . "ciniki_tutorial_tags.permalink, "
            . "COUNT(ciniki_tutorials.id) AS num_tutorials, "
            . "IFNULL(ciniki_tutorial_settings.detail_value, 99) AS catsequence "
            . "FROM ciniki_tutorial_tags "
            . "LEFT JOIN ciniki_tutorials ON ("
                . "ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
                . "AND ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_settings ON ("
                . "CONCAT_WS('-', 'category', 'sequence', ciniki_tutorial_tags.permalink) = ciniki_tutorial_settings.detail_key "
                . "AND ciniki_tutorial_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_tutorial_tags.tag_type = '10' "
            . "GROUP BY tag_name "
            . "ORDER BY catsequence, tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'categories', 'fname'=>'tag_name', 'name'=>'category',
                'fields'=>array('name'=>'tag_name', 'permalink', 'num_tutorials')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['categories']) ) {
            $rsp['categories'] = array();
        } else {
            $rsp['categories'] = $rc['categories'];
        }
    }

    //
    // Check if we should return groups as well
    //
    if( isset($args['groups']) && $args['groups'] == 'yes' ) {
        $strsql = "SELECT ciniki_tutorial_tags.tag_name, "
            . "ciniki_tutorial_tags.permalink, "
            . "COUNT(ciniki_tutorials.id) AS num_tutorials, "
            . "IFNULL(ciniki_tutorial_settings.detail_value, 99) AS catsequence "
            . "FROM ciniki_tutorial_tags "
            . "LEFT JOIN ciniki_tutorials ON ("
                . "ciniki_tutorial_tags.tutorial_id = ciniki_tutorials.id "
                . "AND ciniki_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_tutorial_settings ON ("
                . "CONCAT_WS('-', 'category', 'sequence', ciniki_tutorial_tags.permalink) = ciniki_tutorial_settings.detail_key "
                . "AND ciniki_tutorial_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_tutorial_tags.tag_type = '40' "
            . "GROUP BY tag_name "
            . "ORDER BY catsequence, tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tutorials', array(
            array('container'=>'groups', 'fname'=>'tag_name', 'name'=>'group',
                'fields'=>array('name'=>'tag_name', 'permalink', 'num_tutorials')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['groups']) ) {
            $rsp['groups'] = array();
        } else {
            $rsp['groups'] = $rc['groups'];
        }
    }

    return $rsp;
}
?>
