<?php
/*
 This file contains source code from the Filesender project.

 *
 * FileSender www.filesender.org
 * 
 * Copyright (c) 2009-2012, AARNet, Belnet, HEAnet, SURFnet, UNINETT
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * *    Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 * *    Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 * *    Neither the name of AARNet, Belnet, HEAnet, SURFnet and UNINETT nor the
 *     names of its contributors may be used to endorse or promote products
 *     derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */


require_once 'FilesenderRestClient.class.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "/tmp/php-error.log");

if (!include('config.php'))
   die('Error: Could not open \"config.php\", please edit and rename \"config.php.dist\"');

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

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

$basename = basename($_POST['itemid']);

$ohrmname = substr($basename, 0, 4);

$tempfname = tempnam ( '/tmp/' , $ohrmname );

Zip (ASSET_BASE."/".$basename, $tempfname);

try {
    $c = new FilesenderRestClient(FILESENDER_URL, 'user', FILESENDER_USERID, FILESENDER_APIKEY);
    
    //print_r($c->getInfo());

   /**
     * Upload files to recipients
     *
     * @param string $user_id (will be ignored if remote user authentication in use)
     * @param string $from sender email
     * @param mixed $files file path or array of files path
     * @param array $recipients array of recipients addresses
     * @param string $subject optional subject
     * @param string $message optional message
     * @param string $expires expiry date (yyyy-mm-dd or unix timestamp)
     * @param array $options array of selected option identifiers
     **/
    print_r($c->sendFiles(
	FILESENDER_USERID,
	FILESENDER_USERID,
	array(
$tempfname
),
  array($_POST['email']),
        '['.$ohrmname.'] Your selected items are ready for download.',
        "By downloading this file, you agree to the following conditions : ".ACCESS_CONDITIONS,
	time() + 24*60*60*30,
	array("email_download_complete", "email_report_on_closing")
	));


} catch(Exception $e) {
    echo 'EXCEPTION ['.$e->getCode().'] '.$e->getMessage();
}

unlink ($tempfname);
?>
<!DOCTYPE html>
<html>
<body>
<br /><br />
Your requested items have been sent. Please check your email address <i><?php echo($_POST['email'])?></i> for the download link.
<br /><br />
<?php
if (isset($_POST['referrer'])) {
?>
<a href="<?php echo htmlspecialchars($_POST['referrer']) ;?>">Return to the site</a>
<?php
} else
?>
<a href="/">Return to the site</a>
<?php
?>
</body>
</html>





