<?php
/** 
 * XML Builder Library
 */
require_once 'vendor/autoload.php';
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;

/** 
 * Parse Class 
 */

 class Parse  
{
    public $url;
    public $categories_links_json_file = 'linksFromCat/categories_links.json';
    
    public function __construct($url) {
        $this->url = $url;
    }

    public function parseLinksFromCategory()
    {
        $url = $this->url;
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return 'Links is Not valid';
            die();
        }
    
        $varInJsFile = 'var url="' . $url .'"';
        $jsString =  $varInJsFile . file_get_contents("js_files/parseLinks.js");
        $filename = 'folder/category_' . rtrim(substr($url,34), '/') . '.js';
        file_put_contents($filename, $jsString);
        shell_exec("phantomjs.exe {$filename}");
        if (file_exists('linksFromCat/categories_links.json')) {
            unlink($filename);
            $this->categories_links_json_file = 'linksFromCat/categories_links.json';
        }
    }  
    
    public function ParseEachArticle()
    {
        if (file_exists($this->categories_links_json_file)) {
            $jsonstring = file_get_contents($this->categories_links_json_file);
            $links = json_decode($jsonstring, true);

            $img_links =  $links[1];
            foreach ($img_links as $link) {
                $featImageName = substr($link, strripos($link, '/')+1);
                $imgName = 'photos/' . $featImageName;
                file_put_contents($imgName, file_get_contents($link));
                if(!file_exists($imgName)){
                    die('Cannot Download Fife');
                }
            }

            $article_links = $links[0];
            foreach ($article_links as $article_link) {
                $varInJsFile = 'var url="' . $article_link .'";';
                $jsString =  $varInJsFile . file_get_contents("js_files/articleParse.js");
                $filename = 'folder/' . rtrim(substr($article_link,25), '/') . '.js';
                file_put_contents($filename, $jsString);
                shell_exec("phantomjs.exe {$filename}");

                if (file_exists('folder/'.rtrim(substr($article_link,25), '/').'.json')) {
                    unlink($filename);   
                }   
            }
        }  
    }

    public function BuildXMLFileFromJSON()
    {
        
        $xmlWriterService = new XMLWriterService();
        $xmlBuilder = new XMLBuilder($xmlWriterService);
        
        $dir = dir('folder/');
        $jsonfiles = [];
        while (false !== ($entry = $dir->read())) {
            if ($entry != '.' && $entry != '..') {
                if (file_exists('folder/' .$entry)) {
                    $jsonfiles[] = 'folder/' .$entry;
                }
            }
        }


        $bigObj = array();
        foreach ($jsonfiles as $file) {
         	$bigObj[] = $this->jsontoXml($file);
        }
               

        try {
            $xmlBuilder
                ->createXMLArray()
                    ->startLoop('data', [], function (XMLArray $XMLArray) use ($bigObj){
                        foreach ($bigObj as $obj) {
                            $XMLArray->start('post')
                            ->add('Title', $obj[0])
                            ->addCData('Content', $obj[1])
                            ->add('Slug', $obj[2]);
                        }
                    })
                    ->end();
        
        } catch (XMLArrayException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());
        }


        file_put_contents('posts.xml', $xmlBuilder->getXML());
    }

    public function jsontoXml($Jsonfile){
        
        $slug = rtrim(substr($Jsonfile, 7), '.json');
        $json = file_get_contents($Jsonfile);
        $json = json_decode($json, true)[0];

        $h1 = $json['h1'];
        $thumbnail = $json['thumbnail'];

        $thumbName = substr($thumbnail, strripos($thumbnail, '/')+1);
        $imgName = 'photos/' . $thumbName;
        file_put_contents($imgName, file_get_contents($thumbnail));

        $content = '';
        $featName = rtrim($thumbName, '.jpg').'-250x190.jpg';
        
        if(file_exists('photos\\' . $featName)){
            $content .= PHP_EOL .'<!-- wp:image {"id":1} -->'.PHP_EOL . '<figure class="wp-block-image" id="displayNone"><img src="http://localhost/crafts/wp-content/uploads/2018/12/' . $featName . '" alt="" class="wp-image-1" /></figure>' . PHP_EOL .'<!-- /wp:image -->' . PHP_EOL;
        }
        
        $content .= PHP_EOL .'<!-- wp:image {"id":1} -->'.PHP_EOL . '<figure class="wp-block-image"><img src="http://localhost/crafts/wp-content/uploads/2018/12/' . $thumbName . '" alt="" class="wp-image-1" /></figure>' . PHP_EOL .'<!-- /wp:image -->' . PHP_EOL;


        $article = $json['article'];
        foreach ($article as $key => $value ) {

            foreach ($value as $key1 => $value1) {
                // echo $key1 . PHP_EOL;
                if($key1 === "ul"){
                    $lis = '';
                    foreach ($value1 as $li) {
                        $lis .= '<li>' . $li . '</li>';
                    }
                    $content .= PHP_EOL .'<!-- wp:list -->'.PHP_EOL . '<ul>' . $lis . '</ul>' . PHP_EOL .'<!-- /wp:list -->' . PHP_EOL;
                }else{
                    if($key1 === "p"){
                        $content .= PHP_EOL .'<!-- wp:paragraph -->'.PHP_EOL . '<p>' . $value1 . '</p>' . PHP_EOL .'<!-- /wp:paragraph -->' . PHP_EOL;
                    }else if($key1 === "strong"){
                        $content .= PHP_EOL .'<!-- wp:paragraph -->'.PHP_EOL . '<p><strong>' . $value1 . '</strong></p>' . PHP_EOL .'<!-- /wp:paragraph -->' . PHP_EOL;
                    }else if($key1 === "img"){
                        $fileName = substr($value1, strripos($value1, '/')+1);
                        $imgName = 'photos/' . $fileName;
                        $content .= PHP_EOL .'<!-- wp:image {"id":1} -->'.PHP_EOL . '<figure class="wp-block-image"><img src="http://localhost/crafts/wp-content/uploads/2018/12/' . $fileName . '" alt="" class="wp-image-1"/></figure>' . PHP_EOL .'<!-- /wp:image -->' . PHP_EOL;
                        file_put_contents($imgName, @file_get_contents($value1));
                    }
                }
            }
        }
        $array = [];
        $array[] = $h1;
        $array[] = $content;
        $array[] = $slug;
        
        return $array; 
    }
    
 }


 $cat = new Parse('');
