<?php

/**
 * 插件配置选项
 * 
 * 此文件为安米默认的配置选项，请不要修改!!!
 * 如果你想按照你的需求修改配置项，请复制一份这个文件到相同目录，并且命名为my_mobcent.php.
 * 然后就可以在my_mobcent.php中，按照说明进行相应的配置.
 * 新建my_mobcent.php文件不会随插件发布更新,请自行维护好！
 * 
 * @author 谢建平 <jianping_xie@aliyun.com>
 */

global $dbConfig;

return array(
    'forum' => array(
        'ffmpegCommand' => '',      // ffmpeg 运行命令
        'uploadPath' => array(
            'remote' => 0,
            'ftp' => array(
                'user' => '',
                'password' => '',
            )
        ),
        'post' => array(
            'summaryLength' => 140,
        ),
    ),
    'user' => array(
        'avatarBigLength' => 200,
        'avatarMidLength' => 120,
        'avatarSmallLength' => 48,
    ),
    'misc' => array(
        'apnsCertfilePath' => MOBCENT_UPLOAD_PATH,
        'apnsCertfileName' => 'appbyme_apns.pem',
    ),
    // 更改缓存配置之后一定要在后台清理一下缓存!!!
    'cache' => array(
        'topicSummary' => array(        // 主题列表中摘要缓存设置项, 建议开启!!!
            'enable' => 1,              // 是否开启, 1为开启,0为关闭    
            'expire' => DAY_SECONDS*1,  // 缓存时间，单位秒
        ),
        'postList' => array(    // 帖子详情列表缓存设置项
            'enable' => 0,      // 是否开启, 1为开启,0为关闭    
            'expire' => MINUTE_SECONDS*1,     // 缓存时间，单位秒
        ),
    ),
    'db' => array(
        'discuz' => $dbConfig['mobcentDiscuz'],
        'discuzUCenter' => $dbConfig['mobcentDiscuzUCenter'], 
    ),
    // 表情映射关系
    'phiz' => array(
        '[泪]' => '00.png',
        '[哈哈]' => '01.png',
        '[抓狂]' => '02.png',
        '[嘻嘻]' => '03.png',
        '[偷笑]' => '04.png',
        '[怒]' => '05.png',
        '[鼓掌]' => '06.png',
        '[心]' => '07.png',
        '[心碎了]' => '08.png',
        '[生病]' => '09.png',
        '[爱你]' => '10.png',
        '[害羞]' => '11.png',
        '[馋嘴]' => '12.png',
        '[可怜]' => '13.png',
        '[晕]' => '14.png',
        '[花心]' => '15.png',
        '[太开心]' => '16.png',
        '[亲亲]' => '17.png',
        '[鄙视]' => '18.png',
        '[呵呵]' => '19.png',
        '[挖鼻屎]' => '20.png',
        '[衰]' => '21.png',
        '[兔子]' => '22.png',
        '[good]' => '23.png',
        '[来]' => '24.png',
        '[威武]' => '25.png',
        '[围观]' => '26.png',
        '[萌]' => '27.png',
        '[送花]' => '28.png',
        '[囧]' => '29.png',
        '[酷]' => '30.png',
        '[糗大了]' => '31.png',
        '[撇嘴]' => '32.png',
        '[发呆]' => '33.png',
        '[汗]' => '34.png',
        '[睡]' => '35.png',
        '[吃惊]' => '36.png',
        '[白眼]' => '37.png',
        '[疑问]' => '38.png',
        '[阴险]' => '39.png',
        '[左哼哼]' => '40.png',
        '[右哼哼]' => '41.png',
        '[敲打]' => '42.png',
        '[委屈]' => '43.png',
        '[嘘]' => '44.png',
        '[吐]' => '45.png',
        '[做鬼脸]' => '46.png',
        '[ByeBye]' => '47.png',
        '[要哭了]' => '48.png',
        '[傲慢]' => '49.png',
        '[月亮]' => '50.png',
        '[太阳]' => '51.png',
        '[耶]' => '52.png',
        '[握手]' => '53.png',
        '[ok]' => '54.png',
        '[饭]' => '55.png',
        '[咖啡]' => '56.png',
        '[礼物]' => '57.png',
        '[猪头]' => '58.png',
        '[抱抱]' => '59.png',
        '[赞]' => '60.png',
        '[Hold]' => '61.png',
        '[神马]' => '62.png',
        '[坑爹]' => '63.png',
        '[有木有]' => '64.png',
        '[谢谢]' => '65.png',
        '[蓝心]' => '66.png',
        '[外星人]' => '67.png',
        '[魔鬼]' => '68.png',
        '[紫心]' => '69.png',
        '[绿心]' => '70.png',
        '[黄心]' => '71.png',
        '[音符]' => '72.png',
        '[闪烁]' => '73.png',
        '[星星]' => '74.png',
        '[雨滴]' => '75.png',
        '[火焰]' => '76.png',
        '[便便]' => '77.png',
        '[踩一脚]' => '78.png',
        '[下雨]' => '79.png',
        '[多云]' => '80.png',
        '[闪电]' => '81.png',
        '[雪花]' => '82.png',
        '[旋风]' => '83.png',
        '[包]' => '84.png',
        '[房子]' => '85.png',
        '[烟花]' => '86.png',
    ),
);