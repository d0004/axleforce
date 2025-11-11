<?php

namespace _class;

use Pecee\SimpleRouter\SimpleRouter as Router;

class FastTemplate {

	protected $templateFiles = [];
	protected $templates = [];
	protected $variables = [];
	protected $loaded = [];
	protected $handle = [];
	protected $baseDir = "";
	protected $error = "";
	protected $last = "";

    protected $lang;
    protected $db;

	protected $wordsProcessing;

	public function __construct($pathToTemplates = "")
    {
        if ($pathToTemplates) {
            $this->baseDir = rtrim($pathToTemplates,"/") . '/';
        } else {
            print "BaseDir not defined";
            exit;
        }
    } 

    public function set_lang($lang){
        $this->lang = $lang;
    }
    
    public function set_db(\_class\db $db){
        $this->db = $db;
    }

	public function urlFor(?string $name = null, $parameters = null, ?array $getParams = null)
	{
        if(!isset($parameters['ln'])){
            $parameters['ln'] = $this->lang;
        }
        // if(!$parameters){
        //     $parameters = ['ln' => 'lv'];
        // } else {
        //     if(!isset($parameters['ln'])){
        //         $parameters['ln'] = $this->lang;
        //     }
        // }
		return Router::getUrl($name, $parameters, $getParams);
	}

    public function split_template($fileTag)
    {
        $templateName = basename($this->templateFiles[$fileTag]);
        $tmp = preg_split("/<!-- \[(.*)\] -->/", $this->templates[$fileTag], 0, PREG_SPLIT_DELIM_CAPTURE);
	
		// Добавляем основной блок в шаблоны
        $this->templates[$fileTag] = array_shift($tmp);

		// Добавляем все остальные метки в шаблоны
        for ($i = 0; $i < count($tmp); $i += 2) {
			if(trim($tmp[$i]) != '-'){
				$this->templates[$tmp[$i]] = isset($tmp[$i + 1]) ? $tmp[$i + 1] : '';
				$this->loaded[$tmp[$i]] = true;
				$this->templateFiles[$tmp[$i]] = $templateName;
			}
        }

        return true;
    }

	public function get_template($template)
	{
		if(empty($this->baseDir)){
			$this->error("Не задан основной путь", 1);
			return false;
		}

        $filename = $this->baseDir . $this->templateFiles[$template];
		$contents = @implode("",(@file($filename)));

		if(!$contents || empty($contents)){
			$this->error("get_template() Ошибка: [$filename]", 1);
		}

        $tmp = preg_replace('/\$([A-Z0-9_]+)/', '$this->variables["\1"]', $contents);
        $this->templates[$template] = preg_replace('/\{([A-Z0-9_]+)\}/', '<' . '?php echo $this->variables["\1"]; ?' . '>', $tmp);

        return true;
	}

	public function parse_template($template)
	{
		ob_start();
		@eval("?" . ">" . $this->templates[$template]);
		$template = ob_get_contents();
		ob_end_clean();
		return $template;
	}  

	public function parse($metka, $parseSubject)
	{
		$append = false;
		$this->last = $metka;
		$this->handle[$metka] = 1;

        if((substr($parseSubject, 0, 1)) == '.'){
            $append = true;
            $parseSubject = substr($parseSubject, 1);
        }

		if ($append) {
            if(!isset($this->templates[$metka])){
                $this->templates[$metka] = $this->parse_template($parseSubject);
            } else {
                $this->templates[$metka] .= $this->parse_template($parseSubject);
            }
        } else {
            $this->templates[$metka] = $this->parse_template($parseSubject);
        }

        $this->assign($metka, $this->templates[$metka]);
		return true;
	}

	public function fetch($template = "")
	{
		if (empty($template)) {
			$template = $this->last;
		}
		if ((!(isset($this->templates[$template]))) || (empty($this->templates[$template]))) {
			$this->error("Nothing parsed | {$template} ", 0);
			return "";
		}

		return ($this->__ReplaceWords($this->templates[$template]));
		// return $this->templates[$template];
	}


	public function define(array $fileList)
	{
        foreach($fileList as $FileTag => $FileName){
            $this->templateFiles[$FileTag] = ltrim($FileName, '/');
			$this->loaded[$FileTag] = true;
			$this->get_template($FileTag);
        }
		return true;
	}

	public function clear_parse($metka = "")
	{
		$this->clear($metka);
	}

	public function clear($metka = "")
    {
        if (!empty($metka)) {
            if ((gettype($metka)) != "array") {
                unset($this->templates[$metka]);
                return;
            } else {
                foreach ($metka as $key => $val) {
                    unset($this->templates[$val]);
                }
                return;
            }
        }

        foreach ($this->HANDLE as $key => $val) {
            unset($this->templates[$key]);
		}
		
        return true;
    } 

	// public function clear ($metka = "")
	// {
	// 	if($metka){
	// 		if(!is_array($metka)){
	// 			unset($this->$metka);
	// 			return true;
	// 		} else {
	// 			foreach($metka as $key => $val){
	// 				unset($this->$val);
	// 			}
	// 			return true;
	// 		}
	// 	}
		
	// 	foreach($this->handle as $key => $val){
	// 		unset($this->templates[$key]);
	// 	}

	// 	return true;

	// }

	public function clear_all()
	{
		$this->clear();
		$this->clear_assign();
		$this->clear_define();
		$this->clear_tpl();
		return true;
	}	

	public function clear_tpl($fileHandle = "")
    {
        if (empty($this->loaded)) {
            return true;
        }

        if (empty($fileHandle)) {
            foreach ($this->loaded as $key => $val) {
                unset($this->templates[$key]);
            }
            unset($this->loaded);
            return true;
        } else {
            if (!is_array($fileHandle)) {
                if ((isset($this->templates[$fileHandle])) || (!empty($this->templates[$fileHandle]))) {
                    unset($this->loaded[$fileHandle]);
                    unset($this->templates[$fileHandle]);
                    return true;
                }
            } else {
                foreach ($fileHandle as $key => $val) {
                    unset($this->loaded[$key]);
                    unset($this->templates[$key]);
                }
                return true;
            }
        }
        return false;
    }

	public function clear_define($FileTag = "")
	{
		if(empty($FileTag)){
			unset($this->templateFiles);
			return true;
		}

		if(!is_array($Files)){
			unset($this->templateFiles[$FileTag]);
			return true;
		} else {
			foreach ($FileTag as $tag => $val) {
				unset($this->templateFiles[$Tag]);
			}
			return true;
		}
	}

	public function clear_assign()
	{
		if(!(empty($this->variables))){
			foreach ($this->variables as $ref => $val) {
				unset($this->variables["$Ref"]);
			}
		}
	}

	public function assign ($templateArray, $variable = "")
	{
		if(is_array($templateArray)){
            foreach($templateArray as $key => $val){			
				$this->variables[$key] = $val;
            }
		} else {
			$this->variables[$templateArray] = $variable;
		}
	}

    public function assign_array($array)
    {
        $this->assign($array);
    }


	public function get_assigned($templateName = "")
	{
		if(empty($templateName)) return false;
		
		if(isset($this->variables[$templateName])){
			return ($this->variables[$templateName]);
		} 

		return false;
	}

	public function error ($errorMsg, $die = 0)
	{
		echo "error: $errorMsg </br> \n";
		if ($die) die;
		return false;
	} 

	public function setWordsProcessing(WordsProcessing $wordsProcessing) {
		$this->wordsProcessing = $wordsProcessing;
	}

	public function __ReplaceWords($content)
    {
		if (!($this->wordsProcessing instanceof WordsProcessing)) {
			return $content;
		}
		
		$this->wordsProcessing->extract($content);
		return $this->wordsProcessing->replace_content($content);
    }

	public function pagination($page, $countPerPage, $totalCount, $maxPageToShow = 5, $url = '', $script = '')
    {
        $this->define(['pagination' => '/system/tpl/pagination.html']);
        $this->split_template('pagination', 'PAGINATION');

        $pageCount = ceil($totalCount / $countPerPage);
        if(!$pageCount){
            return false;
        }

        $temp = $page - 1 + $maxPageToShow;
        if($temp <= $pageCount){
            $from = $page > 1 ? $page - 1 : $page;
        } else {
            $temp = $temp - $pageCount;
            $from = $page - $temp;
            $from = $from >= 1 ? $from : 1;
        }
        
        for($i = $from; $i <= $pageCount; $i++){
            
            if($i > $from + $maxPageToShow){
                break;
            }

            if($url){
                // TODO
            }

            $this->assign("PAGINATION_SCRIPT", '');
            if($script){
                $this->assign("PAGINATION_SCRIPT", 'onClick="' . $script . '(' . $i . ')"');
            }

            $this->assign_array([
                "PAGE_NR" => $i,
                "ACTIVE_PAGE_CLASS" => $i == $page ? 'active' : '',
            ]);
            $this->parse("PAGINATION_PAGES", ".pagination_page");

            
        }
        
        // if($pageCount > $maxPageToShow){
        //     $this->parse("PAGINATION_PAGES", ".pagination_dots");
        // }

        $this->assign_array([
            "TOTAL_COUNT" => $totalCount,
            "CURRENT_PAGE" => $page,
            "MAX_PAGE" => $pageCount,
            "MIN_PAGE" => 1,
        ]);

        if($script){
            $this->assign("PAGINATION_SCRIPT_MIN", 'onClick="' . $script . '(1)"');
            $this->assign("PAGINATION_SCRIPT_MAX", 'onClick="' . $script . '(' . $pageCount . ')"');
        }

        $this->parse("PAGINATION_RESULT_HTML", "pagination");
        return $this->fetch("PAGINATION_RESULT_HTML");

	}
	
	public function option_list($tpl, $id, $array)
    {
        if (!is_array($array)) return;
        $option = '';
        foreach ($array as $key => $val) {
            if ($key == $id) {
                $tmp = "selected";
            } else {
                $tmp = "";
            }

            $option .= "<option value=\"$key\" $tmp >$val</option>";
        }

        $this->variables[$tpl] = $option;
        unset($option);
    }
} 