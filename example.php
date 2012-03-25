<?php

/* TODO ! Get https://github.com/petewarden/ParallelCurl FIRST !*/

/* make it or break it */
error_reporting(E_ALL);
ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
ini_set("memory_limit","500M");
set_time_limit(0);

$config = array(
      'tracker' => array(
         'domain' => 'toxicnade',
         'class' => 'images',
         'trackers' => array(
            'server4:7001',
            //            'server9:7001', // DISABLE A BUSY TRACKER MACHINE OR A FIREWALLED ONE
            'server9:7001',
            'server13:7001'
            )
         )
      );

$curl_childs=5;

/* working dir */
$mogdump_base= realpath( dirname(__FILE__));
/* Temp storage */
$mogdump_temp=$mogdump_base . DIRECTORY_SEPARATOR . "temp";

// Setup mogile link
$mfs = new MogileFS($config['tracker']['domain'], $config['tracker']['class'] , $config['tracker']['trackers'] );

$keylist= array( <list of keys> );
/* For a list of mogile id's: */
$mfs->getArray($key_list, $curl_childs , 'on_request_done', $mogdump_temp );

// When all is ok, the temp directory has all the files.... This is untested code written by heart, it should be close though, just to get the point

/* done */

/*
The callback function 'on_request_done' should take four arguments. The first is a string containing the content found at
the URL. The second is the original URL requested, the third is the curl handle of the request that
can be queried to get the results, and the fourth is the arbitrary 'cookie' value that you 
associated with this object. This cookie contains user-defined data.
*/
function on_request_done($content, $url, $ch, $search) {
   $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

   if ($httpcode !== 200) {
      echo "Fetch error $httpcode for '$url'\n";
      $curlInfo = curl_getinfo($ch);
      $curlError = curl_error($ch);
      $curlErrno = curl_errno($ch);
      echo "Info  : " . $curlInfo . "\n";
      echo "Error : " . $curlError . "\n";
      echo "Errno : " . $curlErrno . "\n";
      sleep(10);
      return;
   }

   if (strlen($content)) {
     file_put_contents($search, $content, LOCK_EX);
   } else {
      if (!strlen($content)) {
         $pattern = '/mogilefs:/'; // This is your mogileid prefix from the key, 
         $replacement = 'EMPTY_mogilefs:';
         $search = preg_replace($pattern, $replacement, $search);
         // It looks like we have an empty file here, this only happens for keys that are in the mogile images db but fysically are not there... 
         file_put_contents($search, $content, LOCK_EX);
         // We need to take care of these or the query loop will hang here.
      }
   }
   /* Clean up, we seem to leak memory somewhere inside php */
   unset($content);
}

?>
