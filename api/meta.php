<?php
class html_meta{
    private $url = '';
    private $agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_0 like Mac OS X) AppleWebKit/602.1.38 (KHTML, like Gecko) Version/10.0 Mobile/14A5297c Safari/602.1';
    private $html = '';
    private $head;
    /**
     * @var mixed|string
     */
    private $host;
    /**
     * @var int|mixed|string
     */
    private $port;
    /**
     * @var mixed|string
     */
    private $scheme;

    public function __construct($url)
    {
        $this->url($url);
        $this->html = $this->get_url_content($this->url);
        $this->head();
    }

    private function url($url)
    {
        $parsed = parse_url($url);
        $this->host = $parsed['host'] ?? $parsed['path'];
        $this->scheme = $parsed['scheme'] ?? 'http';
        $this->port = $parsed['port'] ?? '';
        $this->url = $this->scheme . '://' . $this->host . ($this->port ? ':' . $this->port : '');
    }

    public function head()
    {
        if($this->head){
            return $this->head;
        }
        $dom = new DOMDocument();
        @$dom->loadHTML($this->html);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//head');
        if ($nodes->length > 0) {
            $this->head = new DOMXPath($nodes->item(0)->ownerDocument);
            return $this->head;
        }
        return false;
    }

    public function icon(): array
    {
        $icons = [];
        if(empty($this->head)){
            return $icons;
        }
        $tags = $this->head->query('//link[@rel="shortcut icon"]');
        foreach($tags as $tag){
            $icons[] = ['rel'=>'shortcut icon','type'=>$tag->getAttribute('type'),'sizes'=>$tag->getAttribute('sizes'),'href'=>$tag->getAttribute('href'),];
        }

        $tags = $this->head->query('//link[@rel="icon"]');
        foreach($tags as $tag){
            $icons[] = ['rel'=>'icon','type'=>$tag->getAttribute('type'),'sizes'=>$tag->getAttribute('sizes'),'href'=>$tag->getAttribute('href'),];
        }
        $tags = $this->head->query('//link[@rel="apple-touch-icon"]');
        foreach($tags as $tag){
            $icons[] = ['rel'=>'apple-touch-icon','type'=>$tag->getAttribute('type'),'sizes'=>$tag->getAttribute('sizes'),'href'=>$tag->getAttribute('href'),];
        }
        return $icons;
    }

    public function title()
    {
        $title = '';
        if(empty($this->head)){
            return $title;
        }
        $tags = $this->head->query('//title');
        if($tags->length==0){
            return $title;
        }
        return $tags->item(0)->textContent;
    }

    public function description()
    {
        $description = '';
        if(empty($this->head)){
            return $description;
        }
        $tags = $this->head->query('//meta[@name="description"]');
        if($tags->length==0){
            return $description;
        }
        return $tags->item(0)->getAttribute('content');
    }
    public function keywords()
    {
        $keywords = '';
        if(empty($this->head)){
            return $keywords;
        }
        $tags = $this->head->query('//meta[@name="keywords"]');
        if($tags->length==0){
            return $keywords;
        }
        return $tags->item(0)->getAttribute('content');
    }
    public function meta()
    {
        return [
            'title'=>$this->title(),
            'description'=>$this->description(),
            'keywords'=>$this->keywords(),
            'icon'=>$this->icon()
        ];
    }

    public function default_icon()
    {
        return file_get_contents(__DIR__ . '/cache/default.ico');
    }

    /**
     * @param $host
     * @param $type string ['json','ico']
     * @return false|void
     */
    public function check_cache(string $type='json')
    {
        $refresh = $_GET['refresh'] ?? false;
        if ($refresh) {
            return false;
        }

        $cacheFile = TMP_PATH . '/' . $this->host.'.'.$type;
        if (!file_exists($cacheFile)) {
            return false;
        }
        $content = file_get_contents($cacheFile);
        if (empty($content)) {
            return false;
        }
        return $content;
    }

    public function cache($content,$type='json')
    {
        $cacheFile = TMP_PATH . '/' . $this->host.'.'.$type;
        return file_put_contents($cacheFile,json_encode($content));
    }

    public function output($content,$type='json')
    {
        if($type=='json'){
            header('Content-type: application/json');
            exit(json_encode($content));
        }
        $fileType = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
        if ($fileType === 'image/svg+xml') {
            header('Content-type: image/svg+xml');
        } else {
            header('Content-type: image/x-icon');
        }
        exit($content);
    }


    public function get_url_content($url, $timeout = 3, $followRedirects = true, $checkExists = false)
    {
        $ch = curl_init();

        $userAgent = sprintf('%s (Powered by %s)', $_SERVER['HTTP_HOST'], 'sy-records/GetFavicon');

        var_dump($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

        $output = curl_exec($ch);
        var_dump($output);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        var_dump($httpCode);


        curl_close($ch);

        if ($checkExists) {
            return $httpCode == 200;
        }

        return $output;
    }

}