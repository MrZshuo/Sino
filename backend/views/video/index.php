<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

use common\models\mysql\Nav;
/* @var $this yii\web\View */
/* @var $searchModel common\models\mysql\ContentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', '视频');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="content-index">

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', '上传视频'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php Pjax::begin(); ?>    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn','header' => '序号'],
            // 'nav_name',
            [
                'attribute' => 'video_show',
                'format' => [
                    'image',
                    ['width'=>'80','height'=>'80']
                ],
                'value' => function($model)
                {
                    return Yii::$app->params['domain'].$model->video_show;
                }
            ],
            [
                'attribute' => 'content_title',
                'value' => function($model)
                {
                    return mb_strlen($model->content_title,'utf-8') > 20 ? mb_substr($model->content_title, 0, 20, 'utf-8').'...' : $model->content_title;
                }
            ],
            'content_url:url',
            'create_at',
            // 'update_at',
            // 'author',
            // 'status',

            ['class' => 'yii\grid\ActionColumn','header' => '操作','template' =>'{update} {delete}  {content-description}','buttons' => [
                'content-description' => function($url,$model,$key)
                {
                    $options = [
                        'title' => Yii::t('app','添加详情'),
                        'aria-label' => Yii::t('app','添加详情'),
                    ];
                    return Html::a('<i class="fa fa-pencil-square-o" aria-hidden="true"></i>',Url::to(['/content-description/create','id'=>$model->id]),$options);
                }
            ],
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?></div>
