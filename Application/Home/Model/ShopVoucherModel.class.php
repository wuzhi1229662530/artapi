<?php
namespace Home\Model;
use Home\Model\MyModel;
class ShopVoucherModel extends MyModel
{
    protected $tableName = 'shop_voucher';


    /**
     * 取用户  已使用  优惠券
     */
    public function getUsedVoucher($user_id) {
        $sql = "SELECT
                    code,
                    'Custom vouchers' AS title,
                    par AS money,
                    'No time limit' AS start_time,
                    '' AS end_time
                FROM
                    ab_shop_voucher
                WHERE
                    userid = %d
                AND STATUS = '1'
                AND ruleid IS NULL
                UNION
                    SELECT
                        a. code,
                        a.title,
                        b.money,
                        DATE_FORMAT(start_time, '%m/%d/%y') AS start_time,
                        DATE_FORMAT(end_time, '%m/%d/%y') AS end_time
                    FROM
                        (
                            SELECT
                                t1. code,
                                t2.title,
                                t2.templateid,
                                t2.start_time,
                                t2.end_time
                            FROM
                                ab_shop_voucher t1
                            LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                            WHERE
                                t1. STATUS = 1
                            AND t1.userid = %d
                        ) a
                    LEFT JOIN ab_shop_voucher_template b ON a.templateid = b.id";
        $res = $this->query($sql, array($user_id, $user_id) );
        return $res;
    }

    /**
     * 取用户  未使用  优惠券
     */
    public function getNotUsedVoucher($user_id) {
        $sql = "SELECT
                    code,
                    'Custom vouchers' AS title,
                    par AS money,
                    'No time limit' AS start_time,
                    '' AS end_time
                FROM
                    ab_shop_voucher
                WHERE
                    userid = %d
                AND STATUS = '0'
                AND ruleid IS NULL
                UNION
                    SELECT
                        a. code,
                        a.title,
                        b.money,
                        DATE_FORMAT(start_time, '%m/%d/%y') AS start_time,
                        DATE_FORMAT(end_time, '%m/%d/%y') AS end_time
                    FROM
                        (
                            SELECT
                                t1. code,
                                t2.title,
                                t2.templateid,
                                t1.create_time start_time,
                                DATE_ADD(
                                    t1.create_time,
                                    INTERVAL t2.duration_day DAY
                                ) end_time
                            FROM
                                ab_shop_voucher t1
                            LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                            WHERE
                                t2.duration_day <> ''
                            AND NOW() <= DATE_ADD(
                                t1.create_time,
                                INTERVAL t2.duration_day DAY
                            )
                            AND t1. STATUS = 0
                            AND userid = %d
                            UNION
                                SELECT
                                    t1. code,
                                    t2.title,
                                    t2.templateid,
                                    t2.start_time,
                                    t2.end_time
                                FROM
                                    ab_shop_voucher t1
                                LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                                WHERE
                                    t2.duration_day IS NULL
                                AND NOW() <= t2.end_time
                                AND t1. STATUS = 0
                                AND userid = %d
                        ) a
                    LEFT JOIN ab_shop_voucher_template b ON a.templateid = b.id";
        $res = $this->query($sql, array($user_id,$user_id, $user_id));
        return $res;
    }

    /**
     * 根据code 取用户  未使用  优惠券
     */
    public function getNotUsedVoucherByCode($user_id, $code) {
        $sql = "SELECT
                    ab_shop_voucher.id AS voucher_id,
                    code,
                    'Custom vouchers' AS title,
                    par AS money,
                    'No time limit' AS start_time,
                    '' AS end_time
                FROM
                    ab_shop_voucher
                WHERE
                    userid = %d
                AND STATUS = '0'
                AND ruleid IS NULL
                UNION
                    SELECT
                        a.code,
                        a.title,
                        b.money,
                        DATE_FORMAT(start_time, '%m/%d/%y') AS start_time,
                        DATE_FORMAT(end_time, '%m/%d/%y') AS end_time
                    FROM
                        (
                            SELECT
                                t1.code,
                                t2.title,
                                t2.templateid,
                                t1.create_time start_time,
                                DATE_ADD(
                                    t1.create_time,
                                    INTERVAL t2.duration_day DAY
                                ) end_time
                            FROM
                                ab_shop_voucher t1
                            LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                            WHERE
                                t2.duration_day <> ''
                            AND NOW() <= DATE_ADD(
                                t1.create_time,
                                INTERVAL t2.duration_day DAY
                            )
                            AND t1. STATUS = 0
                            AND userid = %d
                            UNION
                                SELECT
                                    t1.code,
                                    t2.title,
                                    t2.templateid,
                                    t2.start_time,
                                    t2.end_time
                                FROM
                                    ab_shop_voucher t1
                                LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                                WHERE
                                    t2.duration_day IS NULL
                                AND NOW() <= t2.end_time
                                AND t1. STATUS = 0
                                AND userid = %d
                        ) a
                    LEFT JOIN ab_shop_voucher_template b ON a.templateid = b.id 
                    WHERE  code =  '%s'  
                    ";
        $res = $this->query($sql, array($user_id,$user_id, $user_id, $code));
        return $res;
    }

    /**
     * 取用户 已过期 优惠券
     */
    public function getExpiredVoucher($user_id) {
        $sql = "SELECT
                    a.code,
                    a.title,
                    b.money,
                    DATE_FORMAT(start_time, '%m/%d/%y') AS start_time,
                    DATE_FORMAT(end_time, '%m/%d/%y') AS end_time
                FROM
                    (
                        SELECT
                            t1. code,
                            t2.title,
                            t2.templateid,
                            t2.start_time,
                            t2.end_time
                        FROM
                            ab_shop_voucher t1
                        LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                        WHERE
                            NOW() > DATE_ADD(
                                t1.create_time,
                                INTERVAL t2.duration_day DAY
                            )
                        AND t1. STATUS = 0
                        AND userid = %d
                        AND ruleid IS NULL
                        UNION
                            SELECT
                                t1. code,
                                t2.title,
                                t2.templateid,
                                t2.start_time,
                                t2.end_time
                            FROM
                                ab_shop_voucher t1
                            LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                            WHERE
                                t2.duration_day IS NULL
                            AND NOW() > t2.end_time
                            AND t1.STATUS = 0
                            AND userid = %d
                    ) a
                LEFT JOIN ab_shop_voucher_template b ON a.templateid = b.id";
        $res = $this->query($sql, array($user_id,$user_id));
        return $res;
    }

    /**
     * 获取未过期的优惠活动
     */
    public function getRandomVoucherActivity($delete_status) {
        $sql = "SELECT
                    id,
                    title,
                    DATE_FORMAT(
                        start_activity_time,
                        '%m/%d/%y'
                    ) AS start_activity_time,
                    DATE_FORMAT(
                        end_activity_time,
                        '%m/%d/%y'
                    ) AS end_activity_time,
                    content,
                    create_time,
                    update_time,
                    delete_status
                FROM
                    ab_shop_voucher_activity
                WHERE
                    delete_status = ".$delete_status."
                AND (
                    NOW() BETWEEN start_activity_time
                    AND end_activity_time
                
                )
                limit 0, 1";
        $res = $this->query($sql);
        return $res;
    }

    /**
     * 获取优惠活动随机发放未领取的优惠券
     */
    public function getRandomVoucherRule($activityid) {
        $sql = "SELECT 
                    ac.ruleid,
                    vr.full_use_of, 
                    (
                        SELECT
                            vt.money
                        FROM
                            ab_shop_voucher_template vt
                        WHERE
                            vr.templateid = vt.id
                    ) as money
                FROM
                    ab_shop_voucher_association ac
                LEFT JOIN ab_shop_voucher_rule vr ON vr.id = ac.ruleid
                AND vr.is_random = 1
                WHERE
                    ac.activityid = %d";
        $res = $this->query($sql, $activityid);
        return $res;
    }
    /**
     * 用户获取优惠券
     */
    public function getRandomVoucher($arr) {
        $sql = "SELECT
                    t1.id voucher_id
                FROM
                    ab_shop_voucher t1
                LEFT JOIN ab_shop_voucher_rule t2 ON t1.ruleid = t2.id
                LEFT JOIN ab_shop_voucher_template t3 ON t2.templateid = t3.id
                LEFT JOIN ab_shop_voucher_association t4 ON t1.ruleid = t4.ruleid
                WHERE
                    t1.ruleid = %d
                AND t4.activityid = %d
                AND userid IS NULL
                AND t3.money = %d";
        $res = $this->query($sql, $arr);
        return $res;
    }
}