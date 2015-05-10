<?php
include "vendor/autoload.php";
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;


class TownsParser {
	private $client;

	private $base_url = 'http://www.nscb.gov.ph/activestats/psgc';

	private $regions = [];
	private $provinces = [];
	private $municipalities = [];
	private $all = [];

	private static $instance;
	private static $areas = ['Regions','Provinces','Municipalities'];

	const REGIONS = 'regions';
	const PROVINCES = 'provinces';
	const MUNICIPALITIES = 'municipalities';

	public function __construct()
	{
		$this->client = new Client();
	}

	static public function getInstance()
	{
		if(self::$instance == NULL) self::$instance = new self;
		return self::$instance;
	}

	private function parseRegions()
	{
		$response = $this->client->get($this->base_url . "/listreg.asp");
		$this->crawlHtml($response->getBody(), 'table p.headline a', self::REGIONS, []);
	}

	private function parseProvinces()
	{
		$regions = self::getArea('Regions');
		foreach ($regions as $region)
		{
			$region_link = $region['region_link'];
			$region_id = $region['region_id'];
			$response = $this->client->get($region_link);
			$this->crawlHtml($response->getBody(), '.dataCellp a', self::PROVINCES, ['region_id'=>$region_id]);
		}
	}

	private function parseMunicipalities()
	{
		$provinces = self::getArea('Provinces');
		foreach ($provinces as $province)
		{
			$province_link = $province['province_link'];
			$province_id = $province['province_id'];
			$region_id = $province['region_id'];
			$response = $this->client->get($province_link);
			$this->crawlHtml($response->getBody(), 'table p.dataCellp a', self::MUNICIPALITIES, ['province_id'=>$province_id,'region_id'=>$region_id]);
		}
	}

	private function crawlHtml($body,$selector,$area_array,$ids)
	{
		if ($body)
		{
			$crawler = new Crawler;
			$crawler->addContent($body);
			$areas = $crawler->filter($selector);

			foreach ($areas as $area)
			{
				$area_name = $area->textContent;
				$area_link = $this->base_url. "/" . $area->getAttribute('href');
				if ($area_array=='regions')
				{
					$id = str_replace("region=","",parse_url($area_link,PHP_URL_QUERY));
					$params = ['region_name'=>$area_name,'region_link'=>$area_link,'region_id'=>$id];
					$this->all['regions'][$id] = $params;
				}else if ($area_array=='provinces')
				{
					$query_string = parse_url($area_link,PHP_URL_QUERY);
					parse_str($query_string, $query_stringed);
					$id = $query_stringed['provCode'];
					$params = ['province_name'=>$area_name,'province_link'=>$area_link,'province_id'=>$id,'region_id'=>$ids['region_id']];
					$this->all['regions'][$ids['region_id']][$area_array][$id] = $params;
				}else
				{
					$query_string = parse_url($area_link,PHP_URL_QUERY);
					parse_str($query_string, $query_stringed);
					$id = $query_stringed['muncode'];
					$params = ['municipality_name'=>$area_name,'municipality_link'=>$area_link,'municipality_id'=>$id,'province_id'=>$ids['province_id'],'region_id'=>$ids['region_id']];
					$this->all['regions'][$ids['region_id']]['provinces'][$ids['province_id']][$area_array][$id] = $params;
				}
				array_push($this->{"$area_array"}, $params);
			}
		}
	}

	public static function getArea($area)
	{
		if ( in_array($area, self::$areas) )
		{
			self::getInstance()->{"parse$area"}();
			return self::getInstance()->{strtolower($area)};
		}else {
			echo "No such area: " . $area;
		}
	}

	public static function getJson($area)
	{
		if ( in_array($area, self::$areas) )
		{
			self::getInstance()->{"parse$area"}();
			return self::getInstance()->all;
		}else {
			echo "No such area: " . $area;
		}
	}
}

echo $municipalities = json_encode(TownsParser::getJson("Municipalities"));
$myfile = fopen("municipalities.json", "w") or die("Unable to open file!");
fwrite($myfile, $municipalities);