<?php
namespace backend\models;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use yii\base\Exception;
use yii\helpers\FileHelper;

/**
* author zhoushuo <z_s106@126.com>
*/
class Upload extends Model
{
	public $file;

	private $_appendRules;

	public function init()
	{
		parent::init();
		$extensions = Yii::$app->params['webuploader']['baseConfig']['accept']['extensions'];
		$this->_appendRules = [
			['file','file','extensions' => $extensions]
		];
	}
	public function rules()
	{
		$baseRules = [];
		return array_merge($baseRules,$this->_appendRules);

	}

	 public function upImage ()
    {
        // $model = new static;
        $this->file = UploadedFile::getInstanceByName('file');
        if (!$this->file) {
            return false;
        }
        $relativePath = $successPath = '';
        if ($this->validate()) {
            $relativePath = Yii::getAlias('@backend/web/').Yii::$app->params['imageUploadRelativePath'];
            $successPath = Yii::$app->params['imageUploadSuccessPath'];
            $fileName = $this->file->baseName . '.' . $this->file->extension;
            $filePath = Yii::getAlias('@backend/web/').$successPath . $fileName;
            if (!is_dir($relativePath)) {
                FileHelper::createDirectory($relativePath);    
            }
            if(!file_exists($filePath))
                $this->file->saveAs($relativePath . $fileName);
            return [
                'code' => 0,
                'url' => Yii::$app->params['domain'] . $successPath . $fileName,
                'attachment' => $filePath
            ];
        } else {
            $errors = $this->errors;
            return [
                'code' => 1,
                'msg' => current($errors)[0]
            ];
        }
    }


}