<?php namespace WarrenCA\PhilippineTowns;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Parser {
	private $client;

	private $base_url = 'http://www.nscb.gov.ph/activestats/psgc';

	private $currentArea = '';
	private $regions = [];
	private $provinces = [];
	private $municipalities = [];
	private $all = [];

	private static $instance;
	private static $areas = ['Regions','Provinces','Municipalities'];

	const REGIONS = 'regions';
	const PROVINCES = 'provinces';
	const MUNICIPALITIES = 'municipalities';

	private $hook;

	public function __construct(ParserHookInterface $hook)
	{
		$this->client = new Client();
		$this->hook = $hook;
	}

	static public function getInstance()
	{
		if(self::$instance == NULL) self::$instance = IoC::make('parser');
		return self::$instance;
	}

	private function parseRegions()
	{
		$response = $this->client->get($this->base_url . "/listreg.asp");
		$this->crawlHtml($response->getBody(), 'table p.headline a', self::REGIONS, []);
	}

	private function parseProvinces()
	{
		$regions = self::getArea('Regions')->{strtolower('Regions')};
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
		$provinces = self::getArea('Provinces')->{strtolower('Provinces')};
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

				$this->hook->save($params);
			}
		}
	}

	public static function getArea($area)
	{
		if ( in_array($area, self::$areas) )
		{
			self::getInstance()->{"parse$area"}();
			self::getInstance()->currentArea = $area;
			return self::getInstance();
		}else {
			echo "No such area: " . $area;
		}
	}

	public function __toString()
	{
		return json_encode($this->{strtolower($this->currentArea)});
	}

	public function all()
	{
		return json_encode(self::getInstance()->all);
	}
}