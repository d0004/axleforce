<?php

include_once("../_main.php");

$xml = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />');

$track = $xml->addChild('url');
$track->addChild('loc', APP_DOMAIN);    
$track->addChild('lastmod', date("Y-m-d"));
$track->addChild('changefreq', 'weekly');    
$track->addChild('priority', '0.5');

$track = $xml->addChild('url');
$track->addChild('loc', APP_DOMAIN . '/lv/about/');    
$track->addChild('lastmod', date("Y-m-d"));
$track->addChild('changefreq', 'weekly');    
$track->addChild('priority', '0.5');

$track = $xml->addChild('url');
$track->addChild('loc', APP_DOMAIN . '/lv/contacts/');    
$track->addChild('lastmod', date("Y-m-d"));
$track->addChild('changefreq', 'weekly');    
$track->addChild('priority', '0.5');

$track = $xml->addChild('url');
$track->addChild('loc', APP_DOMAIN . '/lv/privacy-policy/');    
$track->addChild('lastmod', date("Y-m-d"));
$track->addChild('changefreq', 'weekly');    
$track->addChild('priority', '0.5');

$track = $xml->addChild('url');
$track->addChild('loc', APP_DOMAIN . '/lv/purchase-terms/');    
$track->addChild('lastmod', date("Y-m-d"));
$track->addChild('changefreq', 'weekly');    
$track->addChild('priority', '0.5');

$categoryClass = new \category\Category;

$result = $db->query("SELECT * 
FROM shop_categories 
INNER JOIN shop_categories_lang USING (CATEGORY_ID)
WHERE LANG = 'lv' AND STATUS = 2 AND DELETED = 0")->fetchAll();

foreach($result as $row){
    $track = $xml->addChild('url');
    $track->addChild('loc', APP_DOMAIN . $tpl->urlFor('category/index', ['ln' => 'lv', 'slug' => $categoryClass->getUrl($row['CATEGORY_ID'])]));    
    $track->addChild('lastmod', date("Y-m-d"));
    $track->addChild('changefreq', 'weekly');    
    $track->addChild('priority', '0.5');   
}

$result = $db->query("SELECT * 
FROM shop_products
INNER JOIN shop_products_category USING (ITEM_ID)
INNER JOIN shop_products_prices USING (ITEM_ID)
WHERE STATUS = 2 AND DELETED = 0")->fetchAll();

foreach($result as $row){
    $track = $xml->addChild('url');
    $track->addChild('loc', APP_DOMAIN . $tpl->urlFor('products/single_product', ['ln' => 'lv', 'slug' => $row['NEW_SKU']]));    
    $track->addChild('lastmod', date("Y-m-d", strtotime($row['UPDATE_DATE'])));    
    $track->addChild('changefreq', 'weekly');    
    $track->addChild('priority', '0.5');   
}


Header('Content-type: text/xml');
print($xml->asXML());