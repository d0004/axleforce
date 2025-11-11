<?php

namespace _class;

class FastTemplate
{

    // Список файлов с  шаблонами
    // FILELIST[HANDLE] == "fileName"
    var $FILELIST    =    array();

    // Масив переменных
    // PARSEVARS[HANDLE] == "value"
    var $PARSEVARS   =    array();

    //    We only want to load a template
    //    once - when it's used.
    //    LOADED[FILEHANDLE] == 1 if loaded
    //    undefined if not loaded yet.
    var $LOADED      =    array();

    //    Holds the handle names assigned
    //    by a call to parse()
    var $HANDLE      =    array();

    //    Holds path-to-templates
    var $ROOT        =    "";

    //    Holds the last error message
    var $ERROR       =    [];

    //    Holds the HANDLE to the last
    //    template parsed by parse()
    var $LAST        =    "";

    //    Strict template checking.
    //    Unresolved vars in templates will
    //    generate a warning when found.
    var $STRICT      =    false;

    // spisok peremennih, kotorie dolzni bitj
    // globaljnimi pri kazdom parsinge sablona
    // po umolcaniju pustaja
    // delaesja dlja udobstva programmera.
    // v teori podobnie resenija delajut razrabotku ne trivialjnoj
    // u menjshaet rabotu po sozdaniju`
    var $VAR_GLOBAL  =    "";


    // sprisok obrabativaemih sablonis

    var $TPL = array();

    // suda pomescajutsja te sabloni,
    // kotorie obrabotani split_template
    // dannij masiv budet nuzen dlja proverki prav dostupa
    // i dostavanija ego iz bazi
    var $SPLIT_TPL = array();

    var $PRINT_ERROR = true;

    // versija oformlenija
    var $VARIANT = "";

    // vklju4atj li imena shablonov
    var $TPL_NAMES_INCLUDE = false;

    // udaljatj probeli
    var $TPL_DELETE_SPACES = false;

    protected $router;
    protected $lang;
    protected $db;


    //    ************************************************************

    function __construct($pathToTemplates = "")
    {
        global $php_errormsg;

        // $pathToTemplates = "template/";
        if (!empty($pathToTemplates)) {
            $trailer = substr($pathToTemplates, -1);

            if ((ord($trailer)) != "/") {
                $pathToTemplates = $pathToTemplates . "/";
            }

            $this->ROOT = $pathToTemplates;
        } else {
            print "path to template dir is empty!";
            exit;
        }

        // tut delaem inicializaciu nekotorih sablonov
        // potom sdelaem po drugomu

        //        $this->FILELIST["raquo"] = "raquo";
        $this->LOADED["raquo"] = 1;
        $this->TPL["raquo"] = "&raquo;";

        //        $this->FILELIST["br"] = "br";
        $this->LOADED["br"] = 1;
        $this->TPL["br"] = "<br>";

        $this->LOADED["bull"] = 1;
        $this->TPL["bull"] = "&bull;";



        // инициализация класса, по обработке слов
        // $this->WORD_PROCESSING = new WordsProcessing();
    }    // end (new) FastTemplate ()



    //    ************************************************************
    // Захватывает шаблон от корневого директора, и
    // читает это в  большая строка

    function get_template($template)
    {
        if (empty($this->ROOT)) {
            $this->error("Cannot open template. Root not valid.", 1);
            return false;
        }

        $filename    =    $this->ROOT . $this->FILELIST[$template];

        $contents = @implode("", (@file($filename)));

        if ((!$contents) or (empty($contents))) {
            $this->error("get_template() failure: [ tpl = " . $template . " ];  [ file = " . $filename . " ]", 0);
        }
        // $this->$template = preg_replace('/\$([^_][A-Z0-9_]+)/','$this->PARSEVARS["\1"]',$contents);
        $this->TPL[$template] = preg_replace('/\$([A-Z0-9_]+)/', '$this->PARSEVARS["\1"]', $contents);
        //$keys = '/\{'."$key".'\}/';
        $this->TPL[$template] = preg_replace('/\{([A-Z0-9_]+)\}/', '<' . '?= $this->PARSEVARS["\1"]; ?' . '>', $this->TPL[$template]);

        unset($contents, $filename);
        //$this->$template = $contents;
        // return $contents;
        return true;
    } // end get_template


    function __ReplaceWords($content)
    {
		if (!($this->wordsProcessing instanceof WordsProcessing)) {
			return $content;
		}
		
		$this->wordsProcessing->extract($content);
		return $this->wordsProcessing->replace_content($content);
    }


    //    ************************************************************
    //    This routine get's called by parse() and does the actual
    //    {VAR} to VALUE conversion within the template.
    // Этот стандартный get's, называемый синтаксическим анализом () и делает фактический
    // {ПЕРЕМЕННУЮ ВЕЛИЧИНУ}, чтобы ОЦЕНИТЬ преобразование в пределах шаблона.

    function parse_template($template)
    {

        // tut opredeljaem globaljnie peremennie dlja sablovov
        if ($this->VAR_GLOBAL) {
            @eval("global " . $this->VAR_GLOBAL . ";");
        }


        ob_start();

        //$template = preg_replace("$key","$val","$template");
        @eval("?" . ">" . $this->TPL[$template]);
        //$template = str_replace("$key","$val","$template");

        $template = ob_get_contents();

        ob_end_clean();
        return $template;
    }    // end parse_template();

    //    ************************************************************
    //    The meat of the whole class. The magic happens here.
    // Суть целого класса. Волшебство случается здесь.

    function parse($ReturnVar, $FileTags, $p = false)
    {

        $append = false;
        $this->LAST = $ReturnVar;
        $this->HANDLE[$ReturnVar] = 1;

        // FileTags is not an array

        $val = $FileTags;

        if ((substr($val, 0, 1)) == '.') {
            // Append this template to a previous ReturnVar

            $append = true;
            $val = substr($val, 1);
        }

        if ((!isset($this->TPL[$val])) || (empty($this->TPL[$val]))) {
            $this->LOADED[$val] = 1;
            $this->get_template($val);
        }

        if ($append) {
            // var_dump($ReturnVar);
            if(!isset($this->TPL[$ReturnVar])){
                $this->TPL[$ReturnVar] = $this->parse_template($val);
            } else {
                $this->TPL[$ReturnVar] .= $this->parse_template($val);
            }
        } else {
            $this->TPL[$ReturnVar] = $this->parse_template($val);
        }

        //    For recursive calls.
        $this->assign($ReturnVar, $this->TPL[$ReturnVar]);


        return;
    }    //    End parse()

    // alias na parse
    function p($ReturnVar, $FileTags)
    {
        $this->parse($ReturnVar, $FileTags);
    }

    //    ************************************************************

    function FastPrint($template = "")
    {
        if (empty($template)) {
            $template = $this->LAST;
        }

        if ((!(isset($this->TPL[$template]))) || (empty($this->TPL[$template]))) {
            $this->error("Nothing parsed, nothing printed [" . $template . "]", 0);
            return;
        } else {
            print($this->__ReplaceWords($this->TPL[$template]));
        }

        return;
    }
    //    ************************************************************

    function FastSave($filename, $template = "")
    {
        if ($filename == "") {
            $this->error("FastSave()  I don't know file name!", 0);
            return;
        }

        if (empty($template)) {
            $template = $this->LAST;
        }

        if ((!(isset($this->TPL[$template]))) || (empty($this->TPL[$template]))) {
            $this->error("Nothing parsed, nothing saved [" . $template . "]", 0);
            return;
        } else {
            //print $this->$template;
            $fp = fopen($filename, "w");
            fwrite($fp, $this->TPL[$template]);
            fclose($fp);
        }
        return;
    }
    //    ************************************************************

    /*
	function StylizeContent ($text)
	{
		$arr = array(
			"/(<input type=[\"\'](button|submit)[\"\'][^>]*(\/)?->)/i" => "<table class='button_table'><tr> <td class='l'>&nbsp;</td> <td class='c'>\\1</td> <td class='r'>&nbsp;</td> </tr></table>",
			"/(<button([^>]+)?->[^>]+>)/i" => "<table class='button_table'><tr> <td class='l'>&nbsp;</td> <td class='c'>\\1</td> <td class='r'>&nbsp;</td> </tr></table>",
		);

		return preg_replace(array_keys($arr), $arr, $text);
	}    
	*/

    //    ************************************************************    

    function fetch($template = "")
    {
        if (empty($template)) {
            $template = $this->LAST;
        }
        if ((!(isset($this->TPL[$template]))) || (empty($this->TPL[$template]))) {
            $this->error("Nothing parsed, nothing printed [" . $template . "] ", 0);
            return "";
        }

        return ($this->__ReplaceWords($this->TPL[$template]));
    }


    //    ************************************************************

    function define($fileList)
    {
        foreach ($fileList as $FileTag => $FileName) {
            $this->FILELIST[$FileTag] = $FileName;
        }
        return true;
    }

    function define2($FileTag, $FileName)
    {
        $this->FILELIST[$FileTag] = $FileName;
        return true;
    }

    //    ************************************************************

    function clear_parse($ReturnVar = "")
    {
        $this->clear($ReturnVar);
    }

    //    ************************************************************

    function clear($ReturnVar = "")
    {
        // Clears out hash created by call to parse()

        if (!empty($ReturnVar)) {
            if ((gettype($ReturnVar)) != "array") {
                unset($this->TPL[$ReturnVar]);
                return;
            } else {
                foreach ($ReturnVar as $key => $val) {
                    unset($this->TPL[$val]);
                }
                return;
            }
        }

        // Empty - clear all of them

        foreach ($this->HANDLE as $key => $val) {
            unset($this->TPL[$key]);
        }
        return;
    }    //    end clear()

    //    ************************************************************

    function clear_all()
    {
        $this->clear();
        $this->clear_assign();
        $this->clear_define();
        $this->clear_tpl();

        return;
    }    //    end clear_all

    //    ************************************************************

    function clear_tpl($fileHandle = "")
    {
        if (empty($this->LOADED)) {
            // Nothing loaded, nothing to clear

            return true;
        }
        if (empty($fileHandle)) {
            // Clear ALL fileHandles
            foreach ($this->LOADED as $key => $val) {
                unset($this->TPL[$key]);
            }

            unset($this->LOADED);

            return true;
        } else {
            if ((gettype($fileHandle)) != "array") {
                if ((isset($this->TPL[$fileHandle])) || (!empty($this->TPL[$fileHandle]))) {
                    unset($this->LOADED[$fileHandle]);
                    unset($this->TPL[$fileHandle]);
                    return true;
                }
            } else {
                foreach ($fileHandle as $key => $val) {
                    unset($this->LOADED[$key]);
                    unset($this->TPL[$key]);
                }

                return true;
            }
        }

        return false;
    }    // end clear_tpl

    //    ************************************************************

    function clear_define($FileTag = "")
    {
        if (empty($FileTag)) {
            unset($this->FILELIST);
            return;
        }

        if ((gettype($Files)) != "array") {
            unset($this->FILELIST[$FileTag]);
            return;
        } else {
            foreach ($FileTag as $tag => $val) {
                unset($this->FILELIST[$tag]);
            }
            return;
        }
    }


    //    ************************************************************
    //    Clears all variables set by assign()

    function clear_assign()
    {
        if (!(empty($this->PARSEVARS))) {
            foreach ($this->PARSEVARS as $ref => $val) {
                unset($this->PARSEVARS[$ref]);
            }
        }
    }

    //    ************************************************************

    function clear_href($href)
    {
        if (!empty($href)) {
            if ((gettype($href)) != "array") {
                unset($this->PARSEVARS[$href]);
                return;
            } else {
                foreach ($href as $ref => $val) {
                    unset($this->PARSEVARS[$ref]);
                }
                return;
            }
        } else {
            // Empty - clear them all

            $this->clear_assign();
        }
        return;
    }



    //    ************************************************************
    // eto sdelano dlja uskorenija
    function a($tpl, $trailer)
    {
        $this->PARSEVARS[$tpl] = $trailer;
    }
    // etoto sdelan dlja obratnoj sovmenstimosti so starim classom
    function assign($tpl, $trailer = "")
    {
        if (is_array($tpl)) {
            $this->assign_array($tpl);
            return;
        }

        $this->PARSEVARS[$tpl] = $trailer;
    }

    function assign_array($tpl_array)
    {
        if (!is_array($tpl_array)) {
            return;
        }
        foreach ($tpl_array as $key => $val) {
            if (!(empty($key))) {
                $this->PARSEVARS[$key] = $val;
            }
        }
    }

    function arr($tpl_array)
    {
        $this->assign_array($tpl_array);
    }
    //    ************************************************************

    function assign_html($tpl, $trailer = "")
    {
        if (!is_array($tpl)) {
            $this->PARSEVARS[$tpl] = htmlspecialchars((string) $trailer, ENT_QUOTES, "UTF-8");
            return;
        }

        foreach ($tpl as $key => $val) {
            $this->PARSEVARS[$key] = htmlspecialchars((string) $val, ENT_QUOTES, "UTF-8");
            //$this->assign_html( $key, $val );
        }
    }

    //    ************************************************************

    function ah($tpl, $trailer = "")
    {
        $this->assign_html($tpl, $trailer);
    }


    function assign_append($tpl, $trailer)
    {
        // Empty values are allowed in non-array context now.
        $this->PARSEVARS[$tpl] .= $trailer;
    }

    //    ************************************************************
    //    Return the value of an assigned variable.
    //    Christian Brandel cbrandel@gmx.de

    function get_assigned($tpl_name = "")
    {
        if (empty($tpl_name)) {
            return false;
        }
        if (isset($this->PARSEVARS[$tpl_name])) {
            return ($this->PARSEVARS[$tpl_name]);
        } else {
            return false;
        }
    }

    //    ************************************************************

    function error($errorMsg, $die = 0)
    {


        $hz = debug_backtrace();
        // print_r($hz);
        //if($this->ERROR_PRINT )
        if ($this->PRINT_ERROR)  print "<div id=\"tplerror\" style=\"border:1px solid red;padding:10px;margin:5px; font-size:18px\"><b>TEMPLATE ERROR:</b> " . $errorMsg . "<br><b>at</b> " . $hz[2]["file"] . " <b>line</b> " . $hz[2]["line"] . "</div>";
        // print "<b>SQL ERROR:</b> " .$str ."<br>";

        // $this->ERROR[] = $str;


        //  $this->ERROR = $errorMsg;
        //  echo "ERROR: $this->ERROR <BR> \n";
        if ($die == 1) {
            exit;
        }

        return;
    } // end error()
    //    ************************************************************
    // funkcija stroit spisok options i pomescaet v kazanuju peremenuju

    function option_list($tpl, $id, $array)
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

        $this->PARSEVARS[$tpl] = $option;
        unset($option);
        //     return $option;
    }



    //    ************************************************************
    // moja funkcija
    // ustanavlivaem sabloni iz php v rucnuju
    // *v buduscem vse meslie sabloni budut vineseni v odin fail
    // *i budut parsitsja otdeljno
    // *ctobi ne kopatjsja v kuce meskih failov
    // v nee mozno peredavatj toka tegi v takom formate {}

    function define_template($template, $trailer = "")
    {
        if (!is_array($template) && $trailer != "") {
            $this->LOADED[$template] = 1;
            $this->FILELIST[$template] = "none";
            $this->TPL[$template] = preg_replace('/\{([A-Z0-9_]+)\}/', '<' . '?= $this->PARSEVARS["\1"]; ?' . '>', $trailer);
            //$this->$template = $contents;
            // return $contents;

        } else {
            foreach ($template as $key => $val) {
                $this->LOADED[$key] = 1;
                $this->TPL[$key] = preg_replace('/\{([A-Z0-9_]+)\}/', '<' . '?= $this->PARSEVARS["\1"]; ?' . '>', $val);
                $this->FILELIST[$key] = "none";
            }
        }
    }

    //    ************************************************************
    /*
      moja funkcija dlja sozdaniaj raznorodnogo spiska sablonov
      za odin prohod iz odnogo faila

      nuzna dlja postrojki tablic s neskoljkimi  raznimi strokami

      kasdij element dolzen bitj otdelen ot drugogo
      <!-- [name] -->  (odin na stroku)
      po nemu budet delitjsja etot file

      gde name   imja sablona

	  imja dolzno bitj standartnoe

	  takse posle [name] cerez probel, mozno pisatj korotkoe opisanie etogo sablona,
      opisnaij dolzno bitj v odnu stroku, i zelateljno ctobi soderzalo toka
      bukvi cifri i probeli

      pervij i poslednij elementi masiva idut kak obramlenie dlja vseh strok
      
      tak cto <!-- [-] --> dolzen  otdeljatj footer

      $fileTag - imja sablona kotorij parsim
      $metka - peremenaja kotoraja budet zamenjatjsja etimi sablonami

    */
    function split_template($fileTag, $metka)
    {



        if ((!isset($this->TPL[$fileTag])) || (empty($this->TPL[$fileTag]))) {
            $this->LOADED[$fileTag] = 1;
            //   $fileName = $this->FILELIST[$val];
            //   $this->$val = $this->get_template($fileName);
            $this->get_template($fileTag);
        }
        //
        $file_name = basename($this->FILELIST[$fileTag]);

        $this->SPLIT_TPL[] = $file_name;
        //$tmp = preg_split("/<!-- \[(.*)\] -->/",$this->TPL[$fileTag],-1,PREG_SPLIT_DELIM_CAPTURE);
        $tmp = preg_split("/<!-- \[(.*)\] .*-->/", $this->TPL[$fileTag], -1, PREG_SPLIT_DELIM_CAPTURE);

        // berem pervij i poslednij elementi masiva
        $this->TPL[$fileTag] = array_shift($tmp) . "<" . "?= \$this->PARSEVARS[" . $metka . "];?" . ">" . array_pop($tmp);
        // udaljaem predposlednij
        //array_pop($tmp);
        // print_r($tmp);
        for ($i = 0; $i < count($tmp); $i += 2) {
            $this->TPL[$tmp[$i]] = ($this->TPL_NAMES_INCLUDE ? ('<!--' . $tmp[$i] . '-->') : '') .     // template name
                ($this->TPL_DELETE_SPACES ? (preg_replace(
                    ["'([\t\s]+)'", "'([\r\n]+[\s]*)'"],
                    [' ', "\n"],
                    $tmp[$i + 1]
                )) : (isset($tmp[$i + 1]) ? $tmp[$i + 1] : '')); // template
            $this->LOADED[$tmp[$i]] = 1;
            //$this->FILELIST[$tmp[$i]] = $tmp[$i];
            $this->FILELIST[$tmp[$i]] = $file_name;
        }


        unset($tmp);

        return $file_name;
    }

    // **************************************************************
    //  funkcija objavljaet globaljnie peremenniedlja parse_template
    //  kazidj vizov funkcii sozdaet novij nabor dannih dlja globalizacii
    //  peremennie v nee dolzni perdavatjsja bez $ v masive ili prostoj peremennoj
    function add_global($array = "")
    {
        if ($array == "") {
            $this->VAR_GLOBAL = "";
        } else {
            if (!is_array($array)) {
                $this->VAR_GLOBAL = "\$" . $array;
            } else {
                foreach ($array as $key => $val) {
                    $tmp[] = "\$" . $val;
                }
                $this->VAR_GLOBAL = join($tmp, ",");
            }
        }
        return;
    }

    // **************************************************************
    // izvrascennoe include failov cerez sablon
    // tak mozno podklucatj faili, kotoriene nado budet obrabativatj
    function assign_file($tpl, $filename)
    {
        $contents = @implode("", (@file($filename)));

        if ((!$contents) or (empty($contents))) {
            $this->error("assign_file() failure: [$filename] $php_errormsg", 0);
            $contents = $filename;
        }

        $this->PARSEVARS[$tpl] = $contents;
    }

    //  ************************************************************

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


    // *****
    // postroenie spiska stranic na osnoce sablona
    // $taq - kuda vstavljatj rezuljtat parsinga
    // $qty - kolicestvo zapisej
    // $kolvo  - kolvo zapisej na srtanicu
    // $page - nomer tekuscej stranici
    // $url - url v kotorij vsravivaetsaj nomer stanici
    //        formata takogo "********.php?********&page="
    // $dlina - skoljko stranic vivoditj, po umolcaniju 5
    // $onclick - imja funkcii na onclick
    function pages_list($tag, $qty, $kolvo, $page, $url, $dlina = 5, $onclick = "", $anchor = "")
    {
        // esli kolicestvo stranic menjse cem nado vivoditj na stranicu
        if ($kolvo >= $qty) return;

        $this->clear("PAGES_ROW");

        $page = (int) $page;

        // opredeljaem tekuscuju stranicu
        if ($page == 0) {
            $page_str = 1;
        } else {
            $page_str = (int) ($page / $kolvo) + 1;
        }

        $num = 0;
        //    $list = - $kolvo;
        //    $qty -= $kolvo;

        // uznatj kolicestvo stranic
        $pages = ceil($qty / $kolvo);

        // esli tekuscaja stranica boljse pokazivaemih stranic
        // to vivodim ssilku prev

        $delta1 = $page_str - $dlina - 1;
        if ($delta1 < 0) $delta1 = 0;

        $delta2 = $page_str + $dlina;
        if ($delta2 > $pages) $delta2 = $pages;

        if ($delta1 > 0) {
            if ($onclick != "") {
                $this->assign("ONCLICK", 'onClick="' . $onclick . '(' . (($delta1 - 1) * $kolvo) . '); return false;"');
            }

            if ($url instanceof \_class\PageListRoute) {

                $this->assign("PAGES_URL", $this->urlFor($url->getName(), array_merge($url->getParams(), [$url->getOffsetName() => ($delta1 - 1) * $kolvo])));
            } else {

                $this->assign("PAGES_URL", $url . (($delta1 - 1) * $kolvo));
            }

            // $this->parse("PAGES_ROW",".pages_prev");
            $this->parse("PAGES_PREV", ".pages_prev");
        }

        // strim razresennie srtanici
        for ($i = $delta1; $i < $delta2; $i++) {
            $tmp_page = $i * $kolvo;

            if ($url instanceof \_class\PageListRoute) {

                $this->assign_array(array(
                    "PAGES_URL" => $this->urlFor($url->getName(), array_merge($url->getParams(), [$url->getOffsetName() => $tmp_page])) . ($anchor ? '#' . $anchor : ''),
                    "PAGES_NUM" => $i + 1
                ));
            } else {
                $this->assign_array(array(
                    "PAGES_URL" => $url . $tmp_page . ($anchor ? '#' . $anchor : ''),
                    "PAGES_NUM" => $i + 1
                ));
            }

            if ($onclick != "") {
                $this->assign("ONCLICK", 'onClick="' . $onclick . '(' . $tmp_page . '); return false;"');
            }

            if ($page == $tmp_page) {
                $this->parse("PAGES_ROW", ".pages_select");
            } else {
                $this->parse("PAGES_ROW", ".pages_item");
            }
        }

        // esli ne pokazali poslednjuu stranicu
        // vivodim ssilku next
        if ($delta2 < $pages) {

            if ($onclick != "") {
                $this->assign("ONCLICK", 'onClick="' . $onclick . '(' . (($delta2) * $kolvo) . '); return false;"');
            }

            if ($url instanceof \_class\PageListRoute) {
                $this->assign("PAGES_URL", $this->urlFor($url->getName(), array_merge($url->getParams(), [$url->getOffsetName() => $delta2 * $kolvo])));
            } else {
                $this->assign("PAGES_URL", $url . (($delta2) * $kolvo));
            }

            $this->parse("PAGES_NEXT", ".pages_next");
        }

        $this->parse($tag, "pages_table");
    }
    // end pages_list()


    function set_variant($str)
    {
        $this->VARIANT = $str;
        $this->a("TPL", $str);
    }

    //	funkcija dlja aktivacii helpa
    function help_replace($ReturnVar, $FileTags)
    {
        $this->assign("KODE", "\${1}");
        $this->parse("HELP_CONTAINER", "help");
        $search = "/<help[\s]+data=[\'\"]([A-Z_0-9\|]+)[\'\"][^>]+" . "\/>/isu";
        $return = preg_replace($search, $this->fetch("HELP_CONTAINER"), $this->TPL[$ReturnVar]);
        $this->TPL[$ReturnVar] = $return;
        //var_dump($return);
        /*$search = "/<help[\s]+data=[\'\"]([A-Z_0-9\|]+)[\'\"][^>]+"."\/>/isu";
        $return = preg_replace($search, $this->TPL["help"], $this->TPL[$ReturnVar]);*/
        //$this->assign($ReturnVar, $return);

    }

    public function set_router(route\Router $router)
    {
        $this->router = $router;
    }

    public function set_lang(& $lang)
    {
        $this->lang = & $lang;
    }

    public function set_db(db $db)
    {
        $this->db = $db;
    }

    public function urlFor($name, $params = []) {
        return $this->router->urlFor($name, array_merge(['ln' => $this->lang], array_filter($params)));
    }

    public function urlForMerge($name = '', $params = []) {
        return $this->router->urlForMerge($name, array_merge(['ln' => $this->lang], array_filter($params)));
    }

    public function insertSVG($path) {
        // return file_get_contents( SECURE_HOST . $path);
        return file_get_contents(PUBLIC_DIR . $path);
    }

    public function setWordsProcessing(WordsProcessing $wordsProcessing) {
		$this->wordsProcessing = $wordsProcessing;
	}
} // End class.FastTemplate.php
