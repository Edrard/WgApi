<?php
namespace edrard\WgApi;

use edrard\Log\MyLog;

class GetWgApi
{ 
    protected $config = FALSE;
    protected $methods = array(
        'getPlayerTankStat' => 'account/tanks',
        'getPlayerId' => 'account/list',
        'getPlayerStat' => 'account/info',
        'getPlayerAchiv' => 'account/achievements',
        'getPlayerTankStatFull' => 'tanks/stats',
    );
    function __construct(array $ids = array(), array $config = array())
    {  
        MyLog::init('logs','wgapi');
        MyLog::changeType(array('warning','error','critical'));
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
    protected function addId($server,&$fields){
        $fields['application_id'] = $this->config['id'][$server];
        return $fields;
    }
    public function getUrl($server, $type, $target, $fields){
        $this->addId($server,$fields);
        $fields['language'] = !isset($fields['language']) ? $this->config['lang'][$server] : $fields['language'];
        $fields = $this->createRequest($fields);
        $url = $this->config['url'][$server].'/'.$type.'/'.$target.'/?'.implode('&',$fields);
        MyLog::info("Generated URL - ".$url,array(),'wgapi');
        return $url;
    }
    public function getPlayerId($server,$name,array $extra = array()){
        $extra = array_special_merge(array('search' => $name),$extra);
        return $this->simpleRun($this->methods[__FUNCTION__],$server,$extra);
    }
    public function getPlayerStat($server,array $id, array $type = array(), array $extra = array()){
        !empty($type) ? $extra['extra'] = implode(',', $type) : '';
        $this->accountIdAdd($extra,$id);
        return $this->simpleRun($this->methods[__FUNCTION__],$server,$extra); 
    }
    public function getPlayerTankStat($server, array $id, array $extra = array()){
        $this->accountIdAdd($extra,$id);
        return $this->simpleRun($this->methods[__FUNCTION__],$server,$extra);
    }
    public function getPlayerTankStatFull($server, array $id, array $type = array(), array $extra = array()){
        !empty($type) ? $extra['extra'] = implode(',', $type) : '';
        $this->accountIdAdd($extra,$id);
        return $this->simpleRun($this->methods[__FUNCTION__],$server,$extra); 
    }
    public function getPlayerAchiv($server, array $id, array $extra = array()){
        $this->accountIdAdd($extra,$id);
        return $this->simpleRun($this->methods[__FUNCTION__],$server,$extra);  
    }
    private function simpleRun($uri,$server, array $extra = array(),$type = 'wot'){
        return $this->getUrl($server, $type, $uri, $extra);
    }
    private function accountIdAdd(&$extra, array $id){
        $extra = array_special_merge(array('account_id' => implode(',',$id)),$extra);
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