<?php

/**
 * 主题model类
 *
 * @author 谢建平 <xiejianping@mobcent.com>
 * @author HanPengyu
 * @copyright 2012-2014 Appbyme
 */

if (!defined('IN_DISCUZ') || !defined('IN_APPBYME')) {
    exit('Access Denied');
}

class DzForumThread extends DiscuzAR {

    const TYPE_VOTE = 1;
    const TYPE_ACTIVITY = 4;

    const DISPLAY_ORDER_GLOBAL = 3;  // 全局置顶
    const DISPLAY_ORDER_GROUP = 2;   // 分类置顶
    const DISPLAY_ORDER_FORUM = 1;   // 本版置顶
    const DISPLAY_ORDER_NORMAL = 0;  

    const STATUS_VOTE_VOTED = 1;
    const STATUS_VOTE_CAN_VOTE = 2;
    const STATUS_VOTE_NOPERMISSION = 3;
    const STATUS_VOTE_CLOSED = 4;

    const TYPE_ACTIVITY_ACTION_APPLY = 'apply';
    const TYPE_ACTIVITY_ACTION_CANCEL = 'cancel';
    const TYPE_ACTIVITY_ACTION_LOGIN = 'login';
    const TYPE_ACTIVITY_ACTION_NONE = 'none';

    // 主题图章
    const STAMP_MARROW = 0;
    const STAMP_HOT = 1;
    const STAMP_TOP = 4;
    
    // 主题图标
    const ICON_MARROW = 9;
    const ICON_HOT = 10;
    const ICON_TOP = 13;

    // 图片附件
    const PIC_ATTACHMENT = 2;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return '{{forum_thread}}';
    }

    public function rules() {
        return array(
        );
    }

    // public function attributeLabels() {
    //  return array(
    //  );
    // }

    public static function getCountByFid($fid=0, $params=array()) {
        empty($params['sort']) && $params['sort'] = '';
        return self::_getCountByFid($fid, $params);
    }
    
    private static function _getFids($fid) {
        return $fid == 0 ? ForumUtils::getForumShowFids() : array($fid);
    }

    private static function _getCountByFid($fid, $params) {
        switch ($params['sort']) {
            case 'new': return self::__getNewCountByFid($fid, $params);
            case 'top': return self::__getTopCountByFid($fid, $params);
            case 'marrow': return self::__getMarrowCountByFid($fid, $params);
            case 'all': return self::__getCountByFid($fid, $params);
            // default: return self::__getCountByFid($fid, $params);
            default: return 0;
        }
    }

    private static function __getCountByFid($fid, $params) {
        // return (int)DbUtils::getDzDbUtils(true)->queryScalar('
        //     SELECT COUNT(*)
        //     FROM %t
        //     WHERE fid IN (%n) AND displayorder>=%s
        //     ',
        //     array(
        //         'forum_thread',
        //         self::_getFids($fid),
        //         self::DISPLAY_ORDER_NORMAL
        //     )
        // );
        return self::getByFidCount(self::_getFids($fid), $params);
    }

    private static function __getNewCountByFid($fid, $params) {
        return self::__getCountByFid($fid, $params);
    }

    private static function __getMarrowCountByFid($fid, $params) {
        // return (int)DbUtils::getDzDbUtils(true)->queryScalar('
        //     SELECT COUNT(*)
        //     FROM %t
        //     WHERE 
        //         (fid IN (%n) AND displayorder=%s) AND
        //         (digest>%s OR stamp=%s OR icon=%s)
        //     ',
        //     array(
        //         'forum_thread', 
        //         self::_getFids($fid), self::DISPLAY_ORDER_NORMAL,
        //         0, self::STAMP_MARROW, self::ICON_MARROW  
        //     )
        // );
        // 不取精华的图章，图标
        // return (int)DbUtils::getDzDbUtils(true)->queryScalar('
        //     SELECT COUNT(*)
        //     FROM %t
        //     WHERE 
        //         (fid IN (%n) AND displayorder=%s) AND
        //         (digest>%s)
        //     ',
        //     array(
        //         'forum_thread', 
        //         self::_getFids($fid), self::DISPLAY_ORDER_NORMAL,
        //         0,
        //     )
        // );
        $params['topic_digest'] = array(1, 2, 3);
        return self::getByFidCount(self::_getFids($fid), $params);
    }

    private static function __getTopCountByFid($fid, $params) {
        // return (int)DbUtils::getDzDbUtils(true)->queryScalar('
        //     SELECT COUNT(*)
        //     FROM %t
        //     WHERE 
        //         (fid IN (%n) AND displayorder=%s) OR
        //         (fid IN (%n) AND displayorder=%s) OR
        //         (fid IN (%n) AND (displayorder=%s OR stamp=%s OR icon=%s))
        //     ',
        //     array(
        //         'forum_thread',
        //         self::_getFids(0),
        //         self::DISPLAY_ORDER_GLOBAL,
        //         DzForumForum::getFidsByGid(DzForumForum::getGidByFid($fid)),
        //         self::DISPLAY_ORDER_GROUP,
        //         self::_getFids($fid),
        //         self::DISPLAY_ORDER_FORUM, self::STAMP_TOP, self::ICON_TOP,
        //     )
        // );
        // 不取置顶的图章，图标
        return (int)DbUtils::getDzDbUtils(true)->queryScalar('
            SELECT COUNT(*)
            FROM %t
            WHERE 
                (fid IN (%n) AND displayorder=%s) OR
                (fid IN (%n) AND displayorder=%s) OR
                (fid IN (%n) AND displayorder=%s)
            ',
            array(
                'forum_thread',
                self::_getFids(0),
                self::DISPLAY_ORDER_GLOBAL,
                DzForumForum::getFidsByGid(DzForumForum::getGidByFid($fid)),
                self::DISPLAY_ORDER_GROUP,
                self::_getFids($fid),
                self::DISPLAY_ORDER_FORUM,
            )
        );
    }

    public static function getTopicsByFid($fid=0, $page=1, $pageSize=10, $params=array()) {
        empty($params['sort']) && $params['sort'] = '';
        return self::_getTopicsByFid($fid, $page, $pageSize, $params);
    }
 
    private static function _getTopicsByFid($fid, $page, $pageSize, $params) {
        switch ($params['sort']) {
            case 'new': return self::__getNewTopicsByFid($fid, $page, $pageSize, $params);
            case 'top': return self::__getTopTopicsByFid($fid, $page, $pageSize, $params);
            case 'marrow': return self::__getMarrowTopicsByFid($fid, $page, $pageSize, $params);
            case 'all': return self::__getTopicsByFid($fid, $page, $pageSize, $params);
            // default: return self::__getTopicsByFid($fid, $page, $pageSize, $params);
            default: return array();
        }
    }

    private static function __getTopicsByFid($fid, $page, $pageSize, $params) {
        // return DbUtils::getDzDbUtils(true)->queryAll('
        //     SELECT *
        //     FROM %t
        //     WHERE fid IN (%n) AND displayorder>=%s
        //     ORDER BY lastpost DESC
        //     LIMIT %d, %d
        //     ',
        //     array(
        //         'forum_thread', 
        //         self::_getFids($fid), self::DISPLAY_ORDER_NORMAL,
        //         $pageSize*($page-1), $pageSize   
        //     )
        // );
        $offset = ($page - 1) * $pageSize;
        $params['topic_orderby'] = 'lastpost';
        return self::getByFidData(self::_getFids($fid), $offset, $pageSize, $params);
    }

    private static function __getNewTopicsByFid($fid, $page, $pageSize, $params) {
        // return DbUtils::getDzDbUtils(true)->queryAll('
        //     SELECT *
        //     FROM %t
        //     WHERE fid IN (%n) AND displayorder>=%s
        //     ORDER BY dateline DESC
        //     LIMIT %d, %d
        //     ',
        //     array(
        //         'forum_thread',
        //         self::_getFids($fid), self::DISPLAY_ORDER_NORMAL,
        //         $pageSize*($page-1), $pageSize
        //     )
        // );
        $offset = ($page - 1) * $pageSize;
        return self::getByFidData(self::_getFids($fid), $offset, $pageSize, $params);
    }

    // 获取精华帖
    private static function __getMarrowTopicsByFid($fid, $page, $pageSize, $params) {
        // return DbUtils::getDzDbUtils(true)->queryAll('
        //     SELECT *
        //     FROM %t
        //     WHERE 
        //         (fid IN (%n) AND displayorder=%s) AND
        //         (digest>%s OR stamp=%s OR icon=%s)
        //     ORDER BY dateline DESC
        //     LIMIT %d, %d
        //     ',
        //     array(
        //         'forum_thread', 
        //         self::_getFids($fid), self::DISPLAY_ORDER_NORMAL,
        //         0, self::STAMP_MARROW, self::ICON_MARROW,
        //         $pageSize * ($page-1), $pageSize
        //     )
        // );
        // 不取精华的图章，图标
        // 以下 - -han
        // return DbUtils::getDzDbUtils(true)->queryAll('
        //     SELECT *
        //     FROM %t
        //     WHERE 
        //         (fid IN (%n) AND displayorder=%s) AND
        //         (digest>%s)
        //     ORDER BY dateline DESC
        //     LIMIT %d, %d
        //     ',
        //     array(
        //         'forum_thread', 
        //         self::_getFids($fid), self::DISPLAY_ORDER_NORMAL,
        //         0,
        //         $pageSize * ($page-1), $pageSize
        //     )
        // );
        $offset = ($page - 1) * $pageSize;
        $params['topic_digest'] = array(1,2,3);
        return self::getByFidData(self::_getFids($fid), $offset, $pageSize, $params);
    }

    private static function __getTopTopicsByFid($fid, $page, $pageSize, $params) {
        // return DbUtils::getDzDbUtils(true)->queryAll('
        //     SELECT *
        //     FROM %t
        //     WHERE 
        //         (fid IN (%n) AND displayorder=%s) OR
        //         (fid IN (%n) AND displayorder=%s) OR
        //         (fid IN (%n) AND (displayorder=%s OR stamp=%s OR icon=%s))
        //     ORDER BY displayorder DESC, dateline DESC
        //     LIMIT %d, %d
        //     ',
        //     array(
        //         'forum_thread',
        //         self::_getFids(0),
        //         self::DISPLAY_ORDER_GLOBAL,
        //         DzForumForum::getFidsByGid(DzForumForum::getGidByFid($fid)),
        //         self::DISPLAY_ORDER_GROUP,
        //         self::_getFids($fid),
        //         self::DISPLAY_ORDER_FORUM, 
        //         self::STAMP_TOP, self::ICON_TOP,
        //         $pageSize * ($page-1), $pageSize
        //     )
        // );
        // 不取置顶的图章，图标
        // -- 以下注释 -- //
        return DbUtils::getDzDbUtils(true)->queryAll('
            SELECT *
            FROM %t
            WHERE 
                (fid IN (%n) AND displayorder=%s) OR
                (fid IN (%n) AND displayorder=%s) OR
                (fid IN (%n) AND displayorder=%s)
            ORDER BY displayorder DESC, dateline DESC
            LIMIT %d, %d
            ',
            array(
                'forum_thread',
                self::_getFids(0),
                self::DISPLAY_ORDER_GLOBAL,
                DzForumForum::getFidsByGid(DzForumForum::getGidByFid($fid)),
                self::DISPLAY_ORDER_GROUP,
                self::_getFids($fid),
                self::DISPLAY_ORDER_FORUM, 
                $pageSize * ($page-1), $pageSize
            )
        );
        // $offset = ($page - 1) * $pageSize;
        // $params['topic_stick'] = array(1,2,3);
        // $params['topic_orderby'] = 'displayorder DESC, dateline';
        // return self::getByFidData(self::_getFids(), $offset, $pageSize, $params);
    }

    public static function getTopicByTid($tid) {
        return DbUtils::getDzDbUtils(true)->queryRow('
            SELECT *
            FROM %t
            WHERE tid=%d AND displayorder>=%d
            ',
            array('forum_thread', $tid, self::DISPLAY_ORDER_NORMAL)
        );
    }

    public static function getTopicTypeByTid($tid) {
        $topic = self::_getTopicInfo($tid);
        $type = 'normal';
        if (self::isVote($topic)) {
            $type = 'vote';
        } else if (self::isActivity($topic)) {
            $type = 'activity';
        }
        return $type;
    }

    public static function getTopicVoteInfoByTid($tid) {
        $vote = array();
        $topic = self::_getTopicInfo($tid);

        global $_G;
        $_G['tid'] = $topic['tid'];
        $app = Yii::app()->getController()->mobcentDiscuzApp;
        $app->loadForum($topic['fid'], $topic['tid']);

        require_once libfile('function/forumlist');
        require_once libfile('thread/poll', 'include');

        $status = self::STATUS_VOTE_CAN_VOTE;
        if (!$allwvoteusergroup) {
            $status = self::STATUS_VOTE_NOPERMISSION;
        } else if (!$allowvotepolled) {
            $status = self::STATUS_VOTE_VOTED;
        } else if (!$allowvotethread) {
            $status = self::STATUS_VOTE_CLOSED;
        }

        $vote['multiple'] = $multiple;
        $vote['maxchoices'] = $maxchoices;
        $vote['voterscount'] = $voterscount;
        $vote['visiblepoll'] = $visiblepoll && $_G['group']['allowvote'];
        $vote['expiration'] = $expiration;
        $vote['status'] = $status;
        $vote['options'] = $polloptions;
        
        return $vote;
    }

    public static function getTopicActivityInfoByTid($tid) {
        $activity = array();
        $topic = self::_getTopicInfo($tid);

        global $_G;
        $_G['tid'] = $topic['tid'];
        $app = Yii::app()->getController()->mobcentDiscuzApp;
        $app->loadForum($topic['fid'], $topic['tid']);

        require_once libfile('thread/activity', 'include');
        
        $activity['starttimefrom'] = WebUtils::emptyHtml($activity['starttimefrom']);
        $activity['mobcent']['allApplyNums'] = $allapplynum;
        $activity['mobcent']['leftNums'] = $aboutmembers;
        $activity['mobcent']['applyNums'] = $applynumbers;
        $activity['mobcent']['applied'] = $applied;
        $activity['mobcent']['options'] = $settings;
        $activity['mobcent']['applyInfo'] = $applyinfo;
        $activity['mobcent']['isVerified'] = $isverified;
        $activity['mobcent']['isActivityClose'] = $activityclose;
        $activity['mobcent']['applyList'] = $applylist;
        $activity['mobcent']['noVerifiedNums'] = $noverifiednum;
        $activity['mobcent']['applyListVerified'] = $applylistverified;
        
        return $activity;
    }

    public static function isVote($tid) {
        $topicInfo = self::_getTopicInfo($tid);
        return !empty($topicInfo) && ($topicInfo['special'] == self::TYPE_VOTE);
    }
    
    public static function isActivity($tid) {
        $topicInfo = self::_getTopicInfo($tid);
        return !empty($topicInfo) && ($topicInfo['special'] == self::TYPE_ACTIVITY);
    }

    public static function isHot($tid) {
        $topicInfo = self::_getTopicInfo($tid);
        return !empty($topicInfo) && ($topicInfo['stamp'] == self::STAMP_HOT || $topicInfo['icon'] == self::ICON_HOT);
    }

    public static function isMarrow($tid) {
        $topicInfo = self::_getTopicInfo($tid);
        return !empty($topicInfo) && ($topicInfo['digest'] > 0 || $topicInfo['stamp'] == self::STAMP_MARROW || $topicInfo['icon'] == self::ICON_MARROW);
    }

    public static function isTop($tid) {
        $topicInfo = self::_getTopicInfo($tid);
        return !empty($topicInfo) && ($topicInfo['displayorder'] > 0 || $topicInfo['stamp'] == self::STAMP_TOP || $topicInfo['icon'] == self::ICON_TOP);
    }

    public static function isOnlyAuthor($tid) {
        $topicInfo = self::_getTopicInfo($tid);
        return !empty($topicInfo) && (getstatus($topicInfo['status'], 2) == 1);
    }

    public static function isFavorite($uid, $tid) {
        $count = (int)DbUtils::getDzDbUtils(true)->queryScalar('
            SELECT COUNT(*)
            FROM %t
            WHERE uid=%d AND id=%d AND idtype=%s
            ',
            array('home_favorite', $uid, $tid, 'tid')
        );
        return $count > 0;
    }

    private static function _getTopicInfo($tid) {
        return is_numeric($tid) ? self::getTopicByTid($tid) : $tid;
    }

    // 查询符合条件数据的总条数
    public static function getByFidCount($fids, $params=array(), $longitude='', $latitude='', $radius=100000) {
        global $_G;
        $table = ' %t ft';
        $where = ' 1 AND ft.fid IN (%n)';

        // 用户收藏的板块
        if (in_array('favoriteForum', $params['other_filter'])) {
            $favoriteForum = self::getUserFavorite($_G['uid']);
            $fids = array_intersect($fids, $favoriteForum);
        }

        $term = array('forum_thread', $fids);

        if (!empty($params['topic_picrequired'])) {
            $table = '%t ft INNER JOIN %t fp ON ft.tid = fp.tid';
            $where .= ' AND fp.first=1 AND fp.attachment=%d';
            $term = array('forum_thread', 'forum_post', $fids, self::PIC_ATTACHMENT);
        }

        if ($params['topic_orderby'] == 'distance') {
            $range = self::getRange($longitude, $latitude, $radius);
            $select = '*, ' . self::getSqlDistance($longitude, $latitude) . ' AS distance ';
            $table = '
                %t hsu INNER JOIN %t ft ON hsu.object_id=ft.tid
                INNER JOIN %t aus ON ft.authorid=aus.uid
            ';

            $where = '
                hsu.type=%s 
                AND hsu.longitude BETWEEN %s AND %s
                AND hsu.latitude BETWEEN %s AND %s
                AND aus.ukey=%s
                AND aus.uvalue=%s
                AND ft.authorid!=%s
                AND ft.fid IN (%n)
            ';

            $term = array(
                'home_surrounding_user',
                'forum_thread',
                'appbyme_user_setting',
                SurroundingInfo::TYPE_TOPIC,
                $range['longitude']['min'],
                $range['longitude']['max'],
                $range['latitude']['min'],
                $range['latitude']['max'],
                AppbymeUserSetting::KEY_GPS_LOCATION,
                AppbymeUserSetting::VALUE_GPS_LOCATION_ON,
                $_G['uid'],
                $fids
            );

            if (!empty($params['topic_picrequired'])) {
                $table = '
                    %t hsu 
                    INNER JOIN %t ft ON hsu.object_id=ft.tid
                    INNER JOIN %t aus ON ft.authorid=aus.uid 
                    INNER JOIN %t fp ON ft.tid=fp.tid
                ';

                $where = '
                    hsu.type=%s 
                    AND hsu.longitude BETWEEN %s AND %s
                    AND hsu.latitude BETWEEN %s AND %s
                    AND aus.ukey=%s
                    AND aus.uvalue=%s
                    AND ft.authorid!=%s
                    AND ft.fid IN (%n)
                    AND fp.first = 1
                    AND fp.attachment = %d
                ';

                $term = array(
                    'home_surrounding_user',
                    'forum_thread',
                    'appbyme_user_setting',
                    'forum_post',
                    SurroundingInfo::TYPE_TOPIC,
                    $range['longitude']['min'],
                    $range['longitude']['max'],
                    $range['latitude']['min'],
                    $range['latitude']['max'],
                    AppbymeUserSetting::KEY_GPS_LOCATION,
                    AppbymeUserSetting::VALUE_GPS_LOCATION_ON,
                    $_G['uid'],
                    $fids,
                    self::PIC_ATTACHMENT
                );  
            }


        }

        $sql = 'SELECT COUNT(*)
                FROM '.$table.'
                WHERE '.$where
                ;

        if (!empty($params['topic_digest'])) {
            $sql .= ' AND ft.digest IN (%n)';
            $term[] =  $params['topic_digest'];
        }

        if (!empty($params['topic_stick'])) {
            $sql .= ' AND ft.displayorder IN (%n)';
            $term[] = $params['topic_stick'];
        } else {
            $sql .= ' AND ft.displayorder>=%s';
            $term[] = self::DISPLAY_ORDER_NORMAL;
        }

        if (!empty($params['topic_special'])) {
           $sql .= ' AND ft.special IN (%n)';
           $term[] = $params['topic_special'];
        }

        // if (!empty($params['topic_picrequired'])) {
        //     $sql .= ' AND ft.attachment=%d';
        //     $term[] = self::PIC_ATTACHMENT;
        // }

        if ($params['topic_postdateline'] > 0) {
            $time = time() - $params['topic_postdateline'];
            $sql .= ' AND ft.dateline>%d';
            $term[] = $time;
        }

        if ($params['topic_lastpost'] > 0) {
            $time = time() - $params['topic_lastpost'];
            $sql .= ' AND ft.lastpost>%d';
            $term[] = $time;
        }

        if ($params['topic_sortid'] > 0) {
            $sql .= ' AND ft.sortid=%d';
            $term[] = $params['topic_sortid'];
        }

        if ($params['topic_typeid'] > 0) {
            $sql .= ' AND ft.typeid=%d';
            $term[] = $params['topic_typeid'];            
        }

        $authoridArr = array();
        // 好友
        if (in_array('friend', $params['other_filter'])) {
            // $sql .= ' AND authorid IN (%n)';
            // $term[] = self::getUserFriendList($_G['uid']);
            $authoridArr[] = self::getUserFriendList($_G['uid']);
        }

        // 关注
        if (in_array('follow', $params['other_filter'])) {
            // $sql .= ' AND authorid IN (%n)';
            // $term[] = self::getFollowerUser($_G['uid']);
            $authoridArr[] = self::getFollowerUser($_G['uid']);
        }

        if (!empty($authoridArr)) {
            $tmp = array();
            foreach ($authoridArr as $authorid) {
                $tmp = array_merge($tmp, $authorid);
            }
            $sql .= ' AND ft.authorid IN (%n)';
            $term[] = $tmp;
        }   

        return DbUtils::getDzDbUtils(true)->queryScalar($sql, $term);
    }

    /**
     * 通过fid和过来条件来取出数据
     * 
     * @param array $fids 板块id数组
     * @param int $offset 
     * @param int $limit  
     * @param array $params 过滤条件数组 
     *
     * @return array 
     */
    
    public static function getByFidData($fids, $offset, $limit, $params=array(), $longitude='', $latitude='', $radius=100000) {
        global $_G;
        $select = ' *';
        $table = ' %t ft';
        $where = ' 1 AND ft.fid IN (%n)';

        // 用户收藏的板块
        if (in_array('favoriteForum', $params['other_filter'])) {
            $favoriteForum = self::getUserFavorite($_G['uid']);
            $fids = array_intersect($fids, $favoriteForum); // 交集
        }

        $term = array('forum_thread', $fids);

        if (!empty($params['topic_picrequired'])) {
            $table = '%t ft INNER JOIN %t fp ON ft.tid = fp.tid';
            $where .= ' AND fp.first=1 AND fp.attachment=%d';
            $term = array('forum_thread', 'forum_post', $fids, self::PIC_ATTACHMENT);
        }

        if ($params['topic_orderby'] == 'distance') {
            $range = self::getRange($longitude, $latitude, $radius);
            $select = '*, ' . self::getSqlDistance($longitude, $latitude) . ' AS distance ';
            $table = '
                %t hsu INNER JOIN %t ft ON hsu.object_id=ft.tid
                INNER JOIN %t aus ON ft.authorid=aus.uid
            ';

            $where = '
                hsu.type=%s 
                AND hsu.longitude BETWEEN %s AND %s
                AND hsu.latitude BETWEEN %s AND %s
                AND aus.ukey=%s
                AND aus.uvalue=%s
                AND ft.authorid!=%s
                AND ft.fid IN (%n)
            ';

            $term = array(
                'home_surrounding_user',
                'forum_thread',
                'appbyme_user_setting',
                SurroundingInfo::TYPE_TOPIC,
                $range['longitude']['min'],
                $range['longitude']['max'],
                $range['latitude']['min'],
                $range['latitude']['max'],
                AppbymeUserSetting::KEY_GPS_LOCATION,
                AppbymeUserSetting::VALUE_GPS_LOCATION_ON,
                $_G['uid'],
                $fids
            );

            if (!empty($params['topic_picrequired'])) {
                $table = '
                    %t hsu 
                    INNER JOIN %t ft ON hsu.object_id=ft.tid
                    INNER JOIN %t aus ON ft.authorid=aus.uid 
                    INNER JOIN %t fp ON ft.tid=fp.tid
                ';

                $where = '
                    hsu.type=%s 
                    AND hsu.longitude BETWEEN %s AND %s
                    AND hsu.latitude BETWEEN %s AND %s
                    AND aus.ukey=%s
                    AND aus.uvalue=%s
                    AND ft.authorid!=%s
                    AND ft.fid IN (%n)
                    AND fp.first = 1
                    AND fp.attachment = %d
                ';

                $term = array(
                    'home_surrounding_user',
                    'forum_thread',
                    'appbyme_user_setting',
                    'forum_post',
                    SurroundingInfo::TYPE_TOPIC,
                    $range['longitude']['min'],
                    $range['longitude']['max'],
                    $range['latitude']['min'],
                    $range['latitude']['max'],
                    AppbymeUserSetting::KEY_GPS_LOCATION,
                    AppbymeUserSetting::VALUE_GPS_LOCATION_ON,
                    $_G['uid'],
                    $fids,
                    self::PIC_ATTACHMENT
                );
            }


        }

        $sql = 'SELECT '. $select . '
                FROM '.$table.'
                WHERE '.$where
                ;

        if (!empty($params['topic_digest'])) {
            $sql .= ' AND ft.digest IN (%n)';
            $term[] =  $params['topic_digest'];
        }

        if (!empty($params['topic_stick'])) {
            $sql .= ' AND ft.displayorder IN (%n)';
            $term[] = $params['topic_stick'];
        } else {
            $sql .= ' AND ft.displayorder>=%s';
            $term[] = self::DISPLAY_ORDER_NORMAL;
        }

        if (!empty($params['topic_special'])) {
           $sql .= ' AND ft.special IN (%n)';
           $term[] = $params['topic_special'];
        }

        // if (!empty($params['topic_picrequired'])) {
        //     $sql .= ' AND ft.attachment=%d';
        //     $term[] = self::PIC_ATTACHMENT;
        // }

        if ($params['topic_postdateline'] > 0) {
            $time = time() - $params['topic_postdateline'];
            $sql .= ' AND ft.dateline>%d';
            $term[] = $time;
        }

        if ($params['topic_lastpost'] > 0) {
            $time = time() - $params['topic_lastpost'];
            $sql .= ' AND ft.lastpost>%d';
            $term[] = $time;
        }

        if ($params['topic_sortid'] > 0) {
            $sql .= ' AND ft.sortid=%d';
            $term[] = $params['topic_sortid'];
        }

        if ($params['topic_typeid'] > 0) {
            $sql .= ' AND ft.typeid=%d';
            $term[] = $params['topic_typeid'];            
        }

        $authoridArr = array();
        // 好友
        if (in_array('friend', $params['other_filter'])) {
            // $sql .= ' AND authorid IN (%n)';
            // $term[] = self::getUserFriendList($_G['uid']);
            $authoridArr[] = self::getUserFriendList($_G['uid']);
        }

        // 关注
        if (in_array('follow', $params['other_filter'])) {
            // $sql .= ' AND authorid IN (%n)';
            // $term[] = self::getFollowerUser($_G['uid']);
            $authoridArr[] = self::getFollowerUser($_G['uid']);
        }

        if (!empty($authoridArr)) {
            $tmp = array();
            foreach ($authoridArr as $authorid) {
                $tmp = array_merge($tmp, $authorid);
            }
            $sql .= ' AND ft.authorid IN (%n)';
            $term[] = $tmp;
        }

        if (isset($params['topic_orderby'])) {
            $orderBy = ' ORDER BY ft.' . $params['topic_orderby'] . ' DESC';
            if ($params['topic_orderby'] == 'distance') {
                $orderBy = ' ORDER BY distance ASC';    
            }
            $sql .= $orderBy;
        } else {
            $sql .= ' ORDER BY ft.dateline DESC';
        }

        $sql .= ' LIMIT %d, %d';
        $term[] = $offset;
        $term[] = $limit;
        return DbUtils::getDzDbUtils(true)->queryAll($sql, $term);
    }

    /**
     * 获取好友列表
     * 
     * @param int $uid 用户ID
     * @return array $fuids 好友数组
     */
    public static function getUserFriendList($uid) {
        $result = DbUtils::getDzDbUtils(true)->queryAll('
            SELECT fuid
            FROM %t
            WHERE uid=%d
            ',
            array('home_friend', $uid)
        );
        $fuids = array();
        foreach ($result as $res) {
            $fuids[] = $res['fuid'];
        }
        return $fuids;
    }

    // 周边相关
    public static function getRange($longitude, $latitude, $radius) {
        return SurroundingInfo::getRange($longitude, $latitude, $radius);
    }

    // 周边想关 计算距离
    public static function getSqlDistance($longitude, $latitude) {
        return SurroundingInfo::getSqlDistance($longitude, $latitude);
    }

    // 获取关注用户的uid
    public static function getFollowerUser($uid) {
        $result = DbUtils::getDzDbUtils(true)->queryAll('
            SELECT followuid
            FROM %t
            WHERE uid=%d
            ',
            array('home_follow', $uid)
        );
        $followuids = array();
        foreach ($result as $res) {
            $followuids[] = $res['followuid'];
        }
        return $followuids;
    }

    // 获取用户收藏的板块
    public static function getUserFavorite($uid, $idtype='fid') {
        $result = DbUtils::getDzDbUtils(true)->queryAll('
            SELECT id
            FROM %t
            WHERE uid=%d
            AND idtype=%s
            ',
            array('home_favorite', $uid, $idtype)
        );
        $favoriteids = array();
        foreach ($result as $res) {
            $favoriteids[] = $res['id'];
        }
        return $favoriteids;
    }

}