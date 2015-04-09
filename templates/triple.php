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
function ciniki_tutorials_templates_triple($ciniki, $business_id, $categories, $args) {

	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');

	//
	// Load business details
	//
	$rc = ciniki_businesses_businessDetails($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['details']) && is_array($rc['details']) ) {	
		$business_details = $rc['details'];
	} else {
		$business_details = array();
	}

	//
	// Load INTL settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Create a custom class for this document
	//
	class MYPDF extends TCPDF {
		public $business_name = '';
		public $title = '';
		public $pagenumbers = 'no';
		public $coverpage = 'no';
		public $toc = 'no';
		public $toc_categories = 'no';
		public $footer_height = 0;
		public $header_height = 0;
		public $footer_text = '';

		public function Header() {
			$this->SetFont('helvetica', 'B', 20);
			$this->SetLineWidth(0.25);
			$this->SetDrawColor(125);
			$this->setCellPaddings(5,1,5,2);
			if( $this->title != '' ) {
				$this->Cell(0, 22, $this->title, 'B', false, 'C', 0, '', 0, false, 'M', 'B');
			}
			$this->setCellPaddings(0,0,0,0);
		}

		// Page footer
		public function Footer() {
			// Position at 15 mm from bottom
			// Set font
			if( $this->pagenumbers == 'yes' ) {
				$this->SetY(-15);
				$this->SetFont('helvetica', 'I', 8);
				if( $this->toc == 'yes' ) {
					$this->Cell(0, 8, $this->footer_text . '  --  Page ' . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 
						0, false, 'C', 0, '', 0, false, 'T', 'M');
				} else {
					$this->Cell(0, 8, $this->footer_text . '  --  Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
						0, false, 'C', 0, '', 0, false, 'T', 'M');
				}
			}
		}

		public function AddMySection($ciniki, $business_id, $section) {
			$cnt_height = ($this->getPageHeight() - $this->top_margin - $this->header_height - $this->footer_height);
			$cnt_box_width = 180;
			$image = NULL;
			if( $section['image_id'] > 0 ) {
				$rc = ciniki_images_loadCacheOriginal($ciniki, $business_id, $section['image_id'], 2000, 2000);
				if( $rc['stat'] == 'ok' ) {
					$image = $rc['image'];
					$cnt_box_width = (($this->getPageWidth() - $this->left_margin - $this->right_margin - $this->middle_margin)/2) + 20;
				}
			}
			$cnt_box_height = ($cnt_height/3);
			$details_height = 0;
			if( $section['content'] != '' ) {
				$content = preg_split("/\n\s*\n/m", $section['content']);
				foreach($content as $cnt) {
					$details_height += $this->getStringHeight($cnt_box_width, $cnt);
					$details_height += 3;
				}
			}
			if( $section['subtitle'] != '' ) {
				$subtitle_height = 14;
			} else {
				$subtitle_height = 6;
			}
			$img_box_width = (180 - $cnt_box_width);
			if( $image != NULL ) {
				$img_box_height = $cnt_box_height - $subtitle_height;
			} else {
				$img_box_height = $subtitle_height;
			}

			//
			// Check if we have enough room
			//
//			error_log($this->getY() . '/' . $cnt_height . ':' . $details_height . ' + ' . $subtitle_height . ' + ' . ($details_height>$img_box_height?$details_height:$img_box_height));
			if( ($this->getY() + $subtitle_height + ($img_box_height>$details_height?$img_box_height:$details_height) - $this->top_margin - $this->header_height) > ($cnt_height) ) {
				// Add a page
				$this->AddPage('P');
				$this->SetFillColor(255);
				$this->SetTextColor(0);
				$this->SetDrawColor(51);
				$this->SetLineWidth(0.15);
			}

			$this->SetX($this->left_margin);
			if( $section['subtitle'] != '' ) {
				$this->SetFont('', 'B', '16');
				$this->Cell(180, 10, $section['subtitle'], 0, 1, 'L');
				$this->Ln(1);
			} else {
				$this->Ln(1);
			}

			//
			// Load the image
			//
			if( $image != NULL ) {
				$image = $rc['image'];
				$this->SetLineWidth(0.25);
				$this->SetDrawColor(50);
				$img = $this->Image('@'.$image, '', '', $img_box_width, $img_box_height-4, 'JPEG', '', '', false, 300, '', false, false,
					array('LTRB'=>array('color'=>array(128,128,128))), 'CT');
				$new_y = $this->getY() + ($img_box_height>$details_height?$img_box_height:$details_height); // - $this->top_margin - $this->header_height;
			} else {
				$new_y = $this->getY() + $details_height; // - $this->top_margin - $this->header_height;
			}
		
			if( $section['content'] != '' ) {
				foreach($content as $cnt) {
					if( $image != NULL || $section['subtitle'] != '' ) {
						$this->SetX($this->left_margin + $this->middle_margin + $img_box_width);
						if( $image == NULL ) {
							$cnt_box_width = 180 - $this->middle_margin - $this->middle_margin;
						}
					} else {
						$this->SetX($this->left_margin);
					}
					$this->SetFont('', '', '12');
					$this->MultiCell($cnt_box_width, 5, $cnt, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
					$this->Ln(2);
				}
			}

			$this->SetY($new_y);
		}
	}

	//
	// Start a new document
	//
	$pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

	$pdf->title = $args['title'];

	// Set PDF basics
	$pdf->SetCreator('Ciniki');
	$pdf->SetAuthor($business_details['name']);
	$pdf->footer_text = $business_details['name'];
	$pdf->SetTitle($args['title']);
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set margins
	$pdf->header_height = 28;
	$pdf->footer_height = 10;
	$pdf->top_margin = 15;
	$pdf->left_margin = 15;
	$pdf->right_margin = 15;
	$pdf->middle_margin = 6;
	$pdf->SetMargins($pdf->left_margin, $pdf->header_height, $pdf->right_margin);
	$pdf->SetHeaderMargin($pdf->top_margin);
//	$pdf->SetFooterMargin($pdf->footer_height);
	$pdf->setPageOrientation('P', false);
	$pdf->SetFooterMargin(0);

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
		} else {
			$title = "Tutorials";
		}
		$pdf->pagenumbers = 'no';
		$pdf->AddPage('P');
		
		if( isset($args['coverpage-image']) && $args['coverpage-image'] > 0 ) {
			$img_box_width = 180;
			$img_box_height = 150;
			$rc = ciniki_images_loadCacheOriginal($ciniki, $business_id, $args['coverpage-image'], 2000, 2000);
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
	foreach($categories as $cid => $category) {
		$tutorial_num = 1;
		foreach($category['tutorials'] as $tid => $tutorial) {
			$pdf->title = $tutorial['title'];
			// Start a new page
			$pdf->AddPage('P');
			$pdf->SetFillColor(255);
			$pdf->SetTextColor(0);
			$pdf->SetDrawColor(51);
			$pdf->SetLineWidth(0.15);
			// 
			// Add introduction to tutorial
			//
			$pdf->AddMySection($ciniki, $business_id, 
				array('image_id'=>$tutorial['image_id'], 'subtitle'=>'', 'content'=>strip_tags($tutorial['content'])));
			// Add a table of contents bookmarks
			if( isset($args['toc']) && $args['toc'] == 'yes' ) {
				if( $pdf->toc_categories == 'yes' && $tutorial_num == 1 ) {
					$pdf->Bookmark($category['name'], 0, 0, '', '');
				}
				if( $pdf->toc_categories == 'yes' ) {
					$pdf->Bookmark($pdf->title, 1, 0, '', '');
				} else {
					$pdf->Bookmark($pdf->title, 0, 0, '', '');
				}
			}

			$step_num = 1;
			if( isset($tutorial['steps']) ) {
				foreach($tutorial['steps'] as $sid => $step) {
					$step['number'] = $step_num;
					$pdf->AddMySection($ciniki, $business_id, 
						array('image_id'=>$step['image_id'], 'subtitle'=>'Step ' . $step['number'] . ' - ' . $step['title'], 'content'=>strip_tags($step['content'])));
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
		$pdf->addTOC(($pdf->coverpage=='yes'?2:0), 'courier', '.', 'INDEX', 'B');
		$pdf->pagenumbers = 'yes';
		$pdf->endTOCPage();
	}

	return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
