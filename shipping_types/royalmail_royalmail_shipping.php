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
	
		echo("<!--\n");
		var_dump($parameters);
		echo("-->\n");
		exit();
	
		# These parameters will be translated into URL
		# to retrieve the shipping options available
		$url_params = array();
	
		# Determine the destination country:
		# - if it's UK, employ domestic shipping
		# - anything else, international shipping
		$country = Shop_Country::create()->find_by_id($parameters['country_id']);
		
		# DOMESTIC SHIPPING
		if ($country->code === "GB") {
		
			$url_params[] = "UK";
			
			
		
		# INTERNATIONAL SHIPPING
		} else {
		
			$url_params[] = "OV";
			$url_params[] = $country->code;
		
		}
	
	}

}
?>