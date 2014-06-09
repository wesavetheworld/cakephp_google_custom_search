<?php
/*
 * Google Custom Search for CakePhp (as a lib)
 *
 * @author Hervé Thouzard
 *
 * @notes :
 *		- The Google Classes for Php must be located in the CakePhp vendor folder
 *		- Not yet finished
 *
 * @date 2014-05-30
 */
class Gcs
{
	CONST APPLICATION_NAME = "123456789012";
	CONST DEVELOPER_KEY = "ABCDEFGHIJKLMNOPQRSTUVWXYZazertyuiopqsd";
	CONST CX = "123456789012345678901:azertyuiopq";	// Id du moteur de recherche

	private $options = array();
	private $lang = '';
	private $client = null;			// Application's client
	private $search = null;			// Search service
	private $results = null;		// Search result (Google's format)
	private $current = 0;			// Current page
	private $url = "";

	public $hasNextPage = false;		// Is there a next page ?
	public $hasPreviousPage = false;	// Is there a previous page ?
	public $nextPage = null;			// Data of next page
	public $previousPage = null;		// Previous page's data
	public $totalResults = 0;			// Total count of search results
	public $searchText = "";			// Searched text
	public $items = array();			// search results
	public $start = 0;					// Starting position
	public $limit = 0;					// Elements per page

	function __construct($lang)
	{
		//error_reporting(E_ALL);
		//ini_set("display_errors", "on");
		$vendorFolder = VENDORS.'google/apiclient/src';
		set_include_path(get_include_path() . ';' . $vendorFolder);
		$this->lang = trim(strtolower($lang));
	}

	/**
	 * Create client and search service with default options
	 *
	 * @return void
	 */
	private function init()
	{
		require_once VENDORS."autoload.php";
		$this->client = new Google_Client();
		$this->client->setApplicationName(self::APPLICATION_NAME);
		$this->client->setDeveloperKey(self::DEVELOPER_KEY);

		$this->search = new Google_Service_Customsearch($this->client);
		$this->options = array(
			'cx' => self::CX,
			'num' => 10,		// @todo set as a parameter
		);
		$languages = array('fr' => "lang_fr", 'it' => "lang_it");
		$lang = isset($languages[$this->lang]) ? $languages[$this->lang] : "lang_en";
		$this->options['lr'] = $lang;
	}

	private function setNextPage()
	{
		$this->hasNextPage = false;
		if(isset($this->results['data']['queries']['nextPage'][0])) {
			$this->hasNextPage = true;
			$this->nextPage = $this->results['data']['queries']['nextPage'][0];
		}
	}

	private function setPreviousPage()
	{
		$this->hasPreviousPage = false;
		if(isset($this->results['data']['queries']['previousPage'][0])) {
			$this->hasPreviousPage = true;
			$this->previousPage = $this->results['data']['queries']['previousPage'][0];
		}
	}

	public function getUrl($start)
	{
		return Router::url('/', true).substr(Configure::read('Config.language'), 0, 2).'/search?start='.$start.'&search='.$this->searchText;
	}

	private function setTotalResults()
	{
		$this->totalResults = 0;
		if(isset($this->results['data']['queries']['request'][0])) {
			$this->totalResults = intval($this->results['data']['queries']['request'][0]['totalResults']);
		}
	}

	/**
	 * The Search
	 *
	 * @param string $text		searched text
	 * @param integer $start	starting position
	 * @param integer $limit	count of elements to return
	 *
	 * @return boolean			True if search is ok else false
	 * @note : search results are in $this->items
	 */
	function search($text, $start = 1, $limit = 10)
	{
		if(trim($text) == "") {
			return false;
		}
		$this->searchText = trim(strip_tags($text));
		$this->init();
		$this->start = $this->current = intval($start);
		$this->limit = intval($limit);
		$this->options['start'] = $this->start;
		$this->options['num'] = $this->limit;
		$this->results = $this->search->cse->listCse($this->searchText, $this->options);
		if(!is_object($this->results)) {
			return false;
		}
		$this->setTotalResults();
		$this->setNextPage();
		$this->setPreviousPage();
		$this->items = isset($this->results['data']['items']) ? $this->results['data']['items'] : 0;
		$this->url = Router::url('/', true).'search/?search='.$text.'&start=';
		return true;
	}
}
