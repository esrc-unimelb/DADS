<?php

/*
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

/**
 * Filesender REST client usage example
 */

require_once 'FilesenderRestClient.class.php';

$url='https://cloudstor.aarnet.edu.au/sender/rest.php';
$user_id='';
$apikey='';

try {
    $c = new FilesenderRestClient($url, 'user', $user_id, $apikey);
    
    //print_r($c->getInfo());

   /**
     * Upload files to recipients
     *
     * @param string $user_id (will be ignored if remote user authentication in use)
     * @param string $from sender email
     * @param mixed $files file path or array of files path
     * @param array $recipients array of recipients addresses
     * @param string $subject optionnal subject
     * @param string $message optionnal message
     * @param string $expires expiry date (yyyy-mm-dd or unix timestamp)
     * @param array $options array of selected option identifiers
     */
    print_r($c->sendFiles(
	$user_id,
	$user_id,
	array(
'/etc/passwd',
),
	array('xx@aarnet.edu.au'),
	'API TEST subject',
	'API TEST message',
	'1490943101',
	array("email_download_complete", "email_report_on_closing")
	));


} catch(Exception $e) {
    echo 'EXCEPTION ['.$e->getCode().'] '.$e->getMessage();
}
