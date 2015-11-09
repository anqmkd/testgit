<?php
namespace Theme\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use \ZipArchive;
use \DOMDocument;
use Zend\Db\Sql\Expression;
//use Zend\Db\Adapter\Driver\Mysqli\Mysqli;      //调用事务
use Zend\Db\Adapter\Adapter;

use User\Auth\Auth;
use Theme\Acl\Useracl;
use Theme\Controller\Config;

//db_yl_themes
use Service\db_yl_themes\AlarmLabelTable;
use Service\db_yl_themes\AlarmTable;
use Service\db_yl_themes\AlbumsresTable;
use Service\db_yl_themes\AlbumsTable;
use Service\db_yl_themes\BannerlistTable;
use Service\db_yl_themes\BannerTable;
use Service\db_yl_themes\FontPreviewTable;
use Service\db_yl_themes\FontTable;
use Service\db_yl_themes\PayTable;
use Service\db_yl_themes\PreviewTable;
use Service\db_yl_themes\ProductTable;
use Service\db_yl_themes\RatioTable;
use Service\db_yl_themes\RingSubTypeTable;
use Service\db_yl_themes\RingTable;
use Service\db_yl_themes\RingTypeTable;
use Service\db_yl_themes\SceneTable;
use Service\db_yl_themes\TaskTable;
use Service\db_yl_themes\ThemeInfoTable;
use Service\db_yl_themes\ThemeTable;
use Service\db_yl_themes\WebbannerTable;
use Service\db_yl_themes\WidgetTable;
use Service\db_yl_themes\WplistCpTable;
//db_yl_designer
use Service\db_yl_designer\PersonthemepreviewTable;
use Service\db_yl_designer\PersonthemeTable;
//db_yl_androidesk
use Service\db_yl_androidesk\WallpaperTable;
use Service\db_yl_androidesk\WplistAdTable;
use Service\db_yl_androidesk\WpcommendTable;
use Service\db_yl_androidesk\CateInfoTable;
use Service\db_yl_androidesk\LauncherTable;
//db_yl_themes_records
use Service\db_yl_themes_records\ChargeTable;
//webuser
use Service\webuser\AclTable;
use Service\webuser\RoleTable;
use Service\webuser\UserTable;
//db_yl_recommend
use Service\db_yl_recommend\RecommendTable;
//mongo
use Service\MongoRecord\SummaryRecordTable;
use Service\Logs\Logs;
use Service\Check\sqlcheck;
use Service\ManagerFactory;

class SiteController extends AbstractActionController 
{
	
	protected $widgetTable;
	protected $themeTable;
	protected $themeinfoTable;
	protected $ratioTable;
	protected $previewTable;	
	protected $payTable;
	protected $fontTable;
	protected $fontPreviewTable;	
	protected $ringTable;	
	protected $ringTypeTable;
    protected $ringSubTypeTable;
    protected $alarmTable;	
    protected $alarmLabelTable;
	protected $taskTable;
	protected $sceneTable;
	protected $albumsresTable;
	protected $albumsTable;
	protected $wallpaperTable;
	protected $wplistadTable;
	protected $wplistcpTable;
	protected $bannerTable;
	protected $bannerlistTable;
	protected $webbannerTable;
	protected $personthemeTable;
	protected $personthemepreviewTable;
	protected $chargeTable;
	protected $productTable;
	protected $commendTable;
	protected $cateinfoTable;
	protected $launcherTable;
	protected $themerecommendTable;
	protected $mailNotify;
	protected $defaultWidth  = 540;
	protected $defaultHeight = 960;
	protected $dbAdapter;
	protected $roleTable;
	protected $aclTable;
	
	protected $keyType = array(
		'0'=>'cp级别',
		'1'=>'商品级别',	
	);
	
	protected $chargeArr = array(
			'0'=>'不付费',
			'1'=>'付费',
	);
	protected $typelist  = array(
			'0' => '主题',
			'4' => '铃声',
			'2' => '壁纸',
            '3' => '壁纸',
			'5' => '字体',
			'6' => '解锁',
			'14'=> '闹钟',
	);		
	
	//主题列表页面
	public function rightAction()
    {   
    	try{   		
	    	$pageSize = 20;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)   	{ die('没有权限'); 	}
	    	// $_GET有两种用法：一种是表单传值；一种是链接传参；下面分别是这两种情况
	    	$order          = isset($_GET['order'])?$_GET['order']:'0';
	    	$keyword        = isset($_GET['keyword'])?$_GET['keyword']:'';
	    	$page           = isset($_GET['page'])?$_GET['page']:1;
	    	$keyword = sqlcheck::check($keyword, 30);
	    	$page    = sqlcheck::check($page, 5);
//	    	$order   = sqlcheck::check($order, 3);
	    	if($keyword == ''){
	    		$countWidget    = $this->getThemeTable()->getDistinctnum();
	    	}else{	    		
	    		$countWidget    = $this->getThemeTable()->getKeywordnum($keyword);
	    	}
	    	$totalPage      = ceil($countWidget/$pageSize);
	    	if($page>$totalPage){$page = $totalPage;}
	    	if($page == 0){$page = 1;}
	    	$order_phase = array('asort DESC');
	    	if($order == '0') { $order_phase = array('asort DESC'); }
	    		elseif($order == '1') { $order_phase = array('choice DESC' , 'asort DESC');}
	    	if($keyword == ''){
	    		$tempData = $this->getThemeTable()->getTheme($page,$pageSize, $order_phase);
	    	}else{
	    		$tempData = $this->getThemeTable()->getKeyData($keyword,$page,$pageSize);
	    	}
	    	
	    	$infoData = array();
	    	foreach($tempData as $mydata)
	    	{   
	    		$waresid    = $mydata->waresid;
	    		$waresid    = sqlcheck::check($waresid, 5);
	    		$priceData  = $this->getPayTable()->getAppData(array('waresid'=>$waresid));   		
	    		$tempArr          = (array)$mydata;
	    		$tempArr['price'] = ($priceData)?$priceData->price.'分':'0分';
				$infoData[]       = $tempArr;
	    	}
	    	
	    	$typeList = array("2"=>"简约","3"=>"卡通","4"=>"爱情","5"=>"酷炫","6"=>"创意","7"=>"其他");
	    	$infoDataRatio   = $this->getRatioTable()->getAppDataAll();
	    	$payList = $this->getPayTable()->getAppDataAll(array('warename'=>'主题'));       		
	    	$myratio     = array('480x854','480x800','540x960','720x1280','1080x1920','1200x1920');
	    	$mykernel    = array('1','3');   	 
	    	return new ViewModel(array('myratio'=>$myratio,'mykernel'=>$mykernel,'mycount'=>$countWidget,
	    			'pagesize'=>$pageSize,'totalPage'=>$totalPage,'page'=>$page,
	    			'chargearray'=>$this->chargeArr,'paylist'=>$payList,
	    			'uData'=>$uData,'folderStr'=>Config::REPALCE_STR, 'ratioInfo'=>$infoDataRatio,
	    			'infoData'=>$infoData,'keyword'=>$keyword,'typeList'=>$typeList,'order'=>$order));
    	}catch (Exception $e){
			Logs::write('SiteController::rightAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}	
    }
    
    public function themesortAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if($request->isPost())
	    	{
	    		$postArr  = $request->getPost();
	    		$cpid     = isset($postArr['cpid'])?$postArr['cpid']:'';
	    		$newsort  = isset($postArr['newsort'])?$postArr['newsort']:'';
	    		$oldsort  = isset($postArr['oldsort'])?$postArr['oldsort']:'';
	    		$type     = isset($postArr['type'])?$postArr['type']:'';
	    		$id       = isset($postArr['id'])?$postArr['id']:'';
	    		$identity = isset($postArr['identity'])?$postArr['identity']:'';
	    		$sceneCode= isset($postArr['sceneCode'])?$postArr['sceneCode']:'';
	    		$app      = isset($postArr['app'])?$postArr['app']:'';
	    		$cpid       = sqlcheck::check($cpid);
	    		$newsort    = sqlcheck::check($newsort);
	    		$oldsort    = sqlcheck::check($oldsort);
	    		$type       = sqlcheck::check($type);
	    		$id    		= sqlcheck::check($id);
	    		$identity   = sqlcheck::check($identity);
	    		$sceneCode  = sqlcheck::check($sceneCode);
	    		$app   		= sqlcheck::check($app);
	    		
	    		switch($app)
	    		{
	    			case 'theme':
	    				$data = array('cpid'=>$cpid,'asort'=>$newsort,'update_user'=>$uData->username,'oldsort'=>$oldsort,
						'update_time'=>date('Y-m-d H:i:s'),'id'=>$id);
						switch($type)
						{
							case 'input':   $this->getThemeTable()->getInputData($data);  break;
							case 'asc':     $this->getThemeTable()->getAscData($data);    break;
							case 'desc':    $this->getThemeTable()->getDescData($data);   break;
						}					   		
			    		die('success');
	    				break;
	    			case 'scene':
	    				$data = array('sceneCode'=>$sceneCode,'asort'=>$newsort,'oldsort'=>$oldsort,
						'updateTime'=>date('Y-m-d H:i:s'),'id'=>$id);
						switch($type)
						{
							case 'input':   $this->getSceneTable()->getInputData($data);  break;
							case 'asc':     $this->getSceneTable()->getAscData($data);    break;
							case 'desc':    $this->getSceneTable()->getDescData($data);   break;
						}					   		
			    		die('success');
	    				break;
	    				break;
	    			case 'font':
	    				$data = array('identity'=>$identity,'asort'=>$newsort,'update_user'=>$uData->username,'oldsort'=>$oldsort,
						'update_time'=>date('Y-m-d H:i:s'),'id'=>$id);
						switch($type)
						{
							case 'input':   $this->getFontTable()->getInputData($data);  break;
							case 'asc':     $this->getFontTable()->getAscData($data);    break;
							case 'desc':    $this->getFontTable()->getDescData($data);   break;
						}					   		
			    		die('success');
	    				break;
	    			case 'commend':
	    				$data = array('asort'=>$newsort,'oldsort'=>$oldsort, 'id'=>$id);
						switch($type)
						{
							case 'input':   $this->getCommendTable('commend')->getInputData($data);  break;							
						}					   		
			    		die('success');
	    				break;
	    			case 'wpupsort':
	    				$data = array('asort'=>$newsort,'oldsort'=>$oldsort, 'cpid'=>$cpid);
						switch($type)
						{
							case 'input':   $this->getWplistCpTable()->getInputData($data);  break;							
                            case  'asc':    $this->getWplistCpTable()->getAscData($data);   break;
					        case  'desc':   $this->getWplistCpTable()->getDescData($data);  break;
						}					   		
			    		die('success');
	    				break;
	    		}    		
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::themesortAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function themeorderAction()
	{
		try{
	        $uData    = $this->checkLogin('pay');
			if(!$uData)
			{
				die('没有权限');
			}
			$cpid = isset($_GET['cpid'])?$_GET['cpid']:'';
			$page = isset($_GET['page'])?(int)$_GET['page']:1;
			$cpid   = sqlcheck::check($cpid);
			$page   = sqlcheck::check($page);
			$pageSize = 20;
			if(empty($cpid)){die("error cpid");}
			$num     = $this->getChargeTable()->getMyCountnum(array('cpid'=>$cpid,'status'=>1));
			$totalPage   = ceil($num/$pageSize);
			$result  = $this->getChargeTable()->getAppDataAll($page,$pageSize,array('cpid'=>$cpid,'status'=>1));
			$result1  = $this->getChargeTable()->getMyAppDataAll(array('cpid'=>$cpid,'status'=>1));
			$money   = 0;
			$infoData = array();
			foreach($result1 as $row)
			{
				$money += intval($row['money']);
			}
			if(count($result) > 0)
			{
				foreach($result as $row)
				{
					$infoData[] = (array)$row;
				}
			}
			$totalmoney = number_format($money/100,1);		
			return new ViewModel(array('totalpay'=>$num,'totalmoney'=>$totalmoney,'infoData'=>$infoData,
			                     'page'=>$page,'totalPage'=>$totalPage,'mycount'=>$num,'cpid'=>$cpid));	
		}catch (Exception $e){
			Logs::write('SiteController::themeorderAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function themeAction()
    {
    	try{
	    	$pageSize = 10;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)    	{	die('没有权限');  	}
	    	$cpid = isset($_GET['id'])?$_GET['id']:"";
	    	$page = isset($_GET['page'])?(int)$_GET['page']:0;
	    	$page   = sqlcheck::check($page);
	    	$cpid   = sqlcheck::check($cpid);
	    	$countWidget = $this->getThemeInfoTable()->getCountnum(array('cpid'=>$cpid));
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage)   $page = $totalPage;
	    	if($page==0)           $page = 1;
	    	$tempData = $this->getThemeInfoTable()->getData($page,$pageSize,array('cpid'=>$cpid));
	    	
	    	$nameArr    = $this->getThemeTable()->getAppData(array('cpid'=>$cpid));   	
	    	$infoData = array();
	    	foreach($tempData as $mydata)
	    	{
	    		$waresid    = $nameArr->waresid;
	    		$priceData  = $this->getPayTable()->getAppData(array('waresid'=>$waresid));
	    		$mydataArr  = (array)$mydata;    		
	    		$mydataArr['price'] = ($priceData)?$priceData->price.'分':'0分';
	    		$mydataArr['cpid']  = $nameArr->cpid;
	    		$mydataArr['name']  = $nameArr->name;
	    		$mydataArr['note']  = $nameArr->note;
	    		$mydataArr['ad']     = $nameArr->ad;
	    		$mydataArr['adurl']  = $nameArr->adurl;
	    		$mydataArr['adicon'] = $nameArr->adicon;
	    		$infoData[] = $mydataArr;
	    	}    	
	    	$infoDataRatio   = $this->getRatioTable()->getAppDataAll();     	
	    	$payList = $this->getPayTable()->getAppDataAll(array('warename'=>'主题'));    	
	    	return new ViewModel(array('keydata'=>$this->chargeArr,'folderStr'=>Config::REPALCE_STR,'paylist'=>$payList,
	    			'uData'=>$uData,'ratioInfo'=>$infoDataRatio,'id'=>$cpid,'page'=>$page,'countWidget'=>$countWidget,
	    			'totalPage'=>$totalPage,'infoData'=>$infoData,'showUrl'=>Config::SHOW_URL));
    	}catch (Exception $e){
			Logs::write('SiteController::themeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    //插入一个推荐预览
    public function themerecommendAction()
    {
    	try{
	    	$pageSize = 10;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)    	{	die('没有权限');  	}
	    	$cpid = isset($_GET['id'])?$_GET['id']:"";	    	
	    	$cpid   = sqlcheck::check($cpid);
	    	$recommend = array();
	    	array_push($recommend, $cpid);	    	
	    	$result = $this->getThemeRecommendTable()->getRecommend(0, array('cpid'=>$cpid));
	    	foreach($result as $row)
	    	{
	    		array_push($recommend, $row->recommend);
	    	}
	    	$infoData = array();
	    	foreach($recommend as $mydata)
	    	{
//	    		echo $mydata;
	    		$identity_result = $this->getThemeInfoTable()->getAllData(array('cpid'=>$mydata, 'width'=>'1440', 'height'=>'1280'));
	    		$iden = array();
	    		foreach($identity_result as $row)
	    		{
	    			$iden[] = (array)$row;
	    		}
//	    		var_dump($iden[0]['identity']);
	    		$pic = $this->getPreviewTable()->getInfo(array('identity'=>$iden[0]['identity']));
	    		$mydataArr = array();	    		
	    		foreach($pic as $per)
	    		{
//	    			var_dump($per->url);
	    			$myarray = explode('/', $per->url);
	    			$filename = end($myarray);
	    			if($filename == 'preview01.jpg' || $filename == 'preview01.png')
	    			  { $mydataArr['pre01'] = $per->url;}
	    			if($filename == 'preview02.jpg' || $filename == 'preview02.png')
	    			  { $mydataArr['pre02'] = $per->url; }
	    			if($filename == 'preview03.jpg' || $filename == 'preview03.png')
	    			  { $mydataArr['pre03'] = $per->url; }
	    		}	    		
	    		$infoData[] = $mydataArr;    		
	    	}  
//	    	var_dump($infoData);  
	    	return new ViewModel(array('folderStr'=>Config::REPALCE_STR, 'infoData'=>$infoData, 'showUrl'=>Config::SHOW_URL));
    	}catch (Exception $e){
			Logs::write('SiteController::themeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addthemeAction()
	{   
		try{	
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['ratio']) || empty($postArr['subjectpic']) )
	    		{
	    			die('empty');
	    		}
	    		$cpid          = isset($postArr['cpid'])?$postArr['cpid']:'';
	    		$repeat        = isset($postArr['repeat'])?$postArr['repeat']:'';
	    		$ratio         = $postArr['ratio'];
	    		$mycharge      = isset($postArr['mycharge'])?$postArr['mycharge']:0;
	    		$mychargevalue = isset($postArr['mychargevalue'])?$postArr['mychargevalue']:'';
	    		$appid         = isset($postArr['appid'])?$postArr['appid']:'';
	    		
	    		$cpid   		 = sqlcheck::check($cpid);
	    		$repeat   		 = sqlcheck::check($repeat);
//	    		$ratio   		 = sqlcheck::check($ratio);
	    		$mycharge   	 = sqlcheck::check($mycharge);
	    		$mychargevalue   = sqlcheck::check($mychargevalue);
	    		$appid   		 = sqlcheck::check($appid);
	    		if(intval($mycharge)==1 && empty($mychargevalue))
	    		{
	    			die('empty');
	    		}
	    		if(intval($mycharge)==0)
	    		{
	    			$mychargevalue = '';
	    			$appid         = '';
	    			$price         = '';
	    		}else{
	    			$chargeArr = explode("-",$mychargevalue);
	    			$mychargevalue = $chargeArr[0];
	    			$price         = $chargeArr[1];
	    		}
	    		$ratioArr = explode("-", $ratio);
	    		$mywidth  = $ratioArr[0];
	    		$myheight = $ratioArr[1];
	    		$pic      = $postArr['subjectpic'];
	 			$pic      = urldecode($pic);
				$judgetype     = $this->judgeTemeType($pic);
				if(!$repeat){
					$this->deldir( Config::THEME_FOLDER );
					$zip = new ZipArchive;
					if (  $zip->open( Config::TEMP_FOLDER.$pic ) === TRUE  )
					{  
                                                $zip->extractTo( Config::THEME_FOLDER );
						$zip->close();
					}
				}		
	            $xml       = simplexml_load_file( Config::THEME_FOLDER.Config::CONFIG_FILE );
				$items     = $xml->item;
				$configArr = array();
				foreach($items as $item)
				{
					$t_arr    = (array)$item;
					$tempitem = $t_arr['@attributes'];
					$k        = $tempitem['key'];
					$v        = $tempitem['value'];
					$configArr[$k] = $v;
				}
	 			//验证分辨率是否正确	 
	 			$tempKernel = '';
	 			$themeVersion = isset($configArr['theme_version'])?$configArr['theme_version']:1;		 					
	 			$version = isset($configArr['version'])?$configArr['version']:1;
	 			if($themeVersion == 5){
	 				$tempKernel = $themeVersion;
	 				$xmlRation = explode("x", $configArr['resolution']);
	 				if( ($mywidth != 2*$xmlRation[0]) || ($myheight != $xmlRation[1]) ){
	 					die('mismatch');
	 				}	 		 							
	 			} elseif ($themeVersion < 5) {
	 				$tempKernel = $version;
	 				if($tempKernel == 3){
		 				$xmlRation = explode("x", $configArr['resolution']);
		 				if( ($mywidth != 2*$xmlRation[0]) || ($myheight != $xmlRation[1])){
		 					die('mismatch');
		 				}
		 			}	
	 			}	 
				$fileArr    = explode("/", $pic);
				$copyFolder = Config::TEMP_FOLDER.'/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4]; 
				
				$prev_icon = '';
				$prev_contact = '';
				$prev_mms = '';	
				$prev_icon_small = '';	
				$prev_url = '';	
				
				if(!empty($configArr['min_prev'])){
					$minFileArr = explode( "/",$configArr['min_prev'] );
					$minFile    = end($minFileArr);
					$prev_url    = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$minFile ;
				} else{
					$prev_url    = '';
				}			
				if(!empty($configArr['prev_icon'])){
					$iconFileArr = explode( "/",$configArr['prev_icon'] );
					$iconFile    = end($iconFileArr);
					$prev_icon    = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$iconFile ;
				} else{
					$iconFile    = '';
				}	
				if(!empty($configArr['prev_contact'])){
					$contactFileArr = explode( "/",$configArr['prev_contact'] );
					$contactFile    = end($contactFileArr);
					$prev_contact = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$contactFile ;
				} else {  
					$contactFile = ''; 
			    }
				if(!empty($configArr['prev_mms'])){
					$mmsFileArr = explode( "/",$configArr['prev_mms'] );
					$mmsFile    = end($mmsFileArr);
					$prev_mms     = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$mmsFile ;	
				} else { 
					$mmsFile = '';  
				}	
				if(!empty($configArr['prev_icon_small'])){
					$smallFileArr = explode( "/",$configArr['prev_icon_small'] );
					$smallFile    = end($smallFileArr);
					$prev_icon_small     = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$smallFile ;	
				} else { 
					$smallFile = '';  
				}	
					
				$configArr['folder'] = $fileArr[4];
				if(!$repeat){
					$this->recurse_copy(Config::THEME_FOLDER.Config::RES_FOLDER,$copyFolder);
					if(file_exists(Config::THEME_FOLDER.Config::PRE_FOLDER)){
						$this->recurse_copy(Config::THEME_FOLDER.Config::PRE_FOLDER,$copyFolder);
					}
                  
				}			
				$identity           = sprintf("%u", crc32( file_get_contents(Config::TEMP_FOLDER.$pic)));
				//URL校验是为了防止连续点击，因为主题包每次都被更改过，生成的crc不一样
				//CRC验证是为了防止重复上传
				$infoResult = $this->getThemeInfoTable()->getThemeData(array(
						'url'=> $pic,
				));
				$infoResult_next = $this->getThemeInfoTable()->getThemeData(array(
						'identity'=> $identity,
				));
				if(($infoResult && $infoResult->id) || ($infoResult_next && $infoResult_next->id))
				{
					 die('exist');
				}
				
				$tmpCpid = $cpid;
				if($cpid == ''){
					$cpid               = substr(date('YmdHis'),3,9);
					if(!$repeat){
						$rst                = $this->getThemeTable()->getThemeData(array(
								'name'=>$configArr['label-zh-rCN'],
						));
						if($rst && $rst->id){
							die('repeat');
						}
					}
					if(isset($configArr['classify'])){
						$typename = $configArr['classify'];
						$subtype  = $this->getThemeTable()->getSubType($typename);
					}else{
						$subtype = 0;
					}
					$tmpsort = $this->getThemeTable()->getMaxSort();
					$sort = (int)$tmpsort + 1 ;
					$themeDataArr = array(
							'cpid'           => $cpid,
							'valid'          => 1,
							'type'           => 0,
							'name'           => $configArr['label-zh-rCN'],
							'note'           => $configArr['label'],
							'ischarge'       => $mycharge,
							'waresid'        => $mychargevalue,
							'appid'          => $appid,
							'asort'          => $sort,
							'author'         => isset($configArr['author'])?$configArr['author']:'',
							'cyid'           => isset($configArr['cyname'])?$configArr['cyname']:'',
							'intro'          => isset($configArr['intro'])?$configArr['intro']:'',
							'insert_user'    => $uData->username,
							'insert_time'    => date('Y-m-d H:i:s'),
							'keyword'		 => isset($configArr['keyword'])?$configArr['keyword']:'',
							'subtype'        => $subtype,
							'pre_url'        => $prev_url,
							'pre_icon'      => $prev_icon_small,
							'pre_contact'   => $prev_contact,
							'pre_mms'       => $prev_mms,
							'tdate'         => date('Y-m-d'),
					);		
					
				}	
				$theme_size         = filesize( Config::TEMP_FOLDER.$pic );
				$theme_md5          = md5_file( Config::TEMP_FOLDER.$pic );	
				$themeInfoDataArr = array(
					'identity'       => $identity,
					'cpid'           => $cpid,
					'valid'          => 1,
					'kernel'         => $tempKernel,
					'url'            => $pic,
					'size'           => $theme_size,
					'md5'            => $theme_md5,
					'img_num'        => $configArr['prev_num'],
					'folder'         => $configArr['folder'],
					'width'          => $mywidth,
					'height'         => $myheight,
					'effect'         => isset($configArr['effect'])?$configArr['effect']:'',
					'font_style'     => isset($configArr['font_style'])?$configArr['font_style']:'',
					'keyguard_style' => isset($configArr['keyguard_style'])?$configArr['keyguard_style']:'',
					'insert_user'    => $uData->username,
					'insert_time'    => date('Y-m-d H:i:s'),
					
				);
				
				if(isset($configArr['prev_main'])){
					$mainFileArr = explode( "/",$configArr['prev_main'] );
					$mainFile    = end($mainFileArr);
				}else{
					$mainFile    = '';
				}
				
	
				$handle = opendir($copyFolder);
				
				$sceneArr = '';
				//开启事务
				$db = $this->getAdapter()->getDriver()->getConnection();									
				$db->beginTransaction();
				try{
					while(false!==($mytempfile=readdir($handle)))
					{
						//说明，pre_icon只取prev_icon_small, pre_url是处理后的小图（主题包暂时不提供）， key="prev_icon"是用来标记theme_info表中的type类型
						if($mytempfile=='Thumbs.db' || $mytempfile=='.' || $mytempfile=='..' || $mytempfile=='preview_icons_small.jpg' 
						   || $mytempfile=='min_preview.jpg'){
							continue;
						}
						$fileExtenArr = explode(".",$mytempfile);
						$fileExten    = end($fileExtenArr);
						if($fileExten == 'jpg' || $fileExten == 'png')
						{					
							$type        = 0;					
							if($mytempfile==$mainFile)
							{
								$type = 1;
							}	
							if($mytempfile==$iconFile)
							{
								$type = 2;
							}
							if($mytempfile==$contactFile)
							{
								$type = 3;
							}
							if($mytempfile==$mmsFile)
							{
								$type = 4;
							}				
							if( intval($tempKernel)==3 )
							{
								if( $mytempfile=="preview01.png" || $mytempfile=="preview02.png" || $mytempfile=="preview03.png" )
								{
									$mytempfile = str_replace("png", "jpg", $mytempfile);
								}
							}
							if(!file_exists( $copyFolder.'/'.$mytempfile)){
								die('lackpreview');
							}
							$filemd5     = md5_file( $copyFolder.'/'.$mytempfile );
							$filesize    = filesize( $copyFolder.'/'.$mytempfile );
							$fileurl     = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$mytempfile ;
							
							
							$tdata       = $this->getPreviewTable()->getInfo(array('identity'=>$identity,'md5'=>$filemd5));
							$tresult     = $tdata->current();
							
							if(!$tresult || $mytempfile=="preview05.png" || $mytempfile=="preview06.png")
							{
								$this->getPreviewTable()->saveArr(array(
										'identity' => $identity,
										'type'     => $type,
										'url'      => $fileurl,
										'name'     => $mytempfile,
										'size'     => $filesize,
										'note'     => '',
										'md5'      => $filemd5,
										'folder'   => $fileArr[4],
								));
								
							}else{
								continue;
							}
							if($mytempfile == "preview01.png" ||  $mytempfile == "preview01.jpg")
							{
								if($tmpCpid != ''){
									$fileurl = $this->getThemeTable()->getPicUrl($tmpCpid);
								}
								$sceneArr = array(
										'sceneCode' => $cpid,
										'icon'      => $fileurl,
										'iconHd'    => $fileurl,
										'iconMicro' => $fileurl,
										'ischarge'  => $mycharge,
										'appid'     => $appid,
										'waresid'   => $mychargevalue,
										'md5'       => $theme_md5,
										'totalSize' => $theme_size,
										'author'    => isset($configArr['author'])?$configArr['author']:'',
										'cyid'      => isset($configArr['cyname'])?$configArr['cyname']:'',
								);
							}
		
						}
					}

					if($tmpCpid == ''){
						$this->getThemeTable()->saveArr($themeDataArr);
					}
					if($judgetype == 'themeAndScene' && $sceneArr != ''){
						if($sceneArr['ischarge'] == 1){
							$sceneArr['waresid'] = $this->getThemeTable()->getFontWaresid($price);
						}
						$this->dealThemeAndScene($pic,$sceneArr,$tmpCpid);
					}
					$dl_md5             = md5_file(Config::TEMP_FOLDER.$pic);
					$themeInfoDataArr['dl_md5']=$dl_md5;
					$this->getThemeInfoTable()->saveArr($themeInfoDataArr);
					$db->commit();
					
					
				} catch(Exception $e) {					
						$db->rollBack();
    					Logs::write('SiteController::addthemeAction() Transaction exception, err:'
									.' file:'.$e->getFile()
									.' line:'.$e->getLine()
									.' message:'.$e->getMessage()
									.' trace:'.$e->getTraceAsString(), 'log');
				}
	    		die('success');
	    	}   
		}catch (Exception $e){
			Logs::write('SiteController::addthemeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		} 	
	}
    
    //引用函数
    public function judgeTemeType($pic)
    { 
    	$entry    = 'lockscreen_description.xml';
    	$zip = zip_open(Config::TEMP_FOLDER.$pic);
    	if (!is_resource($zip)){
    		Logs::write('judgeTemeType():'.$zip.'  is_resource failed', 'log');
    	}
    	while ($zip_entry = zip_read($zip)) {
    		$file_name = zip_entry_name($zip_entry);  
    		if(substr($file_name, 0, strlen($entry))!= $entry){
    			continue;
    		}
    		zip_close($zip);
    		return 'themeAndScene';
    	}
    	zip_close($zip);
    	return 'theme';
    }
    
    //清空目录
    public function deldir($dir) 
    {
    	$dh=opendir($dir);
    	while ($file=readdir($dh)) 
    	{
    		if($file!="." && $file!="..") {
    			$fullpath=$dir."/".$file;
    			//is_dir，文件名存在且为一个目录,unlink删除文件
    			if(!is_dir($fullpath)) {
    				unlink($fullpath);
    			} else {
    				$this->deldir($fullpath);
    			}
    		}
    	}
    	closedir($dh);
    	if($dir!=Config::THEME_FOLDER){
	    	if(@rmdir($dir)) {
	    		return true;
	    	} else {
	    		return false;
	    	}
    	}
    }
    
     public function dealThemeAndScene($pic,$sceneArr,$tmpCpid)
    {
    	try{
	    	$zip = zip_open(Config::TEMP_FOLDER.$pic);
			$entry    = 'lockscreen_description.xml';
			$b_find = false;
			if (!is_resource($zip)){
				Logs::write('judgeTemeType():'.$zip.'  is_resource failed', 'log');
			}
			while ($zip_entry = zip_read($zip)) {
				$file_name = zip_entry_name($zip_entry);
				if(substr($file_name, 0, strlen($entry))!= $entry){
					continue;
				}
				$b_find = true;
				if (!zip_entry_open($zip, $zip_entry, "r")){
					continue;
				}
				$content = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
				zip_entry_close($zip_entry);
				zip_close($zip);
				break;
			}
			$picArr       = explode('/', $pic);
			$fileName     = end($picArr);
			$targetDir    = str_replace($fileName, '', Config::TEMP_FOLDER.$pic);
			file_put_contents($targetDir.'lockscreen_description.xml',$content);
						
			$xml_doc = new DOMDocument();
			$xml_doc->load($targetDir.'lockscreen_description.xml');
			$user_info = $xml_doc->documentElement->getElementsByTagName("item");
			foreach ($user_info as $value){
				if( $value->getAttribute("key")=="iconDownURL"){
					$value->setAttribute("value",Config::SHOW_URL2.$sceneArr['icon']);
					continue;			
				}
				if( $value->getAttribute("key")=="iconHd"){
					$value->setAttribute("value",Config::SHOW_URL2.$sceneArr['iconHd']);
					continue;
				}
				if( $value->getAttribute("key")=="apkUrl"){
					$value->setAttribute("value",Config::SHOW_URL2.$pic);
					continue;
				}
				if( $value->getAttribute("key")=="sceneTotalSize"){
					$value->setAttribute("value",$sceneArr['totalSize']);
					continue;
				}
				if( $value->getAttribute("key")=="intro"){
					$intro = $value->getAttribute("value");
					continue;
				}
				if( $value->getAttribute("key")=="sceneZName"){
					$name = $value->getAttribute("value");
					continue;
				}
				if( $value->getAttribute("key")=="sceneEName"){
					$ename = $value->getAttribute("value");
					continue;
				}
			}
			$xml_doc->save($targetDir.'lockscreen_description.xml');
			
			
			$zip = new ZipArchive;
			if ($zip->open(Config::TEMP_FOLDER.$pic) === TRUE) {
				$zip->addFile($targetDir.'lockscreen_description.xml', 'lockscreen_description.xml');
				$zip->close();
			}
			$nowTime = date('Y-m-d H:i:s');
			if($tmpCpid == ''){
				$tempsort = $this->getSceneTable()->getMaxSort();
				$sort = (int)$tempsort+1;
                $dl_md5=md5_file(Config::TEMP_FOLDER.$pic);
				$this->getSceneTable()->saveArr(array(
					'sceneCode'         => $sceneArr['sceneCode'],
					'ischarge'  		=> $sceneArr['ischarge'],
					'waresid'   		=> $sceneArr['waresid'],
					'appid'     		=> $sceneArr['appid'],
					'asort'             => $sort,
					'md5'       		=> $sceneArr['md5'],
					'totalSize' 		=> $sceneArr['totalSize'],
					'url'       		=> $pic,
					'fname'     		=> $fileName,
					'updateTime'		=> $nowTime,
					'createTime'		=> $nowTime,
					'icon'      		=> $sceneArr['icon'],
					'iconMicro' 		=> $sceneArr['iconMicro'],
					'iconHd'    		=> $sceneArr['iconHd'],
					'package'   		=> 'com.ibimuyu.lockscreen',
					'intro'     		=> $intro,
					'zhName'    		=> $name,
					'enName'    		=> $ename,
					'acceptMinKernel' 	=> 0,
					'kernel'            => 2,
					'authorName'        => $sceneArr['author'],
					'cyid'              => $sceneArr['cyid'],
                    'dl_md5'        =>$dl_md5,
				));
			}			
       }catch (Exception $e){
			Logs::write('SiteController::dealThemeAndScene() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}   	
    }
    
    public function useAction()
    {   
    	try{
    		$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		$id      = isset($postArr['id'])?$postArr['id']:"";
	    		$type    = isset($postArr['t'])?$postArr['t']:"";
	    		$myval   = isset($postArr['v'])?$postArr['v']:"n";
	    		$id   	 = sqlcheck::check($id);
	    		$type    = sqlcheck::check($type);
	    		$myval   = sqlcheck::check($myval);
	    		if(empty($id) || empty($type) || $myval=='n')
	    		{
	    			die('error');
	    		}
	    		$widgetArr = array(
						'id'    => $id,
						'valid' => $myval,
	    		);
	    		switch($type)
	    		{
	    			case "product":
	    				$this->getProductTable()->updateData($widgetArr);
	    				break;
	    			case "theme":
	    				$this->getThemeInfoTable()->updateData($widgetArr);
	    				break;
	    			case "webbanner":
	    				$this->getWebbannerTable()->updateData($widgetArr);
	    				break;
	    			case "scene":
	    				$this->getSceneTable()->updateData($widgetArr);
	    				break;
	    			case "bannerlist":
	    				$this->getBannerlistTable()->updateData($widgetArr);
	    				break;
	    			case "albumslist":
	    				$this->getAlbumsresTable()->updateData($widgetArr);
	    				break;
	    			case "albums":
	    				$myWidgetArr = array(
	    					'id'    => $id,
	    					'valid' => $myval,
	    					'update_time'=>date('Y-m-d H:i:s'),
	    			    );
	    				$this->getAlbumsTable()->updateData($myWidgetArr);
	    				break;
	    			case "oldbanner":
	    				$myWidgetArr = array(
	    					'id'    => $id,
	    					'valid' => $myval,
	    					'update_time'=>date('Y-m-d H:i:s'),
	    			    );
	    				$this->getBannerTable()->updateData($myWidgetArr);
	    				break;
	    			case "uptime":
	    			    $myWidgetArr = array(
	    					'id'          => $id,
	    					'insert_time' => date('Y-m-d H:i:s'),
	    				);
	    				$this->getThemeTable()->updateData($myWidgetArr);
	    				break;
	    			case "online":
		    			$myWidgetArr = array(
		    					'cpid'   => $id,
		    					'valid'  => $myval,
		    			);
	    				$this->getThemeTable()->updateOnline($myWidgetArr);
	    				break;
	    			case "hottheme":
		    			$myWidgetArr = array(
		    					'cpid'   => $id,
		    					'choice'  => $myval,
		    			);
	    				$this->getThemeTable()->updateOnline($myWidgetArr);
	    				break;
	    			case "fontonline":
		    			$myWidgetArr = array(
		    					'identity'   => $id,
		    					'valid'  => $myval,
		    			);
	    				$this->getFontTable()->updateOnline($myWidgetArr);
	    				break;
	    			case "persontheme":
		    			$myWidgetArr = array(
		    					'identity'   => $id,
		    					'isdel'  => $myval,
		    			);
	    				$this->getPersonthemeTable()->updateOnline($myWidgetArr);
	    				break;
	    			case 'pushlist':
	    			 	$post_data = array('taskid' => $id, 'valid' => $myval);
	    				$myinfo   = $this->geturldata(Config::TASK_VALID, $post_data, 1); 
	    	    		$myinfo   = json_decode($myinfo,true);
	    	            if($myinfo['result'] == true)	{
	    	            	die('success');
	    	            } else {
	    	            	return false;
	    	            }	    	            
	    				break;
    				case 'adwallpaper':
		    			$myWidgetArr = array(
		    					'adid'   => $id,
		    					'valid'  => $myval,
		    			);
	    				$this->getWallpaperTable()->updateData($myWidgetArr);
	    				break;
    				case 'wallpaper' :
    					$myWidgetArr = array(
    							'cpid'   => $id,
    							'valid'  => $myval,
    					);
    					$this->getWplistCpTable()->updateOnline($myWidgetArr);
         				break;
	    		}
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::useAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function previewAction()
	{
		try{
			$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$identity = isset($_GET['id'])?$_GET['id']:"";
	    	$identity   = sqlcheck::check($identity);
	    	if(empty($identity))
	    	{
	    		die("参数错误");
	    	}
	    	$tempDataObj   = $this->getPreviewTable()->getInfo(array('identity'=>$identity));
	    	$infoDataRatio = array();
	    	foreach($tempDataObj as $mydataratio)
	    	{
	    		$infoDataRatio[] = (array)$mydataratio;
	    	}		
	    	return new ViewModel(array('showurl'=>Config::SHOW_URL,'folderStr'=>Config::REPALCE_STR,'themeid'=>$identity,'ratioInfo'=>$infoDataRatio));
		}catch (Exception $e){
			Logs::write('SiteController::previewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function addpreviewAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr  = $request->getPost();
	    		if( empty($postArr['subjectpic']) )
	    		{
	    			die('empty');
	    		}    		
	    		$identity = $postArr['id'];
	    		$pic      = $postArr['subjectpic'];    	
	    		$fileArr  = explode("/", $pic);
	    		$fileMd5  = md5_file(Config::TEMP_FOLDER.$pic);
	    		$fileSize = filesize(Config::TEMP_FOLDER.$pic);   		
	            $mypicid  = $postArr['mypicid'];  
	            $identity   = sqlcheck::check($identity);
	            $pic        = sqlcheck::check($pic);
	            $mypicid    = sqlcheck::check($mypicid);        
	            
	            if(!$mypicid){            	
	            	$tdata       = $this->getPreviewTable()->getInfo(array('identity'=>$identity,'md5'=>$fileMd5));
	            	$tresult     = $tdata->current();
	            	if($tresult && $tresult->id){
	            		die('exist');
	            	}
	            	
					$this->getPreviewTable()->saveArr(
		    			array(
							'identity' => $identity,
							'type'     => 0,
							'url'      => $pic,
							'name'     => $fileArr[5],
							'size'     => $fileSize,
							'note'     => '',
							'md5'      => $fileMd5,
							'folder'   => $fileArr[4],    						
		    			)
		    		);
				    $this->getThemeInfoTable()->updateDataArr(array('identity'=>$identity,'img_num'=>new Expression('img_num+1') ));
	            }
	            if($mypicid){
	            	//先删除ckfinder产生的Files目录
	            	$dir = Config::TEMP_FOLDER.'/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4];	
	            	$dh=opendir($dir);
	            	if(is_dir($dir.'/Files')){
	            		$this->deldir($dir.'/Files');
	            	}	            	
	            	closedir($dh);
	            	//判断当前上传文件名是否与数据库文件名一致，只能同名覆盖，不同名只能上传
	            	$tdata    = $this->getPreviewTable()->getInfo(array('id'=>$mypicid));
	            	$tresult  = $tdata->current();	
	            	$name = $tresult->name; 
	            	if(substr($tresult->name, 0, 9) != substr($fileArr[5], 0, 9)) {
	            		//删除上传的无用文件
	            		$dh=opendir($dir);
	            		unlink(Config::TEMP_FOLDER.$pic);
	            		closedir($dh);
	            		die('mismatch');
	            	}
	            	//同名覆盖，删除原文件，重命名现有文件	            	            	
	            	$dh=opendir($dir);	            	
	            	unlink($dir.'/'.$name);
	            	rename($dir.'/'.$fileArr[5], $dir.'/'.$name); 
	            	closedir($dh);
	            	//实际上只更新size和MD5
	                $this->getPreviewTable()->updateData(
	                    array(
	                        'id'=>$mypicid,
	                        'identity'=>$identity,
	                        //'type'=>0,	                       
	                        'name'=>$name,
	                        'size'=>$fileSize,
	                        'note'=>'',
	                        'md5'=>$fileMd5,                                                        
	                    )
	                );
	            }
	    		die('success');
	    	}    
    	}catch (Exception $e){
			Logs::write('SiteController::addpreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}	
    }
    
    public function editAction()
    {
    	try{
    		//说明：修改界面只需要同步修改四个表中的文件路径、大小和验证码等参数。表修改过程中不用做校验了
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['ratio']) || empty($postArr['id'])  )
	    		{
	    			die('empty');
	    		}    		
	    		$id        = $postArr['id'];    		    		
	    		$ratio     = explode('-', $postArr['ratio'] );
	    		$mywidth   = $ratio[0]*2;
	    		$myheight  = $ratio[1];  
	            $kernel    = $postArr['kernel'];
	            $pic  = urldecode($postArr['themeurl']);
	            
	            $id    	   = sqlcheck::check((string)$id); 
//	            $ratio     = sqlcheck::check((string)$ratio); 
	            $kernel    = sqlcheck::check((string)$kernel); 
//	            $pic  = sqlcheck::check($pic); 
	            //获取identity和cpid
	            $tempData = $this->getThemeInfoTable()->getAllData(array('id'=>$id));
	            $myresult = array();
		    	foreach($tempData as $mydata)
		    	{		    	
		    		$myresult[] = (array)$mydata;
		    	}    
	            $identity = $myresult[0]['identity'];
	            $cpid     = $myresult[0]['cpid'];
//	            echo($identity."____".$cpid);	            
	            //判断是否存在锁屏
	            $isLockScreenExist = $this->judgeTemeType($pic);	                      
	            //清空TEMP目录，将上传的主题包解压到TEMP目录
	            $this->deldir( Config::THEME_FOLDER );
				$zip = new ZipArchive;
				if (  $zip->open( Config::TEMP_FOLDER.$pic ) === TRUE  )
				{
					$zip->extractTo( Config::THEME_FOLDER );
					$zip->close();
				}				
	            //读取description.xml内容,可用于自动读取分辨率
	            $xml       = simplexml_load_file( Config::THEME_FOLDER.Config::CONFIG_FILE );
				$items     = $xml->item;
				$configArr = array();				
				foreach($items as $item)
				{
					$t_arr    = (array)$item;
					$tempitem = $t_arr['@attributes'];
					$k        = $tempitem['key'];
					$v        = $tempitem['value'];
					$configArr[$k] = $v;
				}
//				var_dump($configArr);
				//验证分辨率是否正确	
				$tempKernel = isset($configArr['version'])?$configArr['version']:1; 
				if($tempKernel == 3){
					$xmlRation = explode("x", $configArr['resolution']);
		 			if(($ratio[0] != $xmlRation[0]) || ($myheight != $xmlRation[1])){
		 				die('mismatch');
		 			}
				}		
				//复制res中的预览图到当前目录
				$fileArr    = explode("/", $pic);
				$copyFolder = Config::TEMP_FOLDER.'/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4]; 
				$configArr['folder'] = $fileArr[4];				
				$this->recurse_copy(Config::THEME_FOLDER.Config::RES_FOLDER,$copyFolder);
				//获取大小和MD5
				$theme_size         = filesize( Config::TEMP_FOLDER.$pic );
				$theme_md5          = md5_file( Config::TEMP_FOLDER.$pic );  
				//下面是获取预览图preview
				if(isset($configArr['prev_main'])){
					$mainFileArr = explode( "/",$configArr['prev_main'] );
					$mainFile    = end($mainFileArr);
				}else{
					$mainFile    = '';
				}			
				$handle = opendir($copyFolder);	
				$sceneArr = '';	
				$db = $this->getAdapter()->getDriver()->getConnection();								
				$db->beginTransaction();	
				try{	
					while(false!==($mytempfile=readdir($handle)))
					{
						if($mytempfile=='Thumbs.db' || $mytempfile=='.' || $mytempfile=='..'){
							continue;
						}
						$fileExtenArr = explode(".",$mytempfile);
						$fileExten    = end($fileExtenArr);					
						if($fileExten == 'jpg' || $fileExten == 'png')
						{					
							$type        = 0;					
							if($mytempfile==$mainFile)
							{
								$type = 1;
							}					
							if( intval($tempKernel)==3 )
							{
								if( $mytempfile=="preview01.png" || $mytempfile=="preview02.png" || $mytempfile=="preview03.png" )
								{
									$mytempfile = str_replace("png", "jpg", $mytempfile);
								}
							}												
							$filemd5     = md5_file( $copyFolder.'/'.$mytempfile );
							$filesize    = filesize( $copyFolder.'/'.$mytempfile );
							$fileurl     = '/'.$fileArr[1].'/'.$fileArr[2].'/'.$fileArr[3].'/'.$fileArr[4].'/'.$mytempfile ;						
							//只更新，不进行添加操作，此处逻辑有漏洞	
//							echo($identity."_".$mytempfile."_".$fileurl."_".$filesize."_".$filemd5."_".$fileArr[4]."________");					
							$this->getPreviewTable()->updateUrl(array(
										'identity' => $identity,
										'type'     => $type,
										'url'      => $fileurl,
										'name'     => $mytempfile,
										'size'     => $filesize,									
										'md5'      => $filemd5,
										'folder'   => $fileArr[4],
							));	
							if($mytempfile == "preview01.png" ||  $mytempfile == "preview01.jpg")
							{							
								$sceneArr = array(
										'sceneCode' => $cpid,
										'icon'      => $fileurl,
										'iconHd'    => $fileurl,
										'iconMicro' => $fileurl,									
										'md5'       => $theme_md5,
										'totalSize' => $theme_size,
										'author'    => isset($configArr['author'])?$configArr['author']:'',
										'cyid'      => isset($configArr['cyname'])?$configArr['cyname']:'',
								);
							}
		
						}
					}
	//				var_dump($sceneArr);				
					if($isLockScreenExist == 'themeAndScene' && $sceneArr != ''){					
						$this->dealThemeAndScene($pic,$sceneArr,$cpid);
						//$cpid一定存在，不会走save;只有当分辨率为1920_1200时，才更新scene表
						if($configArr['resolution'] == "1200x1920"){
							$this->getSceneTable()->updatePayData(array(
								'sceneCode'         => $sceneArr['sceneCode'],						
								'md5'       		=> $sceneArr['md5'],
								'totalSize' 		=> $sceneArr['totalSize'],
								'url'       		=> $pic,						
								'updateTime'		=> date('Y-m-d H:i:s'),						
								'icon'      		=> $sceneArr['icon'],
								'iconMicro' 		=> $sceneArr['iconMicro'],
								'iconHd'    		=> $sceneArr['iconHd'],											
								'authorName'        => $sceneArr['author'],
								'cyid'              => $sceneArr['cyid'],					
							));	
						}				
					}		
	//				var_dump($configArr);
	//				exit();		          
		            //更新ThemeInfo表	    		
	    			$this->getThemeInfoTable()->updateData(array('id'=>$id,'url'=>$pic,'kernel'=>$kernel,'size'=> $theme_size,
							'md5'=> $theme_md5,'folder'=> $configArr['folder'],'width'=>$mywidth,'height'=>$myheight,
	    					'valid'=>1,'update_time'=>date('Y-m-d H:i:s'),'update_user'=>$uData->username));
	    			$db->commit();	
				} catch(Exception $e) {
						$db->rollBack();
    					Logs::write('SiteController::editAction() Transaction exception, err:'
									.' file:'.$e->getFile()
									.' line:'.$e->getLine()
									.' message:'.$e->getMessage()
									.' trace:'.$e->getTraceAsString(), 'log');
				}			
    			die('success');	    		
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::editAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }   
    
    public function setthemefolderAction()
    {
    	try{
	    	$request      = $this->getRequest();
	    	if ($request->isPost()) 
	    	{
	    		$postArr  = $request->getPost();
	    		$identity = $postArr['id'];
	    		$id    	   = sqlcheck::check($id);
				$myresult = $this->getThemeInfoTable()->getThemeData( array('identity'=>$identity) );
				if($myresult)
				{
					$url       = $myresult->url;
					$t_arr     = explode("/",$url);				
					$mycontent = '/'.$t_arr[1].'/'.$t_arr[2].'/'.$t_arr[3].'/'.$t_arr[4];
					@file_put_contents(Config::FILE_PATH, $mycontent);
					die("");
				}
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::setthemefolderAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
     
    public function getidentityAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postarr = $request->getPost(); 
	    		$cpid    = $postarr['cpid'];
	    		$ratio   = explode("x", $postarr['ratio']);
	    		$kernel  = $postarr['kernel'];
	    		$cpid    	   = sqlcheck::check($cpid);
//	    		$ratio    	   = sqlcheck::check($ratio);
	    		$kernel    	   = sqlcheck::check($kernel);
	    		$data    = $this->getThemeInfoTable()->getThemeData(array(
	    					'cpid'   => $cpid,
	    					'valid'  => 1,
	    					'width'  => intval($ratio[0])*2,
	    					'height' => $ratio[1],
	    					'kernel' => $kernel,
	    				));
	    		if($data)
	    		{
	    			$tarray  = array('id'=>$data->identity);
	    			die(json_encode($tarray));    			 
	    		}else{
	    			$tarray  = array('id'=>'');
	    			die(json_encode($tarray));			 
	    		}
	    	}	   
    	}catch (Exception $e){
			Logs::write('SiteController::getidentityAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}	
    }    
     
    public function pushAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$identity = $_GET['id'];
	    	$type     = $_GET['type'];
	    	$name     = $_GET['name'];
	    	$ratio    = isset($_GET['ratio'])?$_GET['ratio']:'';
	        $title    = isset($_GET['title'])?$_GET['title']:'';
	        $content  = isset($_GET['content'])?$_GET['content']:'';
	        $identity      = sqlcheck::check($identity);
	        $type    	   = sqlcheck::check($type);
	        $name          = sqlcheck::check($name);
//	        $ratio    	   = sqlcheck::check($ratio);
	        $title    	   = sqlcheck::check($title);
	        $content       = sqlcheck::check($content);
	    	$myurl    = sprintf(Config::INFO_URL ,$identity,$type,$title,$content);
	    	$myinfo   = $this->getmyurldata($myurl);
	    	return new ViewModel(array('infodata'=>$myinfo,'id'=>$identity,'t'=>$type,'r'=>$ratio,'name'=>$name));
    	}catch (Exception $e){
			Logs::write('SiteController::pushAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    //新版，带post和get方法
    public function geturldata( $url, $data, $method = 0 )
    {    	
    	$post_data = http_build_query($data);
    	$ch     = curl_init($url) ;    	
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
    	if($method == 0){
    		$url = $url."?".$post_data;
//    		echo ($url."      ");
    	} elseif ($method == 1) {
    		curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
    	}
    	curl_setopt($ch, CURLOPT_URL, $url);
    	$output = curl_exec($ch);
    	curl_close($ch);
    	return $output;
    }
    
    //老版的get方法，用于部分的链接的传参
    public function getmyurldata( $url )
    {
    	$ch     = curl_init($url) ;
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
    	$output = curl_exec($ch);
    	curl_close($ch);
    	return $output;
    }
    
    public function setpaythemeAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				$mycharge      = $postArr['mypaycharge'];
				$mychargevalue = $postArr['mypaychargevalue'];
				$cpid          = $postArr['cpid'];
				$ad            = $postArr['ad'];
				$adurl         = $postArr['adurl'];
				$adpic         = $postArr['adpic'];
				$mycharge       = sqlcheck::check($mycharge);
				$mychargevalue  = sqlcheck::check($mychargevalue);
				$cpid           = sqlcheck::check($cpid);
				$ad             = sqlcheck::check($ad);
				$adurl          = sqlcheck::check($adurl);
				$adpic          = sqlcheck::check($adpic);				
					
				if( $cpid=='')
				{
					die('empty');
				}
				if(intval($mycharge)==0)
				{
					$mychargevalue = '';
					$appid         = '';
					$price         = '';
				}else{
					$chargeArr = explode("-",$mychargevalue);
					$mychargevalue = $chargeArr[0];
					$price         = $chargeArr[1];
				}	
				$db = $this->getAdapter()->getDriver()->getConnection();									
				$db->beginTransaction();
				try{
					$this->getThemeTable()->updatePayData(array(
							'cpid'           => $cpid,
							'ischarge'       => $mycharge,
							'waresid'        => $mychargevalue,
							'appid'          => $postArr['appid'],
							'intro'          => $postArr['intro'],
							'keyword'        => $postArr['keyword'],
							'subtype'        => $postArr['typeid'],
							'cyid'           => $postArr['channel'],
							'update_time'    => date('Y-m-d H:i:s'),
							'update_user'    => $uData->username,
					));
					//更新主题广告
					if($ad == '1')
					{
						if( $adurl == '')
						{
							die('empty');
						}
						$this->getThemeTable()->updatePayData(array(
							'cpid'           => $cpid,
							'adurl'          => $adurl,
							'ad'             => $ad,
						));
					}elseif($ad == '2') {
						$fileSize = filesize(Config::TEMP_FOLDER.$adpic); 
						if( $fileSize == 0 )
						{
							die('empty');
						}
						$this->getThemeTable()->updatePayData(array(
							'cpid'           => $cpid,
							'adicon'          => $adpic,
							'ad'             => $ad,
						));
					}
					$result = $this->getSceneTable()->getAppData(array('sceneCode' => $cpid));
					if($result){
						$this->getSceneTable()->updatePayData(array(
								'sceneCode'           => $cpid,
								'ischarge'       => $mycharge,
								'waresid'        => $mychargevalue,
								'appid'          => $postArr['appid'],
								'intro'          => $postArr['intro'],						
								'cyid'           => $postArr['channel'],
								'updateTime'    => date('Y-m-d H:i:s'),						
						));
					}
					$db->commit();
				}catch(Exception $e) {
						$db->rollBack();
    					Logs::write('SiteController::setpaythemeAction() Transaction exception, err:'
									.' file:'.$e->getFile()
									.' line:'.$e->getLine()
									.' message:'.$e->getMessage()
									.' trace:'.$e->getTraceAsString(), 'log');
				}
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::setpaythemeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	 public function setchangetimeAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();			
				$cpid          = $postArr['cpid'];
				$tdate         = $postArr['tdate'];
				$cpid          = sqlcheck::check($cpid);		
				$tdate         = sqlcheck::check($tdate);
				
				$this->getThemeTable()->updatePayData(array(
						'cpid'           => $cpid,
						'tdate'          => $tdate,
				));			
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::setchangetime() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//锁屏列表页面
	public function sceneAction()
    {
    	try{
	    	$pageSize = 10;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$keyword        = isset($_GET['keyword'])?$_GET['keyword']:'';
	    	$page        = isset($_GET['page'])?(int)$_GET['page']:0;
	    	$keyword        = sqlcheck::check($keyword);
	    	$page           = sqlcheck::check($page);
	    	if($keyword == ''){
	    		$countWidget = $this->getSceneTable()->getCountnum();
	    	}else{
	    		$countWidget = $this->getSceneTable()->getKeyCountnum($keyword);
	    	}
	   	
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0) $page = 1;
	    	
	    	if($keyword == ''){
	    		$tempData    = $this->getSceneTable()->getData($page,$pageSize);
	    	}else{
	    		$tempData    = $this->getSceneTable()->getKeyData($keyword,$page,$pageSize);
	    	}
	    	
	    	$infoData    = array();
	    	foreach($tempData as $mydata)
	    	{
	    		$waresid    = $mydata->waresid;
	    		$priceData  = $this->getPayTable()->getAppData(array('waresid'=>$waresid));
	    		$mydataArr  = (array)$mydata;
	    		$mydataArr['price'] = ($priceData)?$priceData->price.'分':'0分';
	    		//$infoData[] = (array)$mydata;
	    		$infoData[] = $mydataArr;
	    		//$infoData[] = (array)$mydata;
	    	}
	    	$paydata = $this->getPayTable()->getAppDataAll(array('warename'=>'锁屏'));
	    	$payList = array();
	    	foreach($paydata as $paydataobj)
	    	{
	    		$payList[] = (array)$paydataobj;
	    	}
	    	$arrCP	 = array('cp_scene_jingling'=>'精灵','cp_scene_weile'=>'微乐','cp_scene_yunlan'=>'云览');
	    	return new ViewModel(array('keydata'=>$this->chargeArr,'showurl'=>Config::SHOW_URL,'paylist'=>$payList,
	    			'folderStr'=>Config::REPALCE_STR,'uData'=>$uData,'page'=>$page,'countWidget'=>$countWidget,
	    			'totalPage'=>$totalPage,'infoData'=>$infoData,'arrCP'=>$arrCP));
    	}catch (Exception $e){
			Logs::write('SiteController::sceneAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addsceneAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if(	!isset($postArr['mycharge'])	)
	    		{
	    			die('empty');
	    		}
	    		$id            = $postArr['id'];
	    		$mycharge      = $postArr['mycharge'];
	    		$mychargevalue = $postArr['mychargevalue'];
	    		$appid         = isset($postArr['appid'])?$postArr['appid']:'';
	    		$channel       = isset($postArr['cpv'])?$postArr['cpv']:'';
	    		$id              = sqlcheck::check($id);
	    		$mycharge        = sqlcheck::check($mycharge);
	    		$mychargevalue   = sqlcheck::check($mychargevalue);
	    		$appid           = sqlcheck::check($appid);
	    		$channel         = sqlcheck::check($channel);
	    		if(intval($mycharge)==1 && empty($mychargevalue))
	    		{
	    			die('empty');
	    		}
	    		if(intval($mycharge)==0)
	    		{
	    			$mychargevalue = '';
	    			$appid         = '';
	    		}
                //id为空值表示添加操作，不为空值则为编辑操作
				if($id == ''){
					$pic          = file_get_contents(Config::FILE_PATH);
					$wholeDir     = Config::TEMP_FOLDER.$pic;
					//生成temp目录路径。在解压前把temp目录文件清空：一是防止temp目录过大；二是隔离temp目录，有效防止恶意文件
					/*把非当天的目录删除，避免temp文件过大,当天的非本次上传的文件不删除;另一种方法是在解压完后直接删除
					$tempic       = explode("/", $pic);
					$mytempdir    = Config::TEMP_FOLDER.'/'.$tempic[1].'/'.$tempic[2];
					$getdirfirst  = $mytempdir;
					$usefuldir    = $tempic[3];
					$mytempdir = opendir($mytempdir);
					while(false !== ($zipfile = readdir($mytempdir))){
						if(in_array($zipfile,array('.', '..', $usefuldir))) continue;					
						$this->deldir($getdirfirst.'/'.$zipfile.'/');
					}					
					*/
					//解压完后删除temp目录，在解压前未进行zip内部文件的判断，容易注入可执行文件
					$tempic       = explode("/", $pic);
					$mytempdir    = Config::TEMP_FOLDER.'/'.$tempic[1].'/'.$tempic[2];
					$wholeHandle  = opendir($wholeDir);
					//读取zip文件,可上传多个文件，未作MD5验证,上传的文件为zip文件，若不是删除文件，并返回error
					while(false !== ($zipfile = readdir($wholeHandle))){
						if(in_array($zipfile,array('.','..'))) continue;
						$zipPathfile  = Config::TEMP_FOLDER.$pic.'/'.$zipfile;
						$zipArr       = explode(".",$zipfile);
						$zipDir       = $zipArr[0];
						$fileType     = $zipArr[1];
						if($fileType != 'zip') 
						{  
							closedir($wholeHandle);
							$this->deldir($mytempdir.'/');
							die('error');
						}	
						$tt           = $pic.'/'.$zipDir.'/';
						$zip = new ZipArchive;
						if (  $zip->open( $zipPathfile ) === TRUE)
						{   
						    $tt   = str_replace('temp/', "", $tt);
							$checkDir  = Config::REPALCE_STR.$tt;
							//校验文件，解压到目标目录				
							$zip->extractTo( $checkDir);
							$zip->close();
							$handle    = opendir($checkDir);
							$nowTime  = date("Y-m-d H:i:s",time());
							//读取文件，存储数据
							while(false!==($myfile=readdir($handle)))
							{
								if($myfile!='Thumbs.db' && $myfile!='.' && $myfile!='..' && $myfile!='')
								{
									$fileextarr   = explode(".", $myfile);
									$fileext      = strtolower(end($fileextarr));									
									if($fileext=="apk")
									{   $dl_md5        =md5_file($checkDir.$myfile);
										$ring_md5     = md5_file($checkDir.$myfile);
										$ring_size    = filesize($checkDir.$myfile);
										$identity     = sprintf("%u", crc32( file_get_contents($checkDir.$myfile)));
										$url          = $tt.$myfile;
										$fname        = $myfile;
									}
									if($fileext=="zip")
									{
                                        $dl_md5        =md5_file($checkDir.$myfile);
										$ring_md5     = md5_file($checkDir.$myfile);
										$ring_size    = filesize($checkDir.$myfile);
										$identity     = sprintf("%u", crc32( file_get_contents($checkDir.$myfile)));
										$url          = $tt.$myfile;
										$fname        = $myfile;
									}
									if($fileext=="jpg")
									{
										if(strpos($myfile, "120"))
										{
											$icon              = $tt.$myfile;
										}
										if(strpos($myfile, "240"))
										{
											$iconMicro         = $tt.$myfile;
										}
										if(strpos($myfile, "480"))
										{
											$iconHd             = $tt.$myfile;
										}
										if(strpos($myfile, "wp"))
										{
											$icon               = $tt.$myfile;
										}
										if(strpos($myfile, "es"))
										{
											$iconHd             = $tt.$myfile;
											$iconMicro          = $tt.$myfile;
										}
									}
									if($fileext=="txt")
									{					
										$content      = file_get_contents(Config::REPALCE_STR.$tt.$myfile);
										$content      = iconv('GBK', 'UTF-8', $content);
										$temparr      = explode("\n", $content);
										$kernel       = str_replace(array("acceptKernel=","\r\n", "\r", "\n"), "", $temparr[0]);
										$name         = str_replace(array("sceneZName=","\r\n", "\r", "\n"), "", $temparr[1]);
										$ename        = str_replace(array("sceneEName=","\r\n", "\r", "\n"), "", $temparr[2]);
										$package      = str_replace(array("PackageName=","\r\n", "\r", "\n"), "", $temparr[3]);
										if(count($temparr) == 5){
											$fother       = $temparr[4];
										}else{
											$fother       = $temparr[4]."\n".$temparr[5];
										}
										$intro        = str_replace("Intro=", "", $fother);								
									}
								}
							}	
							//如果是锁屏，一个包名对应一个apk,所以要判断package是否唯一；动态壁纸所有的package一样，不能沿用此判断，通过zip的MD5来验证							
							if($kernel == 3)	
							{  
								$myresult     = $this->getSceneTable()->getAppData(array(
										'valid'     => 1,
										'md5'       => $ring_md5,
								));
							} else {
								$myresult     = $this->getSceneTable()->getAppData(array(
										'valid'     => 1,
										'package'   => $package,
								));
							}
							if($myresult && $myresult->id)
							{
								$id           = $myresult->id;
							}else{
								$tempsort = $this->getSceneTable()->getMaxSort();								
								$sort = (int)$tempsort+1;
								$id = $this->getSceneTable()->saveArr(array(
										'asort'     => $sort,
										'ischarge'  => $mycharge,
										'waresid'   => $mychargevalue,
										'appid'     => $appid,
										'createTime'=> $nowTime,
										'cyid'      => $channel,
								));
							}
							//为了重用下面这一块，将创建表的步骤分为三个部分
							$this->getSceneTable()->updateData(array(
									'id'        => $id,
									'md5'       => $ring_md5,
									'totalSize' => $ring_size,
									'url'       => $url,
									'fname'     => $fname,
									'updateTime'=> $nowTime,
									'icon'      => $icon,
									'iconMicro' => $iconMicro,
									'iconHd'    => $iconHd,
									'package'   => $package,
									'intro'     => $intro,
									'zhName'    => $name,
									'enName'    => $ename,
                                     'dl_md5'    => $dl_md5,
							));
							//动态壁纸，待更改
							if($kernel == 3)
							{
								$this->getSceneTable()->updateData(array(
									'id'        => $id,
									'kernel'          => 10,
									//acceptMinKernel无用，定义为20,以区分微乐动态壁纸和其他锁屏
									'acceptMinKernel' => 20,      
					
								));
							}else{
								$this->getSceneTable()->updateData(array(
									'id'        => $id,
									'acceptMinKernel'          => $kernel,					
								));
							}							
							if($myresult && $myresult->id)
							{
								$this->getSceneTable()->updateData(array(
										'id'        => $id,
										'ischarge'  => $mycharge,
										'waresid'   => $mychargevalue,
										'appid'     => $appid,
										'cyid'      => $channel,
								));
							}else{
								$this->getSceneTable()->updateData(array(
										'id'        => $id,
										'sceneCode' => $identity,
								));
							}							
						}else{
							closedir($wholeHandle);
							$this->deldir($mytempdir.'/');
							die('error');
						}
						$id   = "";
					}  										
				}else {
                    //编辑操作
					$db = $this->getAdapter()->getDriver()->getConnection();
//					die($db);									
					$db->beginTransaction();
					try{
						$this->getSceneTable()->updateData(array(
								'id'        => $id,
								'ischarge'  => $mycharge,
								'waresid'   => $mychargevalue,
								'appid'     => $appid,						
								'updateTime'=> date("Y-m-d H:i:s",time()),
								'cyid'      => $channel,
						));
						//根据id取sceneCode,再更新themeTable表内容
						$myresult = $this->getSceneTable()->getAppData(array(
											'id'        => $id,
					    ));
					    $result = $this->getThemeTable()->getAllData(array('cpid' => $myresult->sceneCode));
					    if($result){
						    $this->getThemeTable()->updatePayData(array(
									'cpid'           => $myresult->sceneCode,
									'ischarge'       => $mycharge,
									'waresid'        => $mychargevalue,
									'appid'          => $appid,										
									'cyid'           => $channel,
									'update_time'    => date('Y-m-d H:i:s'),
									'update_user'    => $uData->username,
							));
					    }
						 $db->commit();
					} catch(Exception $e) {
						$db->rollBack();
    					Logs::write('SiteController::addsceneAction() Transaction exception, err:'
									.' file:'.$e->getFile()
									.' line:'.$e->getLine()
									.' message:'.$e->getMessage()
									.' trace:'.$e->getTraceAsString(), 'log');
					}
					die('success');
				}
				closedir($wholeHandle);
				$this->deldir($mytempdir.'/');
				die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addsceneAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
	
	//字体列表页面
	public function fontAction()
	{
		try{
			$pageSize = 10;
			$uData    = $this->checkLogin('view');
			if(!$uData)
			{
				die('没有权限');
			}
			$keyword     = isset($_GET['keyword'])?$_GET['keyword']:'';
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$keyword        = sqlcheck::check($keyword);
			$page         = sqlcheck::check($page);
			if($keyword == ''){
	    		$countWidget = $this->getFontTable()->getCountnum();
	    	}else{
	    		$countWidget = $this->getFontTable()->getKeyCountnum($keyword);
	    	}				
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			if($keyword == ''){
	    		$tempData    = $this->getFontTable()->getData($page,$pageSize);
	    	}else{
	    		$tempData    = $this->getFontTable()->getKeyData($keyword,$page,$pageSize);
	    	}			
			$infoData    = array();
			foreach($tempData as $mydata)
			{			
				$waresid    = $mydata->waresid;
				$priceData  = $this->getPayTable()->getAppData(array('waresid'=>$waresid));
				$mydataArr  = (array)$mydata;
				$mydataArr['price'] = ($priceData)?$priceData->price.'分':'0分';
				//$infoData[] = (array)$mydata;
				$infoData[] = $mydataArr;			
				//$infoData[] = (array)$mydata;
			}
			$arrCP	 = array('cp_fangzheng'=>'方正','cp_hanyi'=>'汉仪','cp_huakang'=>'华康','cp_wending'=>'文鼎',
							'cp_zitiguanjia'=>'字体管家','cp_xindi'=>'新蒂');
			$paydata = $this->getPayTable()->getAppDataAll(array('warename'=>'字体'));
			$payList = array();
			foreach($paydata as $paydataobj)
			{
				$payList[] = (array)$paydataobj;
			}				
			return new ViewModel(array('keydata'=>$this->chargeArr,'showurl'=>Config::SHOW_URL,'paylist'=>$payList,
										'folderStr'=>Config::REPALCE_STR,'uData'=>$uData,'page'=>$page,'countWidget'=>$countWidget,
										'totalPage'=>$totalPage,'infoData'=>$infoData,'arrCP'=>$arrCP));
		}catch (Exception $e){
			Logs::write('SiteController::fontAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function addfontAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if(empty($postArr['subjectpic'])&& empty($postArr['id']))
				{
					die('empty');
				}
				$id            = $postArr['id'];
				$mycharge      = $postArr['mycharge'];
				$mychargevalue = $postArr['mychargevalue'];
				$appid         = $postArr['appid'];
				$mycp          = $postArr['cpv'];
				$id              = sqlcheck::check($id);
	    		$mycharge        = sqlcheck::check($mycharge);
	    		$mychargevalue   = sqlcheck::check($mychargevalue);
	    		$appid           = sqlcheck::check($appid);
	    		$mycp            = sqlcheck::check($mycp);
				if(intval($mycharge)==1 && empty($mychargevalue))
				{
					die('empty');
				}
				if(intval($mycharge)==0)
				{
					$mychargevalue = '';
					$appid         = '';
				}
				
				if($id)
				{
					$this->getFontTable()->updateData(array(
							'id'        => $id,
							'ischarge'  => $mycharge,
							'waresid'   => $mychargevalue,
							'appid'     => $appid,
							'cyid'      => $mycp,
					));
					die("success");
				}
				
				$pic          = $postArr['subjectpic'];
				$pic          = urldecode($pic);
				$tempStrFile  = iconv('utf-8', 'gbk',  Config::TEMP_FOLDER.$pic);
				$this->delfontdir( Config::THEME_FONT_FOLDER );
				$picArr       = explode("/", $pic);
				$picfile      = end($picArr);
				$tempStr      = str_replace($picfile, date('YmdHis').'.zip', $pic);
				copy($tempStrFile, Config::TEMP_FOLDER.$tempStr);
				$zip = new ZipArchive;
				if (  $zip->open( Config::TEMP_FOLDER.$tempStr ) === TRUE  )
				{
					$zip->extractTo( Config::THEME_FONT_FOLDER );
					$zip->close();
				}
				$xml             = simplexml_load_file( Config::THEME_FONT_FOLDER.Config::CONFIG_FILE );
				$configArr       = array();
				$configArr       = (array)$xml;
				$identity        = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$tempStr)));
				$theme_size      = filesize( Config::TEMP_FOLDER.$tempStr );
				$theme_md5       = md5_file( Config::TEMP_FOLDER.$tempStr );
				$copyFolder      = Config::TEMP_FOLDER.'/'.$picArr[1].'/'.$picArr[2].'/'.$picArr[3].'/'.$picArr[4];
				$fonturl         = $this->recurse_copynew( Config::THEME_FONT_FOLDER.Config::FONT_FOLDER,$copyFolder );
				if($fonturl == false){
					die('error');
				}		
				$previewfilename = $this->rename_preview( Config::THEME_FONT_FOLDER,$copyFolder );											
				$fonturl         = str_replace(Config::REPALCE_STR, "", $fonturl);			
				$result          = $this->getFontTable()->getAppData(array('identity'=>$identity,));
				if($result && $result->id)
				{
					die('exist');
				}			
				$tempsort = $this->getFontTable()->getMaxSort();
				$sort = (int)$tempsort+1;
                $dl_md5=md5_file(Config::TEMP_FOLDER.$tempStr);
				$this->getFontTable()->saveArr(array(
						'identity'    => $identity,
						'language'    => 'ch',
						'insert_time' => date('Y-m-d H:i:s'),
						'insert_user' => $uData->username,
						'name'        => $configArr['title'],					
						'asort'       => $sort,					
						'fname'       => $configArr['title'].'.ttf',
						'url'         => $fonturl,
						'purl'        => str_replace(Config::REPALCE_STR, "", $previewfilename['newfileurl']),
						'size'        => $theme_size,
						'md5'         => $theme_md5,
						'version'     => $configArr['version'],
						'uiversion'   => $configArr['uiVersion'],
						'ischarge'    => $mycharge,
						'waresid'     => $mychargevalue,
						'appid'       => $appid,
						'designer'    => $configArr['designer'],
						'author'      => $configArr['author'],
						'cyid'        => $mycp,
                       'dl_md5'   =>$dl_md5,
				));			
				$this->getFontPreviewTable()->saveArr(array(
						'identity'    => $identity,
						'width'       => $this->defaultWidth,
						'height'      => $this->defaultHeight,
						'fname'       => $previewfilename['smallfilename'],
						'preview_url' => str_replace(Config::REPALCE_STR, "", $previewfilename['smallfileurl']),
						'largepreurl' => str_replace(Config::REPALCE_STR, "", $previewfilename['largefileurl']),
						'size'        => filesize($previewfilename['smallfileurl']),
						'md5'         => md5_file($previewfilename['smallfileurl']),
						'insert_time' => date('Y-m-d H:i:s'),
				));
				die('success');	
			}	
		}catch (Exception $e){
			Logs::write('SiteController::addfontAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function rename_preview($src,$dst) 
	{	
		$tempsamllfile    = '';
		$tempsmallfileurl = '';
		$templargefile    = '';
		$templargefileurl = '';
		$tempnewfile      = '';
		$tempnewfileurl   = '';
		$dir         = opendir($src);
		while(false !== ( $file = readdir($dir)) ) 
		{
			if (( $file != '.' ) && ( $file != '..' )) 
			{
				$picArr = explode(".", $file);
                                //一定要防止文件中有隐藏文件
				if(in_array(strtolower(end($picArr)), array("jpg","png","jpeg","gif")))
				{
					$fileidentity   = sprintf("%u", crc32( file_get_contents( $src . $file ) ) );
					if(substr($file,0,5) == 'large'){
						$templargefile       = $fileidentity.'_large.'.end($picArr);
						copy($src . $file,$dst . '/' . $templargefile);
						$templargefileurl    = $dst . '/' . $templargefile;
					}if(substr($file,0,3) == 'new'){
						$tempnewfile       = $fileidentity.'_new.'.end($picArr);
						copy($src . $file,$dst . '/' . $tempnewfile);
						$tempnewfileurl    = $dst . '/' . $tempnewfile;
					}if(substr($file,0,5) == 'small'){
						$tempsamllfile       = $fileidentity.'_small.'.end($picArr);
						$tempsmallfileurl    = $dst . '/' . $tempsamllfile;				
						copy($src . $file,$dst . '/' . $tempsamllfile);
					}else {
						continue;
					}
									
				}
			}
		}
		closedir($dir);		
		return array('smallfilename'=>$tempsamllfile, 'smallfileurl'=>$tempsmallfileurl,
					 'largefilename'=>$templargefile, 'largefileurl'=>$templargefileurl,
					 'newfilename'=>$tempnewfile,     'newfileurl'=>$tempnewfileurl);
	} 
	
	public function delfontdir($dir) {
    	$dh=opendir($dir);
    	while ($file=readdir($dh)) {
    		if($file!="." && $file!="..") {
    			$fullpath=$dir."/".$file;
    			if(!is_dir($fullpath)) {
    				unlink($fullpath);
    			} else {
    				$this->delfontdir($fullpath);
    			}
    		}
    	}
    	closedir($dh);
    	if($dir!=Config::THEME_FONT_FOLDER){
    		if(@rmdir($dir)) {
    			return true;
    		} else {
    			return false;
    		}
    	}
    }
		
	public function fontpreviewAction()
	{
		try{
			$uData = $this->checkLogin('view');
			if(!$uData)
			{
				die('没有权限');
			}
			$identity = isset($_GET['id'])?$_GET['id']:"";
			$identity   = sqlcheck::check($identity);
			if(empty($identity))
			{
				die("参数错误");
			}
			$tempDataObj = $this->getFontPreviewTable()->getInfo(array('tb_yl_font_preview.identity'=>$identity));
			$infoData    = array();
			foreach($tempDataObj as $mydataratio)
			{
				$infoData[] = (array)$mydataratio;
			}
			$tempDataResultObj = $this->getRatioTable()->getAppDataAll();
			$infoDataRatio     = array();
			foreach($tempDataResultObj as $mydatarow)
			{
				$infoDataRatio[] = (array)$mydatarow;
			}
			return new ViewModel(array(
					'ratioInfo' => $infoDataRatio,'infoData'=>$infoData,
					'showurl'   => Config::SHOW_URL,'fontid'=>$identity,'folderStr'=>Config::REPALCE_STR,				
			));
		}catch (Exception $e){
			Logs::write('SiteController::fontpreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}

	public function addfontpreviewAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();			
				if( empty($postArr['pic']) || (empty($postArr['subjectpic']) && empty($postArr['lsubjectpic']))
						|| empty($postArr['fontid']) || empty($postArr['ttfurl']) )
				{
					die('empty');
				}
				$id         = $postArr['id'];
				$fontid     = $postArr['fontid'];
				$id   	  = sqlcheck::check($id);
				$fontid   = sqlcheck::check($fontid);	
				
				$ttfurl        	= $postArr['ttfurl'];			
				
				$npic        	= $postArr['pic'];	
				$npic        	= urldecode($npic);
				$fileTypeArr  	= explode(".",$npic);
				$fileType     	= end($fileTypeArr);
				$filenmae       = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$npic ) ) ); 
				$filename     	= $filenmae.'_new';
				$start        	= strrpos($npic,'/');
				$newfile      	= substr_replace($npic, $filename.'.'.$fileType, $start+1);
				$srcDir       	= iconv('utf-8','gbk',Config::TEMP_FOLDER.$npic);
				rename($srcDir,Config::TEMP_FOLDER.$newfile);
				$npic     	 = $newfile;
							
	
				$pic        = $postArr['subjectpic'];
				$pic        = urldecode($pic);
				$fileTypeArr  = explode(".",$pic);
				$fileType     = end($fileTypeArr);
				$filenmae       = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$pic ) ) ); 
				$filename     	= $filenmae.'_small';
				$start        = strrpos($pic,'/');
				$newfile      = substr_replace($pic, $filename.'.'.$fileType, $start+1);
				$srcDir       = iconv('utf-8','gbk',Config::TEMP_FOLDER.$pic);
				rename($srcDir,Config::TEMP_FOLDER.$newfile);
				$pic     	 = $newfile;
							
				$lpic        = $postArr['lsubjectpic'];
				$lpic        = urldecode($lpic);
				$fileTypeArr  = explode(".",$lpic);
				$fileType     = end($fileTypeArr);
				$filenmae       = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$lpic ) ) ); 
				$filename     	= $filenmae.'_large';
				$start        = strrpos($lpic,'/');
				$newfile      = substr_replace($lpic, $filename.'.'.$fileType, $start+1);
				$srcDir       = iconv('utf-8','gbk',Config::TEMP_FOLDER.$lpic);
				rename($srcDir,Config::TEMP_FOLDER.$newfile);
				$lpic     	 = $newfile;
							
				$picArr     = explode("/", $pic);
				$theme_size = filesize( Config::TEMP_FOLDER.$pic );
				$theme_md5  = md5_file( Config::TEMP_FOLDER.$pic );
				if($id)
				{
					$this->getFontPreviewTable()->updateData(array(
							'identity'    => $fontid,
							'fname'       => end($picArr),
							'preview_url' => $pic,
							'largepreurl' => $lpic,						
							'size'        => $theme_size,
							'md5'         => $theme_md5,
					));
					$this->getFontTable()->updatePreviewData(array(
							'identity'    => $fontid,
							'purl'        => $npic,
							'url'         => $ttfurl,
					));
					die('success');
				}
				$result = $this->getFontPreviewTable()->getAppData(array('identity'=>$fontid));
				if($result && $result->id)
				{
					die('exist');
				}				
				
				$this->getFontPreviewTable()->saveArr(array(
						'identity'    => $fontid,
						'fname'       => end($picArr),
						'preview_url' => $pic,
						'largepreurl' => $lpic,
						'purl'        => $npic,
						'size'        => $theme_size,
						'md5'         => $theme_md5,
						'insert_time' => date('Y-m-d H:i:s'),	
				));
				$this->getFontTable()->updatePreviewData(array(
						'identity'    => $fontid,
						'purl'        => $npic,
						'url'         => $ttfurl,
				));
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addfontpreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function fontorderAction()
	{
		try{
			//一个计算分页，一个计算总数
	        $uData    = $this->checkLogin('pay');
			if(!$uData)
			{
				die('没有权限');
			}
			$identity = isset($_GET['identity'])?$_GET['identity']:'';
			$page = isset($_GET['page'])?(int)$_GET['page']:1;
			$identity   = sqlcheck::check($identity);
			$page   	= sqlcheck::check($page);
			$pageSize = 20;
			if(empty($identity)){die("error identity");}
			$num     = $this->getChargeTable()->getMyCountnum(array('identity'=>$identity,'status'=>1));
			$totalPage   = ceil($num/$pageSize);
			$result  = $this->getChargeTable()->getAppDataAll($page,$pageSize,array('identity'=>$identity,'status'=>1));
			$result1  = $this->getChargeTable()->getMyAppDataAll(array('identity'=>$identity,'status'=>1));
			$money   = 0;
			$infoData = array();
			foreach($result1 as $row)
			{
				$money += intval($row['money']);
			}
			if(count($result) > 0)
			{
				foreach($result as $row)
				{
					$infoData[] = (array)$row;
				}
			}
			$totalmoney = number_format($money/100,1);
		
			return new ViewModel(array('totalpay'=>$num,'totalmoney'=>$totalmoney,'infoData'=>$infoData,
			          'page'=>$page,'totalPage'=>$totalPage,'mycount'=>$num,'identity'=>$identity));	
		}catch (Exception $e){
			Logs::write('SiteController::fontorderAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//铃声列表页面
	public function ringAction()
	{
		try{
			$pageSize    = 10;
			$uData       = $this->checkLogin('view');
			if(!$uData)		{ die('没有权限'); }
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$page   	= sqlcheck::check($page);
			$countWidget = $this->getRingTable()->getCountnum();	
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage)     $page = $totalPage;
			if($page==0) $page = 1;
			$tempData    = $this->getRingTable()->getData($page,$pageSize);
			$infoData    = array();
			foreach($tempData as $mydata)
			{   //涉及到子项联表查询，没有在table里处理
				$waresid               = $mydata->waresid;
				$type                  = $mydata->type;
				$subtype               = $mydata->subtype;
				$priceData             = $this->getPayTable()->getAppData(array('waresid'=>$waresid));
				$ringtypeData          = $this->getRingTypeTable()->getAppData(array('type'=>$type));
				if((int)$subtype == 0 || (int)$type == 1){
					$ringsubtypeData   = '';
				}else{
					$ringsubtypeData       = $this->getRingSubTypeTable()->getAppData(array('code'=>$subtype))->chname;
				}
				
				$mydataArr             = (array)$mydata;
				$mydataArr['price']    = ($priceData)?$priceData->price.'分':'0分';
				$mydataArr['typename'] = $ringtypeData->note;
				$mydataArr['subtypename'] = $ringsubtypeData;
				$infoData[]            = $mydataArr;
			}
			$payList                   = $this->getPayTable()->getAppDataAll(array('warename'=>'铃声'));	
			$ringTypeList     = $this->getRingTypeTable()->getAppDataAll();
			$ringSubTypeList     = $this->getRingSubTypeTable()->getAppDataAll();
			return new ViewModel(array('keydata'=>$this->chargeArr,'folderStr'=>Config::REPALCE_STR,
					'showurl'=>Config::SHOW_URL,'ringtypelist'=>$ringTypeList,'paylist'=>$payList,
					'ringsubtypelist'=>$ringSubTypeList,'page'=>$page,'countWidget'=>$countWidget,
					'totalPage'=>$totalPage,'infoData'=>$infoData));
		}catch (Exception $e){
			Logs::write('SiteController::ringAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	public function upringAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				$id      = intval($postArr['id']);
				$val     = intval($postArr['val']);
				$id   	= sqlcheck::check($id);
				$val   	= sqlcheck::check($val);
				$this->getRingTable()->updateData(array('id'=>$id,'msort'=>$val));
				die('');
			}
		}catch (Exception $e){
			Logs::write('SiteController::upringAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	public function addringAction()
	{	
		try{	
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if(	!isset($postArr['myring']) )
				{
					die('empty');
				}
				$id            = $postArr['id'];
				$ringtype      = $postArr['myring'];
				$ringsubtype   = $postArr['mysubring']?$postArr['mysubring']:0;
				$mycharge      = $postArr['mycharge'];
				$mychargevalue = $postArr['mychargevalue'];
				$choice        = $postArr['choice'];
				$id         	= sqlcheck::check($id);
				$ringtype   	= sqlcheck::check($ringtype);
				$ringsubtype	= sqlcheck::check($ringsubtype);
				$mycharge   	= sqlcheck::check($mycharge);
				$mychargevalue  = sqlcheck::check($mychargevalue);
				$choice  	    = sqlcheck::check($choice);				
				
				if(intval($mycharge)==1 && empty($mychargevalue))
				{
					die('emptycharge');
				}
				if(intval($mycharge)==0)
				{
					$mychargevalue = '';
				}				
				if($id)
				{
					$pic          = $postArr['subjectpic'];
					$pic          = urldecode($pic);
					
					$tempStrFile  = iconv('utf-8', 'gbk',  Config::TEMP_FOLDER.$pic);
					
					$picArr       = explode("/", $pic);
					$picfile      = end($picArr);
					$extarr       = explode(".", $picfile);				
					$ext          = end($extarr);	
					//var_dump(array("1"=>$pic, "2"=>$tempStrFile, "3"=>$picfile, "4"=>$ext))	;
					$ring_md5     = md5_file( $tempStrFile );
					
					//echo ($ring_md5."   ");
					$tempStr      = str_replace($picfile, $ring_md5.'.'.$ext, $pic);
					//echo ($tempStr."   ");
					//exit();	
					copy($tempStrFile, Config::TEMP_FOLDER.$tempStr);
					$ring_size    = filesize(Config::TEMP_FOLDER.$tempStr);
					//echo ($ring_size."   ");				
					$identity     = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$tempStr )));
					//echo ($identity."   ");				
					$ringResult   = $this->getRingTable()->getAppData(array('identity'=>$identity)); 
					//var_dump($ringResult);
					//exit();
					if($ringResult && $ringResult->id)
					{
						$this->getRingTable()->updateDataInfo(array(
								'identity'=>$identity,
								'type'    =>$ringtype,
								'subtype' =>$ringsubtype,
								'name'    =>$postArr['rname'],
								'ischarge'=>$mycharge,
								'waresid' =>$mychargevalue,
	// 							'note'    =>$extarr[0],
	// 							'fname'   =>$picfile,
								'choice'  =>$choice,
								'url'	  =>$tempStr,
								'size'    =>$ring_size,
								'md5'     =>$ring_md5,
								'update_time' =>date('Y-m-d H:i:s'),
								'update_user' =>$uData->username,
						));
						die('success');
					}else
					{
						$this->getRingTable()->updateData(array(
							'id'      =>$id,
							'identity'=>$identity,
							'type'    =>$ringtype,
							'subtype' =>$ringsubtype,
							'name'    =>$postArr['rname'],
							'ischarge'=>$mycharge,
							'waresid' =>$mychargevalue,							
	// 						'note'    =>$extarr[0],
	// 						'fname'   =>$picfile,
							'choice'  =>$choice,
							'url'	  =>$tempStr,
							'size'    =>$ring_size,
							'md5'     =>$ring_md5,
							'update_time' =>date('Y-m-d H:i:s'),
							'update_user' =>$uData->username,
						));
						die('success');
					}
				}else
				{
					$folder  = file_get_contents(Config::FILE_PATH);
					$handle  = opendir(Config::TEMP_FOLDER.$folder);
					$filenum = 0;
	//				echo "1";
										
					while(false!==($myfile=readdir($handle)))
					{
	//					echo $myfile."    ";
	//					echo "2";
						if($myfile!='Thumbs.db' && $myfile!='.' && $myfile!='..' && $myfile!='')
						{
	//						第一次是.  第二次是..  第三次是真正目录
	//						echo "3";
							$filenum++;
							$myfile       = iconv('gbk', 'utf-8',  $myfile);
							$pic          = $folder.'/'.$myfile;
							$tempStrFile  = iconv('utf-8', 'gbk',  Config::TEMP_FOLDER.$pic);
							$picArr       = explode("/", $pic);
							$picfile      = end($picArr);
							$extarr       = explode(".", $picfile);
							$ext          = end($extarr);
							$ring_md5     = md5_file($tempStrFile);
							$tempStr      = str_replace($picfile, $ring_md5.'.'.$ext, $pic);
							copy($tempStrFile, Config::TEMP_FOLDER.$tempStr);
	// 						unlink($tempStrFile);
							$ring_size    = filesize(Config::TEMP_FOLDER.$tempStr);
							$identity     = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$tempStr ) ) );
							if($identity == 0)continue;
							$ringResult   = $this->getRingTable()->getAppData(array('identity'=>$identity));
							if($ringResult && $ringResult->id)
							{
	//							echo "4";
								$this->getRingTable()->updateDataInfo(array(
										'identity'=>$identity,
										'type'    =>$ringtype,
										'subtype' =>$ringsubtype,
										'name'    =>$extarr[0],
										'fname'   =>$picfile,
										'choice'      =>$choice,
										'note'    =>$extarr[0],
										'url'	  =>$tempStr,
										'size'    =>$ring_size,
										'md5'     =>$ring_md5,
										'update_time' =>date('Y-m-d H:i:s'),
										'update_user' =>$uData->username,
								));
							}else
							{
	//							echo "5";
								$this->getRingTable()->saveArr(array(
										'identity'    =>$identity,
										'type'        =>$ringtype,
										'subtype'     =>$ringsubtype,
										'name'        =>$extarr[0],
										'ischarge'    =>0,
										'waresid'     =>'',
										'choice'      =>$choice,
										'note'        =>$extarr[0],
										'fname'       =>$picfile,
										'url'	      =>$tempStr,
										'size'        =>$ring_size,
										'md5'         =>$ring_md5,
										'insert_time' =>date('Y-m-d H:i:s'),
										'insert_user' =>$uData->username,
								));							
							}
						}
					}
	//				echo "  次数".$filenum;
	//				exit();
					if($filenum==0)
					{
						die('fileempty');
					}
					die('success');
				}
			}
		}catch (Exception $e){
			Logs::write('SiteController::addringAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//闹钟铃声列表页面
	public function alarmAction()
	{
		try{
			$pageSize    = 10;
			$uData       = $this->checkLogin('view');
			if(!$uData)		{ die('没有权限'); }
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$page  	    = sqlcheck::check($page);
			$countWidget = $this->getAlarmTable()->getCountnum();	
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage)     $page = $totalPage;
			if($page==0) $page = 1;
			$tempData    = $this->getAlarmTable()->getData($page,$pageSize);
			$infoData    = array();
			foreach($tempData as $mydata)
			{   //涉及到子项联表查询，没有在table里处理
				$waresid               = $mydata->waresid;
				$type                  = $mydata->type;
				$subtype               = $mydata->subtype;
				$priceData             = $this->getPayTable()->getAppData(array('waresid'=>$waresid));
	//			$alarmtypeData          = $this->getAlarmTypeTable()->getAppData(array('type'=>$type));
				if((int)$subtype == 0 || (int)$type == 1){
					$alarmsubtypeData   = '';
				}else{		
					$bb = $this->getAlarmLabelTable()->getAppData(array('code'=>$subtype));		
	//				$alarmsubtypeData = isset($this->getAlarmLabelTable()->getAppData(array('code'=>$subtype))->chname)?$this->getAlarmLabelTable()->getAppData(array('code'=>$subtype))->chname:'';
					$alarmsubtypeData = isset($bb->chname)?$bb->chname:'';
				}
				
				$mydataArr             = (array)$mydata;
				$mydataArr['price']    = ($priceData)?$priceData->price.'分':'0分';
	//			$mydataArr['typename'] = $alarmtypeData->note;
				$mydataArr['subtypename'] = $alarmsubtypeData;
				$infoData[]            = $mydataArr;
			}
			$payList                   = $this->getPayTable()->getAppDataAll(array('warename'=>'铃声'));	
	//		$alarmTypeList     = $this->getAlarmTypeTable()->getAppDataAll();
			$alarmSubTypeList     = $this->getAlarmLabelTable()->getAppDataAll();
			return new ViewModel(array('keydata'=>$this->chargeArr,'folderStr'=>Config::REPALCE_STR,
					'showurl'=>Config::SHOW_URL,'paylist'=>$payList,
					'alarmsubtypelist'=>$alarmSubTypeList,'page'=>$page,'countWidget'=>$countWidget,
					'totalPage'=>$totalPage,'infoData'=>$infoData));
		}catch (Exception $e){
			Logs::write('SiteController::alarmAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
		
	public function addalarmAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if(	!isset($postArr['mysubalarm']) )
				{
					die('empty');
				}
				$id            = $postArr['id'];
				$alarmsubtype      = $postArr['mysubalarm'];			
				$mycharge      = $postArr['mycharge'];
				$mychargevalue = $postArr['mychargevalue'];	
				$id  	        = sqlcheck::check($id);
				$alarmsubtype  	= sqlcheck::check($alarmsubtype);
				$mycharge  	    = sqlcheck::check($mycharge);
				$mychargevalue  = sqlcheck::check($mychargevalue);
						
				if(intval($mycharge)==1 && empty($mychargevalue))
				{
					die('emptycharge');
				}
				if(intval($mycharge)==0)
				{
					$mychargevalue = '';
				}				
				if($id)
				{
					$pic          = $postArr['subjectpic'];
					$pic          = urldecode($pic);
					
					$tempStrFile  = iconv('utf-8', 'gbk',  Config::TEMP_FOLDER.$pic);
					
					$picArr       = explode("/", $pic);
					$picfile      = end($picArr);
					$extarr       = explode(".", $picfile);				
					$ext          = end($extarr);	
					//var_dump(array("1"=>$pic, "2"=>$tempStrFile, "3"=>$picfile, "4"=>$ext))	;
					$alarm_md5     = md5_file( $tempStrFile );
					
					//echo ($alarm_md5."   ");
					$tempStr      = str_replace($picfile, $alarm_md5.'.'.$ext, $pic);
					//echo ($tempStr."   ");
					//exit();	
					copy($tempStrFile, Config::TEMP_FOLDER.$tempStr);
					$alarm_size    = filesize(Config::TEMP_FOLDER.$tempStr);
					//echo ($alarm_size."   ");				
					$identity     = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$tempStr )));
					//echo ($identity."   ");				
					$alarmResult   = $this->getAlarmTable()->getAppData(array('identity'=>$identity)); 
					//var_dump($alarmResult);
					//exit();
					if($alarmResult && $alarmResult->id)
					{
						$this->getAlarmTable()->updateDataInfo(array(
								'identity'=>$identity,
								'subtype' =>$alarmsubtype,
								'name'    =>$postArr['rname'],
								'ischarge'=>$mycharge,
								'waresid' =>$mychargevalue,
	// 							'note'    =>$extarr[0],
	// 							'fname'   =>$picfile,
								'url'	  =>$tempStr,
								'size'    =>$alarm_size,
								'md5'     =>$alarm_md5,
								'update_time' =>date('Y-m-d H:i:s'),
								'update_user' =>$uData->username,
						));
						die('success');
					}else
					{
						$this->getAlarmTable()->updateData(array(
							'id'      =>$id,
							'identity'=>$identity,
	//						'type'    =>$alarmtype,
							'subtype' =>$alarmsubtype,
							'name'    =>$postArr['rname'],
							'ischarge'=>$mycharge,
							'waresid' =>$mychargevalue,							
	// 						'note'    =>$extarr[0],
	// 						'fname'   =>$picfile,
	//						'choice'  =>$choice,
							'url'	  =>$tempStr,
							'size'    =>$alarm_size,
							'md5'     =>$alarm_md5,
							'update_time' =>date('Y-m-d H:i:s'),
							'update_user' =>$uData->username,
						));
						die('success');
					}
				}else
				{
					$folder  = file_get_contents(Config::FILE_PATH);
					$handle  = opendir(Config::TEMP_FOLDER.$folder);
					$filenum = 0;		
					//批量插入			
					while(false!==($myfile=readdir($handle)))
					{
						if($myfile!='Thumbs.db' && $myfile!='.' && $myfile!='..' && $myfile!='')
						{
							$filenum++;
							$myfile       = iconv('gbk', 'utf-8',  $myfile);
							$pic          = $folder.'/'.$myfile;
							$tempStrFile  = iconv('utf-8', 'gbk',  Config::TEMP_FOLDER.$pic);
							$picArr       = explode("/", $pic);
							$picfile      = end($picArr);
							$extarr       = explode(".", $picfile);
							$ext          = end($extarr);
							$alarm_md5     = md5_file($tempStrFile);
							$tempStr      = str_replace($picfile, $alarm_md5.'.'.$ext, $pic);
							copy($tempStrFile, Config::TEMP_FOLDER.$tempStr);
	// 						unlink($tempStrFile);
							$alarm_size    = filesize(Config::TEMP_FOLDER.$tempStr);
							$identity     = sprintf("%u", crc32( file_get_contents( Config::TEMP_FOLDER.$tempStr ) ) );
							if($identity == 0)continue;
							$alarmResult   = $this->getAlarmTable()->getAppData(array('identity'=>$identity));
							if($alarmResult && $alarmResult->id)
							{
								$this->getAlarmTable()->updateDataInfo(array(
										'identity'=>$identity,
	//									'type'    =>$alarmtype,
										'subtype' =>$alarmsubtype,
										'name'    =>$extarr[0],
										'fname'   =>$picfile,
	//									'choice'      =>$choice,
										'note'    =>$extarr[0],
										'url'	  =>$tempStr,
										'size'    =>$alarm_size,
										'md5'     =>$alarm_md5,
										'update_time' =>date('Y-m-d H:i:s'),
										'update_user' =>$uData->username,
								));
							}else
							{
								$this->getAlarmTable()->saveArr(array(
										'identity'    =>$identity,
	//									'type'        =>$alarmtype,
										'subtype'     =>$alarmsubtype,
										'name'        =>$extarr[0],
										'ischarge'    =>0,
										'waresid'     =>'',
	//									'choice'      =>$choice,
										'note'        =>$extarr[0],
										'fname'       =>$picfile,
										'url'	      =>$tempStr,
										'size'        =>$alarm_size,
										'md5'         =>$alarm_md5,
										'insert_time' =>date('Y-m-d H:i:s'),
										'insert_user' =>$uData->username,
								));							
							}
						}
					}
					if($filenum==0)
					{
						die('fileempty');
					}
					die('success');
				}
			}
		}catch (Exception $e){
			Logs::write('SiteController::addalarmAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//闹钟专辑列表
	public function alalbumAction()
	{
		try{
			$pageSize = 10;
			$uData    = $this->checkLogin('view');
			if(!$uData)
			{
				die('没有权限');
			}
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$page  = sqlcheck::check($page);
			$countWidget = $this->getAlarmLabelTable()->getCountnum();	
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			$tempData    = $this->getAlarmLabelTable()->getData($page,$pageSize);
			$infoData    = array();
			foreach($tempData as $mydata)
			{
				$mydataArr  = (array)$mydata;
				$infoData[] = $mydataArr;
			}	
			return new ViewModel(array('uData'=>$uData, 'page'=>$page, 'countWidget'=>$countWidget, 'totalPage'=>$totalPage,
	                                   'infoData'=>$infoData, 'showurl'=>Config::SHOW_URL, 'folderStr'=>Config::REPALCE_STR));
		}catch (Exception $e){
			Logs::write('SiteController::alalbumAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	public function addalalbumAction()
	{
		try{
			//id存在就更新，不存在就是插入；更新过程中code值不变，插入过程中code是最大值加1；
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if( empty($postArr['mychname']) || empty($postArr['subjectpic']) || empty($postArr['myenname']) )
				{
					die('empty');
				}
				$id     = $postArr['id'];
				$chname = $postArr['mychname'];
				$enname = $postArr['myenname'];
				$sort   = $postArr['mysort'];	
				$pic    = $postArr['subjectpic'];	
				$id      = sqlcheck::check($id);
				$chname  = sqlcheck::check($chname);
				$enname  = sqlcheck::check($enname);
				$sort    = sqlcheck::check($sort);
				$pic     = sqlcheck::check($pic);		
				$pic    = urldecode($pic);				
				if($id)
				{
					$code = $postArr['code'];
					$this->getAlarmLabelTable()->updateData(array('id'=>$id, 'chname'=>$chname, 'enname'=>$enname, 'imgurl'=>$pic, 'sort'=>$sort, 'code'=>$code));
					die('success');
				}	
				$tmpcode = $this->getAlarmLabelTable()->getMaxSort();
				$code = (int)$tmpcode + 1;
				$result = $this->getAlarmLabelTable()->getAppData(array( " chname='".$chname."' OR enname='".$enname."' " ));
				if($result && $result->id)
				{
					die('exist');
				}	
				$this->getAlarmLabelTable()->saveArr(array('chname'=>$chname, 'enname'=>$enname, 'imgurl'=>$pic, 'sort'=>$sort, 'code'=>$code));
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addalalbumAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//铃声类型页面
	public function ringtypeAction()
	{
		try{
			$pageSize = 10;
			$uData    = $this->checkLogin('view');
			if(!$uData)
			{
				die('没有权限');
			}
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$page     = sqlcheck::check($page);	
			$countWidget = $this->getRingTypeTable()->getCountnum();	
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			$tempData    = $this->getRingTypeTable()->getData($page,$pageSize);
			$infoData    = array();
			foreach($tempData as $mydata)
			{
				$mydataArr  = (array)$mydata;
				$infoData[] = $mydataArr;
			}	
			return new ViewModel(array('uData'=>$uData,'page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData));
		}catch (Exception $e){
			Logs::write('SiteController::ringtypeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	public function addringtypeAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if( empty($postArr['myname']) || empty($postArr['mynote']) )
				{
					die('empty');
				}
				$id = $postArr['id'];
				$appname = $postArr['myname'];
				$appshow = $postArr['mynote'];	
				$mytype  = $postArr['mytype'];
				$id        = sqlcheck::check($id);
				$appname   = sqlcheck::check($appname);
				$appshow   = sqlcheck::check($appshow);
				$mytype    = sqlcheck::check($mytype);
				
				if($id)
				{
					$this->getRingTypeTable()->updateData(array('id'=>$id,'name'=>$appname,'note'=>$appshow));
					die('success');
				}	
				$result = $this->getRingTypeTable()->getAppData(array( "name='".$appname."' OR type='".$mytype."'" ));
				if($result && $result->id)
				{
					die('exist');
				}	
				$this->getRingTypeTable()->saveArr(array('name'=>$appname,'type'=>$mytype,'note'=>$appshow));
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addringtypeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//机型列表页面
	public function productAction()
	{
		try{
			$pageSize = 10;
			$uData    = $this->checkLogin('view');
			if(!$uData)
			{
				die('没有权限');
			}
			$keyword     = isset($_GET['keyword'])?$_GET['keyword']:'';
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$keyword     = sqlcheck::check($keyword);
			$page        = sqlcheck::check($page);
			if($keyword == ''){
				$countWidget = $this->getProductTable()->getCountnum();
			}else{
	    		$countWidget = $this->getProductTable()->getKeyCountnum($keyword);
	    	}	    	
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			if($keyword == ''){
				$tempData = $this->getProductTable()->getData($page,$pageSize);
			}else{
				$tempData = $this->getProductTable()->getKeyData($keyword, $page,$pageSize);
			}
			$infoData = array();
			foreach($tempData as $mydata)
			{
				$infoData[] = (array)$mydata;
			}
		
			$tempDataObj   = $this->getRatioTable()->getAppDataAll();
			$infoDataRatio = array();
			foreach($tempDataObj as $mydataratio)
			{
				$infoDataRatio[] = (array)$mydataratio;
			}
			return new ViewModel(array('uData'=>$uData,'page'=>$page,'ratiodata'=>$infoDataRatio,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData));
		}catch (Exception $e){
			Logs::write('SiteController::productAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function addproductAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if( empty($postArr['myproduct']) || empty($postArr['myratio']) || empty($postArr['mykernel']) || empty($postArr['mytag']) )
				{
					die('empty');
				}
				$id      = $postArr['id'];
				$product = $postArr['myproduct'];
				$ratio   = explode("-",$postArr['myratio']);
				$width   = $ratio[0];
				$height  = $ratio[1];
				$kernel  = $postArr['mykernel'];
				$tag     = $postArr['mytag'];
				$type    = $postArr['mytype'];
				$ispush  = $postArr['ispush'];
				$pushtype= $postArr['pushtype'];
				
//				$id        = sqlcheck::check($id);
//				$product   = sqlcheck::check($product);
//				$kernel    = sqlcheck::check($kernel);
//				$tag       = sqlcheck::check($tag);
//				$type      = sqlcheck::check($type);
//				$ispush    = sqlcheck::check($ispush);
//				$pushtype  = sqlcheck::check($pushtype);				
		
				if($id)
				{
					$this->getProductTable()->updateData(array('id'=>$id,'type'=>$type,'width'=>$width,'height'=>$height,
											'tag'=>$tag,'product'=>$product,'kernelCode'=>$kernel,'update_time'=>date('Y-m-d H:i:s'),
											'ispush'=>$ispush,'pushtype'=>$pushtype));
					die('success');
				}
		
				$result = $this->getProductTable()->getAppData(array('product'=>$product));
				if($result && $result->id)
				{
					die('exist');
				}
		
				$this->getProductTable()->saveArr(array('width'=>$width,'type'=>$type,'height'=>$height,'tag'=>$tag,
						                                'product'=>$product,'kernelCode'=>$kernel,'ispush'=>$ispush,
														'pushtype'=>$pushtype,'insert_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addproductAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//推送列表页面
	public function pushlistAction()
    {
    	try{
	    	$pageSize = 10;
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$page  = isset($_GET['page'])?(int)$_GET['page']:0;
	    	$type  = isset($_GET['type'])?(int)$_GET['type']:0;
	    	$find  = isset($_GET['find'])?$_GET['find']:'';
	    	$startTime = isset($_GET['tStartTime'])?$_GET['tStartTime']:'';
	    	$endTime   = isset($_GET['tEndTime'])?$_GET['tEndTime']:'';
	    	$page   = sqlcheck::check($page);
	    	$type   = sqlcheck::check($type);
	    	$find   = sqlcheck::check($find);
	    	$startTime   = sqlcheck::check($startTime);
	    	$endTime     = sqlcheck::check($endTime);
	    	
	    	$where = array('type'=>$type);    	
	    	if($find!='')
	    	{
	    		$where[] = "name LIKE '%".$find."%'";
	    	}    
	    	if($startTime!='')
	    	{
	    		$where[] = "insert_time >= '$startTime'";
	    	} 
	    	if($endTime!='')
	    	{
	    		$where[] = "insert_time <= '$endTime'";
	    	} 	
	    	$countWidget = $this->getTaskTable()->getCountnum($where);   
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0)         $page = 1;
	    	$tempData    = $this->getTaskTable()->getData($page,$pageSize,$where);
	    	$infoData    = array();
	    	foreach($tempData as $mydata)
	    	{	    		    	    
	    		$infoData[] = (array)$mydata;
	    	}
	    	$tasktype    = array('0'=>'主题','4'=>'铃声','5'=>'字体','3'=>'壁纸', '6'=>'解锁');
	    	return new ViewModel(array('uData'=>$uData,'t'=>$type,'findstr'=>$find,'tasktype'=>$tasktype,'page'=>$page,
                   'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData,
                    'startTime'=>$startTime, 'endTime'=>$endTime));
    	}catch (Exception $e){
			Logs::write('SiteController::pushlistAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    } 
    
    public function taskdetailAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$taskid   = $_GET['id'];
	    	$taskid   = sqlcheck::check($taskid);
	    	if(empty($taskid)){ die('参数非法');  }
	    	$post_data = array('taskid' => $taskid);
	    	$myinfo   = $this->geturldata(Config::TASK_DETAIL, $post_data, 0); 
	    	$myinfo   = json_decode($myinfo,true);
	    	return new ViewModel(array('infodata'=>$myinfo));
    	}catch (Exception $e){
			Logs::write('SiteController::taskdetailAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskmanageAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$taskid   = $_GET['id'];
	    	$taskid   = sqlcheck::check($taskid);
	    	if(empty($taskid)){ die('参数非法');  } 
	    	$post_data = array('taskid' => $taskid);
	    	$myinfo   = $this->geturldata(Config::TASK_DETAIL, $post_data, 0); 
	    	$myinfo   = json_decode($myinfo,true);
	    	return new ViewModel(array('infodata'=>$myinfo));
    	}catch (Exception $e){
			Logs::write('SiteController::taskmanageAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskmobileAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$limit    = 20;
	    	$taskid   = $_GET['id'];
	    	$taskid   = sqlcheck::check($taskid);
	    	if(empty($taskid)){ die('参数非法');  }
	    	$post_data =array(	                
	    			'taskid'=> $taskid,
	    			'start' => 0,
	    			'limit' =>$limit,
	    	        );   	
	    	$myinfo   = $this->geturldata(Config::TASK_MOBILE, $post_data, 1);
	    	$myinfo   = json_decode($myinfo);
	    	return new ViewModel(array('taskid'=>$taskid,'infodata'=>$myinfo));
    	}catch (Exception $e){
			Logs::write('SiteController::taskmobileAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskvalidAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$cid       = isset($_GET['cid'])?$_GET['cid']:'';
	    	$taskid    = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$valid     = isset($_GET['valid'])?$_GET['valid']:'';
	    	$cid     = sqlcheck::check($cid);
	    	$taskid  = sqlcheck::check($taskid);
	    	$valid   = sqlcheck::check($valid);
	    	
	    	if(empty($cid) || empty($taskid) || $valid=='')
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	               
	    			'taskid' => $taskid,
	    			'cid'    => $cid,
	    			'valid'  => $valid,
	    	);	    	
	    	$myinfo    = $this->geturldata(Config::TASK_MVALID, $post_data, 1);
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();  
    	}catch (Exception $e){
			Logs::write('SiteController::taskvalidAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}  	     	
    }
    
    public function taskagainAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$cid      = isset($_GET['cid'])?$_GET['cid']:'';
	    	$taskid   = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$status   = isset($_GET['valid'])?$_GET['valid']:'';
	    	$type     = isset($_GET['t'])?$_GET['t']:'';
	    	$cid      = sqlcheck::check($cid);
	    	$taskid   = sqlcheck::check($taskid);
	    	$status   = sqlcheck::check($status);
	    	$type     = sqlcheck::check($type);
	    	
	    	if(empty($cid) || empty($taskid) || $status=='' || $type=='')
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	                
	    			'taskid' => $taskid,
	    			'cid'    => $cid,
	    			'status' => $status,
	    			'type'   => $type,
	    	);	    	
	    	$myinfo   = $this->geturldata(Config::TASK_AGAIN, $post_data, 1);
	    	$myinfo   = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::taskagainAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function getmsgAction()
    {
    	try{
    		//用来获取产品列表，从mongo上获取，数据量太大，换在themes数据为的产品表，此函数无用
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}    		
    		$url     = Config::PRODUCT_LIST;	    		 			
    		$result  = $this->geturldata($url,array() , 0);
    		die($result);
	    	
    	}catch (Exception $e){
			Logs::write('SiteController::getmsgAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function getmobilemsgAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}  
	    	$request = $this->getRequest();
	    	if($request->isPost())
	    	{
	    		$postarr = $request->getPost();
		    	$filed   = isset($postarr['filed'])?$postarr['filed']:'';
		    	$value   = isset($postarr['value'])?$postarr['value']:'';
		    	$filed   = sqlcheck::check($filed);	    	
		    	$value   = sqlcheck::check($value);
		    	$post_data = array(
		    			'filed' => $filed,
		    			'value' => $value,	    		
		    	);    		    		 			
	    		$result  = $this->geturldata(Config::MDETAILS, $post_data , 0);
	    		die($result);
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::getmobilemsgAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskmobileaddAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$taskid    = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$tmobiles  = isset($_GET['tmobiles'])?$_GET['tmobiles']:'';
	    	$taskid     = sqlcheck::check($taskid); 
	    	$tmobiles   = sqlcheck::check($tmobiles); 
	    	if(empty($tmobiles) || empty($taskid) )
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	                
	    			'taskid'  => $taskid,
	    			'tmobiles'=> $tmobiles,
	    	);
	    	$myinfo    = $this->geturldata(Config::TMADD, $post_data, 1);
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::taskmobileaddAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskproductAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$limit    = 20;
	    	$taskid   = $_GET['id'];	    	
	    	$taskid   = sqlcheck::check($taskid);
	    	//$taskid   = 'CPUSH_TASK_0000205';
	    	if(empty($taskid)){ die('参数非法');  }
	    	$post_data =array(	                
	    			'taskid' => $taskid,
	    			'start' => 0,
	    			'limit' => $limit,
	    	);	    	
	    	$myinfo   = $this->geturldata(Config::TASK_PRODUCT, $post_data,1);
	    	$myinfo   = json_decode($myinfo);	    	
	    	return new ViewModel(array('taskid'=>$taskid,'infodata'=>$myinfo));
    	}catch (Exception $e){
			Logs::write('SiteController::taskproductAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function productversionAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$product    = isset($_GET['productstr'])?$_GET['productstr']:'';
	    	if(empty($product))
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	               
	    			'product' => $product,
	    	);	    	
	    	$myinfo    = $this->geturldata(Config::TASK_TP_VERSION, $post_data, 1);
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?json_encode($myinfo['version']):$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::productversionAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskpvalidAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$cid     = isset($_GET['cid'])?$_GET['cid']:'';
	    	$taskid  = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$status  = isset($_GET['valid'])?$_GET['valid']:'';
	    	$asignver= isset($_GET['asignver'])?$_GET['asignver']:'';
	    	$version = isset($_GET['version'])?$_GET['version']:'';
	    	$cid        = sqlcheck::check($cid);
	    	$taskid     = sqlcheck::check($taskid);
	    	$status     = sqlcheck::check($status);
	    	$asignver   = sqlcheck::check($asignver);
	    	$version    = sqlcheck::check($version);
	    	if(empty($cid) || empty($taskid) || $status=='' || $asignver=='')
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	                
	    			'taskid'  =>$taskid,
	    			'product' =>$cid,
	    			'valid'   =>$status,
	    			'asignver'=>$asignver,
	    			'version' =>$version,
	    	);	    	
	    	$myinfo    = $this->geturldata(Config::TASK_PVALID, $post_data, 1);
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::taskpvalidAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }   
	
	public function taskpagainAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$cid     = isset($_GET['cid'])?$_GET['cid']:'';
	    	$taskid  = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$status  = isset($_GET['valid'])?$_GET['valid']:'';
	    	$type    = isset($_GET['t'])?$_GET['t']:'';
	    	$cid      = sqlcheck::check($cid);
	    	$taskid   = sqlcheck::check($taskid);
	    	$status   = sqlcheck::check($status);
	    	$type     = sqlcheck::check($type);
	    	
	    	if(empty($cid) || empty($taskid) || $status=='' || $type=='')
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	                        
	    			'taskid' =>$taskid,
	    			'product'=>$cid,
	    			'status' =>$status,
	    			'type'   =>$type,
	    	);	    	
	    	$myinfo    = $this->geturldata(Config::TASK_PAGAIN, $post_data, 1);   	 
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::taskpagainAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }   
    
    public function taskaddproductAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$taskid    = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$asignver  = isset($_GET['asignver'])?$_GET['asignver']:'';
	    	$product   = isset($_GET['productstr'])?$_GET['productstr']:'';
	    	$version   = isset($_GET['version'])?$_GET['version']:'';
	    	$taskid      = sqlcheck::check($taskid);
	    	$asignver    = sqlcheck::check($asignver);
	    	$product     = sqlcheck::check($product);
	    	$version     = sqlcheck::check($version);
	    	if(empty($product) || empty($taskid))
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	                
	    			'taskid' =>$taskid,
	    			'product'=>$product,
	    			'version'=>$version,
	    	);
	    	if($asignver!='')
	    	{
	    		$post_data[] = array('asignver' => $asignver);
	    	}	    	
	    	$myinfo    = $this->geturldata(Config::TPADD, $post_data, 1);
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::taskaddproductAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskchangeproductversionAction()
    {
    	try{
    		$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$taskid       = isset($_GET['taskid'])?$_GET['taskid']:'';
	    	$oldasignver  = isset($_GET['oldasignver'])?$_GET['oldasignver']:'';
	    	$oldversion   = isset($_GET['oldversion'])?$_GET['oldversion']:'';
	    	$product      = isset($_GET['productstr'])?$_GET['productstr']:'';
	    	$newversion   = isset($_GET['newversion'])?$_GET['newversion']:'';
	    	$newasignver  = isset($_GET['newasignver'])?$_GET['newasignver']:'';
	    	$taskid        = sqlcheck::check($taskid);
	    	$oldasignver   = sqlcheck::check($oldasignver);
	    	$oldversion    = sqlcheck::check($oldversion);
	    	$product       = sqlcheck::check($product);
	    	$newversion    = sqlcheck::check($newversion);
	    	$newasignver   = sqlcheck::check($newasignver);
	    	if(empty($product) || empty($taskid))
	    	{
	    		die('error');
	    	}
	    	$post_data =array(	              
	    			'taskid'   => $taskid,
	    			'product'  => $product,
	    			'version'  => $newversion,
	    			'asignver' => $newasignver,
	    			'oldversion'  => $oldversion,
	    			'oldasignver' => $oldasignver,
	    	);	    	
	    	$myinfo    = $this->geturldata(Config::TP_CHANGEVER, $post_data, 1);
	    	$myinfo    = json_decode($myinfo,true);
	    	echo ($myinfo['result'])?'success':$myinfo['error'];
	    	exit();
    	}catch (Exception $e){
			Logs::write('SiteController::taskchangeproductversionAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function taskreportAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$limit    = 20;
	    	$taskid   = $_GET['id'];
	    	//sqlcheck全部变小写，mongo区分大小写
//	    	$taskid   = sqlcheck::check($taskid);	    	
	    	if(empty($taskid)){ die('参数非法');  }	    	    	
	    	$myinfo   = $this->geturldata(Config::TASK_REPORT, array('taskid' => $taskid), 0 );
	    	$myinfo   = json_decode($myinfo, true);	    	
	    	//构造饼形图数据	  
	    	$arrChartData = array();
	    	$nowCount = 0;
	    	$nHasRealPushCount = 0;
	    	foreach ($myinfo['actions'] as $row){
				if (strcmp($row['action'], 'receive') == 0 || 
						strcmp($row['action'], 'receiver') == 0 ||
						strcmp($row['action'], 'action_receive') == 0 ||
						strcmp($row['action'], 'action_receiver') == 0){
						$nHasRealPushCount = (int)$row['count'];
				} else {
					$nowCount = $nowCount + (int)$row['count'];					
					$arrData = array($row['action'], (int)$row['count']);
					array_push($arrChartData, $arrData);
				}			
			}			
			$otherCount = $nHasRealPushCount - $nowCount;
			$arrOhter = array('others', $otherCount);
			array_push($arrChartData, $arrOhter);
	    	  	
	    	return new ViewModel(array('taskid'=>$taskid,'infodata'=>$myinfo, 'chardata'=>$arrChartData));
    	}catch (Exception $e){
			Logs::write('SiteController::taskreportAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function tasksummaryAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$nHasPushCount = 0;
			$nHasReceiveCount = 0;
			$nHasRealPushCount = 0;
		
			$strTaskId = $_GET['id'];			
			$arrData = $this->_getSummaryRecord()->getSummaryData($strTaskId);
			if($arrData === false){
				Logs::write('SiteController::tasksummaryAction():getSummaryData() failed', 'log');
			}
			
			$nHasPushCount = isset($arrData[0]['push']['count'])?$arrData[0]['push']['count']:0;
			$nHasReceiveCount = isset($arrData[0]['receipt']['count'])?$arrData[0]['receipt']['count']:0;
			$arrAction = isset($arrData[0]['action'])?$arrData[0]['action']:array();	
			$strNote = isset($arrData[0]['note'])?$arrData[0]['note']:'';
			$tstart  = isset($arrData[0]['tstart'])?$arrData[0]['tstart']:'';
			$tend    = isset($arrData[0]['tend'])?$arrData[0]['tend']:'';
			$tstart = $this->microtime_format('Y-m-d H:i:s', $tstart);
            $tend   = $this->microtime_format('Y-m-d H:i:s', $tend);
			$arrProduct = isset($arrData[0]['product'])?$arrData[0]['product']:array();
			$strProduct = '';
			foreach ($arrProduct as $product){
				$strProduct .= $product.'/';
			}
			
			
			
			$arrActionObj = array();
            $arrProductCount = array();
            $nActionReceive = 0;
            foreach ($arrAction as $row){
                    if (!in_array($row['action'], $arrActionObj)){
                            array_push($arrActionObj, $row['action']);
                    }

                    foreach ($row['receipt'] as $preceipt){
                            $strPro = str_replace('%20', ' ', $preceipt['product']);
                            if (array_key_exists($strPro, $arrProductCount)){
                                    $arrProductCount[$strPro][$row['action']] = $preceipt['count'];
                            } else {
                                    $arrProductCount[$strPro] = array('product'=>$strPro, $row['action'] => $preceipt['count']);
                            }
                    }

                    if (strcmp($row['action'], 'receive') == 0 ||
                                    strcmp($row['action'], 'receiver') == 0 ||
                                    strcmp($row['action'], 'action_receive') == 0 ||
                                    strcmp($row['action'], 'action_receiver') == 0){
                            $nActionReceive = (int)$row['count'];
                    }

            }

            $arrPush = isset($arrData[0]['push']['push'])?$arrData[0]['push']['push']:array();
            foreach ($arrPush as $ppush){
                    if (array_key_exists($ppush['product'], $arrProductCount)){
                            $arrProductCount[$ppush['product']]['push'] = $ppush['count'];
                    } else {
                            $arrProductCount[$ppush['product']] = array('product'=>$ppush['product'], 'push' => $ppush['count']);
                    }
            }

            $arrRece = isset($arrData[0]['receipt']['receipt'])?$arrData[0]['receipt']['receipt']:array();
            foreach ($arrRece as $prece){
                    $strPro = str_replace('%20', ' ', $prece['product']);
                    if (array_key_exists($strPro, $arrProductCount)){
                            $arrProductCount[$strPro]['receipt'] = $prece['count'];
                    } else {
                            $arrProductCount[$strPro] = array('product'=>$strPro, 'receipt' => $prece['count']);
                    }
            }			
			
			if ($nHasPushCount == 0){
				$rateReceive = 0;
			} else {
				$rateReceive = round($nActionReceive/$nHasPushCount, 3)*100;
			}
			
			$dataAction = array();
			foreach ($arrAction as $row){
				if (strcmp($row['action'], 'receive') != 0 && 
						strcmp($row['action'], 'receiver') != 0 &&
						strcmp($row['action'], 'action_receive') != 0 &&
						strcmp($row['action'], 'action_receiver') != 0){
					$nActionCount = (int)$row['count'];
					$rate = round($nActionCount/$nActionReceive, 3)*100;
					array_push($dataAction, array('action'=>$row['action'], 'count'=>$nActionCount, 'rate'=>$rate));
				}
			}
		
			$view =  new ViewModel(array(
					'taskid'	       => $strTaskId,
					'push'		       => $nHasPushCount,
					'receipt'          => $nHasReceiveCount,
					'action_receive'   => $nActionReceive,
					'rate_receive'     => $rateReceive,
					'action_data'      => $dataAction,
					'product' 	       => $strProduct,
					'note'		       => $strNote,
					'tStart'           => $tstart,
					'tEnd'             => $tend,
					'arrActions'        => $arrActionObj,
					'arrProduct'       => $arrProductCount,
					                    ));
			return $view;
    	}catch (Exception $e){
			Logs::write('SiteController::tasksummaryAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
	//banner列表
	public function bannerAction()
    {
    	try{
	    	//form表单没有传bannerid值，只有链接传bannerid，所以初次不显示表
	    	//typeid和bannerid其实 就是cooltype和id，逻辑写的过于麻烦，取cpid，然后再取资源
	    	//bannerid取值到底是id还是identity？
	    	$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$bannerid = 0;
	    	if(isset($_GET['typeid'])){
	    		$typeid = $_GET['typeid'];   
	    		$typeid   = sqlcheck::check($typeid);		
	    	}else{
	    		$typeid = '0';
	    	}
	    	$tempData = $this->getBannerTable()->getAppDataList(array('cooltype'=>$typeid,'valid'=>1));
	    	$infoarr  = array();
	    	$infoarr['top'] = array();;
	    	$infoarr['data']= array();
	    	foreach($tempData as $rowobj)
	    	{
	    		if(intval($rowobj->istop)==1)
	    		{
	    			$infoarr['top'] = (array)$rowobj;
	    		}else{ $infoarr['data'][] = (array)$rowobj;  }
	    	}
	    	$infodata = array();
			
	    	if(isset($_GET['bannerid']))
	    	{
	    		$bannerid = $_GET['bannerid'];
	    		$bannerid   = sqlcheck::check($bannerid);
	    		if($bannerid > 0){
		    		$tdata    = $this->getBannerlistTable()->getAppDataList(array('bannerid'=>$bannerid,'cooltype'=>$typeid));
	// 	    		var_dump($tdata);
		    		foreach($tdata as $temp)
		    		{
		    			$mytemp   = (array)$temp;	    			
		    			$cooltype = $temp->cooltype;
	// 	    			if(intval($cooltype) == 0 && $temp->valid){ $showtheme = false;}
		    			$cpid     = $temp->cpid;
		    			$name     = "";
		    			if(intval($cooltype)==0)//以下是为了获得名称
		    			{
		    				$themeData = $this->getThemeTable()->getThemeData(array("cpid"=>$cpid)); 
		    				if($themeData && $themeData->name){
		    					$name = $themeData->name;
		    				}
		    			}else if(intval($cooltype)==2)
	    				{
	    					$themeData = $this->getWplistCpTable()->getAppData(array("cpid"=>$cpid));				
	    					if($themeData && $themeData->name){
	    						$name = $themeData->name;
	    					}
		    			}else if(intval($cooltype)==5){
		    				$themeData = $this->getFontTable()->getAppData(array("identity"=>$cpid));
		    				if($themeData && $themeData->name){
		    					$name = $themeData->name;
		    				}
		    			}else if(intval($cooltype)==6){
		    				$themeData = $this->getSceneTable()->getAppData(array("sceneCode"=>$cpid));
		    				if($themeData && $themeData->zhName){
		    					$name = $themeData->zhName;
		    				} 				
		    			}else if(intval($cooltype)==14){
		    				$themeData = $this->getAlarmTable()->getAppData(array("identity"=>$cpid));
		    				if($themeData && $themeData->note){
		    					$name = $themeData->note;
		    				} 				
		    			}else{
		    				$themeData = $this->getRingTable()->getAppData(array("identity"=>$cpid));
		    				if($themeData && $themeData->note){
		    					$name = $themeData->note;
		    				}    				
		    			}
		    			$mytemp['appname']  = $name;
		    			$mytemp['typename'] = $this->typelist[$cooltype];
		    			$infodata[]         = $mytemp;
		    		}
	    		}
	    	}
	    	return new ViewModel(array("infodata"=>$infodata,"infoarr"=>$infoarr,'mybanner'=>$bannerid,'mytypevalue'=>$typeid,'folderStr'=>Config::REPALCE_STR,"showurl"=>Config::SHOW_URL));
    	}catch (Exception $e){
			Logs::write('SiteController::bannerAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    } 
    
    public function deleteAction()
    {
    	try{
    		$uData    = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$request = $this->getRequest();
	    	if($request->isPost()){
	    		$postArr = $request->getPost();
	    		$id      = isset($postArr['id'])?$postArr['id']:"";
	    		$type    = isset($postArr['t'])?$postArr['t']:"";
	    		$cooltype = isset($postArr['cooltype'])?$postArr['cooltype']:'';
	    		
	    		$id       = sqlcheck::check($id);
	    		$type     = sqlcheck::check($type);
	    		$cooltype = sqlcheck::check($cooltype);
	    		
	    		if($type == "albums"){
	    			$widgetArr = array(
	    					'identity'    => $id,
	    					'cooltype'     => $cooltype,
	    			);
	    			$this->getAlbumsTable()->deleteAlbum($widgetArr);
	    			die('success');
	    		}
	    		if($type == "albumslist")
	    		{
	    			$widgetArr = array(
	    					'id'    => $id,
	    			);
	    			$this->getAlbumsresTable()->deleteAlbum($widgetArr);
	    			die('success');
	    		}
	    		if($type=="bannerlist")
	    		{
	    			$widgetArr = array(
	    					'id'    => $id,
	    			);
	    			$this->getBannerlistTable()->deleteAlbum($widgetArr);
	    			die('success');
	    		}else if($type=="wallpaper"){
	    			$istop    = isset($postArr['istop'])?$postArr['istop']:0;
	    			$name     = isset($postArr['name'])?$postArr['name']:"";
	    			$adid     = isset($postArr['adid'])?$postArr['adid']:"";
	    			$istop = sqlcheck::check($istop);
	    			$name  = sqlcheck::check($name);
	    			$adid  = sqlcheck::check($adid);
	    			$widgetArr = array(
	    					'name'    => $name,
	    					'adid'    => $adid,
	    			);   			
	
	    			$this->getWplistCpTable()->deleteInfo($widgetArr);
	   			
	    			die('success');
	    		}
	    		
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::deleteAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addbannerAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['bannername']) || empty($postArr['pic']) )
	    		{
	    			die('empty');
	    		}
	    		$id      = $postArr['id'];
	    		$pic     = $postArr['pic'];
	    		$id   = sqlcheck::check($id);
	    		$pic   = sqlcheck::check($pic);
	    		$pic     = urldecode($pic);
	    		$desFile = iconv('utf-8', 'gbk', Config::TEMP_FOLDER.$pic);
	    		$name    = $postArr['bannername'];
	    		$name   = sqlcheck::check($name);
	    		$type    = $postArr['typeid'];
	    		$type   = sqlcheck::check($type);   		
	    		$fileMd5 = md5_file($desFile);
	    		$fileSize= filesize($desFile);
	    		$filearr = explode("/", $pic);
	    		$filename= end($filearr);
	    		$identity= sprintf("%u", crc32(md5_file($desFile).date('YmdHis')));
	  
	    		if($id)
	    		{
	    			$this->getBannerTable()->updateData(array(
	    					'identity'    => $identity,
	    					'id'          => $id,
	    					'valid'       => 1,
	    					'istop'       => $postArr['istop'],
	    					'name'        => $name,
	    					'url'         => $pic,
	    					'filename'    => $filename,
	    					'size'        => $fileSize,
	    					'cooltype'    => $type,
	    					'md5'         => $fileMd5,
	    					'update_time' => date('Y-m-d H:i:s'),
	    					'update_user' => $uData->username,
	    			));
	    			die('success');
	    		}  
	    		$result = $this->getBannerTable()->getAppData(array('identity'=>$identity,'cooltype'=>$type));
	    		if($result && $result->id)
	    		{
	    			//不改变会触发exit?
	    			die('exist');
	    		}   
	    		$this->getBannerTable()->saveArr(array(
	    				'identity'    => $identity,
	    				'valid'       => 1,
	    				'istop'       => $postArr['istop'],
	    				'name'        => $name,
	    				'url'         => $pic,
	    				'cooltype'    => $type,
	    				'filename'    => $filename,
	    				'size'        => $fileSize,
	    				'md5'         => $fileMd5,
	    				'insert_time' => date('Y-m-d H:i:s'),
	    				'insert_user' => $uData->username,    				
	    		));
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addbannerAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    } 
    		
    public function queryresAction()
    {
    	try{
    		$uData    = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
//	    		$type    = $postArr['type'];
	    		$key     = $postArr['keyword'];
//	    		$type   = sqlcheck::check($type);
	    		$key    = sqlcheck::check($key);
	    		
    			//主题
    			$theme_data = $this->getThemeTable()->getThemeLikeData(array("name LIKE '%".$key."%'"));
    			$theme_array = $this->getArray($theme_data, 0);
    		    //壁纸
    			$wp_data = $this->getWplistCpTable()->getAppDataLike(array("name LIKE '%".$key."%'"));
    			$wp_array = $this->getArray($wp_data, 3);
    		    //字体
    			$font_data = $this->getFontTable()->getAppDataLike(array("name LIKE '%".$key."%'"));
    			$font_array = $this->getArray($font_data, 5);
    		    //锁屏，名称和主题重复，无法选择
//    			$scene_data = $this->getSceneTable()->getAppDataLike(array("zhName LIKE '%".$key."%'"));
//    			$scene_array = $this->getArray($scene_data, 6);
    		    //铃声
    			$ring_data = $this->getRingTable()->getAppDataLike(array("name LIKE '%".$key."%'"));
    			$ring_array = $this->getArray($ring_data, 4);
    		    //闹钟铃声
    			$alarm_data = $this->getAlarmTable()->getAppDataLike(array("name LIKE '%".$key."%'"));
    			$alarm_array = $this->getArray($alarm_data, 14);
	    			
	    		$infoarr = array_merge($theme_array, $wp_array, $font_array, $ring_array, $alarm_array);	    		
	    		die(json_encode($infoarr));    		
	    	}    	
    	}catch (Exception $e){
			Logs::write('SiteController::queryresAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function getArray($tdata, $type)
    {    
    	$infoarr = array();	
		foreach($tdata as $myobj)
		{
			if(intval($type) == 0)     { $cpid = $myobj->cpid; $name = $myobj->name;}
			else if(intval($type) == 3){ $cpid = $myobj->cpid; $name = $myobj->name;}
			else if(intval($type) == 5){ $cpid = $myobj->identity; $name = $myobj->name;}
			else if(intval($type) == 6){ $cpid = $myobj->sceneCode; $name = $myobj->zhName;}
			else if(intval($type) == 4){ $cpid = $myobj->identity; $name = $myobj->name;}   
			else if(intval($type) == 14){ $cpid = $myobj->identity; $name = $myobj->name;}   				
			$infoarr[] = array("cpid"=>$cpid,"name"=>$name, "type"=>$type);
		}    			 
	    return 	$infoarr;
    }	
    
    public function addbannerlistAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['res']) || empty($postArr['id']) )
	    		{
	    			die('empty');
	    		}
	    		$id      = $postArr['id'];
	    		$type    = $postArr['type'];	    		
	    		$cpidStr = $postArr['res'];
	    		$id        = sqlcheck::check($id);
	    		$type      = sqlcheck::check($type);
	    		$cpidStr   = sqlcheck::check($cpidStr);
	    		$cpidArr = explode(',', $cpidStr);
	    		
	    		foreach($cpidArr as $cpid){
	    			$result = $this->getBannerlistTable()->getAppData(array('bannerid'=>$id,'cpid'=>$cpid));
	    			if($result && $result->id)
	    			{
	    				die('exist');
	    			}
	    			$this->getBannerlistTable()->saveArr(array(
	    					'bannerid'    => $id,
	    					'cpid'        => $cpid,
	    					'cooltype'    => $type,
	    					'valid'       => 1,
	    					'insert_time' => date('Y-m-d H:i:s'),
	    					'insert_user' => $uData->username,
	    			));
	    		}  		
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addbannerlistAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }	
    
    //壁纸上传
    public function wpuploadAction()
    {	
    	try{
	    	$pageSize = 20;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$type  = isset($_GET['typeid'])?(int)$_GET['typeid']:100;    	   	 
	    	$page  = isset($_GET['page'])?(int)$_GET['page']:0;
	    	$countWidget = $this->getWplistCpTable()->getWPCountnum(array('type'=>$type));
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	//只显示前200页，后面显示无意义
	    	if($totalPage > 200) {
	    		$totalPage = 200;
	    	}
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0) $page = 1; 
	    	$tempData    = $this->getWplistCpTable()->getWPData($page,$pageSize, array('type'=>$type));
	    	$infoData    = array();
	    	foreach ($tempData as $mydata)
	    	{
	    		$infoData[] = (array)$mydata;
	    	}  	

			$cate = array('100'=>'全部', '1'=>'美女', '2'=>'风景', '3'=>'视觉', '4'=>'动漫', '5'=>'城市', 
						  '6'=>'情感', '7'=>'创意', '8'=>'动物', '9'=>'机械', '10'=>'游戏', 
						  '11'=>'物语', '12'=>'男人', '13'=>'艺术', '14'=>'运动', '15'=>'影视', 
						  '16'=>'其他' ,'17'=>'高清', '18'=>'明星', '19'=>'文字');
	    	return new ViewModel(array('page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'type'=>$type, 'cate'=>$cate, 
	    			'showurl'=>Config::SHOW_URL,'folderStr'=>Config::REPALCE_STR_WP,'infoData'=>$infoData));
    	}catch (Exception $e){
			Logs::write('SiteController::wpuploadAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addwpuploadAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				$id           = $postArr['id'];
				$wpsource     = $postArr['source'];
				$folder       = file_get_contents(Config::FILE_PATH);
				$wholeDir     = Config::WALLPAPER_FOLDER.$folder;
				$wholeHandle  = opendir($wholeDir);
				$error_pic    = array();
//				echo $wholeDir; exit;
				while(false !== ($zipfile = readdir($wholeHandle))){
					$data = array();
					if(in_array($zipfile,array('.','..'))) continue;
					$r            = strstr($zipfile,'_mid.');				
					if($r === false){$imgType = 'ori';}
					else 			 $imgType = 'mid';
					$pic          = $folder.'/'.$zipfile;
					$data         = $this->dealimage2($imgType,$pic);					
					if($data == false) {
						array_push($error_pic, $zipfile);
						continue;
					}
					$data['author']   = $wpsource;					
					
					if($id){
						$data['id']   = $id;
						$this->getWplistCpTable()->updateData($data);
					}else{					
						$tempResult = $this->getWplistCpTable()->getAppDataList(array(
								'cpid'  	=> $data['cpid'],
								'height'   	=> $data['height'],
								'width'   	=> $data['width'],
						));
						$data['insert_time'] = date('Y-m-d H:i:s');
						//choice字段无用
//						$data['choice']      = 1;
						if($tempResult == 0){
							$tmpsort = $this->getWplistCpTable()->getMaxSort();
							$data['asort'] = (int)$tmpsort+1;
							$data['insert_user'] = $uData->username;					
							$this->getWplistCpTable()->saveArr($data);					
						}else{
							$this->getWplistCpTable()->changeData($data);
						}					
					}			
				}				
				//删除temp目录		
				$dir = Config::WALLPAPER_FOLDER.Config::TEMP_WPUPLOAD.'temp';            	
            	$this->deldir($dir); 
            	$mydata = array('data'=>'success', 'error'=>$error_pic);           	
				die(json_encode($mydata));	
//				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addwpuploadAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function dealimage2($imgType,$temppic){
		$pic          = str_replace('temp/', "", $temppic);
		$picArr       = explode("/", $pic);
		$picfile      = end($picArr);
		$folder       = $picArr[4];
		
		$tarr         = explode("_", $picfile);
		if(count($tarr) < 2)
		{
			Logs::write('The file name _ is error,name is:'.$picfile, 'log');
			return false;
		}		
		$name         = $tarr[1];
		$type         = (int)$tarr[2];
//    	$mycate = array('1'=>'meinv', '2'=>'fengjing', '3'=>'shijue', '4'=>'dongman', '5'=>'chengshi', 
//					  '6'=>'qinggan', '7'=>'chuangyi', '8'=>'dongwu', '9'=>'jixie', '10'=>'youxi', 
//					  '11'=>'wuyu', '12'=>'nanren', '13'=>'yishu', '14'=>'yundong', '15'=>'yingshi', 
//					  '16'=>'qita' ,'17'=>'gaoqing', '18'=>'mingxing', '19'=>'wenzi');
//		$type = array_search($typename, $mycate);
		$ratiostr     = $tarr[3];
		$ratioarr     = explode(".", $ratiostr);
		$ratio        = $ratioarr[0];
		$ratio        = strtolower($ratio);
		//加入验证，分辨率验证和类型验证
		$valid_ratio  = array('960x800', '960x854', '1080x960', '1440x1280', '2160x1920', '2400x1920', '2880x2560');
		if(!in_array($ratio, $valid_ratio))
		{
			Logs::write('The ratio is not right', 'log');
			return false;
		}
		if($type < 1 || $type > 19) 
		{
			Logs::write('The cate is not right', 'log');
			return false;
		}
		$wAndh        = explode("x", $ratio);	
		if(count($wAndh) != 2){Logs::write('The file name x is error,name is:'.$picfile, 'log');return false;}
		list($width,$height) = $wAndh;			
		$cpid         = sprintf("%u", crc32( $tarr[1].'_'.$tarr[2]) );
				
		$extarr       = explode(".", $picfile);
		$ext          = end($extarr);
		$ring_md5     = date('YmdHis').'_'.$this->num_rand(8);
		if($imgType == 'ori'){
			$tempStr      = str_replace($picfile, $ring_md5.'.'.$ext, $pic); 		
    		$tofolder     = str_replace("/".$ring_md5.'.'.$ext, "", Config::WALLPAPER_FOLDER.$tempStr);
		}else{
			$tempStr      = str_replace($picfile, $ring_md5.'_mid'.'.'.$ext, $pic);
			$tofolder     = str_replace("/".$ring_md5.'_mid'.'.'.$ext, "", Config::WALLPAPER_FOLDER.$tempStr);
		}
		
    	if(!is_dir($tofolder)){ @mkdir($tofolder,0755,true);}
    	
    	$tempStrFile  = Config::WALLPAPER_FOLDER.$temppic;
		copy($tempStrFile, Config::WALLPAPER_FOLDER.$tempStr);		
		$filesize     = filesize(Config::WALLPAPER_FOLDER.$tempStr);
		$filemd5      = md5_file(Config::WALLPAPER_FOLDER.$tempStr);		
		
		//unlink($tempStrFile);
//		if($istop == 1){$addUrl = Config::WALLPAPER_URL;}
//		else {$addUrl = '';}
		$addUrl = '';
		if($imgType == 'ori'){
			
			$smallfile    = str_replace($ring_md5.'.'.$ext, $ring_md5.'_small'.'.'.$ext, Config::WALLPAPER_FOLDER.$tempStr);
			$this->img_resize(0.25, Config::WALLPAPER_FOLDER.$tempStr, $smallfile);
			$smallmd5     = md5_file($smallfile);
			
			$data = array(
					'type'      => $type,
					'name'      => $name,
					'folder'    => $folder,
					'cpid'      => $cpid,
					'url'       => $addUrl.$tempStr,
					'small_url' => $addUrl.str_replace(Config::WALLPAPER_FOLDER, '', $smallfile),
					'md5'       => $filemd5,
					'small_md5' => $smallmd5,
					'size'      => $filesize,
					'height'    => $height,
					'width'     => $width,
			);
		}else{
			$data = array(
					'cpid'      => $cpid,
					'height'    => $height,
					'width'     => $width,
					'mid_url'   => $addUrl.$tempStr,
					'mid_md5'	=> $filemd5,
			);
		}
		
		return $data;
	}
	
	//上传壁纸排序
    public function wpupsortAction()
    {   
    	try{
	    	$pageSize = 150;
	    	$uData    = $this->checkLogin('wallpaper');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}	
	    	$mycate  = isset($_GET['mycate'])?$_GET['mycate']:'100';  
	    	$myratio = isset($_GET['myratio'])?$_GET['myratio']:'1920x1080';
	    	$online  = isset($_GET['online'])?$_GET['online']:'';
	    	$rations = explode("x", $myratio); 
	    	$height = (int)($rations[0]);
	    	$width  = 2*((int)($rations[1]));
	    	$page        = isset($_GET['page'])?(int)$_GET['page']:0;	    	
	    	$page           = sqlcheck::check($page); 
	    	$countWidget = $this->getWplistCpTable()->getCountnumUp($mycate, $height, $width);  	
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0) $page = 1;
	    	if($online==1){
	    		$tempData    = $this->getWplistCpTable()->getSortData($page,$pageSize, $mycate, $height, $width);
	    	}else{
	    		$tempData    = $this->getWplistCpTable()->getwholeData($page,$pageSize, $mycate, $height, $width);
	    	}
	    	$infoData    = array();
	    	foreach($tempData as $mydata)
	    	{  
	    		$infoData[] = (array)$mydata;    		
	    	}	
	    	$cate = array('100'=>'全部', '1'=>'美女', '2'=>'风景', '3'=>'视觉', '4'=>'动漫', '5'=>'城市', 
						  '6'=>'情感', '7'=>'创意', '8'=>'动物', '9'=>'机械', '10'=>'游戏', 
						  '11'=>'物语', '12'=>'男人', '13'=>'艺术', '14'=>'运动', '15'=>'影视', 
						  '16'=>'其他' ,'17'=>'高清', '18'=>'明星', '19'=>'文字', '0'=>'banner');
						  
			$valid_ratio  = array('800x480', '854x480', '960x540', '1280x720', '1920x1080', '1920x1200', '2560x1440');
	    	return new ViewModel(array('uData'=>$uData,'page'=>$page,'countWidget'=>$countWidget, 'cate'=>$cate, 'ratio'=>$valid_ratio,
	    			'c'=>$mycate, 'r'=>$myratio, 'totalPage'=>$totalPage,'infoData'=>$infoData, 'folderStr'=>Config::SHOW_URL,));
    	}catch (Exception $e){
			Logs::write('SiteController::wpupsortAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function wpuptypeAction()
    {
    	try{
	    	$uData = $this->checkLogin('wallpaper');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$cpid  = isset($_GET['cpid'])?$_GET['cpid']:'';  
	    	$type  = isset($_GET['type'])?$_GET['type']:'';
	    	$cate = array('1'=>'美女', '2'=>'风景', '3'=>'视觉', '4'=>'动漫', '5'=>'城市', 
						  '6'=>'情感', '7'=>'创意', '8'=>'动物', '9'=>'机械', '10'=>'游戏', 
						  '11'=>'物语', '12'=>'男人', '13'=>'艺术', '14'=>'运动', '15'=>'影视', 
						  '16'=>'其他' ,'17'=>'高清', '18'=>'明星', '19'=>'文字', '0'=>'banner');
			$mycate = $cate[$type];
	    	
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();	    		
	    		$cpid       = $postArr['cpid'];
	    		$newtype        = $postArr['newtype']; 
	    		$mytable       = sqlcheck::check($cpid);
	    		$id_wallpaper  = sqlcheck::check($newtype);
	    		$this->getWplistCpTable()->updateType(array('cpid'=>$cpid, 'type'=>$newtype)); 

	    		
	    	}
	    	return new ViewModel(array('uData'=>$uData,'cpid'=>$cpid, 'mycate'=>$mycate, 'cate'=>$cate));
    	}catch (Exception $e){
			Logs::write('SiteController::wpuptypeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function wpupeditAction()
    {
    	try{
	    	$uData = $this->checkLogin('wallpaper');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}	    	
	    	
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();	    		
	    		$cpid       = $postArr['cpid'];
	    		$newtype        = $postArr['newcate']; 
	    		$mytable       = sqlcheck::check($cpid);
	    		$id_wallpaper  = sqlcheck::check($newtype);
	    		$this->getWplistCpTable()->updateType(array('cpid'=>$cpid, 'type'=>$newtype)); 
				die('success');	    		
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::wpupeditAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    //壁纸banner管理
    public function wallpaperAction()
    {
    	try{
	    	$pageSize = 10;
	    	$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	} 
	    	$page=isset($_GET['page'])?$_GET['page']:1;
	    	$infoarr  = array();
	    	$countwidget=$this->getBannerTable()->getCount(array('cooltype'=>2));
	    	$totalPage=ceil($countwidget/$pageSize);
	    	if($page>$totalPage){ $page=$totalPage;}
	    	if($page==0){ $page=1;}
	    	$tempData = $this->getBannerTable()->getAppDataPage($page,$pageSize,array('cooltype'=>2));
	    	foreach($tempData as $rowobj)
	    	{  
	    		$tarr          = (array)$rowobj;
	    		$infoarr[]     = $tarr;
	    	}
	    	return new ViewModel(array("folderStr"=>Config::REPALCE_STR,'page'=>$page,'pagesize'=>$pageSize,'totalPage'=>$totalPage,'mycount'=>$countwidget,'showurl'=> Config::SHOW_URL,"infoarr"=>$infoarr));
    	}catch (Exception $e){
			Logs::write('SiteController::wallpaperAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }     
    
    public function wallpaperlistAction()
    {  
    	try{
	    	$pageSize = 10;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$name  = isset($_GET['name'])?$_GET['name']:'';    	
	    	$adid  = isset($_GET['adid'])?$_GET['adid']:'';
	    	$istop = isset($_GET['istop'])?$_GET['istop']:0;    	 
	    	$page  = isset($_GET['page'])?(int)$_GET['page']:0;
//	    	$name   = sqlcheck::check($name);
//	    	$adid   = sqlcheck::check($adid);
//	    	$istop  = sqlcheck::check($istop);
//	    	$page   = sqlcheck::check($page);
	    	
			$istop = intval($istop);
	    	$countWidget = $this->getWplistCpTable()->getCountnum($adid);
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0) $page = 1;
	    	$tempData    = $this->getWplistCpTable()->getData($page,$pageSize,$adid);
	    	$infoData    = array();
	    	foreach ($tempData as $mydata)
	    	{
	    		$infoData[] = (array)$mydata;
	    	}
	    	return new ViewModel(array('page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'adid'=>$adid,
	    			'showurl'=>Config::SHOW_URL,'folderStr'=>Config::REPALCE_STR,'infoData'=>$infoData,'name'=>$name,'istop'=>$istop));
    	}catch (Exception $e){
			Logs::write('SiteController::wallpaperlistAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }    
    
    public function addwallpaperlistAction()
	{  
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if(	!isset($postArr['name']) )
				{
					die('empty');
				}
				$id           = $postArr['id'];          //id用于判断是编辑还是添加
				$istop        = $postArr['istop'];
				$folder       = file_get_contents(Config::FILE_PATH);
				$wholeDir     = Config::TEMP_FOLDER.$folder;
				$wholeHandle  = opendir($wholeDir);
				while(false !== ($zipfile = readdir($wholeHandle))){
					$data = array();
					if(in_array($zipfile,array('.','..'))) continue;
					$r            = strstr($zipfile,'_mid.');				
					if($r === false){$imgType = 'ori';}
					else $imgType             = 'mid';
					$pic          = $folder.'/'.$zipfile;
					
					$data         = $this->dealimage($imgType,$pic,$istop);
					if($data == false) continue;
					//id为空表示添加，不为空为编辑
					if($id){
						$data['id']   = $id;
						$this->getWplistCpTable()->updateData($data);
					}else{					
						$tempResult = $this->getWplistCpTable()->getAppDataList(array(
								'cpid'  	=> $data['cpid'],
								'height'   	=> $data['height'],
								'width'   	=> $data['width'],
						));
						$data['insert_time'] = date('Y-m-d H:i:s');
						if($tempResult == 0){
							$data['type']        = 0;
							$data['insert_user'] = $uData->username;
							$data['name']        = $postArr['name'];
							$data['author']      = $uData->username;						
							$this->getWplistCpTable()->saveArr($data);					
						}else{
							$this->getWplistCpTable()->changeData($data);
						}					
					}
					$data['adid'] = $postArr['adid'];
					$this->putCpList($data);			
				}			
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addwallpaperlistAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
    public function img_resize( $resize_percent, $src_file, $des_file){
		try{
			$arrImgInfo  = getimagesize($src_file);
			list($src_width, $src_height) = $arrImgInfo;
			
			$tempArr     = explode("/",$arrImgInfo['mime']);
			$suff        = end($tempArr);

			$new_width = $src_width * $resize_percent;
			$new_height = $src_height * $resize_percent;
				
			$new_image = imagecreatetruecolor($new_width, $new_height);
			
			switch ($suff){
				case "jpg":
				case "jpeg":
					{
						$src_image = imagecreatefromjpeg($src_file);
						imagecopyresampled($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
// 						imagecopyresized($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
						imagejpeg($new_image, $des_file, 75);
					}break;
				case "png":
					{
						$src_image = imagecreatefrompng($src_file);
						imagecopyresampled($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
// 						imagecopyresized($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
						imagepng($new_image,$des_file);
					}break;
				case "gif":
					{
						$src_image = imagecreatefromgif($src_file);
						imagecopyresampled($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
// 						imagecopyresized($new_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
						imagegif($new_image,$src_file);
					}break;
				default:
					{
						return false;
					}break;
			}
		}catch (Exception $e){
			Log::write("ImgResize::resize() exception: ".$e->getMessage(), "log");
			return false;
		}
		return true;
	}	
	
	public function dealimage($imgType,$temppic,$istop){
		$pic          = str_replace('temp/', "", $temppic);
		$picArr       = explode("/", $pic);
		$picfile      = end($picArr);
		$folder       = $picArr[4];
		
		$tarr         = explode("_", $picfile);
		if(count($tarr) < 2)
		{
			Logs::write('The file name _ is error,name is:'.$picfile, 'log');
			return false;
		}
		if(count($tarr) < 4)
		{
			$ratiostr     = $tarr[1];
			$ratioarr     = explode(".", $ratiostr);
			$ratio        = $ratioarr[0];
			$wAndh        = explode("x", $ratio);	
			if(count($wAndh) != 2){Logs::write('The file name x is error,name is:'.$picfile, 'log');return false;}
			list($width,$height) = $wAndh;			
			$cpid         = sprintf("%u", crc32( $tarr[0]) );
		} 
		if(count($tarr) > 3)
		{
			$ratiostr     = $tarr[3];
			$ratioarr     = explode(".", $ratiostr);
			$ratio        = $ratioarr[0];
			$wAndh        = explode("x", $ratio);	
			if(count($wAndh) != 2){Logs::write('The file name x is error,name is:'.$picfile, 'log');return false;}
			list($width,$height) = $wAndh;			
			$cpid         = sprintf("%u", crc32( $tarr[1].'_'.$tarr[2]) );
		}		
		$extarr       = explode(".", $picfile);
		$ext          = end($extarr);
		$ring_md5     = date('YmdHis').'_'.$this->num_rand(8);
		if($imgType == 'ori'){
			$tempStr      = str_replace($picfile, $ring_md5.'.'.$ext, $pic); 		
    		$tofolder     = str_replace("/".$ring_md5.'.'.$ext, "", Config::TEMP_FOLDER.$tempStr);
		}else{
			$tempStr      = str_replace($picfile, $ring_md5.'_mid'.'.'.$ext, $pic);
			$tofolder     = str_replace("/".$ring_md5.'_mid'.'.'.$ext, "", Config::TEMP_FOLDER.$tempStr);
		}
		
    	if(!is_dir($tofolder)){ @mkdir($tofolder,0755,true);}
    	
    	$tempStrFile  = Config::TEMP_FOLDER.$temppic;
		copy($tempStrFile, Config::TEMP_FOLDER.$tempStr);		
		$filesize     = filesize(Config::TEMP_FOLDER.$tempStr);
		$filemd5      = md5_file(Config::TEMP_FOLDER.$tempStr);		
		
		//unlink($tempStrFile);
//		if($istop == 1){$addUrl = Config::WALLPAPER_URL;}
//		else {$addUrl = '';}
		$addUrl = '';
		if($imgType == 'ori'){
			
			$smallfile    = str_replace($ring_md5.'.'.$ext, $ring_md5.'_small'.'.'.$ext, Config::TEMP_FOLDER.$tempStr);
			$this->img_resize(0.25, Config::TEMP_FOLDER.$tempStr, $smallfile);
			$smallmd5     = md5_file($smallfile);
			
			$data = array(
					'folder'    => $picfile,
					'cpid'      => $cpid,
					'url'       => $addUrl.$tempStr,
					'small_url' => $addUrl.str_replace(Config::TEMP_FOLDER, '', $smallfile),
					'md5'       => $filemd5,
					'small_md5' => $smallmd5,
					'size'      => $filesize,
					'height'    => $height,
					'width'     => $width,
			);
		}else{
			$data = array(
					'cpid'      => $cpid,
					'height'    => $height,
					'width'     => $width,
					'mid_url'   => $addUrl.$tempStr,
					'mid_md5'	=> $filemd5,
			);
		}
		
		return $data;
	}
	
	public function putCpList($data)
	{
		$bannerID   = $data['adid'];
		$cpid       = $data['cpid'];
		$result = $this->getBannerlistTable()->getAppData(array('bannerid'=>$bannerID,'cpid'=>$cpid));
		if($result && $result->id)
		{  
			$this->getBannerlistTable()->updateData(array(
				'id'    	  => $result->id,
				'update_time' => date('Y-m-d H:i:s'),
			));
		}else{
			$this->getBannerlistTable()->saveArr(array(
					'bannerid'    => $bannerID,
					'cpid'        => $cpid,
					'cooltype'    => 2,
					'valid'       => 1,
					'insert_time' => date('Y-m-d H:i:s'),
					'insert_user' => $data['insert_user'],
			));
		}
	}
    
     public function addwallpaperAction()
    {  
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['name']) || empty($postArr['subjectpic']))
	    		{
	    			die('empty');
	    		}
	    		$id      = $postArr['id'];
	    		$istop   = $postArr['istop'];
	    		$appname = $postArr['name'];
	    		$istop   = $postArr['istop'];
	    		$id       = sqlcheck::check($id);
	    		$istop    = sqlcheck::check($istop);
	    		$appname  = sqlcheck::check($appname);
	    		$istop    = sqlcheck::check($istop);
	    		$identity= sprintf("%u", crc32(date('YmdHis').$appname));
	    		$pic          = $postArr['subjectpic'];
	    		$pic          = urldecode($pic);
	    		
	    		$desFile = iconv('utf-8', 'gbk', Config::TEMP_FOLDER.$pic);
	    		$fileMd5 = md5_file($desFile);
	    		$fileSize= filesize($desFile);
	    		$filearr = explode("/", $pic);
	    		$filename= end($filearr);
	    		if($id)
	    		{
					$this->getBannerTable()->updateData(array(
						'id'          => $id,				
	    				'valid'       => 1,
						'name'        => $appname,
	    				'istop'       => $istop,
						'cooltype'    => 2,
	    				'url'         => $pic,
	    				'filename'    => $filename,
	    				'size'        => $fileSize,
	    				'md5'         => $fileMd5,
	    				'update_time' => date('Y-m-d H:i:s'),
	    				'update_user' => $uData->username,
	    				));
	    				die('success');
	    		}
	    		$result = $this->getBannerTable()->getAppData(array('identity'=>$identity,'cooltype'=>2));
	    		if($result && $result->id)
	    		{
	    			die('exist');
	    		}
	    		$this->getBannerTable()->saveArr(array(
	    			'identity'    => $identity,   					
	    			'valid'       => 1,
					'name'        => $appname,
	    			'istop'       => $istop,
					'cooltype'    => 2,
	    			'url'         => $pic,
	    			'filename'    => $filename,
	    			'size'        => $fileSize,
	    			'md5'         => $fileMd5,
	    			'insert_time' => date('Y-m-d H:i:s'),
	    			'insert_user' => $uData->username,
	    		));
	    		die('success');  		
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addwallpaperAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    } 		
    
    public function pushalbumsAction()
    {
    	try{
	    	$uData    = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$identity = $_GET['cpid'];
	    	$isclean  = (bool)$_GET['clean'];
	    	$title    = $_GET['title'];
	    	$content  = $_GET['content'];
			$islarge  = $_GET['islarge'];
			$identity    = sqlcheck::check($identity);
			$isclean     = sqlcheck::check($isclean);
			$title       = sqlcheck::check($title);
			$content     = sqlcheck::check($content);
			$islarge     = sqlcheck::check($islarge);
			$myinfo   = array("isdirect"=>true,"entity"=>array("pushtype"=>8,"isorder"=>false,"title"=>$title,
							"content"=>$content,"isgoing"=>$isclean,"islarge"=>false,
								"url"=>Config::ALBUMS_URL,"id"=>$identity,"isorder"=>false));
			$myinfo   = json_encode($myinfo);
	    	return new ViewModel(array('infodata'=>$myinfo,'id'=>$identity));
    	}catch (Exception $e){
			Logs::write('SiteController::pushalbumsAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addalbumslistAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['res']) || empty($postArr['id']) )
	    		{
	    			die('empty');
	    		}
	    		$id      = $postArr['id'];
//	    		$type    = $postArr['type'];	    		
	    		$cpidStr = $postArr['res'];
	    		$id       = sqlcheck::check($id);
//	    		$type     = sqlcheck::check($type);
	    		$cpidStr  = sqlcheck::check($cpidStr);
	    		$cpidArr = explode(',', $cpidStr);   		
	    		foreach($cpidArr as $new_cpid){
	    			$new_cpid_arr = explode('_', $new_cpid); 
	    			$cpid = $new_cpid_arr[0];
	    			$type = $new_cpid_arr[1];
	    			$result = $this->getAlbumsresTable()->getAppData(array('albumid'=>$id,'cpid'=>$cpid));
	    			if($result && $result->id)
	    			{
	    				die('exist');
	    			}
	    			$this->getAlbumsresTable()->saveArr(array(
	    					'albumid'    => $id,
	    					'cpid'        => $cpid,
	    					'cooltype'    => $type,
	    					'valid'       => 1,
	    					'insert_time' => date('Y-m-d H:i:s'),
	    					'insert_user' => $uData->username,
	    			));
	    		}
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addalbumslistAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    //壁纸广告banner
    public function adwallpaperAction()
    {
    	try{
	    	$uData = $this->checkLogin('wallpaper');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}   	
	    	$infoarr  = array();
	    	   	
	    	$tempData = $this->getWallpaperTable()->getAppDataAll();
	    	foreach($tempData as $rowobj)
	    	{
	    		$tarr          = (array)$rowobj;
	    		$infoarr[]     = $tarr;
	    	}
	    	
	    	return new ViewModel(array("infoarr"=>$infoarr));
    	}catch (Exception $e){
			Logs::write('SiteController::adwallpaperAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    } 
    
    //精品壁纸管理
    public function qualitywpAction()
    {
    	try{
	    	$pageSize = 150;
	    	$uData    = $this->checkLogin('wallpaper');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}	
	    	$mycate  = isset($_GET['mycate'])?$_GET['mycate']:'commend';  
	    	$myratio = isset($_GET['myratio'])?$_GET['myratio']:'id_640_480';
	    	$tempcate = $this->getCateInfoTable()->getCateInfo(); 
	    	$cate    = array();
	    	array_push($cate, array('commend', '精品'));
	    	foreach($tempcate as $mydata)
	    	{
	    		array_push($cate, array($mydata['cate_table_name'], $mydata['cate_name']));	    		
	    	}	
	    	$ratio = array('id_1600_1280'=>'1600x1280', 'id_1536_1280'=>'1536x1280', 'id_1440_1280'=>'1440x1280', 'id_1280_960'=>'1280x960',
	    				   'id_1200_1024'=>'1200x1024', 'id_1200_800'=>'1200x800',   'id_1080_960'=>'1080x960',   'id_960_854'=>'960x854',
	    				   'id_960_800'=>'960x800',     'id_800_600'=>'800x600',     'id_640_480'=>'640x480'); 	
	    	$page        = isset($_GET['page'])?(int)$_GET['page']:0;	    	
	    	$page           = sqlcheck::check($page);
	    	$countWidget = $this->getCommendTable($mycate)->getCountnum();	
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0) $page = 1;
	    	$tempData    = $this->getCommendTable($mycate)->getData($page,$pageSize);	    	
	    	$infoData    = array();
	    	foreach($tempData as $mydata)
	    	{
	    		$infoData[] = (array)$mydata;    		
	    	}	    	
	    	return new ViewModel(array('uData'=>$uData,'page'=>$page,'countWidget'=>$countWidget, 'cate'=>$cate, 'ratio'=>$ratio,
	    			'c'=>$mycate, 'r'=>$myratio, 
	    			'totalPage'=>$totalPage,'infoData'=>$infoData));
    	}catch (Exception $e){
			Logs::write('SiteController::qualitywpAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function savepicAction()
    {
    	try{
	    	$uData = $this->checkLogin('wallpaper');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();	    		
	    		$mytable       = $postArr['table'];
	    		$id_wallpaper  = $postArr['id']; 
	    		$mytable       = sqlcheck::check($mytable);
	    		$id_wallpaper  = sqlcheck::check($id_wallpaper);
//	    		$mytable = 'commend';
//	    		$id_wallpaper = '54d038f669401b2036fe5f83';
	    		$result = $this->getCommendTable($mytable)->getAppData(array('id_wallpaper'=>$id_wallpaper));
	    		$details = array();
	    		foreach($result as $unit)
	    		{
	    			$details[] = (array)$unit;
	    		}
	    		$myresult = $this->getLauncherTable()->getAppData(array('id_wallpaper'=>$details[0]['id_wallpaper']));
	    		$mydata = array();
	    		foreach($myresult as $unit)
	    		{
	    			$mydata[] = (array)$unit;
	    		}
	    		if($mydata) {
	    			die('exist');
	    		} else {	    			
	    			$insertarray = array('id_wallpaper'=>$details[0]['id_wallpaper'], 'id_1600_1280'=>$details[0]['id_1600_1280'], 
										 'id_1536_1280'=>$details[0]['id_1536_1280'], 'id_1440_1280'=>$details[0]['id_1440_1280'], 
										 'id_1280_960'=>$details[0]['id_1280_960'],   'id_1200_1024'=>$details[0]['id_1200_1024'], 
										 'id_1200_800'=>$details[0]['id_1200_800'],   'id_1080_960'=>$details[0]['id_1080_960'],   
										 'id_960_854'=>$details[0]['id_960_854'],     'id_960_800'=>$details[0]['id_960_800'],    
										 'id_800_600'=>$details[0]['id_800_600'],     'id_640_480'=>$details[0]['id_640_480'],
										 'id_480_400'=>$details[0]['id_480_400'],     'id_480_384'=>$details[0]['id_480_384'], 
										 'id_480_320'=>$details[0]['id_480_320'],     'id_300_225'=>$details[0]['id_300_225'], 
										 'id_160_120'=>$details[0]['id_160_120'],     'id_origin'=>$details[0]['id_origin'], 
										 'origin_h'=>$details[0]['origin_h'],         'author'=>$details[0]['author'], 
										 'ad_rank'=>$details[0]['ad_rank'],           'origin_w'=>$details[0]['origin_w'],
										 'cp_rank'=>$details[0]['cp_rank'],           'inserttime'=>$details[0]['inserttime']
										 );
	    			$this->getLauncherTable()->saveArr($insertarray);
	    		}
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::savepicAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    //新版banner列表
    public function albumsAction()
    {  
    	try{
	    	$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$pageSize = 8;
	    	$typeid = isset($_GET['typeid'])?$_GET['typeid']:'0';
	    	$album  = isset($_GET['album'])?$_GET['album']:'0';
	    	$page   = isset($_GET['page'])?$_GET['page']:0;
	    	$typeid     = sqlcheck::check($typeid);
	    	$page       = sqlcheck::check($page);
	    	$countWidget = $this->getAlbumsTable()->getCountnum(array('cooltype'=>$typeid, 'album'=>$album));
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage)$page = $totalPage;
	    	if($page == 0)$page = 1;
	    	$tempData = $this->getAlbumsTable()->getAppDataList($typeid,$album,$page,$pageSize);
	    	$infoarr  = array();
	    	foreach($tempData as $rowobj)
	    	{
	    		$tarr          = (array)$rowobj;
	    		$infoarr[]     = $tarr;
	    	}
	    	return new ViewModel(array("infoarr"=>$infoarr,'mytypevalue'=>$typeid, 'myalbum'=>$album, 'folderStr'=>Config::REPALCE_STR,
	    								"showurl"=>Config::SHOW_URL,'page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage));
    	}catch (Exception $e){
			Logs::write('SiteController::albumsAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addalbumsAction()
    {  
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['name']) || empty($postArr['subjectpic']))
	    		{
	    			die('empty');
	    		}
	    		$id      = $postArr['id'];
	    		$istop   = $postArr['istop'];
	    		$album   = $postArr['album'];
	    		$appname = $postArr['name'];
	    		$istop   = $postArr['istop'];
	    		$cooltype= $postArr['cooltype'];	    		
	    		$pic     = $postArr['subjectpic'];
	    		$H5      = $postArr['H5'];
	    		$H5Url   = $postArr['H5Url'];
	    		$id         = sqlcheck::check($id);
	    		$istop      = sqlcheck::check($istop);
	    		$appname    = sqlcheck::check($appname);
	    		$istop      = sqlcheck::check($istop);
	    		$cooltype   = sqlcheck::check($cooltype);
//	    		$pic        = sqlcheck::check($pic);
//	    		$H5Url      = sqlcheck::check($H5Url);
	    		
	    		$pic          = urldecode($pic); 
                        $desFile = iconv('utf-8', 'gbk', Config::TEMP_FOLDER.$pic);
	    		$identity= sprintf("%u", crc32(file_get_contents($desFile)));
//                        if($identity == 0) {
//	    			die('error');
//	    		}
	    		$fileMd5 = md5_file($desFile);
	    		$fileSize= filesize($desFile);
	    		
	    		$filearr = explode("/", $pic);
	    		$filename= end($filearr);
	  
	    		if($id)
	    		{
	    			$this->getAlbumsTable()->updateData(array(
	    					'id'          => $id,
	    					'valid'       => 1,
	    					'name'        => $appname,
	    					'istop'       => $istop,
	    					'album'       => $album,
	    					'cooltype'    => $cooltype,
	    					'url'         => $pic,
	    					'filename'    => $filename,
	    					'size'        => $fileSize,
	    					'md5'         => $fileMd5,
	    					'update_time' => date('Y-m-d H:i:s'),
	    					'update_user' => $uData->username,
	    			));
	    			if($H5 == '1')
	    			{
	    				$this->getAlbumsTable()->updateData(array(
	    					'id'          => $id,
	    					'H5'          => $H5,
	    					'H5Url'       => $H5Url,
	    				));
	    			}
	    			die('success');
	    		}
	    		$result = $this->getAlbumsTable()->getAppData(array('identity'=>$identity,'cooltype'=>$cooltype, 'album'=>$album));
	    		if($result && $result->id)
	    		{
	    			die('exist');
	    		}
	    		$this->getAlbumsTable()->saveArr(array(
	    				'identity'    => $identity,
	    				'valid'       => 1,
	    				'name'        => $appname,
	    				'istop'       => $istop,
	    				'album'       => $album,
	    				'cooltype'    => $cooltype,
	    				'url'         => $pic,
	    				'filename'    => $filename,
	    				'size'        => $fileSize,
	    				'md5'         => $fileMd5,
	    				'insert_time' => date('Y-m-d H:i:s'),
	    				'update_time' => date('Y-m-d H:i:s'),
	    				'insert_user' => $uData->username,
	    		));
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addalbumsAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function getalbumslistAction()
    {
    	try{
    		$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if($request->isPost()){
	    		$postArr 	= $request->getPost();
	    		$albumid 	= $postArr['albumid'];
//	    		$cooltype	= $postArr['cooltype'];
	    		$albumid        = sqlcheck::check($albumid);
//	    		$cooltype       = sqlcheck::check($cooltype);
//	    		$tdata    = $this->getAlbumsresTable()->getAppDataList(array('albumid'=>$albumid,'cooltype'=>$cooltype));
	    		$tdata    = $this->getAlbumsresTable()->getAppDataList(array('albumid'=>$albumid));
	    		$infodata = array();
	    		foreach($tdata as $temp)
	    		{
	    			$mytemp   = (array)$temp;
	    			$cooltype = $temp->cooltype;
	    			$cpid     = $temp->cpid;
	    			$name     = "";
	    			if(intval($cooltype)==0)//以下是为了获得名称
	    			{
	    				$themeData = $this->getThemeTable()->getThemeData(array("cpid"=>$cpid));
	    				if($themeData && $themeData->name){
	    					$name = $themeData->name;
	    				}
	    			}else if(intval($cooltype)==3)
	    			{
	    				$themeData = $this->getWplistCpTable()->getAppData(array("cpid"=>$cpid));
	    				if($themeData && $themeData->name){
	    					$name = $themeData->name;
	    				}
	    			}else if(intval($cooltype)==5){
	    				$themeData = $this->getFontTable()->getAppData(array("identity"=>$cpid));
	    				if($themeData && $themeData->name){
	    					$name = $themeData->name;
	    				}
	    			}else if(intval($cooltype)==6){
	    				$themeData = $this->getSceneTable()->getAppData(array("sceneCode"=>$cpid));
	    				if($themeData && $themeData->zhName){
	    					$name = $themeData->zhName;
	    				}
	    			}else if(intval($cooltype)==14){
	    				$themeData = $this->getAlarmTable()->getAppData(array("identity"=>$cpid));
	    				if($themeData && $themeData->note){
	    					$name = $themeData->note;
	    				}
	    			}else{
	    				$themeData = $this->getRingTable()->getAppData(array("identity"=>$cpid));
	    				if($themeData && $themeData->note){
	    					$name = $themeData->note;
	    				}
	    			}
	    			$mytemp['appname']  = $name;
	    			$mytemp['typename'] = $this->typelist[$cooltype];
	    			$infodata[]         = $mytemp;
	    		}
	    		die(json_encode($infodata));
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::getalbumslistAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    //酷秀网站banner
    public function addcoolshowbannerAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if( empty($postArr['seltheme']) || empty($postArr['url']) )
				{
					die('empty');
				}
				$id       = $postArr['id'];
				$seltheme = $postArr['seltheme'];
				$pic      = $postArr['url'];
				$id         = sqlcheck::check($id);
				$seltheme   = sqlcheck::check($seltheme);
				$pic        = sqlcheck::check($pic);
		
				$selarr   = explode("~", $seltheme);
				$fileMd5  = md5_file(Config::TEMP_FOLDER.$pic);
				$fileSize = filesize(Config::TEMP_FOLDER.$pic);
				$identity = $selarr[1];
				$cpid     = $selarr[0];
				$name     = $selarr[2];
				if($id)
				{
					$this->getWebbannerTable()->updateData(array('id'=>$id,'identity'=>$identity,'cpid'=>$cpid,'name'=>$name,'url'=>$pic,'size'=>$fileSize,'md5'=>$fileMd5));
					die('success');
				}
		
				$result = $this->getWebbannerTable()->getAppData(array('identity'=>$identity,'cpid'=>$cpid,'name'=>$name,'md5'=>$fileMd5));
				if($result && $result->id)
				{
					die('exist');
				}
		
				$this->getWebbannerTable()->saveArr(array('identity'=>$identity,'cpid'=>$cpid,'name'=>$name,'url'=>$pic,'size'=>$fileSize,'md5'=>$fileMd5,'insert_user'=>$uData->username));
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addcoolshowbannerAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function coolshowbannerAction()
	{
		try{
			$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
			$pageSize = 10;			
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$page   = sqlcheck::check($page);
			$countWidget = $this->getWebbannerTable()->getCountnum();
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			$tempData = $this->getWebbannerTable()->getData($page,$pageSize);
			$infoData = array();
			foreach($tempData as $mydata)
			{
				$infoData[] = (array)$mydata;
			}
			return new ViewModel(array('uData'=>$uData,'showurl'=>Config::SHOW_URL,'page'=>$page,'folderStr'=>Config::REPALCE_STR,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData));
		}catch (Exception $e){
			Logs::write('SiteController::coolshowbannerAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function getqueryAction()
	{
		try{
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				$key     = $postArr['keyword'];
				$key     = sqlcheck::check($key);
				$tdata   = $this->getThemeTable()->getThemeLikeData(array("name LIKE '%".$key."%'"));
				$total   = $tdata->count();
				if($total==0)
				{
					die(json_encode(array('t'=>$total)));
				}
				$infoarr = array();
				if($total>0)
				{
					foreach($tdata as $myobj)
					{
						$cpid = $myobj->cpid; $name = $myobj->name;$identity = isset($myobj->identity)?$myobj->identity:'';
						$infoarr[] = array("cpid"=>$cpid,"name"=>$name,"identity"=>$identity);
					}
				}
				die(json_encode( array("t"=>$total,"datalist"=>$infoarr) ));
			}
		}catch (Exception $e){
			Logs::write('SiteController::getqueryAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	} 	
	
	public function getbannerAction()
	{
		try{
			//此函数可能无用
			$uData = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
			$tdata = $this->getWebbannerTable()->getAppDataAll(array('valid'=>1));
			$infoData = array();
	                $width = $_GET['width'];
	                $height = $_GET['height'];
	                $kernel = $_GET['kernel'];
	                $width    = sqlcheck::check($width);
	                $height   = sqlcheck::check($height);
	                $kernel   = sqlcheck::check($kernel);
			foreach($tdata as $mydata)
			{
				$newdata        = (array)$mydata;
				$newdata['url'] = Config::SHOW_URL.$newdata['url'];
	                        $cpid           = $newdata['cpid'];
	                        $gData          = $this->getThemeInfoTable()->getThemeData(array('cpid'=>$cpid,'valid'=>1,'width'=>$width,'height'=>$height,'kernel'=>$kernel));
	                        if($gData && $gData->identity){
	                               //echo $gData->identity;exit;
	                               $newdata['identity'] = $gData->identity;
	                               $infoData[]     = $newdata;
	                        }
				//$infoData[]     = $newdata;
	
			}
			die($_GET['jsoncallback']."(".json_encode($infoData).")");
		}catch (Exception $e){
			Logs::write('SiteController::getbannerAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	//设计师主题管理
	public function personthemeAction()
	{
		try{
			$pageSize = 10;
			$uData    = $this->checkLogin('management');
			if(!$uData)
			{
				die('没有权限');
			}
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$typeid      = isset($_GET['typeid'])?$_GET['typeid']:0;
			$page     = sqlcheck::check($page);
			$typeid   = sqlcheck::check($typeid);
			$countWidget = $this->getPersonthemeTable()->getCountnum($typeid);
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			$tempData = $this->getPersonthemeTable()->getData($page,$pageSize,$typeid);
			$infoData = array();
			foreach($tempData as $mydata)
			{	
	    		        $waresid    = $mydata->waresid;
	                    $appid      = $mydata->appid;
	    		        $priceData  = $this->getPayTable()->getAppData(array('waresid'=>$waresid,'appid'=>$appid));
	    		        $mydataArr  = (array)$mydata;    		
	    		        $mydataArr['price'] = ($priceData)?$priceData->warename.'-'.$priceData->price.'分':'0分';
	    		        $infoData[] = $mydataArr;
	
				//$infoData[] = (array)$mydata;
			}
			$typearr = array('','简约','卡通','爱情','酷炫','创意','其他');
			$statusarr = array('0'=>'待审核','1'=>'审核通过','2'=>'审核未通过');
			return new ViewModel(array('uData'=>$uData,'typearr'=>$typearr,'statusarr'=>$statusarr,'showurl'=>Config::BASE_URL,
			                    'page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData,'mytypevalue'=>$typeid));
		}catch (Exception $e){
			Logs::write('SiteController::personthemeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
    
	public function personthemepreviewAction()
	{
		try{
			$uData    = $this->checkLogin('management');
			if(!$uData)
			{
				die('没有权限');
			}
	        $identity = isset($_GET['id'])?$_GET['id']:"";
	        $identity   = sqlcheck::check($identity);
			if(empty($identity))
			{
				die("参数错误");
			}
			$tempDataObj   = $this->getPersonthemeTable()->getAppData(array('identity'=>$identity));
			$folder           = $tempDataObj->folder;
			$baseUrl       = Config::PAY_QUERY_PATH.'/designer/';
			$infoDataRatio = array();
			for($i=1;$i<=3;$i++){
				$dir=$baseUrl.$folder.'theme/preview/preview0'.$i.'.jpg';
				$file = '/designer/'.$folder.'theme/preview/preview0'.$i.'.jpg';
				$size = round(filesize($dir)/1024,0).'KB';
				$data =array('name'=>'preview0'.$i.'.jpg','size'=>$size,'folder'=>$file,'url'=>$file);
				$infoDataRatio[]=$data;
			}
			return new ViewModel(array('showurl'=>Config::BASE_URL,'folderStr'=>Config::REPALCE_STR,'themeid'=>$identity,'ratioInfo'=>$infoDataRatio));
		}catch (Exception $e){
			Logs::write('SiteController::personthemepreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function addpersonthemepreviewAction()
	{
		try{
			$uData = $this->checkLogin('management');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr  = $request->getPost();
				if( empty($postArr['subjectpic']) )
				{
					die('empty');
				}
				$identity = $postArr['id'];
				$pic      = $postArr['subjectpic'];
				$identity   = sqlcheck::check($identity);
				$pic        = sqlcheck::check($pic);
				$fileArr  = explode("/", $pic);
				$fileMd5  = md5_file(Config::TEMP_FOLDER.$pic);
				$fileSize = filesize(Config::TEMP_FOLDER.$pic);
				$mypicid  = $postArr['mypicid'];
				if(!$mypicid){
					$this->getPersonthemepreviewTable()->saveArr(
							array(
									'identity' => $identity,
									'type'     => 0,
									'url'      => $pic,
									'name'     => '',
									'size'     => $fileSize,
									'note'     => '',
									'md5'      => $fileMd5,
									'folder'   => $fileArr[4],
							)
					);
					$this->getPersonthemeTable()->updateDataArr(array('identity'=>$identity,'img_num'=>new Expression('img_num+1') ));
				}
				if($mypicid){
					$this->getPersonthemepreviewTable()->updateData(
							array(
									'id'=>$mypicid,
									'identity'=>$identity,
									//'type'=>0,
									'url'=>$pic,
									'name'=>'',
									'size'=>$fileSize,
									'note'=>'',
									'md5'=>$fileMd5,
							)
					);
				}
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addpersonthemepreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function updatepersonthemeAction()
	{
		try{
			$uData = $this->checkLogin('management');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr 	= $request->getPost();
				$status  	= $postArr['status'];
				$cyname     = $postArr['cyname'];
				$themename  = $postArr['themename'];
				$status     = sqlcheck::check($status);
				$cyname     = sqlcheck::check($cyname);
				$themename     = sqlcheck::check($themename);
				if($status == 2){
					$reason = $postArr['reason'];
					$reason     = sqlcheck::check($reason);
					
				}else{
					$reason = '';
				}
				$infoData   = array('verify_time'=>date('Y-m-d H:i:s'),'reason'=>$reason,
						'verify_user'=>$uData->username,'status'=>intval($status),'cpid'=>$postArr['id']);
				$this->getPersonthemeTable()->updateData($infoData);
				if($status == 1 || $status == 2){
				  $result = $this->getPersonthemeTable()->getselecttype($cyname);
				  $selecttype = $result->selecttype;
				  $userid     = $result->userid;
				$userInfo    = $this->getPersonthemeTable()->getUserInfo($selecttype,$userid);
				$this->_mailNotify($userInfo->email,$userInfo->devname,$status,$reason,$themename);
			    }
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::updatepersonthemeAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	public function _mailNotify($email,$devname,$authenType,$reason,$themename)
	{
		$strSubject = $this->getMailNotify()->getNotifySubject();
		$strBody = $this->getMailNotify()->getNotifyBody($devname,$authenType,$reason,$themename);
		$bResult = $this->getMailNotify()->notify($email,$strSubject, $strBody);
		if(!$bResult){
			Logs::write('ApiController::_mailNotify() failed', 'log');
			return false;
		}
		return true;
	}
	
	public function getMailNotify()
	{
		if(!$this->mailNotify){
			$this->mailNotify = new MailNotify(Config::$MAILS);
		}
		return $this->mailNotify;
	}
    	
    public function getconfigAction()
    {	
		$cyname     = $_GET['cyname'];
// 		$insertname = $_GET['insertname'];
		$author     = $_GET['author'];
		$ischarge   = $_GET['ischarge'];
		$waresid    = $_GET['waresid'];
		$appid      = $_GET['appid'];
		$cpid       = $_GET['cpid'];
		$cyname   = sqlcheck::check($cyname);
		$author   = sqlcheck::check($author);
		$ischarge = sqlcheck::check($ischarge);
		$waresid  = sqlcheck::check($waresid);
		$appid    = sqlcheck::check($appid);
		$cpid     = sqlcheck::check($cpid);
	
		header("Content-type: application/force-download");
		header("Content-Disposition:filename=description.xml");
		$xmlcontent = "<root>\r\n";
		$xmlcontent .= "\t".'<item key="cyname" value="'.$cyname.'" />'."\r\n";
		$xmlcontent .= "\t".'<item key="cpid" value="'.$cpid.'" />'."\r\n";
		$xmlcontent .= "\t".'<item key="insertname" value="'.$insertname.'" />'."\r\n";
		$xmlcontent .= "\t".'<item key="author" value="'.$author.'" />'."\r\n";
		$xmlcontent .= "\t".'<item key="ischarge" value="'.$ischarge.'" />'."\r\n";
		$xmlcontent .= "\t".'<item key="waresid" value="'.$waresid.'" />'."\r\n";
		$xmlcontent .= "\t".'<item key="appid" value="'.$appid.'" />'."\r\n";
		$xmlcontent .= "</root>\r\n";
		echo $xmlcontent;
	
		//$xmlcontent = "sdsdsd\r\ndfdfdf\r\n";
		//file_put_contents("/home/wwwroot/app/public/description.xml",$xmlcontent);
		//var_dump($e);exit;
		//header("Content-type: application/force-download");
		//header("Location:http://csm.coolyun.com/description.xml");
		exit;
	}	
	
	//分辨率列表	
     public function ratioAction()
    {
    	try{
	    	$pageSize = 10;
	    	$uData    = $this->checkLogin('view');
	    	if(!$uData)
	    	{
	    		die('没有权限');
	    	}
	    	$page        = isset($_GET['page'])?(int)$_GET['page']:0;
	    	$page   = sqlcheck::check($page);
	    	$countWidget = $this->getRatioTable()->getCountnum();    	 
	    	$totalPage   = ceil($countWidget/$pageSize);
	    	if($page>$totalPage) $page = $totalPage;
	    	if($page==0) $page = 1;
	    	$tempData = $this->getRatioTable()->getData($page,$pageSize);
	    	$infoData = array();
	    	foreach($tempData as $mydata)
	    	{
	    		$infoData[] = (array)$mydata;
	    	}
	    	return new ViewModel(array('uData'=>$uData,'page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData));
    	}catch (Exception $e){
			Logs::write('SiteController::ratioAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function addratioAction()
    {
    	try{
	    	$uData = $this->checkLogin('edit');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postArr = $request->getPost();
	    		if( empty($postArr['mywidth']) || empty($postArr['myheight']) )
	    		{
	    			die('empty');
	    		}
	    		$id      = $postArr['id'];
	    		$appname = $postArr['mywidth'];
	    		$appshow = $postArr['myheight'];
	    		$id        = sqlcheck::check($id);
	    		$appname   = sqlcheck::check($appname);
	    		$appshow   = sqlcheck::check($appshow);
	    
	    		if($id)
	    		{
	    			$this->getRatioTable()->updateData(array('id'=>$id,'width'=>$appname,'height'=>$appshow));
	    			die('success');
	    		}
	    
	    		$result = $this->getRatioTable()->getAppData(array('width'=>$appname,'height'=>$appshow));
	    		if($result && $result->id)
	    		{
	    			die('exist');
	    		}
	    
	    		$this->getRatioTable()->saveArr(array('width'=>$appname,'height'=>$appshow));
	    		die('success');
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::addratioAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }	
    
    //付费编号列表
    public function payAction()
	{
		try{
			$pageSize    = 10;
			$uData       = $this->checkLogin('pay');
			if(!$uData)
			{
				die('没有权限');
			}
			$page        = isset($_GET['page'])?(int)$_GET['page']:0;
			$page        = sqlcheck::check($page);
			$countWidget = $this->getPayTable()->getCountnum();	
			$totalPage   = ceil($countWidget/$pageSize);
			if($page>$totalPage) $page = $totalPage;
			if($page==0) $page = 1;
			$tempData    = $this->getPayTable()->getData($page,$pageSize);
			$infoData    = array();
			foreach($tempData as $mydata)
			{
				$infoData[] = (array)$mydata;
			}
			return new ViewModel(array('uData'=>$uData,'keytype'=>$this->keyType,'page'=>$page,'countWidget'=>$countWidget,'totalPage'=>$totalPage,'infoData'=>$infoData));
		}catch (Exception $e){
			Logs::write('SiteController::payAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function addpayAction()
	{
		try{
			$uData   = $this->checkLogin('pay');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();
				if( empty($postArr['mywaresid']) || empty($postArr['myappkey'])|| empty($postArr['myappid'])
					|| empty($postArr['mymodkey']) || empty($postArr['myappresppley']) || empty($postArr['mywarename'])
					|| empty($postArr['mychargepoint']) || empty($postArr['myprice']) || !isset($postArr['mykeyflag'])	)
				{
					die('empty');
				}
				$id            = $postArr['id'];
				$mywaresid     = $postArr['mywaresid'];
				$myappid       = $postArr['myappid'];
				$mywarename    = $postArr['mywarename'];
				$myappkey      = $postArr['myappkey'];			
				$mymodkey      = $postArr['mymodkey'];
				$myappresppley = $postArr['myappresppley'];
				$mychargepoint = $postArr['mychargepoint'];
				$myprice       = $postArr['myprice'];
				$mykeyflag     = $postArr['mykeyflag'];	
				$id              = sqlcheck::check($id);
				$mywaresid       = sqlcheck::check($mywaresid);
				$myappid         = sqlcheck::check($myappid);
				$mywarename      = sqlcheck::check($mywarename);
				$myappkey        = sqlcheck::check($myappkey);
				$mymodkey        = sqlcheck::check($mymodkey);
				$myappresppley   = sqlcheck::check($myappresppley);
				$mychargepoint   = sqlcheck::check($mychargepoint);
				$myprice         = sqlcheck::check($myprice);
				$mykeyflag       = sqlcheck::check($mykeyflag);
				
				if($id)
				{
					$this->getPayTable()->updateData(array(
							'id'          => $id,
							'waresid'     => $mywaresid,
							'appid'       => $myappid,
							'warename'    => $mywarename,
							'appkey'      => $myappkey,
							'appmodkey'   => $mymodkey,
							'appresppley' => $myappresppley,
							'chargepoint' => $mychargepoint,
							'price'       => $myprice,
							'keyflag'     => $mykeyflag,
					));
					die('success');
				}	
				$result = $this->getPayTable()->getAppData(array(
						'waresid'     => $mywaresid,
						'appid'       => $myappid,
						'warename'    => $mywarename,
						'appkey'      => $myappkey,
						'appmodkey'   => $mymodkey,
						'appresppley' => $myappresppley,
						'chargepoint' => $mychargepoint,
						'price'       => $myprice,
						'keyflag'     => $mykeyflag,										
				));
				if($result && $result->id)
				{
					die('exist');
				}	
				$this->getPayTable()->saveArr(array(
						'waresid'     => $mywaresid,
						'appid'       => $myappid,
						'warename'    => $mywarename,
						'appkey'      => $myappkey,
						'appmodkey'   => $mymodkey,
						'appresppley' => $myappresppley,
						'chargepoint' => $mychargepoint,
						'price'       => $myprice,
						'keyflag'     => $mykeyflag,
						'insert_time' => date('Y-m-d H:i:s'),
				));
				die('success');
			}
		}catch (Exception $e){
			Logs::write('SiteController::addpayAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
    		
    public function getpaydataAction(){
    	try{
    		$uData   = $this->checkLogin('pay');
			if(!$uData)
			{
				die('nopower');
			}
			$tempdata = $this->getPayTable()->getAppDataList(array("warename"=>"主题"));
			$infoData = array();
			foreach($tempdata as $tdata){
				$infoData[] = (array)$tdata;
			}
			echo json_encode($infoData);
			exit;
    	}catch (Exception $e){
			Logs::write('SiteController::getpaydataAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function getpaydatainfoAction(){
		try{
			$uData   = $this->checkLogin('pay');
			if(!$uData)
			{
				die('nopower');
			}
			$tempdata = $this->getPayTable()->getAppDataList(array("warename"=>"主题"));
			$infoData = array();
			foreach($tempdata as $tdata){
				$infoData[] = array("waresid" => $tdata->waresid,"appid" => $tdata->appid,"price" => intval($tdata->price)/100);
			}
			echo json_encode($infoData);
			exit;
		}catch (Exception $e){
			Logs::write('SiteController::getpaydatainfoAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	//付费审核
	public function payreviewAction(){
		try{
	        $uData    = $this->checkLogin('pay');
			if(!$uData)
			{
				die('没有权限');
			}
			$request       = $this->getRequest();
			$start         = date("Y-m-d",time()-86400*7);
			$end           = date("Y-m-d",time());
			if($request->isPost()){
				$postArr        = $request->getPost();
				$start          = trim($postArr["inputStartDate"]," ");
				$end            = trim($postArr["inputEndDate"]," ");
				$start   = sqlcheck::check($start);
				$end     = sqlcheck::check($end);
			}
	
			$page          = isset($_GET['page'])?$_GET['page']:1;
			$start         = isset($_GET['stime'])?$_GET['stime']:$start;
			$end           = isset($_GET['stime'])?$_GET['etime']:$end;
			$page    = sqlcheck::check($page);
			$start   = sqlcheck::check($start);
			$end     = sqlcheck::check($end);		
			$startTime     = $start.' 00:00:00';
			$endTime       = $end.' 23:59:59';
	
			$pageSize      = 10;
			
			$ownSum  = $this->getChargeTable()->getOwnSum($startTime,$endTime);
			$result  = $this->getChargeTable()->getOwnDifferences($startTime,$endTime,$page,$pageSize);
			$ownData = array();
			foreach($result as $row){
				$ownData[] = (array)$row;
			}
			 
			$countNum      = $this->getChargeTable()->getToneDifNum($startTime,$endTime);
			$totalPage      = ceil((int)$countNum/$pageSize);
			if($page>$totalPage){$page = $totalPage;}
			$toneSum  = $this->getChargeTable()->getToneSum($startTime,$endTime);
			
	 		if($page<=0){$page=1;}
			$result  = $this->getChargeTable()->getToneDifferences($startTime,$endTime,$page,$pageSize);
			$toneData = array();
			foreach($result as $row){
				$toneData[] = (array)$row;
			}
			
			$ordernum = $this->getChargeTable()->getOrderNum($startTime,$endTime);
			return new ViewModel(array('ownSum'=>$ownSum,'toneSum'=>$toneSum,'ownData'=>$ownData,
					'tStart'=>$start,'tEnd'=>$end,'toneData'=>$toneData,'ordernum'=>$ordernum,
					'page'=>$page,'totalPage'=>$totalPage,'mycount'=>$countNum));
		}catch (Exception $e){
			Logs::write('SiteController::payreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	//酷秀打点
	public function hitdotAction()
	{
		try{
			$uData = $this->checkLogin('push');
			if(!$uData)
			{
				die('没有权限');
			}
			return new ViewModel(array('folderStr'=>Config::REPALCE_STR));
		}catch (Exception $e){
			Logs::write('SiteController::hitdotAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}	
	
	public function getproductAction()
    {
    	//推送时获取机型，不通过curl获取
    	try{
    		$uData = $this->checkLogin('view');
			if(!$uData)
			{
				die('没有权限');
			}
	    	$request = $this->getRequest();
	    	if($request->isPost())
	    	{
	    		$postarr = $request->getPost();
	    		$ratio   = isset($postarr['tempratio'])?$postarr['tempratio']:'';
	    		$pushtype= isset($postarr['tmppushtype'])?$postarr['tmppushtype']:'';
//	    		$ratio      = sqlcheck::check($ratio);
	    		$pushtype   = sqlcheck::check($pushtype);
	    		if($ratio != '' && $pushtype != '')
	    		{
	    			list($width,$height)= explode('x', $ratio);
	    			$condition = array('width'=>$width,'height'=>$height,'ispush'=>1,'valid'=>1,'pushtype'=>$pushtype);
	    		}elseif($ratio == '' && $pushtype != ''){
	    			$condition = array('ispush'=>1,'valid'=>1,'pushtype'=>$pushtype);
	    		} else {
	    			$condition = array('ispush'=>1,'valid'=>1);
	    		}
	    		
	    		$tempData = $this->getProductTable()->getProduct($condition);
	    		$tempArr  = array();
	    		foreach($tempData as $row){
	    			$tarr = array('product'=>$row['product'],'pushtype'=>$row['pushtype'],'version'=>$row['kernelCode'],
	    							'ratio'=>$row['width'].'x'.$row['height']);
	    			$tempArr[] = $tarr;
	    		}
	    		$json_rsp = array(
	    				'result'   => true,
	    				'products' => $tempArr,
	    		);
	    		die(json_encode($json_rsp));
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::getproductAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    
    public function pushserverAction()
    {
    	try{
    		//传到外部链接的参数，sql验证未加，之前加了
	    	$uData   = $this->checkLogin('push');
	    	if(!$uData)
	    	{
	    		die('nopower');
	    	}
	    	$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postarr = $request->getPost();
	    		$msginfo = json_decode($postarr['appmsg'],true);
	    		$msgarr  = $postarr['appmsg'];
	    		$pusharr = array(
	    				'appid'       => $postarr['appid'],
	    				'appnote'     => $postarr['mynote'],
	    				'type'        => $postarr['sendtype'],
	    				'mobiles'     => (intval($postarr['sendtype'])==1)?explode(',',$postarr['mobile']):array(),
	    				'products'    => (intval($postarr['sendtype'])==2)?explode(',',$postarr['product']):array(),
	    				'appmsg'      => $msgarr,
	    				'ispush'      => (int)$postarr['ispush'],
	    				'isoffline'   => (int)$postarr['ifoffline'],
	    				'offtime'     => (intval($postarr['ifoffline'])==1)?$postarr['offlinetime']:'',
	    				'istimer'     => (int)$postarr['istimer'],
	     				'timerdetail' => (intval($postarr['istimer'])==1)?$postarr['timer']:'',
	     				'notify'   => array(
						'title'    => $postarr['title'],
						'activity' => $postarr['activity'],
						'content'  => $postarr['content'],
						'afterpush'=> $postarr['afterpush'],
						'isclean'  => $postarr['clean'],
						'isring'   => $postarr['ring'],
						'isshake'  => $postarr['shake'],
	     				),
	    		);
	            $newmydata = json_encode($pusharr);   
	            $newmydata = urlencode($newmydata);	
//	            $result    = $this->geturldatapost(Config::PUSH_URL,'task='.$newmydata);	
	            $result    = $this->geturldata(Config::PUSH_URL,array('task'=>$newmydata),1);	
	    		$resultarr = json_decode($result,true);
	    		$resultstr = $resultarr['result']?'success':'failed';   		
	    		if($resultarr['result'])
	    		{
	    			$taskid   = $resultarr['taskid'];
	    			$tempid   = $postarr['tempid'];
	    			$temptype = $postarr['temptype'];
	    			$tempname = $postarr['tempname'];
	    			$tempr    = $postarr['tempratio'];
	    			$this->getTaskTable()->saveArr(array('identity'=>$tempid,'type'=>$temptype,'ratio'=>$tempr,'name'=>$tempname,'taskid'=>$taskid));
	    		}
	    		die($resultstr);
	    	}
    	}catch (Exception $e){
			Logs::write('SiteController::pushserverAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
    }
    	
    public function dotserverAction()
	{
		try{
			//curl，sql验证未加
			$uData    = $this->checkLogin('push');
			if(!$uData)
			{
				die('没有权限');
			}
			$request = $this->getRequest();
	    	if ($request->isPost())
	    	{
	    		$postarr = $request->getPost();
	    		$isshow  = $postarr['isshow']?true:false;
	    		$isgoing = $postarr['clean']?true:false;
	    		$islarge = $postarr['large']?true:false;
	    		$pushtype= isset($postarr['pushtype'])?(int)$postarr['pushtype']:0;
	    		$url     = isset($postarr['subjectpic'])?$postarr['subjectpic']:'';
	    		$url     = Config::SHOW_URL.$url;
	    		$msgarr  = array('isdirect'=>true,'entity'=>array('pushtype'=>(int)$postarr['typeid'],'isorder'=>true,
	    				          'isshow'=>$isshow,'title'=>$postarr['title'],'content'  => $postarr['content'],
	    						 'isgoing'=>$isgoing,'islarge'=>$islarge,'url'=>$url,'id'=>"234",'pushDisplayType'=>$pushtype));
	    		$msgarr  = json_encode($msgarr);
	    		$pusharr = array(
	    				'appid'       => $postarr['appid'],
	    				'appnote'     => $postarr['mynote'],
	    				'type'        => $postarr['sendtype'],
	    				'mobiles'     => (intval($postarr['sendtype'])==1)?explode(',',$postarr['mobile']):array(),
	    				'products'    => (intval($postarr['sendtype'])==2)?explode(',',$postarr['product']):array(),
	    				'appmsg'      => $msgarr,
	    				'ispush'      => (int)$postarr['ispush'],
	    				'notify'  => array(
	    						'title'    => $postarr['title'],
	    						'content'  => $postarr['content'],
	    				),
	    		);
	            $newmydata = json_encode($pusharr);
	            $newmydata = str_replace('&', '%26', $newmydata);	    		
//	            $result    = $this->geturldatapost(Config::PUSH_URL,'task='.$newmydata);
				$result    = $this->geturldata(Config::PUSH_URL,array('task'=>$newmydata), 1);
	    		$resultarr = json_decode($result,true);
	    		$resultstr = $resultarr['result']?'success':'failed';   		
                if($resultarr['result'])
	    		{
	    			$taskid   = $resultarr['taskid'];
	    			$this->getTaskTable()->saveArr(array('type'=>$postarr['typeid'] ,'taskid'=>$taskid));
	    		} 
	    		die($resultstr);
	    	}
		}catch (Exception $e){
			Logs::write('SiteController::dotserverAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//预览图处理
	public function previewdlAction()
	{
		try{
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('没有权限');
			}
			return new ViewModel(array('folderStr'=>Config::REPALCE_STR));
		}catch (Exception $e){
			Logs::write('SiteController::previewdlAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
//用于更新之前没有加dl_md5字段的记录
    public function  dlMd5Action()
    {
        try{
            $tempData=$this->getThemeInfoTable()->getAllInfo();
            foreach($tempData as $mydata)
            {
                if($mydata['dl_md5']==null)
                {
                    $dl_md5 = md5_file(Config::TEMP_FOLDER.$mydata['url']);
                    $this->getThemeInfoTable()->updatedlMd5Data( array('dl_md5' =>$dl_md5), $mydata);
                }
            }
            echo"<script>alert('更新完成');</script>";
            die();
        }catch (Exception $e){
            Logs::write('SiteController::previewdlAction() exception, err:'
                .' file:'.$e->getFile()
                .' line:'.$e->getLine()
                .' message:'.$e->getMessage()
                .' trace:'.$e->getTraceAsString(), 'log');
        }
    }

    public function  dlSceneAction()
    {
        try{
            $tempData=$this->getSceneTable()->getAllInfo();
            foreach($tempData as $mydata)
            {
                if($mydata['dl_md5']==null)
                {
                    $dl_md5 = md5_file(Config::TEMP_FOLDER.$mydata['url']);
                    $this->getSceneTable()->updatedlMd5Data( array('dl_md5' =>$dl_md5), $mydata);
                }
            }
            echo"<script>alert('更新完成');</script>";
            die();
        }catch (Exception $e){
            Logs::write('SiteController::previewdlAction() exception, err:'
                .' file:'.$e->getFile()
                .' line:'.$e->getLine()
                .' message:'.$e->getMessage()
                .' trace:'.$e->getTraceAsString(), 'log');
        }
    }

    public function  dlFontAction()
    {
        try{
            $tempData=$this->getFontTable()->getAllInfo();
            foreach($tempData as $mydata)
            {
                if($mydata['dl_md5']==null)
                {
                    $dl_md5 = md5_file(Config::TEMP_FOLDER.$mydata['url']);
                    $this->getFontTable()->updatedlMd5Data( array('dl_md5' =>$dl_md5), $mydata);
                }
            }
            echo"<script>alert('更新完成');</script>";
            die();
        }catch (Exception $e){
            Logs::write('SiteController::previewdlAction() exception, err:'
                .' file:'.$e->getFile()
                .' line:'.$e->getLine()
                .' message:'.$e->getMessage()
                .' trace:'.$e->getTraceAsString(), 'log');
        }
    }
	
	public function exportpreviewAction()
	{
		try{
			//Logs::write('11111', 'log');
			$theme_identity = $this->getThemeTable()->getDataAll();
			//Logs::write('__________', 'log');
			$result = array();
			foreach ($theme_identity as $unit)
			{
				$identity = $this->getThemeInfoTable()->getAllData(array('cpid'=>$unit['cpid'], 'width'=>'2160', 'height'=>'1920'));
				//Logs::write('222222222222', 'log');
				foreach($identity as $idunit)
		    	{
		    		//Logs::write('88888888______'.$idunit['identity'], 'log');
		    		$per_pic  = $this->getPreviewTable()->getInfo(array('identity'=>$idunit['identity'], 'type'=>'1'));
		    		//Logs::write('333333333333333______', 'log');
		    		$mypic = array();
		    		foreach ($per_pic as $perunit)
		    		{
		    			$mypic[] = (array)$perunit;
		    		}
		    		$src = Config::REPALCE_STR.$mypic[0]['url'];
		    		
		    		
		    		$fileExtenArr = explode(".", $mypic[0]['url']);
					$fileExten    = end($fileExtenArr);
		    		
		    		
		    		if(file_exists($src))
					{
						@copy($src, Config::PREVIEW_EXPORT.$mypic[0]['identity'].'.'.$fileExten);
					}
					//Logs::write('444444444444', 'log');		    	
		    	}
			}
			die('success');
		} catch (Exception $e){
			Logs::write('SiteController::exportpreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function addminpreviewAction()
	{	
		try{	
			$uData = $this->checkLogin('edit');
			if(!$uData)
			{
				die('nopower');
			}
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$postArr = $request->getPost();	
				$pic          = $postArr['subjectpic'];
				$pic          = urldecode($pic);
				$folder       = file_get_contents(Config::FILE_PATH);
				$zip = new ZipArchive;
				if (  $zip->open( Config::TEMP_FOLDER.$pic ) === TRUE  )
				{
					$zip->extractTo( Config::TEMP_FOLDER.$folder );
					$zip->close();
				}
				$handle  = opendir(Config::TEMP_FOLDER.$folder.Config::TEMP_NAME);
				$filenum = 0;
				
//				$myresult   = $this->getThemeInfoTable()->getAllData(array('identity'=>'1933835959'));
//				
//						$myarray  = array();
//						foreach ($myresult as $unit)
//						{
//							 array_push($myarray, $unit);
//						}
////						var_dump($myarray[0]['cpid']);  exit();
//						Logs::write('3333____'.$myarray[0]['cpid'], 'log');
									
				while(false!==($myfile=readdir($handle)))
				{
					if($myfile!='Thumbs.db' && $myfile!='.' && $myfile!='..' && $myfile!='' && $myfile!='Files')
					{
						$filenum++;						
						$myfile       = iconv('gbk', 'utf-8',  $myfile);
						$pic          = $folder.Config::TEMP_NAME.'/'.$myfile;						
						
						$pic_name = explode('.', $myfile);
						$identity = $pic_name[0];
						Logs::write('2222____'.$identity, 'log');
						$result   = $this->getThemeInfoTable()->getAllData(array('identity' => $identity));
						$myarray  = array();
						foreach ($result as $unit)
						{
							array_push($myarray, $unit);
						}
						Logs::write('3333____'.$myarray[0]['cpid'], 'log');
						if($myarray)
						{							
							$myresult = $this->getThemeTable()->getThemeData(array('cpid'=>$myarray[0]['cpid']));
							if($myresult)
							{
								$this->getThemeTable()->updateOnline(array('cpid'=>$myarray[0]['cpid'], 'pre_url'=>$pic));	
//								Logs::write('3333____'.$myarray[0]['cpid'], 'log');
							} else { continue;}														
						} else { continue; }
										
					}
				}	
				die('success');
			}
			
		}catch (Exception $e){
			Logs::write('SiteController::addminpreviewAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	//预处理部分
    public function indexAction()
    {
    	$uData = $this->checkLogin('base');
    	if(!$uData)
    	{
    		die('没有权限');
    	}		 
        return new ViewModel();
    }  
      
    public function topAction()
    {
    	$uData = $this->checkLogin('base');
    	if(!$uData)
    	{
    		die('没有权限');
    	}
    	return new ViewModel(array('uData'=>$uData));    	
    }
    
    public function leftAction()
    {
    	$uData = $this->checkLogin('base');
    	if(!$uData)
    	{
    		die('没有权限');
    	}
		/*
    	$result = $this->getRoleTable()->getAppData(array('user_id'=>$uData->userid, 'app'=>'theme') );
    	$myresult = $this->getAclTable()->getAppData(array('role'=>$result->role,    'app'=>$result->app) );
    	$data = array();
    	foreach ($myresult as $temp)
    	{
    		array_push($data, $temp->acl);
    	}
    	return new ViewModel(array('data'=>$data));
		*/
		return new ViewModel();
    }
    
    public function downAction()
    {
    	$uData = $this->checkLogin('base');
    	if(!$uData)
    	{
    		die('没有权限');
    	}
    	return new ViewModel();
    }  
    
    public function centerAction()
    {
    	$uData = $this->checkLogin('base');
    	if(!$uData)
    	{
    		die('没有权限');
    	}
    	return new ViewModel();
    } 
    
    //设置上传路径参数，可以优化
	 public function setfolderAction()
    {
    	$request = $this->getRequest();
    	if ($request->isPost()) 
    	{
    		$postArr   = $request->getPost();
    		$id        = $postArr['id'];
    		$ratio     = $postArr['ratio'];
    		$id      = sqlcheck::check($id);
//    		$ratio   = sqlcheck::check($ratio);
    		$ratio     = str_replace("-", "x", $ratio);
    		$mycontent = Config::FOLDER_STR.$id.'/'.$ratio;
    		@file_put_contents(Config::FILE_PATH, $mycontent);
    		die("");
    	}
    }
    
	public function setpathAction()
    {
    	$request = $this->getRequest();
    	if ($request->isPost()) 
    	{
    		$postArr   = $request->getPost();
//     		$ratio     = $postArr['ratio'];
			$daytime   = date('Ymd');
			$atime     = date('His');
			$randnum   = $this->num_rand(Config::TEMPLEN);
//     		$ratio     = str_replace("-", "x", $ratio);
    		$mycontent = Config::TEMP_THEME.$daytime.Config::LARGE_THEME.$atime.'_'.$randnum;
    		//echo $mycontent;
    		@file_put_contents(Config::FILE_PATH, $mycontent);
    		die("");
    	}
    }
	
	public function setalbumspathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('YmdHis');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_ALBUMS.date('Ym').'/'.$atime.'_'.$randnum;
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	public function setwallpaperpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('YmdHis');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_WALLPAPER.date('Ymd').'/'.$atime.'_'.$randnum;
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	public function setwallpaperlistpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('YmdHis');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_WALLPAPER.'temp/'.date('Ymd').'/'.$atime.'_'.$randnum;
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	public function setwpuploadpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('YmdHis');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_WPUPLOAD.'temp/'.date('Ymd').'/'.$atime.'_'.$randnum;
			//echo $mycontent;
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	public function setscenepathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('His');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_SCENE.date('Ymd').'/'.$atime.'_'.$randnum;				
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}		
	}
	
	public function setlivewpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('His');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_LIVEWP.date('Ymd').'/'.$atime.'_'.$randnum;				
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}		
	}
	
	public function setdotpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$atime     = date('His');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = '/dotpicture/'.date('Ymd').'/'.$atime.'_'.$randnum;
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	public function setbannerpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$postArr   = $request->getPost();
			$type      = $postArr['type'];
			$album     = $postArr['album'];
			$type      = sqlcheck::check($type);			
			switch($type){
				case '0': $temp = 'theme/';break;
				case '2': $temp = 'wallpaper/';break;
                case '3': $temp = 'wallpaper/';break;
				case '5': $temp = 'font/';break;
				case '6': $temp = 'scene/';break;
				case '4': $temp = 'ring/';break;
				case '14': $temp = 'alarm/';break;
				//将闹钟专辑封面自定义为100
				case '100': $temp = 'alarmpic/';break;
			}
			$atime     = date('YmdHis');
			$randnum   = $this->num_rand(Config::TEMPLEN);
			$mycontent = Config::TEMP_BANNER.$temp.date('Ym').'/'.$atime.'_'.$randnum;
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}			
	
	public function setringpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$postArr      = $request->getPost();			
			$ringtype     = $postArr['ringtype'];	
			$ringtype     = sqlcheck::check($ringtype);			
			$ringtypeinfo = $this->getRingTypeTable()->getAppData( array('type'=>intval($ringtype), ));
			$atime        = date('YmdHis');
			$randnum      = $this->num_rand(Config::TEMPLEN);
			$mycontent    = Config::TEMP_RING.'/'.$ringtypeinfo->name.'/'.date('Ym').'/'.$atime.'_'.$randnum;					
			Logs::write("setringpathAction:".$mycontent, "log");
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	public function setalarmpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{
//			$postArr      = $request->getPost();			
//			$alarmtype     = $postArr['alarmtype'];				
			$atime        = date('YmdHis');
			$randnum      = $this->num_rand(Config::TEMPLEN);
			$mycontent    = Config::TEMP_ALARM.'/'.date('Ym').'/'.$atime.'_'.$randnum;						
//			Logs::write("setalarmpathAction:".$mycontent, "log");
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	public function setfontpathAction()
	{
		try{
			$request = $this->getRequest();
			if ($request->isPost())
			{				
				$postArr = $request->getPost();
				if( empty($postArr['id']) ){
					$myurl = $postArr['myurl'];
					if(empty($myurl))
					{
						$atime     = date('YmdHis');
						$randnum   = $this->num_rand(Config::TEMPLEN);
						$mycontent = Config::TEMP_FONT.date('Ym').'/'.$atime.'_'.$randnum;
					}else
					{
						$myurlArr  = explode("/", $myurl);
						$mycontent = Config::TEMP_FONT.$myurlArr[3].'/'.$myurlArr[4];
					}
				}else
				{
					$fontid       = $postArr['id'];
					$fontid       = sqlcheck::check($fontid);
					$tempresult   = $this->getFontTable()->getAppData(array('identity'=>$fontid));
					$mytempurl    = $tempresult->url;
					$myurltempArr = explode("/", $mytempurl);
					$mycontent    = Config::TEMP_FONT.$myurltempArr[3].'/'.$myurltempArr[4];
				}			
				@file_put_contents(Config::FILE_PATH, $mycontent);
				die("");
			}
		}catch (Exception $e){
			Logs::write('SiteController::setfontpathAction() exception, err:'
					.' file:'.$e->getFile()
					.' line:'.$e->getLine()
					.' message:'.$e->getMessage()
					.' trace:'.$e->getTraceAsString(), 'log');
		}
	}
	
	public function setminpreviewpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{			
			$mycontent    = Config::PREVIEW_IMPORT;						
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	public function setadpathAction()
	{
		$request = $this->getRequest();
		if ($request->isPost())
		{			
			$mycontent    = Config::THEME_AD;						
			@file_put_contents(Config::FILE_PATH, $mycontent);
			die("");
		}
	}
	
	//未知函数         
    public function get_zip_entry_content($zip_file, $entry)
    {
    	try{
    		$zip = zip_open($zip_file);
//     		Logs::write('fnlib::get_zip_entry_content()zip_file:'.$zip_file, 'log');
    			
    		$b_find = false;
    		if (!is_resource($zip)){
    			Logs::write('fnlib::get_zip_entry_content()zip:'.$zip.'  is_resource failed', 'log');
    		}
    		while ($zip_entry = zip_read($zip)) {
    			$file_name = zip_entry_name($zip_entry);
    			if(substr($file_name, 0, strlen($entry))!= $entry){
    				continue;
    			}
    			$b_find = true;
    			if (!zip_entry_open($zip, $zip_entry, "r")){
    				continue;
    			}
    			$content = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
    			zip_entry_close($zip_entry);
    			break;
    		}
    		// 			}
    		zip_close($zip);
    	}catch(Exception $e){
    		Logs::write('fnlib::get_zip_entry_content()exception error:'.$e->getMessage(), 'log');
    		return false;
    	}
    	if($b_find){
    		return $content;
    	}
//     	Logs::write('fnlib::get_zip_entry_content() b_find is false', 'log');
    	return false;
    }      

	public function recurse_copy($src,$dst) 
	{      		
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) 
		{
			if (( $file != '.' ) && ( $file != '..' )) 
			{
                                   if ( is_dir($src . '/' . $file) ) {  
                     recurse_copy($src . '/' . $file,$dst . '/' . $file);  
                 }  
                 else {  
				copy($src . '/' . $file,$dst . '/' . $file);
			}
		}		
	}
                    closedir($dir); 
	}

    public function recurse_copynew($src,$dst)
    {
    	$dir = opendir($src);

        while(false !== ( $file = readdir($dir)) ) 
        {
        	if (( $file != '.' ) && ( $file != '..' ) && ($file != '.DS_Store'))
        	{
            	if(strtolower(substr($file, -3)) == 'ttf' || strtolower(substr($file, -3)) == 'TTF') {
            		copy($src . '/' . $file,$dst . '/' . $file);
            		closedir($dir);
            		return $dst.'/'.$file;
            	}      		
            }
        }
        return false;
    }	   
    
    public function checkLogin($action)
    {
    	$myAuth  = new Auth();
    	//$myAuth->logout();
    	$objUser = $myAuth->isLogin();
    	if(!$objUser)
    	{
    		return $this->redirect()->toRoute('user', array('controller'=>'user','action' => 'login'));
    	}    	
		$roleArr  = json_decode($objUser->roleStr,true);
		$roleData = isset($roleArr['theme'])?$roleArr['theme']:"";
		if($roleData=="")
		{
			return false;
		}		
		$userAcl   = new Useracl();
		$allowData = $userAcl->checkAction($roleData, $action);
		if(!$allowData)
		{
			return false;
		}
			return $objUser;    	
    }

	public function num_rand($lenth){
		mt_srand((double)microtime() * 1000000);
		$randval   = '';
		for($i=0;$i<$lenth;$i++){
			$randval.= mt_rand(0,9);
		}
		$randval=substr(md5($randval),mt_rand(0,32-$lenth),$lenth);
		return $randval;
	}	
	
	public function putAdList($data){
		$rationArr     = array($data['width'],$data['height']);
		$data['value'] = array($data['url'],$data['mid_url'],$data['small_url']);
		$data['colum'] = array();				
		switch($rationArr){
			case array('1440','1280'):
				$data['colum']      = array('id_1440_1280','id_800_600','id_480_320');							
				break;
			case array('1080','960'):
				$data['colum']      = array('id_1080_960','id_640_480','id_300_225');
				break;
			case array('960','800'):
				$data['colum']      = array('id_960_800','id_480_400','id_300_225');
				break;
			case array('960','854'):
				$data['colum']      = array('id_960_854','id_480_400','id_300_225');
				break;
			case array('960','960'):
				$data['colum']      = array('id_1080_960','id_640_480','id_480_400');
				break;
			case array('2160','1920'):
				$data['colum']      = array('id_1440_1280','id_1080_960','id_640_480');
				break;
			case array('1080','888'):
				$data['colum']      = array('id_1080_960','id_640_480','id_300_225');
				break;
			case array('2400','1920'):
				$data['colum']      = array('id_1600_1280','id_1200_1024','id_480_384');
				break;				
		}
		if(count($data['colum']) == 0){
			Logs::write($data['width'].'x'.$data['height'].' is not having ', 'log');
			return;
		}
		$this->getWallpaperTable()->querySql($data);
	}	
		
	//表处理    
    public function getThemeTable()
    {
    	if (!$this->themeTable) {
    		$sm = $this->getServiceLocator();
    		$this->themeTable = $sm->get('Service\db_yl_themes\ThemeTable');
    	}
    	return $this->themeTable;
    }
    
    public function getThemeInfoTable()
    {
    	if (!$this->themeinfoTable) {
    		$sm = $this->getServiceLocator();
    		$this->themeinfoTable = $sm->get('Service\db_yl_themes\ThemeInfoTable');
    	}
    	return $this->themeinfoTable;
    }
    
    public function getThemeRecommendTable()
    {
    	if (!$this->themerecommendTable) {
    		$sm = $this->getServiceLocator();
    		$this->themerecommendTable = $sm->get('Service\db_yl_recommend\RecommendTable');
    	}
    	return $this->themerecommendTable;
    }
    
    public function getRatioTable()
    {
    	if (!$this->ratioTable) {
    		$sm = $this->getServiceLocator();
    		$this->ratioTable = $sm->get('Service\db_yl_themes\RatioTable');
    	}
    	return $this->ratioTable;
    }

    public function getPreviewTable()
    {
    	if (!$this->previewTable) {
    		$sm = $this->getServiceLocator();
    		$this->previewTable = $sm->get('Service\db_yl_themes\PreviewTable');
    	}
    	return $this->previewTable;
    }

    public function getPayTable()
    {
    	if (!$this->payTable) {
    		$sm = $this->getServiceLocator();
    		$this->payTable = $sm->get('Service\db_yl_themes\PayTable');
    	}
    	return $this->payTable;
    }
    
    public function getFontTable()
    {
    	if (!$this->fontTable) {
    		$sm = $this->getServiceLocator();
    		$this->fontTable = $sm->get('Service\db_yl_themes\FontTable');
    	}
    	return $this->fontTable;
    }    
    
    public function getFontPreviewTable()
    {
    	if (!$this->fontPreviewTable) {
    		$sm = $this->getServiceLocator();
    		$this->fontPreviewTable = $sm->get('Service\db_yl_themes\FontPreviewTable');
    	}
    	return $this->fontPreviewTable;
    }

    public function getRingTable()
    {
    	if (!$this->ringTable) {
    		$sm = $this->getServiceLocator();
    		$this->ringTable = $sm->get('Service\db_yl_themes\RingTable');
    	}
    	return $this->ringTable;
    }
    
    public function getAlarmTable()
    {
    	if (!$this->alarmTable) {
    		$sm = $this->getServiceLocator();
    		$this->alarmTable = $sm->get('Service\db_yl_themes\AlarmTable');
    	}
    	return $this->alarmTable;
    }
    
    public function getAlarmLabelTable()
    {
    	if (!$this->alarmLabelTable) {
    		$sm = $this->getServiceLocator();
    		$this->alarmLabelTable = $sm->get('Service\db_yl_themes\AlarmLabelTable');
    	}
    	return $this->alarmLabelTable;
    }

    public function getRingTypeTable()
    {
    	if (!$this->ringTypeTable) {
    		$sm = $this->getServiceLocator();
    		$this->ringTypeTable = $sm->get('Service\db_yl_themes\RingTypeTable');
    	}
    	return $this->ringTypeTable;
    }
    
    public function getRingSubTypeTable()
    {
    	if (!$this->ringSubTypeTable) {
    		$sm = $this->getServiceLocator();
    		$this->ringSubTypeTable = $sm->get('Service\db_yl_themes\RingSubTypeTable');
    	}
    	return $this->ringSubTypeTable;
    }

    public function getTaskTable()
    {
    	if (!$this->taskTable) {
    		$sm = $this->getServiceLocator();
    		$this->taskTable = $sm->get('Service\db_yl_themes\TaskTable');
    	}
    	return $this->taskTable;
    }
    
    public function getSceneTable()
    {
    	if (!$this->sceneTable) {
    		$sm = $this->getServiceLocator();
    		$this->sceneTable = $sm->get('Service\db_yl_themes\SceneTable');
    	}
    	return $this->sceneTable;
    } 
    
    public function getBannerTable()
    {
    	if (!$this->bannerTable) {
    		$sm = $this->getServiceLocator();
    		$this->bannerTable = $sm->get('Service\db_yl_themes\BannerTable');
    	}
    	return $this->bannerTable;
    }   
     
    public function getBannerlistTable()
    {
    	if (!$this->bannerlistTable) {
    		$sm = $this->getServiceLocator();
    		$this->bannerlistTable = $sm->get('Service\db_yl_themes\BannerlistTable');
    	}
    	return $this->bannerlistTable;
    }
    
    public function getAlbumsresTable()
    {
    	if (!$this->albumsresTable) {
    		$sm = $this->getServiceLocator();
    		$this->albumsresTable = $sm->get('Service\db_yl_themes\AlbumsresTable');
    	}
    	return $this->albumsresTable;
    }
    
    public function getAlbumsTable()
    {
    	if (!$this->albumsTable) {
    		$sm = $this->getServiceLocator();
    		$this->albumsTable = $sm->get('Service\db_yl_themes\AlbumsTable');
    	}
    	return $this->albumsTable;
    }
    
    public function getWallpaperTable()
    {
    	if (!$this->wallpaperTable) {
    		$sm = $this->getServiceLocator();
    		$this->wallpaperTable = $sm->get('Service\db_yl_androidesk\WallpaperTable');
    	}
    	return $this->wallpaperTable;
    }
    
    public function getLauncherTable()
    {
    	if (!$this->launcherTable) {
    		$sm = $this->getServiceLocator();
    		$this->launcherTable = $sm->get('Service\db_yl_androidesk\LauncherTable');
    	}
    	return $this->launcherTable;
    }
    
    public function getWplistCpTable()
    {
    	if (!$this->wplistcpTable) {
    		$sm = $this->getServiceLocator();
    		$this->wplistcpTable = $sm->get('Service\db_yl_themes\WplistCpTable');
    	}
    	return $this->wplistcpTable; 	
    }
    
    public function getWebbannerTable()
    {
    	if (!$this->webbannerTable) {
    		$sm = $this->getServiceLocator();
    		$this->webbannerTable = $sm->get('Service\db_yl_themes\WebbannerTable');
    	}
    	return $this->webbannerTable;
    }    

    public function getPersonthemeTable()
    {
    	if (!$this->personthemeTable) {
    		$sm = $this->getServiceLocator();
    		$this->personthemeTable = $sm->get('Service\db_yl_designer\PersonthemeTable');
    	}
    	return $this->personthemeTable;
    }
    
    public function getPersonthemepreviewTable()
    {
    	if (!$this->personthemepreviewTable) {
    		$sm = $this->getServiceLocator();
    		$this->personthemepreviewTable = $sm->get('Service\db_yl_designer\PersonthemepreviewTable');
    	}
    	return $this->personthemepreviewTable;
    }
    
    public function getChargeTable()
    {
    	if (!$this->chargeTable) {
    		$sm = $this->getServiceLocator();
    		$this->chargeTable = $sm->get('Service\db_yl_themes_records\ChargeTable');
    	}
    	return $this->chargeTable;
    }
    
    public function getProductTable()
	{
		if (!$this->productTable) {
			$sm = $this->getServiceLocator();
			$this->productTable = $sm->get('Service\db_yl_themes\ProductTable');
		}
		return $this->productTable;
	}
	
	public function getCommendTable($mytable)
	{
		if(!$this->commendTable){
			$manageFact = new ManagerFactory();
			$manageFact->createService($this->getServiceLocator());
			$arrMongoConfig = ManagerFactory::$params['androidesk'];
			$dbAdapter = new Adapter($arrMongoConfig);			
			$this->commendTable = new WpcommendTable($dbAdapter, $mytable);	
		}	
		return $this->commendTable;
	}
	
	public function getCateInfoTable()
	{
		if (!$this->cateinfoTable) {
			$sm = $this->getServiceLocator();
			$this->cateinfoTable = $sm->get('Service\db_yl_androidesk\CateInfoTable');
		}
		return $this->cateinfoTable;
	}	
	
	public function getRoleTable()
	{
		if (!$this->roleTable) {
			$sm = $this->getServiceLocator();
			$this->roleTable = $sm->get('Service\webuser\RoleTable');
		}
		return $this->roleTable;
	}
	
	public function getAclTable()
	{
		if (!$this->aclTable) {
			$sm = $this->getServiceLocator();
			$this->aclTable = $sm->get('Service\webuser\AclTable');
		}
		return $this->aclTable;
	}
	
	private function _getSummaryRecord()
	{
		$manageFact = new ManagerFactory();
		$manageFact->createService($this->getServiceLocator());
		$arrMongoConfig = ManagerFactory::$params['mongo']['summary'];
		$this->summaryRecord = new SummaryRecordTable($arrMongoConfig);
	
		return $this->summaryRecord;
	}
	
	//可用事务处理的函数有：addtheme, edit, setpaytheme, addscene, addfont
    public function getAdapter()
    {
       if (!$this->dbAdapter) {
           $manageFact = new ManagerFactory();
           $manageFact->createService($this->getServiceLocator());
           $arrConfig = ManagerFactory::$params['themes'];
//           var_dump(ManagerFactory::$params);  exit();
           $this->dbAdapter = new Adapter($arrConfig);
       }
       return $this->dbAdapter;;
    }
    
	
	public function microtime_format($tag, $time)
	{
			list($usec, $sec) = explode(" ", $time);

			$date = date($tag, $sec - 8*60*60);
			return $date;
	}
}