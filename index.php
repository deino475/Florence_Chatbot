<?php
function search_for_address($text) {
	$address = null;
	$words = explode(" ", $text);
	for ($i = 0; $i < sizeof($text); $i++) {
		$word = preg_replace("/[^a-zA-Z 0-9]+/", "", $words[$i]);
		if (strlen($word) == '5') {
			if (is_numeric($word)) {
				$address = $word;
				return $address;
			}
		}
	}
	return $address;
}

function get_coords($address) {
	$coords = array();
	$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key=AIzaSyD2uPNooAUapFrrB8gRkN3tsPj4kRlgKgw';
	$data = json_decode(file_get_contents($url),true);
	$coords['lat'] = $data['results'][0]['geometry']['location']['lat'];
	$coords['lng'] = $data['results'][0]['geometry']['location']['lng'];
	return $coords;
}

function get_closest_shelter($lat,$lng) {
	$data = json_decode(file_get_contents("https://hurricane-florence-api.herokuapp.com/api/v1/shelters"),true);
	$nearest_data = null;
	$shortest_distance = 100000;
	$end_block = $data['shelters'];
	foreach ($end_block as $shelter) {
		if (sqrt(pow(($lat - $shelter['latitude']), 2) + pow(($lng - $shelter['longitude']), 2)) < $shortest_distance) {
			$shortest_distance = sqrt(pow(($lat - $shelter['latitude']), 2) + pow(($lng - $shelter['longitude']), 2));
			$nearest_data = $shelter;
		}
	}
	return $nearest_data;
}

function get_closest_org($lat, $lng) {
	$chain_data = json_decode(file_get_contents('blockchain.json'),true);
	if (sizeof($chain_data) == 0) {
		$block = new Block('','','00000000000');
		$block->generate_genesis_block();
		array_push($chain_data, $block->export_block($json = false));
	}
	$end_block = array_pop($chain_data);
	if (sizeof($end_block) == 0) {
		return null;
	}
	$nearest_data = null;
	$shortest_distance = 100000;
	foreach ($end_block['data'] as $shelter) {
		if (sqrt(pow(($lat - $shelter[8]), 2) * pow(($lng - $shelter[9]), 2)) < $shortest_distance) {
			$nearest_data = $shelter;
		}
	}
	return $nearest_data;
}

function return_message ($org_name, $address) {
	return "<Response><Message>The nearest hurricane shelter is $org_name at $address. For more information, go to florenceresponse.org.</Message></Response>";
}

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$address = search_for_address($_POST['Body']);
if ($address == null) {
	echo '<Response><Message>Hello, this is a Hurricane Shelter Textbot, helping you find the nearest hurricane shelter. Please type your zip code.</Message></Response>';
}
else {
	$coords = get_coords($address);
	$closest_org = get_closest_shelter($coords['lat'], $coords['lng']);
	if ($closest_org == null) {
		echo "<Response><Message>An error occurred. Please try again.</Message></Response>";
	}
	else {
		echo return_message($closest_org['shelter'],$closest_org['address']);
	}	
}