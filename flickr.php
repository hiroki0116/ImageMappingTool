<?php

class phpFlickr {

		
	var $api_key;
	var $secret;
	var $endpoint = 'https://api.flickr.com/services/rest/';
	var $response;
	var $parsed_response;
	var $die_on_error;

	

	function phpFlickr ($api_key, $secret = NULL, $die_on_error = false) {

		$this->api_key = $api_key;
		$this->secret = $secret;
		$this->die_on_error = $die_on_error;
		$this->service = "flickr";
	}


	function getContent ($data) {

		if ( function_exists('curl_init') ) {
			
			$curl = curl_init($this->endpoint);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			curl_close($curl);
		} else {
			//if no curl connection, use a socket
			foreach ( $data as $key => $value ) {
				$data[$key] = $key . '=' . urlencode($value);
			}
			$data = implode('&', $data);

			$fp = @pfsockopen('ssl://'.$matches[1], 443);
			if (!$fp) {
				die('Connection Error.');
			}
			fputs ($fp,'POST ' . $matches[2] . " HTTP/1.1\n");
			fputs ($fp,'Host: ' . $matches[1] . "\n");
			fputs ($fp,"Content-type: application/x-www-form-urlencoded\n");
			fputs ($fp,"Content-length: ".strlen($data)."\n");
			fputs ($fp,"Connection: close\r\n\r\n");
			fputs ($fp,$data . "\n\n");
			$response = "";
			while(!feof($fp)) {
				$response .= fgets($fp, 1024);
			}
			fclose ($fp);
			$chunked = false;
			$http_status = trim(substr($response, 0, strpos($response, "\n")));
			if ( $http_status != 'HTTP/1.1 200 OK' ) {
				die('Flickr returned  a "' . $http_status . '" response');
			}
			if ( strpos($response, 'Transfer-Encoding: chunked') !== false ) {
				$temp = trim(strstr($response, "\r\n\r\n"));
				$response = '';
				$length = trim(substr($temp, 0, strpos($temp, "\r")));
				while ( trim($temp) != "0" && ($length = trim(substr($temp, 0, strpos($temp, "\r")))) != "0" ) {
					$response .= trim(substr($temp, strlen($length)+2, hexdec($length)));
					$temp = trim(substr($temp, strlen($length) + 2 + hexdec($length)));
				}
			} elseif ( strpos($response, 'HTTP/1.1 200 OK') !== false ) {
				$response = trim(strstr($response, "\r\n\r\n"));
			}
		}
		return $response;
	}

	
	function request ($command, $args = array())
	{
		//Send a request to Flickr
		if (substr($command,0,7) != "flickr.") {
			$command = "flickr." . $command;
		}

		//Deal with parmeters and fetch the content
		$args = array_merge(array("method" => $command, "format" => "json", "nojsoncallback" => "1", "api_key" => $this->api_key), $args);
		ksort($args);
		$auth_sig = "";
		$this->last_request = $args;
		$this->response = $this->getContent($args);
		$this->parsed_response = json_decode($this->response, TRUE);
	

		return $this->response;
	}

	//flickr.photos.search method 
	function photos_search ($args = array()) {

		$this->request("flickr.photos.search", $args);
		return ($this->parsed_response) ? $this->parsed_response['photos'] : false;
	}

	//flickr.phptos.geo.getLocation 
	function photos_geo_getLocation ($photo_id) {
		
		$this->request("flickr.photos.geo.getLocation", array("photo_id"=>$photo_id));
		return $this->parsed_response ? $this->parsed_response['photo'] : false;
	}

}


?>