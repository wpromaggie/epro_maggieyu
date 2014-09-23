<?php
/**
 * Invoice or Receipt PDF.
 */
util::load_lib('fpdf');

abstract class InvoiceReceiptPDF extends FPDF
{
	const line_thin = 0.01;
	const line_thick = 0.025;

	protected $type_title;
	protected $type_bill_line;
	protected $type_pay_line;

	public $client;
	public $contact;
	public $date;
	public $invoice_num;

	public $address_1;
	public $address_2;
	public $address_3;
	
	public $wpro_phone;
	
	public $charges;

	public $notes;

	abstract protected function initType();

	public function __construct()
	{
		parent::__construct('P','in','Letter');

		//Set up type info
		$this->initType();

		//Set up document
		$this->SetDisplayMode('fullpage');
		$this->SetMargins(0.75,1);
		$this->SetFont('Arial','',12);
		$this->SetLineWidth(self::line_thin);
	}

	public function MakeThingsHappen()
	{
		$this->AddPage();

		//Wpro Logo
		$this->Image(\epro\CGI_PATH.'img/wprom_logo.jpg',2.75,1,3);
		$this->Ln(2.0);

		//Company Info
		$this->SetFont('Arial','',12);
		$this->Cell(0, 0.25, 'Wpromote, Inc. - 1700 E. Walnut Ave, Fifth Floor - El Segundo, CA 90245', 'T', 1, 'C');
		$this->Cell(0, 0.25, $this->wpro_phone, 'B', 1, 'C');

		//Invoice/Receipt
		$this->SetFont('Arial','B',16);
		$this->Cell(0,0.75,$this->type_title,'',1,'C');

		//Client Info
		$this->write_meta();

		//Addresses
		$addy_top = $this->GetY();

		$this->SetLeftMargin(1.25);
		$this->SetFont('Arial', 'B', 12);
		$this->SetTextColor(70,130,180);
		$this->Cell(3, 0.3, $this->type_bill_line, 0, 1, 'L');
		$this->Ln(0.1);
		$this->SetFont('Arial', '', 12);
		$this->SetTextColor(0);
		$this->MultiCell(3, 0.2, $this->address_1."\n".$this->address_2."\n".$this->address_3, 0, 'L');

		$this->SetLeftMargin(4.75);
		$this->SetX(4.75);
		$this->SetY($addy_top);
		$this->SetFont('Arial', 'B', 12);
		$this->SetTextColor(70,130,180);
		$this->Cell(3, 0.3, $this->type_pay_line, 0, 1, 'L');
		$this->Ln(0.1);
		$this->SetFont('Arial', '', 12);
		$this->SetTextColor(0);
		$this->MultiCell(3, 0.2, "Wpromote, Inc.\n1700 E. Walnut Ave, Fifth Floor\nEl Segundo, CA 90245", 0, 'L');

		$this->SetLeftMargin(0.75);
		$this->Ln(0.3);

		//Billing Items
		$this->SetFont('Arial', 'B', 12);
		$this->SetTextColor(70,130,180);
		$this->SetLineWidth(self::line_thick);
		$this->Cell(0.25, 0.3, '', 'TB', 0);
		$this->Cell(5.0, 0.3, 'Description', 'TB', 0, 'L');
		$this->Cell(1.5, 0.3, 'Charges', 'TB', 0, 'R');
		$this->Cell(0.25, 0.3, '', 'TB', 1);
		$this->Ln(0.03);

		$this->SetFont('Arial', '', 12);
		$this->SetTextColor(0);
		$this->SetLineWidth(self::line_thin);
		$charge_total = 0;
		foreach ($this->charges as $description => $charge) {
			$charge_total += $charge;
			$this->Cell(0.25, 0.3, '', 'B', 0);
			$this->Cell(5.0, 0.3, $description, 'B', 0, 'L');
			$this->Cell(1.5, 0.3, util::format_dollars($charge), 'B', 0, 'R');
			$this->Cell(0.25, 0.3, '', 'B', 1);
		}

		$this->SetLineWidth(self::line_thick);
		$this->Cell(0.25, 0.3, '', 'B', 0);
		$this->Cell(5.0, 0.3, 'Total Charges', 'B', 0, 'L');
		$this->Cell(1.5, 0.3, util::format_dollars($charge_total), 'B', 0, 'R');
		$this->Cell(0.25, 0.3, '', 'B', 1);
		$this->Ln(0.3);

		//Notes
		if ('' != $this->notes) {
			$this->SetLeftMargin(1.0);

			$this->SetFont('Arial', 'B', 12);
			$this->SetTextColor(70,130,180);
			$this->Cell(3, 0.3, 'Notes:', 0, 1, 'L');

			$this->SetFont('Arial', '', 12);
			$this->SetTextColor(0);
			$this->MultiCell(6.5, 0.2, $this->notes, 0, 'L');

			$this->SetLeftMargin(0.75);
		}
	}

	protected function write_meta()
	{
		$this->SetLineWidth(self::line_thick);
		$this->Cell(0,0,'','T',1);

		$this->SetFont('Arial', 'B', 12);
		$this->SetTextColor(70,130,180);
		$this->SetLineWidth(self::line_thin);
		$this->Cell(2.75, 0.3, 'Client', 'B', 0, 'C');
		$this->Cell(1.75, 0.3, 'Contact', 'B', 0, 'C');
		$this->Cell(1.25, 0.3, 'Date', 'B', 0, 'C');
		$this->Cell(1.25, 0.3, $this->type_title.' No', 'B', 1, 'C');

		$this->SetFont('Arial', '', 12);
		$this->SetTextColor(0);
		$this->SetLineWidth(self::line_thick);
		$this->Cell(2.75, 0.3, $this->client, 'B', 0, 'C');
		$this->Cell(1.75, 0.3, $this->contact, 'B', 0, 'C');
		$this->Cell(1.25, 0.3, $this->date, 'B', 0, 'C');
		$this->Cell(1.25, 0.3, $this->invoice_num, 'B', 1, 'C');
		$this->Ln(0.3);
	}
}

class InvoicePDF extends InvoiceReceiptPDF
{
	protected function initType()
	{
		$this->type_title = 'Invoice';
		$this->type_bill_line = 'Bill To:';
		$this->type_pay_line = 'Remit Payment To:';
	}
}

class ReceiptPDF extends InvoiceReceiptPDF
{
	protected function initType()
	{
		$this->type_title = 'Receipt';
		$this->type_bill_line = 'Billed To:';
		$this->type_pay_line = 'Payment Received By:';
	}
}

class SBP_ReceiptPDF extends ReceiptPDF
{
	protected function write_meta()
	{
		$this->SetLineWidth(self::line_thick);
		$this->Cell(0,0,'','T',1);

		$this->SetFont('Arial', 'B', 12);
		$this->SetTextColor(70,130,180);
		$this->SetLineWidth(self::line_thin);
		$this->Cell(3.5, 0.3, 'Date', 'B', 0, 'C');
		$this->Cell(3.5, 0.3, $this->type_title.' No', 'B', 1, 'C');

		$this->SetFont('Arial', '', 12);
		$this->SetTextColor(0);
		$this->SetLineWidth(self::line_thick);
		$this->Cell(3.5, 0.3, date(util::US_DATE, strtotime($this->date)), 'B', 0, 'C');
		$this->Cell(3.5, 0.3, $this->invoice_num, 'B', 1, 'C');
		$this->Ln(0.3);
	}
}

?>
