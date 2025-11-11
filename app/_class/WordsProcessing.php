<?php

namespace _class;

class WordsProcessing
{
	protected $db; 
	
	protected $path_to_modules = "templates/";
	protected $module = "";
	protected $path_to_tpl = "";
	protected $words_new = [];
	protected $words_all = [];
	protected $words_search = [];
	protected $words_replace = [];
	protected $langs = [];
	protected $lang_default = 'lv';
	public $lang = 'lv';
	protected $extract_from_db_done = false;
	
	public function set_db(db $db)
	{
		$this->db = $db;
	}

	public function __construct()
	{
		$this->lang = $this->lang_default;
	}

	
	public function _get_all_matches(&$content)
	{
		preg_match_all("/<lang[\ ]+id=[\'\"]([A-Z_0-9]+)([^>]+)?".">(.*?)<\/lang>/isu", $content, $matches);
		return $matches;
	}
	
	public function __extract_from_db()
	{
		if(!$this->extract_from_db_done){
			$sel = '';
			foreach($this->words_search as $k => $v){
				$sel .= ", '".$this->db->escape_string($k)."'";
			}
	
			if($sel != ''){
				$words = null;
				if (!$words) {
					$words = [];
					$words = $this->db->query("SELECT d.ID, d.VALUE AS DVALUE, u.VALUE AS UVALUE, en.VALUE AS EVALUE FROM tbl_words AS d
					LEFT JOIN tbl_words AS u ON (d.ID=u.ID AND u.LID=?)
					LEFT JOIN tbl_words AS en ON (d.ID=en.ID AND en.LID='lv')
					WHERE d.LID = ? AND d.ID IN(".substr($sel,2).")", $this->lang, $this->lang_default)->fetchAll();
				}
				
				foreach($words as $line){
					$this->words_search[$line["ID"]] = "/<lang[\ ]+id=[\'\"](". $line["ID"] .")[\'\"]([^>]+)?".">(.*?)<\/lang>/su";
					$this->words_replace[$line["ID"]] = ($line["UVALUE"])? $line["UVALUE"] : (($line["EVALUE"]) ? $line["EVALUE"] : $line["DVALUE"]);
					unset($this->words_new[$line["ID"]]);
				}

				unset($words);
			}

			$this->extract_from_db_done = true;
		}
	}
	
	public function extract(&$content, $module = '', $file = '')
	{
		$this->extract_from_db_done = false;
		$this->module = $module;
		$sel = '';

		$matches = $this->_get_all_matches($content);
	
		foreach($matches[1] as $k=>$v)
		{
			$this->words_all[$module][$v]  = "1";
			$this->words_search[$v] = "/<lang[\ ]+id=[\'\"](". $v .")[\'\"]([^>]+)?".">(.*?)<\/lang>/su";
			$this->words_replace[$v] = "\\3";

			$this->words_new[$v] = [
				"MODULE" => $module,
				"FILE" => $file,
				"VALUE" => $matches[3][$k],
			];
		}

		$this->__extract_from_db();
		unset($matches);
	}


	public function repalce_files()
	{
		$this->__extract_from_db();
	}

	public function insert_to_db()
	{
		$this->__extract_from_db();
		if(count($this->words_new)){
			
			foreach($this->words_new as $k=>$v){
				$to_insert[$k] = [
					"`MODULE`" => $v["MODULE"],
					"`FILE`" => $v["FILE"],
					"`ID`" => $k,
					"`VALUE`" => trim($v["VALUE"])
				];
			}

			if(count($to_insert)){
				foreach($to_insert as $v){
					$v["LID"]=$this->lang_default;
					$this->db->insert_array("tbl_words", $v);
				}
			}
		}
	}

	public function replace_content(&$content)
	{
		$this->__extract_from_db();
		$this->words_search["_EMPTY_"] = "/<lang>(.*?)<\/lang>/su";
		$this->words_replace["_EMPTY_"] = "\\1";
		return preg_replace($this->words_search, $this->words_replace, $content);
	}
	
}

