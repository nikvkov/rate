<?php
/**
 * Created by PhpStorm.
 * User: Kovalenko Nikolaj
 * Date: 22.02.2019
 * Time: 16:51
 */

/***
 * Class CurrencyConverter
 * класс для получения курса обмена валюты
 */
class CurrencyConverter
{
    /*ПОЛЯ КЛАССА*/
    //провайдер данных
    var $provider = '';

    //Базовая валюта
    var $or_currency = 'USD';

    //Валюта конвертации
    var $rate_currency = 'UAH';

    //Сумма операции
    var $summ = 1;

    //ставка
    var $rate = 0;

    //сумма операции
    var $result_summ = 0;

    /**
     * CurrencyConverter constructor.
     */
    public function __construct($data)
    {
        $this->provider = isset($data['provider'])?$data['provider']:"";
        $this->or_currency = isset($data['or_cur'])? mb_strtoupper($data['or_cur']):"USD";
        $this->rate_currency = isset($data['rate_cur'])? mb_strtoupper($data['rate_cur']):"UAH";
        $this->summ = isset($data['summ'])?$data['summ']:"";
    }//__construct

    /***
     * get_response
     * отправка запроса на сервер с API курса валют
     * @return mixed - ставка обмена
     */
    private function get_response(){
        try {
            //  инициализация
            $ch = curl_init();
            // отключаем проверку SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // Вернуть запрос
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // установка ссылки
            curl_setopt($ch, CURLOPT_URL, $this->get_current_provider_url());
            // выполнение
            $result = curl_exec($ch);
            // закрываем соединение
            curl_close($ch);

            // декодируем ответ сервера
            $data = json_decode($result, true);

            //получаем ставку обмена
            $rate = $this->get_rate($data);

            return $rate;
        }catch (Exception $ex){
            $this->writeToLog($ex->getTraceAsString().$ex->getMessage());
            return 0;
        }

    }//get_response

    /***
     * get_rate($data)
     * получаем текущую ставку
     * @param $data - результаты запроса на сервер api
     * @return mixed - ставка обмена
     */
    private function get_rate($data){
        try {
            switch ($this->provider) {
                case 'currencyconverterapi':
                    return $data[$this->or_currency . "_" . $this->rate_currency];
                    break;
                case 'currate':
                    $this->writeToLog(json_encode($data));
                    $data = (array)$data['data'];
                    return $data[$this->or_currency . $this->rate_currency];
                    break;
                default:
                    return $data[$this->or_currency . "_" . $this->rate_currency];
                    break;
            }//switch
        }catch (Exception $ex){
            $this->writeToLog($ex->getTraceAsString().$ex->getMessage());
            return 0;
        }
    }//get_rate($data)

    /***
     * get_current_provider_url()
     * получаем ссылку для запроса
     * @return string - сткока url сервера api
     */
    private function get_current_provider_url(){
        switch ($this->provider){
            case 'currencyconverterapi':
                return "https://free.currencyconverterapi.com/api/v6/convert?q=".
                    $this->or_currency."_".$this->rate_currency.
                    "&compact=ultra&apiKey=6d080861b4a881d5d9d0";
                break;
            case 'currate':
                return "https://currate.ru/api/?get=rates&pairs=".
                    $this->or_currency.$this->rate_currency.
                    "&key=52593596484ca1987c8fa17cdeb77311";
                break;
            default:
                return "https://free.currencyconverterapi.com/api/v6/convert?q=".
                    mb_strtoupper($this->or_currency)."_".
                    mb_strtoupper($this->rate_currency).
                    "&compact=ultra&apiKey=6d080861b4a881d5d9d0";
                break;
        }//switch
    }//get_current_provider_url

    /***
     * validate()
     * валидация данных
     * @return bool
     */
    private function validate(){
        return isset($this->provider) &&
            isset($this->or_currency) &&
            isset($this->rate_currency)  &&
            isset($this->summ) &&
            $this->summ>0;
    }//validate()

    /***
     * getResult($err=null)
     * получение результата
     * @param null $err - ошибка
     * @return string - строка результата обработки в формате JSON
     */
    public function getResult($err=null){

         $this->rate = $this->get_response();

         $this->result_summ = $this->summ*$this->rate;

         return json_encode(['rate'=>$this->rate, "result_summ"=>$this->result_summ, 'err'=>$err] );

    }//getResult

    /***
     * writeToLog
     * логгирование
     * @param $data-данные для записи
     * @return bool - удалась ли запись
     */
    function writeToLog($data) {
        $log = "\n------------------------\n";
        $log .= date("Y.m.d G:i:s") . "\n";
        $log .= $data;
        $log .= "\n------------------------\n";
        file_put_contents('debug.log', $log, FILE_APPEND);
        return true;
    }//writeToLog($data)

}//CurrencyConverter