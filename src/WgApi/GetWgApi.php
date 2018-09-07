<?php
namespace edrard\WgApi;

use edrard\Log\MyLog;

class GetWgApi
{ 
    protected $config = FALSE;
    protected $prefix = '';
    protected $methods = array(
        'getPlayerTankStat' => array( 'type' => 'account/tanks','max' => '25'),
        'getPlayerId' => array( 'type' => 'account/list','max' => '1'),
        'getPlayerStat' => array( 'type' => 'account/info','max' => '25'),
        'getPlayerAchiv' => array( 'type' => 'account/achievements','max' => '25'),
        'getPlayerTankStatFull' => array( 'type' => 'tanks/stats','max' => '1'),
    );
    function __construct(array $ids = array(), array $config = array())
    {  
        MyLog::init('logs','wgapi');
        MyLog::changeType(array('warning','error','critical'),'wgapi');
        $this->config = !empty($config) ? $config : include 'config.php';
        $this->changeIds($ids);
    }
    /**
    * Full log on
    * 
    */
    public function fullLog(){       
        MyLog::changeType(array('info','warning','error','critical'),'wgapi');
        MyLog::info("Full Log on",array(),'wgapi'); 
    }
    public function changeIds(array $ids = array()){
        !empty($ids) ? $this->config['id'] = $ids : '';    
    }
    public function changeUrlPrefix($prefix){
        $this->prefix = $prefix;
    }
    public function getUrlPrefix(){
        return $this->prefix;
    }
    protected function addId($server,&$fields){
        $fields['application_id'] = $this->config['id'][$server];
        return $fields;
    }
    protected function maxInOneLink($type,$max){
        return $max !== FALSE ? min(abs($max),$this->methods[$type]['max']) : $this->methods[$type]['max'];    
    }
    public function getUrl($server, $type, $target, $fields){
        $this->addId($server,$fields);
        $fields['language'] = !isset($fields['language']) ? $this->config['lang'][$server] : $fields['language'];
        $fields = $this->createRequest($fields);
        $url = $this->config['url'][$server].'/'.$type.'/'.$target.'/?'.implode('&',$fields);
        MyLog::info("Generated URL - ".$url,array(),'wgapi');
        return $url;
    }
    private function prepeare($type, $server, array $id, array $extra = array(),$max = FALSE, $account_add = 'account_id'){
        $max = $this->maxInOneLink($type,$max);
        $return = [];
        $i = 0;
        foreach(array_chunk($id, $max) as $vals){
            $return[$this->prefix.$i] = $this->simpleRun(
                $this->methods[$type]['type'],
                $server,
                $this->accountAdd($extra,$vals,$account_add)
            );
            $i++;
        }
        return $return;
    }
    /**
    * Adding to id array server specific base
    * 
    * @param array $id
    * @param string $server
    */
    public function addServerBaseId(array &$id,$server){
        $add = $this->config['start'][$server];
        array_walk($id, function(&$val, $key) use($add) {
            $val += $add;        
        });    
    }
    public function getPlayerId($server,array $name,array $extra = array(),$max = FALSE){
        return $this->prepeare(__FUNCTION__,$server, $name, $extra ,$max,'search');
    }
    public function getPlayerStat($server,array $id, array $type = array(), array $extra = array(),$max = FALSE){
        !empty($type) ? $extra['extra'] = implode(',', $type) : '';
        return $this->prepeare(__FUNCTION__,$server, $id, $extra ,$max); 
    }
    public function getPlayerTankStat($server, array $id, array $extra = array(),$max = FALSE){
        return $this->prepeare(__FUNCTION__,$server, $id, $extra ,$max); 
    }
    public function getPlayerTankStatFull($server, array $id, array $type = array(), array $extra = array(),$max = FALSE){
        !empty($type) ? $extra['extra'] = implode(',', $type) : '';
        return $this->prepeare(__FUNCTION__,$server, $id, $extra ,$max);  
    }
    public function getPlayerAchiv($server, array $id, array $extra = array(),$max = FALSE){
        return $this->prepeare(__FUNCTION__,$server, $id, $extra ,$max);  
    }
    private function simpleRun($uri,$server, array $extra = array(),$type = 'wot'){
        return $this->getUrl($server, $type, $uri, $extra);
    }
    private function accountAdd($extra, array $id, $type = 'account_id'){
        $extra = array_special_merge(array($type => trim(implode(',',$id),',')),$extra); 
        return $extra;
    }
    private function createRequest($fields){
        $return = array(); 
        foreach($fields as $key => $val){
            $return[] = $key.'='.$val;
        }
        return $return;
    }
}