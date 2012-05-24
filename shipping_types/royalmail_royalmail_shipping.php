<?php
class RoyalMail_RoyalMail_Shipping extends Shop_ShippingType {

	public function get_info() {
		return array(
			'name'=>'UK Royal Mail',
			'description'=>'This shipping method allows to request quotes
			and shipping options from the United Kingdom\'s Royal Mail postal service.
			No account of any kind is needed to use this shipping method.'
		);
	}

	public function build_config_ui($host_obj, $context = null) {}
	public function validate_config_on_save($host_obj) {}
	public function init_config_data($host_obj) {}
	
	public function get_quote($parameters) {
	
		# These parameters will be translated into URL
		# to retrieve the shipping options available
		$url_params = array();
	
		# Determine the destination country:
		# - if it's UK, employ domestic shipping
		# - anything else, international shipping
		$country = Shop_Country::create()->find_by_id(intval($parameters['country_id'], 10));
		
		# DOMESTIC SHIPPING
		if ($country->code === "GB") {
		
			$url_params[] = "UK";
			$url_params[] = "GB";
			
			# Send large letter if the package weights up to 750g;
			# Send packet if the package weight over  750g
			if ($parameters['total_weight'] <= 0.75) {
				$url_params[] = "Large_Letter";
			} else {
				$url_params[] = "Packet";
			}
			
			# Add weight, maximum number of days for delivery (we'll use 10) and expected value of the package
			$url_params[] = number_format($parameters['total_weight'] * 1000, 0, '.', '');
			$url_params[] = '10';
			$url_params[] = '0.0'; #$url_params[] = number_format($parameters['total_price'], 0, '', '');
			
			# Add the weight measurement unit (kilograms)
			$url_params[] = 'g';
		
		# INTERNATIONAL SHIPPING
		} else {
		
			$url_params[] = "OV";
			$url_params[] = $country->code;
			
			# Send items as small packets
			$url_params[] = "Small_Packets";
			
			# Add weight, maximum number of days for delivery (we'll use 10) and expected value of the package
			$url_params[] = number_format($parameters['total_weight'] * 1000, 0, '.', '');
			$url_params[] = '10';
			$url_params[] = '0.0'; #$url_params[] = number_format($parameters['total_price'], 0, '', '');
			
			# Add the weight measurement unit (kilograms)
			$url_params[] = 'g';
		
		}
		
		# Prepare the entire query URL
		$url = rtrim('http://www.royalmail.com/pricefinder/ajax/', '/') . '/' . implode('/', $url_params);
		
		# cURL the whole thing
		$curl = curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json, text/javascript, */*',
				'Host: www.royalmail.com',
				'Referer: http://www.royalmail.com/price-finder',
				'X-Requested-With: XMLHttpRequest'
			)
		));
		$result = curl_exec($curl);
		curl_close($curl); unset($curl);
		
		# Parse the result returned in JSON form
		$result = json_decode($result, true);
		$dom = new DOMDocument(); $dom->strictErrorChecking = false;
		try { $dom->loadHTML($result['data']);  } catch (Exception $e) {} unset($result);
		$xpath = new DOMXPath($dom);
		
		# This array will hold all the available options
		$options = array();
		
		# Iterate over available services
		foreach ($xpath->query("//table[@class='results-table']") as $table) {
		
			# Get service name
			$service = trim($xpath->query("caption", $table)->item(0)->nodeValue);
			
			# Iterate over available service options
			foreach ($xpath->query("*//tr[@class='display-result-data']", $table) as $row) {
			
				# Service type name
				$name = $xpath->query("*//div[@class='pf-results-data-service']/strong", $row)->item(0)->nodeValue;
				$name = preg_replace('/ stamps$/i', '', trim($name));
				
				# Service type ID (required for further reference)
				$id = strtolower(str_replace(" ", "-", $service));
				$id .= '-' . preg_replace('/\s/i', '-', preg_replace('/[()._-]/i', '', strtolower(trim($name))));
				
				# Parse the price (adjust by VAT if needed)
				$price = trim($xpath->query("td[last()-1]", $row)->item(0)->childNodes->item(0)->nodeValue);
				$matches = array(); preg_match('/^£([0-9]+(?:\.[0-9]+)?)(.+)?$/i', $price, $matches);
				
				$price = floatval($matches[1]);
				if (isset($matches[2])) { $price = round(ceil($price * 100) / 100); }
				
				$options[$service . ': ' . $name] = array('id' => $id, 'quote' => $price);
			
			}
		
		}
		
		unset($xpath); unset($dom);
		return $options;
	
	}

}
?>