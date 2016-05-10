<?php

/**
 * 主题列表接口
 *
 * @author 谢建平 <jianping_xie@aliyun.com>
 * @copyright 2012-2014 Appbyme
 */

if (!defined('IN_DISCUZ') || !defined('IN_APPBYME')) {
    exit('Access Denied');
}
// Mobcent::setErrors();
class TopicListAction extends MobcentAction {

    public function run($boardId=0, $page=1, $pageSize=10, $sortby='all', $filterType='', $filterId=0, $isImageList=0, $topOrder=0) {
        switch ($boardId) {
            case -1: $sortby = 'new'; $boardId = 0; break;
            case -2: $sortby = 'marrow'; $boardId = 0; break;
            case -3: $sortby = 'photo'; $boardId = 0; break;
        }

        $sortby == '' && $sortby = 'all';
        $sortMaps = array(
            'publish' => 'new',
            'essence' => 'marrow',
            'top' => 'top',
            'new' => 'new',
            'marrow' => 'marrow',
            'all' => 'all',
            'photo' => 'photo',
        );
        $sort = $sortby;
        $sort =  isset($sortMaps[$sort]) ? $sortMaps[$sort] : '';

        $fid = (int)$boardId;

        global $_G;
        $key = CacheUtils::getTopicListKey(array($fid, $_G['groupid'], $page, $pageSize, $sort, $filterType, $filterId, $isImageList, $topOrder));

        $this->runWithCache($key, array('fid' => $fid, 'page' => $page, 'pageSize' => $pageSize, 'sort' => $sort, 'filterType' => $filterType, 'filterId' => $filterId, 'isImageList' => $isImageList, 'topOrder' => $topOrder));
        // Mobcent::dumpSql();
    }

    protected function runWithCache($key, $params=array()) {
        $page = $params['page'];
        $fid = $params['fid'];

        $res = array();

        $cache = $this->getCacheInfo();
        if (!$cache['enable'] || ($res = Yii::app()->cache->get($key)) === false) {
            // var_dump('no cache');
            if ($params['sort'] == 'photo') {
                $_GET = array_merge($_GET, $params);
                ob_start();
                $this->getController()->forward('forum/photogallery', false);
                $res = WebUtils::jsonDecode(ob_get_clean());
                $res = WebUtils::outputWebApi($res, 'utf-8', false);
            } else {
                $res = WebUtils::outputWebApi($this->getResult($params), '', false);
            }
            if ($page == 1) {
                if ($fid > 0 && $sort != 'top') {
                    $sql = '
                        SELECT lastpost
                        FROM %t
                        WHERE fid=%d
                        ';
                    $params = array('forum_forum', $fid);
                } else {
                    $sql = '
                        SELECT MAX(dateline)
                        FROM %t
                        ';
                    $params = array('forum_post');
                }
                $dep = new DiscuzDbCacheDependency($sql, $params);
                if ($cache['enable']) {
                    Yii::app()->cache->set($key, $res, $cache['expire'], $dep);
                }
            }
        }

        echo $res;
    }

    protected function getCacheInfo() {
        $cacheInfo = array('enable' => 1, 'expire' => DAY_SECONDS * 1);

        if (($cache = WebUtils::getDzPluginAppbymeAppConfig('cache_topiclist')) > 0) {
            $cacheInfo['expire'] = $cache;
        } else {
            $cacheInfo['enable'] = 0;
        }

        return $cacheInfo;
    }

    protected function getResult($params=array()) {
        extract($params);

        $res = WebUtils::initWebApiArray_oldVersion();

        if ($fid != 0) {

            ForumUtils::initForum($fid);

            // check permisson
            global $_G;
            if (empty($_G['forum']['fid'])) {
                return $this->_makeErrorInfo($res, 'forum_nonexistence');
            }
            if($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm']) && !$_G['forum']['allowview']) {
                $msg = mobcent_showmessagenoperm('viewperm', $_G['fid'], $_G['forum']['formulaperm']);
                return $this->_makeErrorInfo($res, $msg['message'], $msg['params']);
            } elseif ($_G['forum']['formulaperm']) {
                $msg = mobcent_formulaperm($_G['forum']['formulaperm']);
                if ($msg['message'] != '') {
                    return $this->_makeErrorInfo($res, $msg['message'], $msg['params']);
                }
            }

            if($_G['forum']['password']) {
                if($_GET['action'] == 'pwverify') {
                    if($_GET['pw'] != $_G['forum']['password']) {
                        showmessage('forum_passwd_incorrect', NULL);
                    } else {
                        dsetcookie('fidpw'.$_G['fid'], $_GET['pw']);
                        showmessage('forum_passwd_correct', "forum.php?mod=forumdisplay&fid=$_G[fid]");
                    }
                // } elseif($_G['forum']['password'] != $_G['cookie']['fidpw'.$_G['fid']]) {
                } else {
                    // include template('forum/forumdisplay_passwd');
                    // exit();
                    return $this->_makeErrorInfo($res, 'mobcent_forum_passwd');
                }
            }

            if($_G['forum']['price'] && !$_G['forum']['ismoderator']) {
                $membercredits = C::t('common_member_forum_buylog')->get_credits($_G['uid'], $_G['fid']);
                $paycredits = $_G['forum']['price'] - $membercredits;
                if($paycredits > 0) {
                    // if($_GET['action'] == 'paysubmit') {
                    //     updatemembercount($_G['uid'], array($_G['setting']['creditstransextra'][1] => -$paycredits), 1, 'FCP', $_G['fid']);
                    //     C::t('common_member_forum_buylog')->update_credits($_G['uid'], $_G['fid'], $_G['forum']['price']);
                    //     showmessage('forum_pay_correct', "forum.php?mod=forumdisplay&fid=$_G[fid]");
                    // } else {
                        if(getuserprofile('extcredits'.$_G['setting']['creditstransextra'][1]) < $paycredits) {
                            return $this->makeErrorInfo($res, lang('message', 'forum_pay_incorrect', array('paycredits' => $paycredits, 'credits' => $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][1]]['unit'].$_G['setting']['extcredits'][$_G['setting']['creditstransextra'][1]]['title'], 'title' => $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][1]]['title'])));
                        } else {
                            return $this->makeErrorInfo($res, 'forum_pay_incorrect_paying', array('{paycredits}' => $paycredits, '{credits}' => $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][1]]['unit'].$_G['setting']['extcredits'][$_G['setting']['creditstransextra'][1]]['title'], 'title' => $_G['setting']['extcredits'][$_G['setting']['creditstransextra'][1]]['title']));
                            // include template('forum/forumdisplay_pay');
                            // exit();
                        }
                    // }
                }
            }
        }

        $res['newTopicPanel'] = $this->_getNewTopicPanel();

        $topicClassfications = $this->_getTopicClassificationInfos($fid);
        $res['classificationTop_list'] = $topicClassfications['sorts'];
        $res['classificationType_list'] = $topicClassfications['types'];
        $res['isOnlyTopicType'] = $topicClassfications['requireTypes'] ? 1 : 0;

        // 获取公告列表
        $hasAnnouncements = $fid != 0 && $page == 1;
        $res['anno_list'] =  !$hasAnnouncements ? array() : $this->_getAnnouncementList($sort);

        $topicInfos = $this->_getTopicInfos($fid, $page, $pageSize, $sort, $filterType, $filterId, $isImageList, $topOrder);
        $list = $topicInfos['list'];
        $topTopicList = $topicInfos['topTopicList'];
        $count = $topicInfos['count'];

        $res['forumInfo'] = $this->_getForumInfo($fid);
        $res['topTopicList'] = $topTopicList;
        $res['list'] = $list;
        $res = array_merge($res, WebUtils::getWebApiArrayWithPage_oldVersion($page, $pageSize, $count));
        return $res;
    }

    private function _makeErrorInfo($res, $message, $params=array()) {
        return WebUtils::makeErrorInfo_oldVersion($res, $message, $params);
    }

    // 获取主题分类，分类信息 相关的
    private function _getTopicClassificationInfos($fid) {
        return ForumUtils::getTopicClassificationInfos(0);
    }

    // 获取公告列表
    private function _getAnnouncementList($sort) {
        $announcementShow = WebUtils::getDzPluginAppbymeAppConfig('forum_announcement_show');
        $announcementShow = dunserialize($announcementShow);
        !is_array($announcementShow) && $announcementShow = array();

        $sortMaps = array('new' => 2, 'marrow' => 3, 'top' => 4);
        $index = isset($sortMaps[$sort]) ? $sortMaps[$sort] : 1;
        if (in_array(0, $announcementShow) || !in_array($index, $announcementShow)) {
            return array();
        }

        $list = array();
        $announcements = ForumUtils::getAnnouncementList();
        foreach ($announcements as $announcement) {
            $list[] = array(
                'announce_id' => $announcement['id'],
                'author' => $announcement['author'],
                'board_id' => 0,
                'forum_id' => 0,
                'start_date' => $announcement['starttime'] . '000',
                'title' => WebUtils::emptyHtml($announcement['subject']),
                'redirect' => $announcement['type'] == DzForumAnnouncement::TYPE_URL ? $announcement['message'] : '',
            );
        }
        return $list;
    }

    private function _getTopicInfos($fid, $page, $pageSize, $sort, $filterType='', $filterId='', $isImageList='', $topOrder='') {

        $infos = array('count' => 0, 'list' => array(), 'topTopicList' => array());

        // global $_G;
        // $forum = $_G['forum'];

        $count = $this->_getTopicCount($fid, $sort, $filterType, $filterId);
        $topicList = $this->_getTopicList($fid, $page, $pageSize, $sort, $filterType, $filterId);

        $topTopicList = $topTopicListTmp = $topList = array();
        if ($page == 1 && $topOrder != 0) {
            $status = $_GET['topOrder'];
            $fids = array();
            switch ($status) {
                case DzForumThread::DISPLAY_ORDER_GLOBAL :
                    $fids = ForumUtils::getForumShowFids();
                    break;
                case DzForumThread::DISPLAY_ORDER_GROUP :
                    $fids = DzForumForum::getFidsByGid(DzForumForum::getGidByFid($fid));
                    break;
                case DzForumThread::DISPLAY_ORDER_FORUM :
                    $fids = array($fid);
                    break;
                default:
                    break;
            }
            $topTopicListTmp = DzForumThread::getByFidData($fids, 0, 3, array('topic_stick' => array($status)));
            foreach ($topTopicListTmp as $top) {
                $topList['id'] = (int)$top['tid'];
                $topList['title'] = (string)$top['subject'];
                $topTopicList[] = $topList;
            }
        }
            // $topTopicList = $this->_getListField($topTopicList);

        $list = $this->_getListField($topicList);

        $infos['count'] = $count;
        $infos['list'] = $list;
        $infos['topTopicList'] = $topTopicList;
        return $infos;
    }

    private function _getNewTopicPanel() {
        return ForumUtils::getNewTopicPanel();
    }

    private function _getTopicCount($fid, $sort, $filterType, $filterId) {
        return ForumUtils::getTopicCount($fid, $this->_transParams($sort, $filterType, $filterId));
    }

    private function _getTopicList($fid, $page, $pageSize, $sort, $filterType, $filterId) {
        return ForumUtils::getTopicList($fid, $page, $pageSize, $this->_transParams($sort, $filterType, $filterId));
    }

    private function _transParams($sort, $filterType, $filterId) {
        $sortMaps = array(
            'publish' => 'new',
            'essence' => 'marrow',
            'top' => 'top',
            'new' => 'new',
            'marrow' => 'marrow',
            'all' => 'all'
        );
        $params = array();
        if ($filterType == 'sortid') {
            $params['topic_sortid'] = $filterId;
        } else if ($filterType == 'typeid'){
            $params['topic_typeid'] = $filterId;
        }
        if (($sort != '' && isset($sortMaps[$sort]))) {
            $params['sort'] = $sortMaps[$sort];
        }
        return  $params;
    }

    private function _getListField($topicList) {
        global $_G;
        $forum = $_G['forum'];

        $isImageList = isset($_GET['isImageList']) ? $_GET['isImageList'] : 0;

        $list = array();
        foreach($topicList as $topic) {
            // 该主题是由别的版块移动过来的
            $isTopicMoved = false;
            $movedTitle = '';
            if($topic['closed'] > 1) {
                $movedTitle = WebUtils::t('移动: ');
                $isTopicMoved = true;
                $topic['tid'] = $topic['closed'];
            }

            $tid = (int)$topic['tid'];
            $topicFid = (int)$topic['fid'];

            // 主题分类标题
            $typeTitle = '';
            if (WebUtils::getDzPluginAppbymeAppConfig('forum_allow_topictype_prefix')) {
                if (isset($forum['threadtypes']['prefix']) &&
                    $forum['threadtypes']['prefix'] == 1 &&
                    isset($forum['threadtypes']['types'][$topic['typeid']])) {
                    $typeTitle = '['.$forum['threadtypes']['types'][$topic['typeid']].']';
                }
            }
            // 分类信息标题
            $sortTitle = '';
            if (WebUtils::getDzPluginAppbymeAppConfig('forum_allow_topicsort_prefix')) {
                if (!empty($forum['threadsorts']['prefix']) &&
                    isset($forum['threadsorts']['types'][$topic['sortid']])) {
                    $sortTitle = '['.$forum['threadsorts']['types'][$topic['sortid']].']';
                }
            }
            $isTopicMoved && $typeTitle = $sortTitle = '';

            $topicInfo['board_id'] = $topicFid;
            $topicInfo['board_name'] = $fid != 0 ? $forum['name'] : ForumUtils::getForumName($topicFid);
            $topicInfo['board_name'] = WebUtils::emptyHtml($topicInfo['board_name']);

            $topicInfo['topic_id'] = $tid;
            $topicInfo['type'] = ForumUtils::getTopicType($topic);
            $topicInfo['title'] = $movedTitle . $typeTitle . $sortTitle . $topic['subject'];
            $topicInfo['title'] = WebUtils::emptyHtml($topicInfo['title']);

            // 修正帖子查看数
            if (isset($_G['forum_thread']['views']) &&
                $_G['forum_thread']['tid'] == $topic['tid'] &&
                $_G['forum_thread']['views'] > $topic['views']) {
                $topic['views'] = $_G['forum_thread']['views'];
            }

            $topicInfo['user_id'] = (int)$topic['authorid'];
            $topicInfo['user_nick_name'] = $topic['author'];
            $topicInfo['last_reply_date'] = $topic['lastpost'] . '000';
            $topicInfo['vote'] = ForumUtils::isVoteTopic($topic) ? 1 : 0;
            $topicInfo['hot'] = ForumUtils::isHotTopic($topic) ? 1 : 0;
            $topicInfo['hits'] = (int)$topic['views'];
            $topicInfo['replies'] = (int)$topic['replies'];
            $topicInfo['essence'] = ForumUtils::isMarrowTopic($topic) ? 1 : 0;
            $topicInfo['top'] = ForumUtils::isTopTopic($topic) ? 1 : 0;
            $topicInfo['status'] = (int)$topic['status'];

            $cache = Yii::app()->params['mobcent']['cache']['topicSummary'];
            $key = sprintf('mobcentTopicSummary_%s_%s_%s', $tid, $_G['groupid'], $isImageList);
            if (!$cache['enable'] || ($topicSummary = Yii::app()->cache->get($key)) === false) {
                $topicSummary = ForumUtils::getTopicSummary($tid, 'forum', true, array('imageList' => $isImageList, 'imageListLen' => 9, 'imageListThumb' => 1));
                if ($cache['enable']) {
                    Yii::app()->cache->set($key, $topicSummary, $cache['expire']);
                }
            }

            $topicInfo['subject'] = $topicSummary['msg'];
            // $topicInfo['pic_path'] = ImageUtils::getThumbImage($topicSummary['image']);
            $tempTopicInfo = ImageUtils::getThumbImageEx($topicSummary['image'], 15, true, false);
            $topicInfo['pic_path'] = $tempTopicInfo['image'];
            $topicInfo['ratio'] = $tempTopicInfo['ratio'];
            $topicInfo['userAvatar'] = UserUtils::getUserAvatar($topic['authorid']);
            $topicInfo['gender'] = (int)UserUtils::getUserGender($topic['authorid']);
            $topicInfo['recommendAdd'] = (int)ForumUtils::getRecommendAdd($tid);
            $topicInfo['isHasRecommendAdd'] = ForumUtils::isHasRecommendAdd($tid);
            $topicInfo['imageList'] = (array)$topicSummary['imageList'];
            $topicInfo['sourceWebUrl'] = (string)ForumUtils::getSourceWebUrl($tid, 'topic');
            $tmpList = 
            $list[] = $topicInfo;
        }
        return $list;
    }

    private function _getForumInfo($fid) {
        global $_G;
        $forum = $_G['forum'];

        require_once libfile('function/forumlist');
        $forumImage = get_forumimg($forum['icon']);
        $forumImage = (string)WebUtils::getHttpFileName($forumImage);

        $forumInfo = array();
        $forumInfo['id'] = (int)$fid;
        $forumInfo['title'] = $fid != 0 ? (string)WebUtils::emptyHtml($forum['name']) : '';
        $forumInfo['description'] = (string)WebUtils::emptyHtml($forum['description']);
        $forumInfo['icon'] = (string)$forumImage;
        return $forumInfo;
    }

}
