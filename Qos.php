<?php
date_default_timezone_set('America/Los_Angeles');
//echo date_default_timezone_get();
class Logging {
    // declare log file and file pointer as private properties
    private $log_file, $fp;
    // set log file (path and name)
    public function lfile($path) {
        $this->log_file = $path;
    }
    // write message to the log file
    public function lwrite($message) {
        // if file pointer doesn't exist, then open log file
        if (!is_resource($this->fp)) {
            $this->lopen();
        }
        // define script name
        $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
        // define current time and suppress E_WARNING if using the system TZ settings
        // (don't forget to set the INI setting date.timezone)
        $time = @date('Ymd:H:i:s');
        // write current time, script name and message to the log file
        fwrite($this->fp, "$time, $message" . PHP_EOL);
    }
    // close log file (it's always a good idea to close a file when you're done with it)
    public function lclose() {
        fclose($this->fp);
    }
    // open log file (private method)
    private function lopen() {
        // in case of Windows set default log file
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $log_file_default = './QOS.log';
        }
        // set default log file for Linux and other systems
        else {
            $log_file_default = './QOS.log';
        }
        // define log file from lfile method or use previously set default
        $lfile = $this->log_file ? $this->log_file : $log_file_default;
        // open log file for writing only and place file pointer at the end of the file
        // (if the file does not exist, try to create it)
        $this->fp = fopen($lfile, 'a') or exit("Can't open $lfile!");
    }
}


class Dejaview extends Logging{
    public static $url = 'http://central.paparazzipass.com:2050';

    public function get_web_page( $url )
	{
	    $options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
	        CURLOPT_TIMEOUT        => 120,      // timeout on response
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	    );

	    $ch      = curl_init( $url );
	    curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );

	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}

   	public function get_final_array($content){
   		$key_array = array();
		$value_array = array();
		$array = array();
		$f_array = array();
		$count =0;
		foreach ($content as $temp) {
		    $array = json_decode(json_encode($temp),true);
		    foreach($array['values'] as $row => $value){
		        foreach($value as $key=>$value){
		            //echo "key--$key holds value-- $value <br/>";
		            if($key=='name'){
		                array_push($key_array, $value);
		            }
		            else if($key=='value'){
		                array_push($value_array, $value);
		            }
		        }
		        $final_array = array_combine($key_array, $value_array);
		    }
		    array_push($f_array, $final_array);
		    
		}
		
		return $f_array;
   	}

   	public function millisecond() {
        list($usec, $sec) = explode(' ',microtime());
    	return intval(($usec+$sec)*1000.0);
    }

   	public function logexecution($methodname, $query=''){
   		$output = 'output=json';
   		$set_query ='?' . 'op='.$methodname.'&'.$output;
   		if($query!='')
   			$set_query ='?' . 'op='.$methodname.'&'.$query.'&'.$output;
		/* Update URL to container Query String of Paramaters */
		$st_url=$this::$url.$set_query;
		$log = new Logging();
		// set path and name of log file (optional)
		$log->lfile('./QOS.log');

		
		$time_start = $this->millisecond();
		$contents = $this->get_web_page($st_url);
		$time_end = $this->millisecond();
		$content_temp = json_decode($contents['content']);
		$final_array = $this->get_final_array($content_temp);
		$execution_time = $time_end - $time_start;

		//execution time of the script
		$log->lwrite($methodname .'(),' .$execution_time.'ms');
		print "method:$methodname";
		print ",Elapsed Time, $execution_time ms<br>";
		

		// close log file
		$log->lclose();
		//var_dump($final_array);
		if(empty($final_array))
			return $final_array;

		return $final_array[0];
	}
} 


if (empty($_GET)) {
	echo "cant run the test cases";
}
elseif ($_GET['UID'] != 'zflacQnGJRC09TW5kwSv7gOdpLM8uB') {
	echo "cant run the test cases";
	}
elseif ($_GET['UID'] == 'zflacQnGJRC09TW5kwSv7gOdpLM8uB') {
	$obj = new Dejaview();
	$token = $obj->logexecution('gettoken','userName=test&password=test@123');
	$query = 'token=' .$token['token'];
	// echo $token['token'];
	// echo "</br>";
	//Site Configuration
	$site = $obj->logexecution('getSiteList',$query);
	$query = 'token=' .$token['token'].'&siteId='.$site['siteId'];
	$obj->logexecution('getSite',$query);
	$query = 'token=' .$token['token'].'&siteType='.$site['siteType'];
	$obj->logexecution('getSite',$query);
	$query = 'token=' .$token['token'].'&latitude='.$site['latitude'].'&longitude='.$site['longitude'].'&radius=30';
	$obj->logexecution('getSite',$query);
	$obj->logexecution('getSiteTypeList');
	$query = 'token=' .$token['token'];
	$obj->logexecution('getPublicMediaList',$query);

	//Utility

	$obj->logexecution('getGuid');
	$obj->logexecution('getUTC');
	$obj->logexecution('getTos');
	$obj->logexecution('getTosVersion');
	
	//Security and Media
	//commented as above method is already called
	$output = $obj->logexecution('getUser',$query);
	$query = 'token=' .$token['token'].'&email='.$output['email'];
	$obj->logexecution('requestUserPasswordReset',$query );
	$query = 'token=' .$token['token'].'&userName='.$output['userName'];
	$obj->logexecution('requestUserPasswordReset',$query );
	//if (!$token['error']) {
	$query = 'token=' .$token['token'];
	$output = $obj->logexecution('getMediaList',$query);
	$obj->logexecution('getVersion',$query );
	$obj->logexecution('getLog',$query);

	//}
	
//	if (!$output['error']) {
	$query = 'token=' .$token['token'].'&mediaId='.$output['mediaId'];
	$obj->logexecution('setMediaSeen',$query);
	$obj->logexecution('getMedia',$query);
	$obj->logexecution('setMediaNotSeen',$query);
	$obj->logexecution('setMediaStarred',$query);
	$obj->logexecution('setMediaNotStarred',$query);
	$obj->logexecution('deleteMedia',$query);
	$query = 'token=' .$token['token'];
	//below 3 lines are not working due flagcontent is returning blank array
	$media = $obj->logexecution('getContentList',$query);
	if(!empty($media)){
	// //var_dump($media);
		$query ='token=' .$token['token'].'&zone='.$output['zone'].'&dateTimeBegin='.$output['dateTimeBegin'].'&dateTimePeak='.$output['dateTimePeak'].'&dateTimeEnd='.$output['dateTimeEnd'];
		$obj->logexecution('flagContent',$query);
	}

//	}

	//Licensing
	$obj->logexecution('getSiteLicensePlanList');
	$query = 'token=' .$token['token'];
	$obj->logexecution('getTransactionList',$query);
	$query = 'token=' .$token['token'].'&siteId='.$output['siteId'];
	$obj->logexecution('getSiteLicenseStatus',$query);
	$obj->logexecution('getPaymentPublicKey');

	//creation and updation  of user
	$query = 'userName=test&password=test@123&firstName=testuser&lastName=test&securityQuestion=foo&securityAnswer=foo&email=test@testmail.org';
	$obj->logexecution('createUser',$query);
	$obj->logexecution('updateUser',$query);
}
?>