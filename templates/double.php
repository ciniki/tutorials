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
function ciniki_tutorials_templates_double($ciniki, $business_id, $tutorials, $args) {

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
		public $pagenumbers = 'yes';
		public $footer_height = 0;
		public $header_height = 0;
		public $footer_text = '';
		public function Header() {
			$this->SetFont('helvetica', 'B', 20);
			$this->SetLineWidth(0.25);
			$this->SetDrawColor(125);
			$this->setCellPaddings(5,1,5,2);
			$this->Cell(0, 22, $this->title, 'B', false, 'C', 0, '', 0, false, 'M', 'B');
			$this->setCellPaddings(0,0,0,0);
		}

		// Page footer
		public function Footer() {
			// Position at 15 mm from bottom
			// Set font
			if( $this->pagenumbers == 'yes' ) {
				$this->SetY(-10);
				$this->SetFont('helvetica', 'I', 8);
				$this->Cell(0, 8, $this->footer_text . '  --  Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
					0, false, 'C', 0, '', 0, false, 'T', 'M');
			}
		}

		public function AddSubPage($ciniki, $business_id, $offset, $page) {
			//
			// Calculate how many lines are required at the bottom of the page
			//
			$this->SetY($this->header_height);
			$nlines = 0;
			$details_height = 0;
			$img_box_width = ($this->getPageWidth()/2) - $this->middle_margin - $this->right_margin;
			$img_box_height = $this->getPageHeight() - $this->top_margin - $this->footer_height - $this->header_height;
			if( $page['subtitle'] != '' ) {
				$details_height += 14;
			} else {
				$details_height += 10;
			}
			if( $page['content'] != '' ) {
//				$nlines += $this->getNumLines($page['content'], ($this->getPageWidth()/2) - $this->middle_margin - $this->right_margin);
				$content = preg_split("/\n\s*\n/m", $page['content']);
				foreach($content as $cnt) {
					$details_height += $this->getStringHeight($img_box_width, $cnt);
					$details_height += 3;
				}
			}

//			error_log('nlines:' . $nlines);
//			error_log('Height:' . $this->getPageHeight());
//			$details_height += 0 + ($nlines * 9);
			$img_box_height -= ($details_height);
//			error_log('Height:' . $details_height);
//			error_log('Height:' . $img_box_height);

			//
			// Add the image title
			//
			if( $page['subtitle'] != '' ) {
				$this->SetX($offset);
				$this->SetFont('', 'B', '16');
				$this->Cell($img_box_width, 10, $page['subtitle'], 0, 1, 'L');
				$this->Ln(1);
			} else {
				$this->Ln(3);
			}
	
			if( $page['content'] != '' ) {
				foreach($content as $cnt) {
					$this->SetX($offset);
					$this->SetFont('', '', '12');
					$this->MultiCell($img_box_width, 5, $cnt, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
					$this->Ln(3);
				}
			}
			$this->Ln(2);

			//
			// Load the image
			//
			if( $page['image_id'] > 0 ) {
				$this->SetX($offset);
				$rc = ciniki_images_loadCacheOriginal($ciniki, $business_id, $page['image_id'], 2000, 2000);
				if( $rc['stat'] == 'ok' ) {
					$image = $rc['image'];
					$this->SetLineWidth(0.25);
					$this->SetDrawColor(50);
					$img = $this->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 1, 'CT');
				}
			}
		}

		public function AddMyPage($ciniki, $business_id, $title, $page1, $page2) {
			// Add a page
			$this->title = $title;
			$this->AddPage('L');
			$this->SetFillColor(255);
			$this->SetTextColor(0);
			$this->SetDrawColor(51);
			$this->SetLineWidth(0.15);
	
			$this->AddSubPage($ciniki, $business_id, $this->left_margin, $page1);
			if( $page2 != NULL) {
				$this->AddSubPage($ciniki, $business_id, $this->middle_margin + ($this->getPageWidth()/2), $page2);
			}
		}
	}

	//
	// Start a new document
	//
	$pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

	$pdf->title = $args['title'];

	// Set PDF basics
	$pdf->SetCreator('Ciniki');
	$pdf->SetAuthor($business_details['name']);
	$pdf->footer_text = $business_details['name'];
	$pdf->SetTitle($args['title']);
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set margins
	$pdf->header_height = 25;
	$pdf->footer_height = 5;
	$pdf->top_margin = 10;
	$pdf->left_margin = 8;
	$pdf->right_margin = 8;
	$pdf->middle_margin = 5;
	$pdf->SetMargins($pdf->left_margin, $pdf->header_height, $pdf->right_margin);
	$pdf->SetHeaderMargin($pdf->top_margin);
//	$pdf->SetFooterMargin($pdf->footer_height);
	$pdf->setPageOrientation('L', false);
	$pdf->SetFooterMargin(0);

	// Set font
	$pdf->SetFont('times', 'BI', 10);
	$pdf->SetCellPadding(0);

	//
	// Add the tutorials items
	//
	$page_num = 1;
	foreach($tutorials as $tid => $tutorial) {
		$pdf->title = $tutorial['title'];

		// 
		// Add introduction to tutorial
		//
		$prev = array('image_id'=>$tutorial['image_id'], 'subtitle'=>'', 'content'=>strip_tags($tutorial['content']));

		$step_num = 1;
		foreach($tutorial['steps'] as $sid => $step) {
			$step['number'] = $step_num;
			if( $prev == NULL ) {
				$prev = array('image_id'=>$step['image_id'], 'subtitle'=>'Step ' . $step['number'] . ' - ' . $step['title'], 'content'=>strip_tags($step['content']));
			} else {
				$pdf->AddMyPage($ciniki, $business_id, $tutorial['title'], $prev, 
					array('image_id'=>$step['image_id'], 'subtitle'=>'Step ' . $step['number'] . ' - ' . $step['title'], 'content'=>strip_tags($step['content'])));
				$prev = NULL;
			}
			$page_num++;
			$step_num++;
		}
		if( $prev != NULL ) {
			$pdf->AddMyPage($ciniki, $business_id, $tutorial['title'], $prev, NULL);
		}
	}

	return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
