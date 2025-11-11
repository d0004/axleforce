<?php

namespace _class;

class Document
{

    protected $styles = [];
    protected $scriptsHeader = [];
    protected $scriptsFooter = [];
    protected $metaTitle = '';
    protected $metaKeywords = '';
    protected $metaDescription = '';
    protected $overrideUrlParams = [];


    public function setOverrideUrlParams($params)
    {
        $this->overrideUrlParams = $params;
    }

    public function getOverrideUrlParams()
    {
        return $this->overrideUrlParams;
    }

    public function addStyle($style)
    {
        $this->styles[] = $style;
    }

    public function addScript($script, $placeHeader = true)
    {
        if($placeHeader){
            $this->scriptsHeader[] = $script;
        } else {
            $this->scriptsFooter[] = $script;
        }
    }

    public function getStyles()
    {
        return $this->styles;
    }

    public function getScripts($placeHeader = true)
    {
        if($placeHeader){
            return $this->scriptsHeader;
        } else {
            return $this->scriptsFooter;
        }
    }

    public function setMetaTitle($title)
    {
        $this->metaTitle = $title;
    }
    
    public function setMetaKeywords($keywords)
    {
        $this->metaKeywords = $keywords;
    }
    
    public function setMetaDescription($description)
    {
        $this->metaDescription = $description;
    }

    public function getMetaTitle()
    {
        return $this->metaTitle;
    }
    
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

}