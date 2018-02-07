<?php
require('fpdf.php');

class PDF extends FPDF
{
    public $sourcefilename = '';
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
        $this->Cell(30,10,'Title',1,0,'C');
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


// Recursively generate PDF of a collection
function generatePDF($source, $destination, $recipient)
{
    $pdf = new PDF();
    $pdf->SetAutoPageBreak(false);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->MultiCell(180,10,'This archive has been provided to '.$recipient.' under the following conditions: '.ACCESS_CONDITIONS);

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
                $pdf->AddPage();
                $pdf->Image($file);
            }
        }
    } else if (is_file($source) === true) {
        $pdf->AddPage();
        $pdf->Image($file);
    }

    $pdf->Output("F", $destination);
}

// Recursively preview/create a proof sheet of a collection
function Preview($source)
{
    // IMAGETYPE is the directory that we're getting the source images - we'll use thumbnails at the moment, as quality doesn't matter; it might matter in future if we use seam carving
    define("IMAGETYPE", 'thumbnails');

    $source = str_replace('\\', '/', realpath($source));
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
?>