<?php
//
// Description
// -----------
// This function will output a pdf document as a series of thumbnails.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tutorials_templates_single($ciniki, $tnid, $categories, $args) {

    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheJPEG');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');

    //
    // Load tenant details
    //
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Create a custom class for this document
    //
    class MYPDF extends TCPDF {
        public $tenant_name = '';
        public $title = '';
        public $coverpage = 'no';
        public $toc = 'no';
        public $toc_categories = 'no';
        public $doublesided = 'no';
        public $pagenumbers = 'yes';
        public $footer_height = 0;
        public $header_height = 0;
        public $footer_text = '';

        public function Header() {
            $this->SetFont('helvetica', 'B', 20);
            if( $this->title != '' ) {
                $this->Cell(0, 20, $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'B');
            }
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            // Set font
            if( $this->pagenumbers == 'yes' ) {
                $this->SetY(-18);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, $this->footer_text . '  --  Page ' . $this->getAliasNumPage().' of '.$this->getAliasNbPages(), 
                    0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }

        public function AddMyPage($ciniki, $tnid, $category_title, $title, $image_id, $subtitle, $content) {
            // Add a page
            $this->title = $title;
            $this->AddPage();
            $this->SetFillColor(255);
            $this->SetTextColor(0);
            $this->SetDrawColor(51);
            $this->SetLineWidth(0.15);
        
            // Add a table of contents bookmarks
            if( $this->toc == 'yes' && $category_title !== NULL ) {
                if( $this->toc_categories == 'yes' && $category_title != '' ) {
                    $this->Bookmark($category_title, 0, 0, '', '');
                }
                if( $this->toc_categories == 'yes' ) {
                    $this->Bookmark($this->title, 1, 0, '', '');
                } else {
                    $this->Bookmark($this->title, 0, 0, '', '');
                }
            }

            //
            // Calculate how many lines are required at the bottom of the page
            //
            $nlines = 0;
            $details_height = 0;
            if( $subtitle != '' ) {
                $details_height += 12;
            }
            if( $content != '' ) {
                $nlines += $this->getNumLines($content, $this->getPageWidth() - $this->left_margin - $this->right_margin);
            }

            $img_box_width = $this->getPageWidth() - $this->left_margin - $this->right_margin;
            $img_box_height = $this->getPageHeight() - $this->footer_height - $this->header_height;
            $details_height += 0 + ($nlines * 6);
            $img_box_height -= ($details_height);

            //
            // Add the image title
            //
            if( $subtitle != '' ) {
                $this->SetFont('', 'B', '16');
                $this->Cell($img_box_width, 12, $subtitle, 0, 1, 'L');
            }
        
            if( $content != '' ) {
                $this->SetFont('', '', '12');
                $this->MultiCell($img_box_width, 8, $content, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
            }
            $this->Ln(6);

            //
            // Load the image
            //
            if( $image_id > 0 ) {
                $rc = ciniki_images_loadCacheJPEG($ciniki, $tnid, $image_id, 2000, 2000);
                if( $rc['stat'] == 'ok' ) {
                    $image = $rc['image'];
                    $this->SetLineWidth(0.25);
                    $this->SetDrawColor(50);
                    $img = $this->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 
                        array('LTRB'=>array('color'=>array(128,128,128))), 'CT');
                }
            }
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    $pdf->title = $args['title'];

    // Set PDF basics
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->footer_text = $tenant_details['name'];
    $pdf->SetTitle($args['title']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    if( isset($args['doublesided']) ) {
        $pdf->doublesided = $args['doublesided'];
    }

    // set margins
    $pdf->header_height = 25;
    $pdf->footer_height = 15;
    $pdf->top_margin = 10;
    $pdf->left_margin = 25;
    $pdf->right_margin = 25;
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);
    $pdf->SetFooterMargin($pdf->footer_height);

    // Set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(0);

    //
    // Check if coverpage is to be outputed
    //
    if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
        $pdf->coverpage = 'yes';
        $pdf->title = '';
        if( isset($args['title']) && $args['title'] != '' ) {
            $title = $args['title'];
            $pdf->footer_text .= '  --  ' . $args['title'];
        } else {
            $title = "Tutorials";
        }
        $pdf->pagenumbers = 'no';
        $pdf->AddPage('P');
        
        if( isset($args['coverpage-image']) && $args['coverpage-image'] > 0 ) {
            $img_box_width = 180;
            $img_box_height = 150;
            $rc = ciniki_images_loadCacheJPEG($ciniki, $tnid, $args['coverpage-image'], 2000, 2000);
            if( $rc['stat'] == 'ok' ) {
                $image = $rc['image'];
                $pdf->SetLineWidth(0.25);
                $pdf->SetDrawColor(50);
                $img = $pdf->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 0, 'CT');
            }
            $pdf->SetY(-50);
        } else {
            $pdf->SetY(-100);
        }


        $pdf->SetFont('times', 'B', '30');
        $pdf->MultiCell(180, 5, $title, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
        $pdf->endPage();
        if( $pdf->doublesided == 'yes' ) {
            $pdf->AddPage();
            $pdf->Cell(0, 0, '');
            $pdf->endPage();
        }
    }
    $pdf->pagenumbers = 'yes';

    //
    // Add the tutorials items
    //
    $page_num = 1;
    $pdf->toc_categories = 'no';
    if( count($categories) > 1 ) {
        $pdf->toc_categories = 'yes';
    }
    if( isset($args['toc']) && $args['toc'] == 'yes' ) {
        $pdf->toc = 'yes';
    }
    if( $pdf->toc == 'yes' && $pdf->doublesided == 'yes' ) {
        $pdf->AddPage();
        $pdf->Cell(0, 0, '');
        $pdf->endPage();
    }

    foreach($categories as $cid => $category) {
        $tutorial_num = 1;
        foreach($category['tutorials'] as $tid => $tutorial) {
            if( isset($args['removetext']) && $args['removetext'] != '' ) {
                $tutorial['title'] = preg_replace('/' . $args['removetext'] . '/', '', $tutorial['title']);
            }
            $pdf->title = $tutorial['title'];

            // 
            // Add introduction to tutorial
            //
            $pdf->AddMyPage($ciniki, $tnid, ($tutorial_num==1&&isset($category['name'])?$category['name']:''), $tutorial['title'], $tutorial['image_id'], '', strip_tags($tutorial['content']));

            $step_num = 1;
            if( isset($tutorial['steps']) ) {
                $num_steps = count($tutorial['steps']);
                foreach($tutorial['steps'] as $sid => $step) {
                    $pdf->AddMyPage($ciniki, $tnid, NULL, $tutorial['title'], $step['image_id'], 'Step ' . $step_num . ' of ' . $num_steps . ' - ' . $step['title'], strip_tags($step['content']));
                    $page_num++;
                    $step_num++;
                }
            }
            $tutorial_num++;    
        }
    }

    if( isset($args['toc']) && $args['toc'] == 'yes' ) {
        $pdf->title = 'Table of Contents';
        $pdf->addTOCPage();
        $pdf->Ln(8);
        $pdf->SetFont('', '', 14);
        $pdf->pagenumbers = 'no';
        $pdf->addTOC((($pdf->coverpage=='yes')?($pdf->doublesided=='yes'?3:2):0), 'courier', '.', 'INDEX', 'B');
        $pdf->pagenumbers = 'yes';
        $pdf->endTOCPage();
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
