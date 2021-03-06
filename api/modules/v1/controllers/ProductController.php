<?php

namespace api\modules\v1\controllers;

use Yii;

use common\models\mysql\Product;
use common\models\mysql\ProductDescription;
use common\models\mysql\ProductProperty;
use common\models\mysql\Language;
use common\models\mysql\Category;
/**
* author zhoushuo <z_s106@126.com>
*/
class ProductController extends ApiController
{
	const NEW_PRODUCT = 'new_product_';
	const HOT_PRODUCT = 'hot_product_';
	const LIST_PRODUCT = 'list_product_';
	const COUNT_PRODUCT = 'count_product';
	const CATEGORY_LIST_PRODUCT = 'category_list_product_';
	
	public $modelClass = 'common\models\mysql\Product';

	/**
	*@param $lang 语言
	*@return array 产品list 
	*/
	public function actionList($lang = 'en',$page = 1,$pageSize = 12)
	{
	    if(empty($lang))
	        $lang = 'en';
		//查询是否存在缓存
		if(Yii::$app->cache->exists(self::LIST_PRODUCT.$page.'_'.$lang) && Yii::$app->params['cache'] === true)
			return Yii::$app->cache->get(self::LIST_PRODUCT.$page.'_'.$lang);
		else
		{
			$lang_id = Language::getIdByShortName($lang);
			$from = ($page-1)*$pageSize;
			$count = ProductDescription::find()->select(['id'])->where(['language_id' => $lang_id])->count();
			$data = ProductDescription::find()->select(['p.id','p.image_url','p.name as searchName','d.display_name'])->from('product_description d')
                ->leftJoin('product p','p.id=d.product_id')->where(['d.language_id'=>$lang_id,'p.status'=>1])
                ->orderBy('create_at DESC')->offset($from)->limit($pageSize)->asArray()->all();
			if(empty($data))
				$info = [
					'msg' => 'error',
					'lang' => $lang,
					'message' => '不存在该语言对应的产品信息',
				];
			else
			{
				foreach ($data as &$value) {
                    $value['image_url'] = $this->getFirstImage($value['image_url']);
				}
				$info = [
					'msg' => 'ok',
					'lang' => $lang,
					'page' => $page,
					'totalCount' => $count,
					'list' => $data,
				];
			}
			
			if(Yii::$app->params['cache'])
				Yii::$app->cache->set(self::LIST_PRODUCT.$page.'_'.$lang,$info);
			return $info;
		}
	}
	//产品详细信息
	public function actionView($id,$lang = 'en')
	{
	    if(empty($lang))
	        $lang = 'en';
		$lang_id = Language::getIdByShortName($lang); 
		$data = ProductDescription::find()->select(['p.image_url','p.color','d.display_name as name','d.short_info','d.content'])
            ->from('product_description d')->leftJoin('product p','p.id=d.product_id')
            ->where(['d.language_id'=>$lang_id,'p.status'=>1,'p.id'=>$id])->asArray()->one();
		$property = ProductProperty::find()->select(['d.property_name','d.property_value'])->from('product_property d')
            ->leftJoin('product p','p.id=d.product_id')->where(['d.language_id'=>$lang_id,'p.status'=>1,'p.id'=>$id])->asArray()->all();
		if(!empty($data))
		{
			if(!empty($data['image_url']))
			{
				$data['image_url'] = $this->explodeUrl($data['image_url']);
			}
			if(!empty($data['color']))
            {
                $data['color'] = $this->explodeUrl($data['color']);
            }
			if($pos = strpos($data['short_info'], '#'))
            {
                $data['short_info'] = explode('#', $data['short_info']);
            }
		}
		return [
			'msg' => 'ok',
			'lang' => $lang,
			'info' => $data,
            'property' => $property,
		];
	}
	// 将string类型多组图片地址 转化为数组
    private function explodeUrl($s_url)
    {
         $arr = [];
        if(($pos = strpos($s_url,',')) > 1)
        {
            $arr = explode(',',$s_url);
            foreach ($arr as &$value)
                $value = Yii::$app->params['domain'].$value;
        }
        else
            $arr[0] = Yii::$app->params['domain'].$s_url;
        return $arr;
     }
     // 获取产品第一张图
    private function getFirstImage($str)
    {
        if(($pos = strpos($str,',')) > 1)
        {
            return Yii::$app->params['domain'].substr($str,0,$pos);
        }
        else
        {
            return Yii::$app->params['domain'].$str;
        }
    }
	// 热门产品  查询8条
	public function actionHotProduct($lang = 'en',$page = 1, $pageSize = 8)
	{
	    if(empty($lang))
	        $lang = 'en';
		if(Yii::$app->cache->exists(self::HOT_PRODUCT.$lang))
			return Yii::$app->cache->get(self::HOT_PRODUCT.$lang);
		else
		{
			$lang_id = Language::getIdByShortName($lang);
            $from = ($page-1)*$pageSize;
            $count = ProductDescription::find()->select(['id'])->where(['language_id' => $lang_id])->count();
			$data = ProductDescription::find()->select('p.id,p.name as searchName,d.display_name as name,p.image_url,p.create_at')->from('product_description d')
                ->leftJoin('product p','p.id=d.product_id')->where(['d.language_id'=>$lang_id,'p.status'=>1])->orderBy('visitor desc')
                ->offset($from)->limit($pageSize)->asArray()->all();
			if(empty($data))
				$info = [
					'msg' => 'error',
					'lang' => $lang,
					'message' => '不存在该语言对应的产品信息',
				];	
			else
            {
                foreach ($data as &$value)
                {
                    $value['image_url'] = $this->getFirstImage($value['image_url']);
                }
				$info = [
					'msg' => 'ok',
					'lang' => $lang,
                    'totalCount' => $count,
					'list' => $data,
				];
				if(Yii::$app->params['cache'] === true)
					Yii::$app->cache->set(self::HOT_PRODUCT.$lang,$info);
			}
			//缓存数据
			return $info;
		}
	}

	//新产品 查询8条
	public function actionNewProduct($lang = 'en',$page = 1,$pageSize = 8)
	{
	    if(empty($lang))
	        $lang = 'en';
		if(Yii::$app->cache->exists(self::NEW_PRODUCT.$lang))
			return Yii::$app->cache->get(self::NEW_PRODUCT.$lang);
		else
		{
			$lang_id = Language::getIdByShortName($lang);
            $from = ($page-1) * $pageSize;
            $count = ProductDescription::find()->select(['id'])->where(['language_id' => $lang_id])->count();
			$data = ProductDescription::find()->select('p.id,p.name as searchName,d.display_name as name,p.image_url,p.create_at')->from('product_description d')
                ->leftJoin('product p','p.id=d.product_id')->where(['d.language_id'=>$lang_id,'p.status'=>1])->orderBy('create_at desc')
                ->offset($from)->limit($pageSize)->asArray()->all();
			if(empty($data))
				$info = [
					'msg' => 'error',
					'lang' => $lang,
					'message' => '不存在该语言对应的产品信息',
				];
			else
			{
                foreach ($data as &$value)
                    $value['image_url'] = $this->getFirstImage($value['image_url']);
				$info = [
					'msg' => 'ok',
					'lang' => $lang,
                    'totalCount' => $count,
					'list' => $data,
				];
			//缓存数据
				if(Yii::$app->params['cache'] === true)
					Yii::$app->cache->set(self::NEW_PRODUCT.$lang,$info);
			}
			return $info;
		}
	}

	//统计产品访问次数
	public function actionVisitor()
	{
		$id = Yii::$app->request->post('product_id');
		if($model = Product::findOne($id))
		{
			$model->visitor += 1;
			$model->save(false);
			return [
				'msg' => 'ok',
				'count' => $model->visitor,
			];
		}
		else
			return [
				'msg' => 'error',
				'message' => '产品不存在',
			]; 
	}
	//产品分类列表
	public function actionCategoryList($lang = 'en',$category_id,$page = 1,$pageSize = 12)
	{
	    if(empty($lang))
	        $lang = 'en';
		//查询是否存在缓存
        if($category_id == 6)
        {
            return $this->actionNewProduct($lang,$page,$pageSize);
        }
        if($category_id == 5)
        {
            return $this->actionHotProduct($lang,$page,$pageSize);
        }
		if(Yii::$app->cache->exists(self::CATEGORY_LIST_PRODUCT.$category_id.'_'.$lang) && Yii::$app->params['cache'] === true)
			return Yii::$app->cache->get(self::CATEGORY_LIST_PRODUCT.$category_id.'_'.$lang);
		else
		{
			$lang_id = Language::getIdByShortName($lang);
			$pid = Category::find()->select(['id'])->where(['pid' => $category_id])->asArray()->column();
			if(!empty($pid))
			    $category_id = $pid;
			$from = ($page-1)*$pageSize;
			$count = $data = ProductDescription::find()->select(['p.id'])->from('product_description d')
                ->leftJoin('product p','p.id=d.product_id')->where(['p.category_id'=>$category_id,'d.language_id'=>$lang_id,'p.status'=>1])->count();
			$data = ProductDescription::find()->select(['p.id','p.image_url','p.name as searchName','d.display_name as name'])->from('product_description d')
                ->leftJoin('product p','p.id=d.product_id')->where(['p.category_id'=>$category_id,'d.language_id'=>$lang_id,'p.status'=>1])
                ->orderBy('p.create_at DESC')->offset($from)->limit($pageSize)->asArray()->all();

			if(empty($data))
				$info = [
					'msg' => 'error',
					'lang' => $lang,
					'message' => '不存在该语言对应的产品信息',
				];
			else
			{
                foreach ($data as &$value)
                    $value['image_url'] = $this->getFirstImage($value['image_url']);
				$info = [
					'msg' => 'ok',
					'lang' => $lang,
					'page' => $page,
					'pageSize' => $pageSize,
					'totalCount' => $count,
					'list' => $data,
				];
				if(Yii::$app->params['cache'] === true)
					Yii::$app->cache->set(self::CATEGORY_LIST_PRODUCT.$category_id.'_'.$lang,$info);
			}
			return $info;
		}
	}
    // 产品搜索
	public function actionSearch($lang = 'en',$keywords,$page = 1,$pageSize = 12)
	{
	    if(empty($lang))
	        $lang = 'en';
		$lang_id = Language::getIdByShortName($lang);
		$from = ($page-1)*$pageSize;
		$count = ProductDescription::find()->select(['id'])->where(['like','display_name',$keywords])->count();
		$data = ProductDescription::find()->select(['p.id','p.image_url','p.name as searchName','d.display_name'])->from('product_description d')
            ->leftJoin('product p','p.id=d.product_id')->where(['d.language_id'=>$lang_id,'p.status'=>1])->
            andWhere(['like','d.display_name',$keywords])->orderBy('p.create_at DESC')->offset($from)->limit($pageSize)->asArray()->all();
		if(empty($data))
			return [
				'msg' => 'error',
				'code' => 500,
				'message' => '未找到相应的信息',
			];
		else
        {
            foreach ($data as &$value)
            {
                $value['image_url'] = $this->getFirstImage($value['image_url']);
            }
            return [
                'msg' => 'ok',
                'code' => 200,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalCount' => $count,
                'list' => $data
            ];
        }

	}

}