<?php

namespace system;

class Menu extends \_class\AbstractClass
{

    protected $tpl;
    protected $menuPages = [];

    public function __construct()
    {
        parent::__construct();
        include_once(APP_DIR . '/system/menu_langs.php');

        $this->menuPages = [
            HOME_PAGE_LANG => "index",
            ABOUT_PAGE_LANG => "about",
            CONTACTS_PAGE_LANG => "contacts",
            // "Sebmenu" => [
            //     "parent" => [
            //         "Parent page" => "index",    
            //     ],
            //     "sub" => [
            //         "Child page 1" => "index",
            //         "Child page 2" => "index",
            //     ],
            // ],
        ];
    }
    
    public function showMenu()
    {

        $this->tpl->define(["site_menu" => "/system/tpl/menu.html"]);
        $this->tpl->split_template("site_menu", "SITE_MENU");

        foreach($this->menuPages as $title => $page){
            if(is_array($page)){
                foreach($page['sub'] as $titleChild => $linkChild){
                    $this->tpl->assign_array([
                        "LINK_TO_CHILD_PAGE" => $this->tpl->urlFor($linkChild),
                        "CHILD_PAGE_NAME" => $titleChild,
                    ]);
                    $this->tpl->parse("CHILD_PAGES", ".child_page_row");    
                    $this->tpl->parse("MOBILE_CHILD_ELEMETS", ".mobile_child_elements");    
                }
                foreach($page['parent'] as $titleParent => $linkParent){
                    $this->tpl->assign_array([
                        "PARENT_LINK_TO_PAGE" => $this->tpl->urlFor($linkParent),
                        "PARENT_PAGE_NAME" => $titleParent,
                    ]);
                }
                $this->tpl->parse("NAVBAR_PAGES", ".dropdown_page_block");
                $this->tpl->parse("MOBILE_MENU_ELEMENT", ".mobile_menu_element_with_submenu");
            } else {
                $this->tpl->assign_array([
                    "LINK_TO_PAGE" => $this->tpl->urlFor($page),
                    "PAGE_NAME" => $title,
                ]);
                $this->tpl->parse("NAVBAR_PAGES", ".page_block");
                $this->tpl->parse("MOBILE_MENU_ELEMENT", ".mobile_menu_element");
            }
        }

        foreach(\_class\Registry::load('fullLanguages') as $langCode => $langTitle){
            if(request()->getLoadedRoute()){
                // echo '<pre>' . print_r($this->document->getOverrideUrlParams(), 2) . '</pre>';

                $routeName = request()->getLoadedRoute()->getName();
                $routeParams = request()->getLoadedRoute()->getParameters();
                $routeParams['ln'] = $langCode;

                $overrided = $this->document->getOverrideUrlParams();
                $overrided = $overrided[$langCode] ?? [];

                $routeParams = array_merge($routeParams, $overrided);
                // echo '<pre>' . print_r($overrided, 2) . '</pre>';

                $this->tpl->assign_array([
                    "LANGUAGE_CODE" => $langCode,
                    "LANGUAGE_TITLE" => $langTitle,
                    "NEW_LANGUAGE_LINK" => $this->tpl->urlFor($routeName, $routeParams)
                ]);
            }

            $this->tpl->parse("DESKTOP_LANGUAGE_ITEMS", ".desktop_language_item");
            $this->tpl->parse("MOBILE_LANGUAGE_ITEM", ".mobile_language_item");
        }

        $this->tpl->assign("CURRENT_LANGUAGE", \_class\Registry::load('lang'));

        // var_dump(\_class\Registry::load('router')->getCurrentRoute()->getName()); die;

        $this->tpl->parse("NAVBAR", "site_menu");
    }

    

    // public function desktopCategoryMenu()
    // {
    
    //     $categoryClass = new \category\Category;

    //     $this->tpl->define(["desktop_category_menu" => "/system/tpl/category_menu.html"]);
    //     $this->tpl->split_template("desktop_category_menu", "DESKTOP_CATEGORY_MENU");

    //     $result = $categoryClass->getCategoriesByParent(0);


    //     // echo '<pre>' . print_r($categoryClass->getCategoriesByParent(84), 2) . '</pre>'; die;

    //     foreach($result as $category){
    //         $this->tpl->clear_parse("LEVEL_2_CHILD_ELEMENTS");
    //         $this->tpl->clear_parse("DROPDOWN_MENU_CONTENT");
    //         $this->tpl->clear_parse("DESKTOP_CATEGORY_MENU_ITEMS");
    //         $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS");
    //         $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE");

    //         $category['TITLE'] = mb_ucfirst(mb_strtolower($category['TITLE']));
    //         if($category['CHILD_COUNT'] > 0){
                
    //             $childs = $categoryClass->getCategoriesByParent($category['CATEGORY_ID']);
    //             foreach($childs as $child){
    //                 $this->tpl->clear_parse("LEVEL_2_CHILD_ELEMENTS");

    //                 $child['TITLE'] = mb_ucfirst(mb_strtolower($child['TITLE']));
    //                 if($child['CHILD_COUNT'] > 0){
    //                     $childLevel2 = $categoryClass->getCategoriesByParent($child['CATEGORY_ID']);

    //                     if($childLevel2){
    //                         foreach($childLevel2 as $level2){
    //                             $level2['TITLE'] = mb_ucfirst(mb_strtolower($level2['TITLE']));
    //                             $this->tpl->assign_array($level2); 
    //                             $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($level2['CATEGORY_ID']));  
    //                             $this->tpl->parse("LEVEL_2_CHILD_ELEMENTS", ".dropdown_category_menu_simple_item");
    //                         }

    //                         $this->tpl->assign_array($child);
    //                         $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
    //                         $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS", ".dropdown_category_menu_parent_item");
    //                     } else {
    //                         $this->tpl->clear_parse("LEVEL_2_CHILD_ELEMENTS");
    //                         $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS");
    //                         $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE");

    //                         $this->tpl->assign_array($child);
    //                         $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
    //                         $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE", ".dropdown_category_menu_simple_item");
    //                         // $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS", ".dropdown_category_menu_simple_item");
    //                     }
    //                 } else {
    //                     $this->tpl->assign_array($child);
    //                     $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
    //                     $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE", ".dropdown_category_menu_simple_item");
    //                     // $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS", ".dropdown_category_menu_simple_item");
    //                 }
    //             }

    //             $this->tpl->assign_array($category);
    //             $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID']));
    //             $this->tpl->parse("DROPDOWN_MENU_CONTENT", ".dropdown_menu_content");
    //             $this->tpl->parse("CATEGORY_ITEM_IN_MENU", ".item_with_childs");
    //         } else {
    //             $this->tpl->assign_array($category);
    //             $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID']));
    //             $this->tpl->parse("CATEGORY_ITEM_IN_MENU", ".item_no_child");
    //         }
    //     }

    //     $this->tpl->parse("DESKTOP_CATEGORY_MENU_ITEMS", "desktop_category_menu");
    // }

    public function desktopCategoryMenu()
    {
        $categoryClass = new \category\Category;

        // Определение и разбиение шаблона
        $this->tpl->define(["desktop_category_menu" => "/system/tpl/category_menu.html"]);
        $this->tpl->split_template("desktop_category_menu", "DESKTOP_CATEGORY_MENU");

        // Получаем корневые категории (с parent_id = 0)
        $rootCategories = $categoryClass->getCategoriesByParent(0);

        foreach ($rootCategories as $category) {
            // Очистка шаблонов для каждой категории
            $this->clearCategoryTemplates();

            // Приводим название категории в нужный формат
            $category['TITLE'] = mb_ucfirst(mb_strtolower($category['TITLE']));

            $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS");
            // Обрабатываем категории с подкатегориями
            if ($category['CHILD_COUNT'] > 0) {
                $this->processCategoryWithChildren($category, $categoryClass);
            } else {
                // Категория без подкатегорий
                $this->tpl->assign_array($category);
                $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID']));
                $this->tpl->parse("CATEGORY_ITEM_IN_MENU", ".item_no_child");

            }
        }

        // Парсинг итогового меню
        $this->tpl->parse("DESKTOP_CATEGORY_MENU_ITEMS", "desktop_category_menu");
    }

    /**
     * Очищает все нужные шаблоны перед началом обработки каждой категории.
     */
    private function clearCategoryTemplates()
    {
        $this->tpl->clear_parse("LEVEL_2_CHILD_ELEMENTS");
        $this->tpl->clear_parse("DROPDOWN_MENU_CONTENT");
        $this->tpl->clear_parse("DESKTOP_CATEGORY_MENU_ITEMS");
        $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS");
        $this->tpl->clear_parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE");

        $this->tpl->assign("LEVEL_2_CHILD_ELEMENTS", "");
        $this->tpl->assign("DROPDOWN_MENU_CONTENT", "");
        $this->tpl->assign("DESKTOP_CATEGORY_MENU_ITEMS", "");
        $this->tpl->assign("CATEGORY_DROPDOWN_MENU_ELEMENTS", "");
        $this->tpl->assign("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE", "");
    }

    /**
     * Обрабатывает категории с подкатегориями (уровень 1 и 2).
     */
    private function processCategoryWithChildren($category, $categoryClass)
    {
        // Получаем подкатегории первого уровня
        $childCategories = $categoryClass->getCategoriesByParent($category['CATEGORY_ID']);

        foreach ($childCategories as $child) {
            // Очистка шаблона уровня 2 перед каждым ребенком
            $this->tpl->clear_parse("LEVEL_2_CHILD_ELEMENTS");

            $child['TITLE'] = mb_ucfirst(mb_strtolower($child['TITLE']));

            // echo $child['CATEGORY_ID'] . "-" . $child['CHILD_COUNT'] . " ";

            // Если у подкатегории есть свои дети (2-й уровень)
            if ($child['CHILD_COUNT'] > 0) {
                $this->processChildCategoryWithChildren($child, $categoryClass);
            } else {
                // Подкатегория без детей (простой элемент)
                $this->tpl->assign_array($child);
                $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
                $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE", ".dropdown_category_menu_simple_item");
            }
        }

        // Парсинг родительской категории с дочерними элементами
        $this->tpl->assign_array($category);
        $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID']));
        $this->tpl->parse("DROPDOWN_MENU_CONTENT", ".dropdown_menu_content");
        $this->tpl->parse("CATEGORY_ITEM_IN_MENU", ".item_with_childs");
    }

    /**
     * Обрабатывает подкатегории второго уровня.
     */
    private function processChildCategoryWithChildren($child, $categoryClass)
    {
        // Получаем подкатегории второго уровня
        $childLevel2Categories = $categoryClass->getCategoriesByParent($child['CATEGORY_ID']);

        // Если есть подкатегории второго уровня, парсим их
        if ($childLevel2Categories) {
            foreach ($childLevel2Categories as $level2) {
                $level2['TITLE'] = mb_ucfirst(mb_strtolower($level2['TITLE']));
                $this->tpl->assign_array($level2);
                $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($level2['CATEGORY_ID']));
                $this->tpl->parse("LEVEL_2_CHILD_ELEMENTS", ".dropdown_category_menu_simple_item");
            }

            // Парсинг родительской подкатегории с детьми
            $this->tpl->assign_array($child);
            $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
            $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS", ".dropdown_category_menu_parent_item");
        } else {
            // Если нет подкатегорий второго уровня, парсим как простой элемент
            $this->tpl->assign_array($child);
            $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
            $this->tpl->parse("CATEGORY_DROPDOWN_MENU_ELEMENTS_SIMPLE", ".dropdown_category_menu_simple_item");
        }
    }


    public function mobileCategoryMenu()
    {
        $categoryClass = new \category\Category;

        $this->tpl->define(["mobile_category_menu" => "/system/tpl/mobile_category_menu.html"]);
        $this->tpl->split_template("mobile_category_menu", "MOBILE_CATEGORY_MENU");

        $result = $categoryClass->getCategoriesByParent(0);
        foreach($result as $category){

            $this->tpl->clear_parse("MOBILE_CATEGORY_SUBMENU_ELEMENT");
            // $this->tpl->clear_parse("CATEGORY_MOBILE_ELEMENT");
           
            $category['TITLE'] = mb_ucfirst(mb_strtolower($category['TITLE']));

            if($category['CHILD_COUNT'] > 0){
                $childs = $categoryClass->getCategoriesByParent($category['CATEGORY_ID']);
                foreach($childs as $child){
                    $child['TITLE'] = mb_ucfirst(mb_strtolower($child['TITLE']));
                    $this->tpl->assign_array($child);
                    $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($child['CATEGORY_ID']));
                    $this->tpl->parse("MOBILE_CATEGORY_SUBMENU_ELEMENT", ".mobile_category_submenu_element");
                }
                $this->tpl->assign_array($category);
                $this->tpl->parse("CATEGORY_MOBILE_ELEMENT", ".has_sub_menu_element");
            } else {
                $this->tpl->assign_array($category);
                $this->tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID']));
                $this->tpl->parse("CATEGORY_MOBILE_ELEMENT", ".no_sub_menu_element");
            }
        }

        $this->tpl->parse("MOBILE_SHOP_MENU", "mobile_shop_menu");
        
    }
}


