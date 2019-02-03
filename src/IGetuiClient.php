<?php
namespace IGetui;

use IGetui\igetui\IGtSingleMessage;
use IGetui\igetui\IGtTarget;
use IGetui\exception\RequestException;
use IGetui\igetui\IGtListMessage;
use IGetui\igetui\IGtAppMessage;
use IGetui\igetui\utils\AppConditions;
use IGetui\igetui\template\IGtTransmissionTemplate;
use IGetui\igetui\IGtAPNPayload;
use IGetui\igetui\DictionaryAlertMsg;

class IGetuiClient
{

    private $host;

    private $appKey;

    private $appId;

    private $masterSecret;

    private $cid;

    private $deviceToken;

    private $igt;

    public function __construct($host, $appKey, $appId, $masterSecret)
    {
        // 赋值
        $this->host = $host;
        $this->appKey = $appKey;
        $this->appId = $appId;
        $this->masterSecret = $masterSecret;
        $this->cid = '';
        $this->deviceToken = '';
        $this->igt = new IGetui($this->host, $this->appKey, $this->masterSecret);
    }

    public function pushToUser($uid)
    {
        $accountId = "uid$uid";
        // 透传消息模板
        $msg = array();
        $msg['title'] = '世间安得双全法，不负科研不负患者？';
        $msg['description'] = '临床医生做科研一直以来都饱受争议，一方面是临床医生越来越繁重的工作，另一方面是以论文数量来评价工作能力的“一刀切”的硬性指标。发SCI还是不发SCI，这已然不是个选择题。';
        $msg['url'] = 'http://www.apple.com.cn';
        $template = $this->IGtTransmissionTemplateDemo($msg);

        // 个推信息体
        $message = new IGtSingleMessage();
        // 是否离线
        $message->set_isOffline(true);
        // 离线时间
        $message->set_offlineExpireTime(3600 * 12 * 1000);
        // 设置推送消息类型
        $message->set_data($template);
        // 设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        $message->set_PushNetWorkType(0);

        // 接收方
        $target = new IGtTarget();
        $target->set_appId($this->appId);
        $target->set_alias($accountId);

        try {
            $rep = $this->igt->pushMessageToSingle($message, $target);
        } catch (RequestException $e) {
            $requstId = e . getRequestId();
            // 失败时重发
            $rep = $this->igt->pushMessageToSingle($message, $target, $requstId);
        }
    }

    // 单推接口案例
    function pushMessageToSingle($msg, $cid)
    {
        // 透传消息模板
        $template = $this->IGtTransmissionTemplateDemo($msg);

        // 个推信息体
        $message = new IGtSingleMessage();
        // 是否离线
        $message->set_isOffline(false);
        // 离线时间
        $message->set_offlineExpireTime(3600 * 12 * 1000);
        // 设置推送消息类型
        $message->set_data($template);
        // 设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        $message->set_PushNetWorkType(0);

        // 接收方
        $target = new IGtTarget();
        $target->set_appId($this->appId);
        $target->set_clientId($cid);
        // $target->set_alias($toUid);

        try {
            $rep = $this->igt->pushMessageToSingle($message, $target);
        } catch (RequestException $e) {
            $requstId = e . getRequestId();
            // 失败时重发
            $rep = $this->igt->pushMessageToSingle($message, $target, $requstId);
        }
    }

    public function queryCID($alias)
    {
        $rep = $this->igt->queryClientId($this->appId, $alias);
    }

    // 多推接口案例
    function pushMessageToList($msg, $toUids)
    {
        putenv("needDetails=true");
        $template = $this->IGtTransmissionTemplateDemo($msg);
        // 个推信息体
        $message = new IGtListMessage();

        // 是否离线
        $message->set_isOffline(true);
        // 离线时间
        $message->set_offlineExpireTime(3600 * 12 * 1000);
        // 设置推送消息类型
        $message->set_data($template);
        // 设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        $message->set_PushNetWorkType(0);
        $contentId = $this->igt->getContentId($message);
        // 根据TaskId设置组名，支持下划线，中文，英文，数字
        // $contentId = $this->igt->getContentId($message,"toList任务别名功能");

        // 接收方
        foreach ($toUids as $k => $v) {
            $target[$k] = new IGtTarget();
            $target[$k]->set_appId($this->appId);
            $target[$k]->set_alias($v);
            $targetList[] = $target[$k];
        }

        $rep = $this->igt->pushMessageToList($contentId, $targetList);
    }

    function pushMessageToApp($msg)
    {
        // 定义透传模板，设置透传内容，和收到消息是否立即启动启用
        $template = $this->IGtTransmissionTemplateDemo($msg);
        // 定义"AppMessage"类型消息对象，设置消息内容模板、发送的目标App列表、是否支持离线发送、以及离线消息有效期(单位毫秒)
        $message = new IGtAppMessage();
        $message->set_isOffline(true);
        // 离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message->set_offlineExpireTime(10 * 60 * 1000);
        $message->set_data($template);

        $appIdList = array(
            $this->appId
        );
        $phoneTypeList = array(
            'IOS',
            'ANDROID'
        );
        $provinceList = array(
            '廊坊'
        );
        $tagList = array(
            'haha'
        );

        $cdt = new AppConditions();
        $cdt->addCondition(AppConditions::PHONE_TYPE, $phoneTypeList);
        $cdt->addCondition(AppConditions::REGION, $provinceList);
        $cdt->addCondition(AppConditions::TAG, $tagList);

        $message->set_appIdList($appIdList);
        $message->set_conditions($cdt);

        $rep = $this->igt->pushMessageToApp($message);
    }

    // 消息模板
    public function IGtTransmissionTemplateDemo($msgObject)
    {
        $template = new IGtTransmissionTemplate();
        // 应用appid
        $template->set_appId($this->appId);
        // 应用appkey
        $template->set_appkey($this->appKey);
        // 透传消息类型
        $template->set_transmissionType(1);
        // 透传内容
        $template->set_transmissionContent(json_encode($msgObject));
        // 设置ANDROID客户端在此时间区间内展示消息
        // $template->set_duration(BEGINTIME,ENDTIME);

        // APN高级推送
        $apn = new IGtAPNPayload();
        $alertmsg = new DictionaryAlertMsg();
        $alertmsg->body = $msgObject['description'];
        // $alertmsg->actionLocKey = "ActionLockey";
        // $alertmsg->locKey = "LocKey";
        // $alertmsg->locArgs = array(
        // "locargs"
        // );
        // $alertmsg->launchImage = "launchimage";
        // iOS8.2 支持
        $alertmsg->title = $msgObject['title'];
        // $alertmsg->titleLocKey = "TitleLocKey";
        // $alertmsg->titleLocArgs = array(
        // "TitleLocArg"
        // );

        $apn->alertMsg = $alertmsg;
        $apn->badge = 1;
        $apn->sound = "";
        $apn->add_customMsg("title", $msgObject['title']);
        $apn->add_customMsg("description", $msgObject['description']);
        $apn->add_customMsg("url", $msgObject['url']);

        $apn->contentAvailable = 1;
        $apn->category = "ACTIONABLE";
        $template->set_apnInfo($apn);

        return $template;
    }
}