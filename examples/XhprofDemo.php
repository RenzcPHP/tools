<?php

$obj = new MY_Controller();
echo $obj->demo();


/**
 * 演示在基类控制器层引入 xhprof 性能分析，在基类控制器层实现xhprof代码，就能全局实现对所有页面进行xhprof性能分析
 * Class MY_Controller
 */
class MY_Controller{
    public function __construct()
    {

        $userId = 100;
        //自定义性能分析结果保存文件后缀字符串
        $suffixXhprofFileName = "user_id_".$userId;
        // 自定义性能分析总耗时超过多少秒，则保存性能分析结果到文件
        $recordMinAccessTimeSecond = 3;
        \Burning\Tools\XhprofService::setProfiling($this->whetherRunXhprof());
        \Burning\Tools\XhprofService::startXhprof($suffixXhprofFileName, $recordMinAccessTimeSecond);
    }

    public function demo()
    {
        $sum = 0;
        foreach (range(1, 100) as $num){
            $sum += $num;
        }

        return $sum;
    }

    /**
     * 开启xhprof性能分析，需确定环境是否有安装xhprof
     * 判断是否是192.168.71.141开发环境
     * @return bool true-开启，false-不开启
     */
    protected function whetherRunXhprof()
    {
        // 以指定的概率  100分之1的概率启用 xhprof 性能分析
        return !(rand(1, 100)%99);
    }

    public function __destruct()
    {
        // 结束性能分析并保存分析文件
        \Burning\Tools\XhprofService::endXhprof();
    }
}