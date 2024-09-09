<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\CryptoRecon;


class CryproReconciliationController extends Controller
{
    /**
     * {@inheritdoc}
     */
    
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        //static file
        $csvToRead = fopen('3rd_party_transactions_export.csv', 'r');
        while (! feof($csvToRead)) {
            $csvArray[] = fgetcsv($csvToRead, 1000, ',');
        }
        fclose($csvToRead);
        
        foreach ($csvArray as $key => $record)
        {
            if($key == 0)
                continue;

            $transactionHash = $record[1];
            $creproDbRecord = CryptoRecon::find()->where(['tx_hash' => $transactionHash])->one();
            if(!$creproDbRecord)
            {
              $record['Match_Result'] = 'No Match';
              $record['Data_Diff'] = 'No Match';
              $result[]=$record;
              continue;
            }
            
            $record = $this->validateTransaction($creproDbRecord,$record);
            $result[]=$record;
        }
        
        $fileName = 'result.csv';
        $this->saveFile($result,$fileName);
        $csvContent = \file_get_contents($fileName);
    
        // Set response headers
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/csv');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="result.csv"');
    
        // Output CSV content
        return $csvContent;
    }


    private function validateTransaction($originalTransaction,$newTransaction)
    {
        $matching = [];
        if($originalTransaction['cryptocurrency'] != $newTransaction[0] )
        {
            $matching[] = 'cryptocurrency';
        }
        if($originalTransaction['receiver_address'] != $newTransaction[2] )
        {
            $matching[] = 'receiver_address';
        }
        if($originalTransaction['amount'] != $newTransaction[3] )
        {
            $matching[] = 'amount';
        }
        if(!empty($newTransaction[4]) )
        {
            $originalTransactionDate = new \DateTime($originalTransaction['date']);
            $recordTransactionDate = new \DateTime($newTransaction[4]);
            $diff = $originalTransactionDate->getTimestamp() - $recordTransactionDate->getTimestamp();
            if(abs($diff) > 4*60 ){
                $matching[] = 'Date';
            }
        }else {
            $matching[] = 'Date';
        }

        $newTransaction['Match_Result'] = empty($matching)?'Full Match':'Partial Match:';
        $newTransaction['Data_Diff'] = !empty($matching)?implode(',',$matching) :"None";
        return $newTransaction;
    }

    private function saveFile($data ,$fileNamse)
    {
        $fp = fopen($fileNamse, 'w');
        fputcsv($fp, ['Cryptocurrency','Transaction Hash','Receiver Address','Amount','Date Done','Match Result','Data Diff']);
        foreach ($data as $fields) 
        {
            fputcsv($fp,$fields);
        }        
        fclose($fp);
    }
}
