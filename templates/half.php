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
function ciniki_tutorials_templates_half($ciniki, $business_id, $tutorials, $args) {

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
		public $doublesided = 'no';
		public $header_height = 0;

		public function Header() {
			$this->SetFont('helvetica', 'B', 20);
			$this->Cell(0, 20, $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'B');
		}

		// Page footer
		public function Footer() {
			// Position at 15 mm from bottom
			// Set font
			if( $this->pagenumbers == 'yes' ) {
				$this->SetY(-15);
				$this->SetFont('helvetica', 'I', 8);
				$this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
					0, false, 'C', 0, '', 0, false, 'T', 'M');
			}
		}

		public function AddMyPage($ciniki, $business_id, $title, $image_id, $subtitle, $content) {
			// Add a page
			$this->title = $title;
			$this->AddPage();
			$this->SetFillColor(255);
			$this->SetTextColor(0);
			$this->SetDrawColor(51);
			$this->SetLineWidth(0.15);
		
			//
			// Calculate how many lines are required at the bottom of the page
			//
			$nlines = 0;
			$details_height = 0;
			if( $subtitle != '' ) {
				$details_height += 12;
			}
			if( $content != '' ) {
				$nlines += $this->getNumLines($content, 120);
			}

			$img_box_width = 120;
			$img_box_height = $this->getPageHeight() - $this->footer_height - $this->header_height;
			$details_height += 15 + ($nlines * 5);
			$img_box_height -= ($details_height);

			//
			// Add the image title
			//
			if( $subtitle != '' ) {
				$this->SetFont('', 'B', '14');
				$this->Cell(120, 10, $subtitle, 0, 1, 'L');
			}
		
			if( $content != '' ) {
				$this->SetFont('', '', '11');
				$this->MultiCell(120, 5, $content, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
			}
			$this->Ln(5);

			//
			// Load the image
			//
			if( $image_id > 0 ) {
				$rc = ciniki_images_loadCacheOriginal($ciniki, $business_id, $image_id, 2000, 2000);
				if( $rc['stat'] == 'ok' ) {
					$image = $rc['image'];
					$this->SetLineWidth(0.25);
					$this->SetDrawColor(50);
					$img = $this->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 1, 'CT');
				}
			}
		}
	}

	//
	// Start a new document
	//
	$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'ORGANIZERL', true, 'UTF-8', false);

	$pdf->title = $args['title'];

	// Set PDF basics
	$pdf->SetCreator('Ciniki');
	$pdf->SetAuthor($business_details['name']);
	$pdf->SetTitle($args['title']);
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set margins
	$pdf->header_height = 18;
	$pdf->footer_height = 5;
	$pdf->SetMargins(10, $pdf->header_height, 10);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin($pdf->footer_height);

	// Set font
	$pdf->SetFont('times', 'BI', 10);
	$pdf->SetCellPadding(0);

	if( isset($args['doublesided']) ) {
		$pdf->doublesided = $args['doublesided'];
	}

	//
	// Add the tutorials items
	//
	$page_num = 1;
	foreach($tutorials as $tid => $tutorial) {
		$pdf->title = $tutorial['title'];

		// 
		// Add introduction to tutorial
		//
		$pdf->AddMyPage($ciniki, $business_id, $tutorial['title'], $tutorial['image_id'], '', strip_tags($tutorial['content']));

		$step_num = 1;
		foreach($tutorial['steps'] as $sid => $step) {
			$pdf->AddMyPage($ciniki, $business_id, $tutorial['title'], $step['image_id'], 'Step ' . $step_num . ' - ' . $step['title'], strip_tags($step['content']));
			$page_num++;
			$step_num++;
		}
	}

	return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
