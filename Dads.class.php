<?php
require('fpdf.php');
 // IMAGETYPE is the directory that we're getting the source images - we'll use thumbnails at the moment, as quality doesn't matter; it might matter in future if we use seam carving
define("IMAGETYPE", 'thumbnails');

class PDF extends FPDF
{
    public $sourcefilename = '';


    const DPI = 150;
    const MM_IN_INCH = 25.4;
    const A4_HEIGHT = 297;
    const A4_WIDTH = 210;
    // tweak these values (in pixels)
    const MAX_WIDTH = 1650;
    const MAX_HEIGHT = 1150;

    // Page header
    function Header()
    {
        // Logo
        // $this->Image('logo.png',10,6,30);
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30,10,"BROW00003 - Ian's Death, Funeral, Power of Attorney and related materials",1,0,'C');
        // Line break
        $this->Ln(20);
    }

// Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Filename - Left justify
        $this->Cell(0,10,basename($this->sourcefilename),0,0,'L');
        // Page number - Right Justify
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'R');
    }

// We use this function for pretty printing bullated lists
    function MultiCellBltArray($w, $h, $blt_array, $border=0, $align='J', $fill=false)
    {
        if (!is_array($blt_array))
        {
            die('MultiCellBltArray requires an array with the following keys: bullet, margin, text, indent, spacer');
            exit;
        }

        //Save x
        $bak_x = $this->x;

        for ($i=0; $i<sizeof($blt_array['text']); $i++)
        {
            //Get bullet width including margin
            $blt_width = $this->GetStringWidth($blt_array['bullet'] . $blt_array['margin'])+$this->cMargin*2;

            // SetX
            $this->SetX($bak_x);

            //Output indent
            if ($blt_array['indent'] > 0)
                $this->Cell($blt_array['indent']);

            //Output bullet
            $this->Cell($blt_width, $h, $blt_array['bullet'] . $blt_array['margin'], 0, '', $fill);

            //Output text
            $this->MultiCell($w-$blt_width, $h, $blt_array['text'][$i], $border, $align, $fill);

            //Insert a spacer between items if not the last item
            if ($i != sizeof($blt_array['text'])-1)
                $this->Ln($blt_array['spacer']);

            //Increment bullet if it's a number
            if (is_numeric($blt_array['bullet']))
                $blt_array['bullet']++;
        }

        //Restore x
        $this->x = $bak_x;
    }

    function pixelsToMM($val) {
        return $val * self::MM_IN_INCH / self::DPI;
    }
    function resizeToFit($imgFilename) {
        list($width, $height) = getimagesize($imgFilename);
        $widthScale = self::MAX_WIDTH / $width;
        $heightScale = self::MAX_HEIGHT / $height;
        $scale = min($widthScale, $heightScale);
        return array(
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        );
    }

    function centreImage($img) {
        list($width, $height) = $this->resizeToFit($img);

        if ($width < $height) {
            $this->Image(
                $img, (self::A4_HEIGHT - $width) / 2,
                (self::A4_WIDTH - $height) / 2,
                $width,
                $height
            );
        } else {
            $this->Image(
                $img,
                (self::A4_WIDTH - $height) / 2, (self::A4_HEIGHT - $width) / 2,
                $height,$width
            );
        }
    }
}

// A PHP5 compatible clone of PHP7's dirname function
function dirname_r($path, $count=1){
    if ($count > 1){
        return dirname(dirname_r($path, --$count));
    }else{
        return dirname($path);
    }
}

// Recursively archive/zip a collection
function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source) === true) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

function htmlPrettyPrint ($htmlstring)
{
    $bits = explode("\n", $htmlstring);
    $htmlstring = "<ul>";
    foreach($bits as $bit)
    {
        $htmlstring .= "<li>" . $bit . "</li>";
    }
    $htmlstring .= "</ul>";

    return $htmlstring;
}

function pdfPrettyPrint ($pdf, $pdfstring)
{
    $test1 = array();
    $test1['bullet'] = chr(149);
    $test1['margin'] = ' ';
    $test1['indent'] = 0;
    $test1['spacer'] = 0;
    $test1['text'] = array();
    $i = 0;

    $bits = explode("\n", $pdfstring);

    foreach ($bits as $bit) {
        $test1['text'][$i] = $bit;
        $i++;
    }

    $pdf->SetX(10);
    $pdf->MultiCellBltArray(190, 6, $test1);
}

function pushImage($pdf, $file)
{
    // Should the page orientation be Landscape, or portrait?
    list($width, $height) = getimagesize($file);

    if ($width > $height)
    {
            $pdf->Addpage ('L');
    }
        else
        {
            $pdf->Addpage ('P');
        }
    $pdf->centreImage($file);
}

// Recursively generate PDF of a collection
function generatePDF($source, $destination, $recipient)
{
    $pdf = new PDF();
    $pdf->SetAutoPageBreak(true);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->sourcefilename = '';
    //$pdf->MultiCell(180,10,'This archive has been provided to '.$recipient.' under the following conditions: '.ACCESS_CONDITIONS);
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'Source Statement:');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(180,6,'These materials are digital surrogates of part of the Brown Family Collection held and managed by  Australian Manuscripts Collection, State Library of Victoria, manuscript number MS 13489. These materials were discovered on the web via a finding aid, The Brown Family Guide to Records, initially published by the Australian Science and Technology Heritage Centre (now the eScholarship Research Centre) at the University of Melbourne <https://web.esrc.unimelb.edu.au/BROWt/brown.htm>.');
    $pdf->MultiCell(180,6,'');
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'Date of request for delivery:');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(180,6,date('l jS \of F Y h:i:s A'));
    $pdf->MultiCell(180,6,'');
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'Item Description:');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(180,6,"File inscribed 'Ian's Death, Funeral etc', containing: death certificate, Gwen Marjorie Brown, 14 February 1992; small notebook (Book No 2), containing share information, 1970s-c1985; copy of income tax return, Dr Ian Brown, 1 July 1986 to 30 June 1987; letter from Kiddle Briggs Solicitors to Richard Brown, regarding the estate of Ian Brown, copy of Will attached, 2 March 1988; photocopy Power of Attorney documentation from Gwen Marjorie Brown to Ian Brown; photocopy of Administration Order appointing Richard Brown the Administrator of the estate of Gwen Marjorie Brown, 15 March 1988; Certificate of Cremation of Gwen Marjorie Brown, 17 February 1992; copy of last Will and Testament of Gwen Marjorie Brown, 21 June 1967; copy of inventory of assets of Ian Brown, 27 November 1987; typed message for Gwen Brown, after her death,1992; death registration of Ian Brown, 31 August 1987; Letters of Administration in the probate jurisdiction in the will and estate of Ian Brown, 10 February 1988; letter from Kiddle Briggs Solicitors to Richard Brown regarding the estate of Ian Brown, including a full copy of their trust ledger, 19 December 1989; 2 letters and 6 cards expressing sympathy at the deaths of Ian Brown (1987) and Gwen Brown (1992); letter to Ian and Gwen Brown from the Australian Broadcasting Commission, 15 September 1975; 6 notes of sympathy on Ian Brown's death, 1987; note of sympathy on Gwen Brown's death, 1992; letter and card from Guy Waddington to Ian Brown and family, 27 November 1987; letter of sympathy on Ian's death, 1987; hand-written card containing information about Gwen Brown's health, March 1981 -1984; extract of death registration, Ian Brown, 31 August 1987; letter and certificate of cremation, Ian Brown, 1987; 4 page hand-written copy of eulogy for Ian Brown, 3 September 1987; death and funeral notices for Ian Brown, 'The Age', Melbourne, 1 September 1987; typed letter to Richard Brown from John Lane, including copy of obituary for Ian Brown to be published in 'Chemistry in Australia', 17 September 1987; photograph of Ian Brown and rowing team from Collegiate School of St. Peter, 1934; hand-written card detailing appointments for November 1986; hand-written card detailing appointments for December 1985; and, hand-written card detailing medical information about Ian Brown, June 1986.\nCreator Ian Brown \nDate range 1934 - 1992    Quantity 2 cm, 1 file\n 
Formats General Correspondence, News Clippings, Notebooks, Loose Notes, Mounted Photographic Prints, Photocopied Documents, Legal Documents and Calculations\n 
Inventory Identifier BROW00003 Box Number 1 Series 1");
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'Content Notice:');
    $pdf->SetFont('Arial','',10);
    pdfPrettyPrint($pdf,CONTENT_NOTICE);
    $pdf->MultiCell(180,6,'');
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'Access Conditions:');
    $pdf->SetFont('Arial','',10);
    pdfPrettyPrint($pdf,ACCESS_CONDITIONS);
    $pdf->MultiCell(180,6,'');
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'Conditions of use:');
    $pdf->SetFont('Arial','',10);
    pdfPrettyPrint($pdf,USAGE_CONDITIONS);
    // Lets not recurse the entire collection!
    $source .= '/large';
    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        // Sort our files, by filename
        $sorted_files = iterator_to_array($files);
        sort($sorted_files, SORT_NATURAL);
        foreach ($sorted_files as $file) {
            $pdf->sourcefilename = $file;
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_file($file) === true) {
                pushImage($pdf, $file);
            }
        }
    } else if (is_file($source) === true) {
        pushImage($pdf, $file);
    }

    if ((defined('DADS_DEBUG') && 1 == DADS_DEBUG))
    {
        $pdf->Output("I");
    } else
    {
        $pdf->Output("F", $destination);
    }
}

// Recursively preview/create a proof sheet of a collection
function Preview($source)
{   $source = str_replace('\\', '/', realpath($source));
    $source = rtrim($source, "\\");

    if (is_dir($source . "/" . "thumbnails") === true) {

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . "/" . IMAGETYPE), RecursiveIteratorIterator::SELF_FIRST);

        // Sort our files, by filename
        $sorted_files = iterator_to_array($files);
        sort($sorted_files, SORT_NATURAL);

        foreach ($sorted_files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);
            $imagedirectory = basename(dirname_r($file, 2));
            if (is_file($file))
                echo '<img src="' . dirname(htmlspecialchars($_SERVER['HTTP_REFERER'])) . '/images/' . $imagedirectory . '/' . IMAGETYPE . '/' . basename($file) . '" width="40"/>'; // image width of 40 is somewhat arbitrary -is it still too large? In future, DADS should create the thumbnails.
        }

    }
    return;
}



// Recursively preview/create a proof sheet of a collection
function countitems($source)
{
	$items=0;
    $source = str_replace('\\', '/', realpath($source));
    $source = rtrim($source, "\\");

    if (is_dir($source . "/" . "thumbnails") === true) {

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . "/" . IMAGETYPE), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);
            $imagedirectory = basename(dirname_r($file, 2));
            if (is_file($file))
		$items++;
        }

    }
    return $items;
}
?>
