<?php
include "vendor/autoload.php";
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;


class CityParser {
	private $client;
	private $crawler;
	private $base_url = 'http://www.nscb.gov.ph/activestats/psgc';
	private $regions = [];
	private $provinces = [];
	private $cities = [];
	private static $instance;
	private static $areas = ['Regions','Provinces','Cities'];

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
		$response_region = $this->client->get($this->base_url . "/listreg.asp");
		$this->crawlHtml($response_region->getBody(), 'table p.headline a', 'regions');
	}

	private function parseProvinces()
	{
		$regions = self::getArea('Regions');
		foreach ($regions as $region)
		{
			$region_link = $region[1];
			$response_province = $this->client->get($region_link);
			$this->crawlHtml($response_province->getBody(), '.dataCellp a', 'provinces');
		}
	}

	private function parseCities()
	{
		$provinces = self::getArea('Provinces');
		foreach ($provinces as $province)
		{
			$city_link = $province[1];
			$response_city = $this->client->get($city_link);
			$this->crawlHtml($response_city->getBody(), 'table p.dataCellp a', 'cities');
		}
	}

	private function crawlHtml($body,$selector,$area_array)
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
				array_push($this->{"$area_array"}, [$area_name,$area_link]);
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
}

print_r(CityParser::getArea("Cities"));