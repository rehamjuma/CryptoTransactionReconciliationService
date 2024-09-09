<?php

namespace app\models;

class CryptoRecon extends \yii\db\ActiveRecord 
{
    public static function tableName()
    {
        return '{{crypto_recon}}';
    }

    public function rules()
    {
        return [
        ];
    }

}
