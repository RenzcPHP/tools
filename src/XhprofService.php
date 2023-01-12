<?php


namespace Burning\Tools;

/**
 * xhprof性能分析服务类
 * Class XhprofService
 * @package Burning\Tools
 */
class XhprofService
{
    /**
     * 是否进行性能分析  0-禁用； 值为真则启用
     * @var int
     */
    private static $profiling = 0;
    /**
     * 奔驰请求总耗时
     * @var null
     */
    private static $xhprofWaitTimeSecond = null;

    /**
     * 总耗时超过设定的访问时间，则将本次xhprof分析结果保存到文件
     * @var int 默认为0，记录所有分析结果保存到文件。如为3，则表示只保存耗时超过3s的分析结果到文件
     */
    private static $recordMinAccessTimeSecond = 0;

    /**
     * 文件后缀，供自定义分析保存文件后缀，方便扩展
     * @var string
     */
    private static $suffixXhprofFileName = '';

    /**
     * 设置是否开启性能分析
     * @param int $profiling
     */
    public static function setProfiling($profiling = 0)
    {
        // 判断php环境变量 XHPROF_ROOT_PATH 是否有配置，请在php-fpm.conf文件配置如 "env[XHPROF_ROOT_PATH]=/mnt/default/xhprof/"  请配置前确认服务器已安装xhprof
        if (!isset($_SERVER['XHPROF_ROOT_PATH'])){
            // 未配置则不开启性能分析
            return false;
        }

        if (!empty($profiling)){
            static::$profiling = $profiling;
        }
    }

    /**
     * 开始性能分析
     * @param string $suffixXhprofFileName 设定文件后缀
     * @param int $recordMinAccessTimeSecond 设置需要记录分析结果的最小耗时
     */
    public static function startXhprof($suffixXhprofFileName = "", $recordMinAccessTimeSecond = 0)
    {
        if (static::$profiling){
            static::$suffixXhprofFileName = trim($suffixXhprofFileName);
            static::$recordMinAccessTimeSecond = intval($recordMinAccessTimeSecond);

            xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
        }
    }


    /**
     * 结束性能分析
     */
    public static function endXhprof()
    {
        if (empty(static::$profiling)){
            return false;
        }

        $data = xhprof_disable();

        // 获取保存文件名
        $xhprofFilename = self::generateXhprofFileName();

        // 暂只记录生产环境访问时间超过2s的页面
        if (static::$xhprofWaitTimeSecond >= static::$recordMinAccessTimeSecond){
            include_once $_SERVER['XHPROF_ROOT_PATH'] . "xhprof_lib/utils/xhprof_lib.php";
            include_once $_SERVER['XHPROF_ROOT_PATH'] . "xhprof_lib/utils/xhprof_runs.php";
            $x = new \XHProfRuns_Default();

            //print_r($data);die;//此处的打印数据看起来非常不直观，所以需要安装yum install graphviz 图形化界面显示,更直观
            $x->save_run($data, $xhprofFilename);
        }
    }

    /**
     * 生成性能分析保存文件名称
     * @return string 返回文件名称
     */
    protected static function generateXhprofFileName()
    {
        //获取当前路由,以当前路由作为文件名
        //拼接文件名
        $xhprofFilename = '';
        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])){
            $requestUri = explode('?', $_SERVER['REQUEST_URI']);
            $xhprofFilename = "page".str_replace("/", "_", $requestUri[0]).'_'.date('Ymd_His').'_'.rand(1000, 9999);
        }
        if (empty($xhprofFilename)){
            $xhprofFilename = date('YmdHis').'_'.rand(1000, 9999);
        }

        // 微秒
        $xhprofWaitTimeMs = isset($data['main()']['wt'])?$data['main()']['wt']:0;
        // 占用内存 bytes
        $xhprofMemoryUsageBytes = isset($data['main()']['mu'])?$data['main()']['mu']:0;
        $xhprofMemoryUsageMB = round($xhprofMemoryUsageBytes/1024/1024);
        // 秒
        static::$xhprofWaitTimeSecond = round($xhprofWaitTimeMs/1000/1000);

        $xhprofFilename .= "_time_".static::$xhprofWaitTimeSecond."s_Mem_".$xhprofMemoryUsageMB."MB";

        if (!empty(static::$suffixXhprofFileName)){
            $xhprofFilename .= "_".str_replace(["."," ", "/", "|"], "_", static::$suffixXhprofFileName);
        }
        return $xhprofFilename;
    }

}