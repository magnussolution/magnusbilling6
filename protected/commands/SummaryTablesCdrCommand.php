<?php
/**
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2018 MagnusBilling. All rights reserved.
 * ###################################
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 * Please submit bug reports, patches, etc to https://github.com/magnusbilling/mbilling/issues
 * =======================================
 * Magnusbilling.com <info@magnusbilling.com>
 *
 */

class SummaryTablesCdrCommand extends CConsoleCommand
{
    private $date;
    private $cdr_id;
    private $cdr_falide_id;
    private $month_cdr_id;
    private $month_cdr_falide_id;
    private $day;

    public function run($args)
    {

        $this->set_ids($args);

        if (isset($args[0])) {
            try {
                if ($args[0] == 'processCdrToday') {
                    $this->processSummary('day');
                } elseif ($args[0] == 'processCdrLast30Days') {
                    $this->processSummary('month');
                }
            } catch (Exception $e) {
                $class_methods = get_class_methods('SummaryTablesCdrCommand');
                echo "Availables comands";
                for ($i = 1; $i < 16; $i++) {
                    echo $class_methods[$i] . "\n";
                }
            }

        } else {
            $this->perDay();
            $this->perDayUser();
            $this->perDayTrunk();
            $this->perDayAgent();
            $this->perMonth();
            $this->perMonthUser();
            $this->perMonthTrunk();
            $this->perUser();
            $this->perTrunk();
        }

    }

    private function set_ids($args)
    {
        if (date('Hi') > '2350') {
            exit;
        }
        if (date('H') < '1') {
            $day = date('d') - 1;
        } else if (isset($args[1])) {
            $day = $args[1];
        } else {
            $day = date('d');
        }

        $this->day = $day;

        $sql    = "SELECT cdr_id, cdr_falide_id FROM pkg_cdr_summary_ids WHERE `day` = '" . date('Y-m-') . $day . "' LIMIT 1";
        $result = Yii::app()->db->createCommand($sql)->queryAll();
        if (!isset($result[0]['cdr_id']) || ($result[0]['cdr_id'] == 0 || $result[0]['cdr_falide_id'] == 0)) {
            $sql       = "SELECT id  FROM pkg_cdr WHERE starttime > '" . date('Y-m-') . $day . "' ORDER BY id ASC LIMIT 1";
            $resultCdr = Yii::app()->db->createCommand($sql)->queryAll();
            if (!isset($resultCdr[0]['id'])) {
                exit;
            }

            $sql             = "SELECT id  FROM pkg_cdr_failed WHERE starttime > '" . date('Y-m-') . $day . "' ORDER BY id ASC LIMIT 1";
            $resultCdrFailed = Yii::app()->db->createCommand($sql)->queryAll();
            if (!isset($resultCdrFailed[0]['id'])) {
                exit;
            }
            $this->cdr_id        = $resultCdr[0]['id'];
            $this->cdr_falide_id = $resultCdrFailed[0]['id'];

            $sql = "INSERT INTO pkg_cdr_summary_ids (day, cdr_id, cdr_falide_id) VALUES ('" . date('Y-m-') . $day . "', '" . $this->cdr_id . "', '" . $this->cdr_falide_id . "' )";
            Yii::app()->db->createCommand($sql)->execute();

        } else {
            $this->cdr_id        = $result[0]['cdr_id'];
            $this->cdr_falide_id = $result[0]['cdr_falide_id'];
        }

        $sql    = "SELECT cdr_id, cdr_falide_id FROM pkg_cdr_summary_ids WHERE `day` = '" . date('Y-m-') . "01' LIMIT 1";
        $result = Yii::app()->db->createCommand($sql)->queryAll();
        if (!isset($result[0]['cdr_id']) || ($result[0]['cdr_id'] == 0 || $result[0]['cdr_falide_id'] == 0)) {
            $sql       = "SELECT id  FROM pkg_cdr WHERE starttime > '" . date('Y-m-') . "01' ORDER BY id ASC LIMIT 1";
            $resultCdr = Yii::app()->db->createCommand($sql)->queryAll();
            if (!isset($resultCdr[0]['id'])) {
                return;
            }

            $sql             = "SELECT id  FROM pkg_cdr_failed WHERE starttime > '" . date('Y-m-') . "01' ORDER BY id ASC LIMIT 1";
            $resultCdrFailed = Yii::app()->db->createCommand($sql)->queryAll();
            if (!isset($resultCdrFailed[0]['id'])) {
                return;
            }
            $this->month_cdr_id        = $resultCdr[0]['id'];
            $this->month_cdr_falide_id = $resultCdrFailed[0]['id'];

            $sql = "INSERT INTO pkg_cdr_summary_ids (day, cdr_id, cdr_falide_id) VALUES ('" . date('Y-m-') . "01', '" . $this->month_cdr_id . "', '" . $this->month_cdr_falide_id . "' )";
            Yii::app()->db->createCommand($sql)->execute();

        } else {
            $this->month_cdr_id        = $result[0]['cdr_id'];
            $this->month_cdr_falide_id = $result[0]['cdr_falide_id'];
        }

    }

    public function processSummary($type)
    {

        if ($type == 'day') {
            $this->date = date("Y-m-d");
        } elseif ($type == 'month') {
            $this->date = date("Y-m-d", strtotime("- 30 day"));
        }

        //perday
        $this->perDay();
        $this->perDayUser();
        $this->perDayTrunk();
        $this->perDayAgent();
        $this->perMonth();
        $this->perMonthUser();
        $this->perMonthTrunk();

        if ($type == 'month') {

            $this->perUser();
            $this->perTrunk();
        }

    }
    public function perDay()
    {

        if ($this->day == date('d')) {
            $filter = 't.id >= ' . $this->cdr_id . ' AND starttime > "' . date('Y-m-d') . '"';
        } else {
            $filter = 't.id >= ' . $this->cdr_id;
        }

        $sql = "SELECT DATE(starttime) AS day,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill
                FROM pkg_cdr t WHERE $filter GROUP BY day ORDER BY day DESC";
        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['day'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['day'] > date('Y-m-d')) {
                continue;
            }
            $line .= "('" . $value['day'] . "', '" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "'),";

            $sql = "DELETE FROM pkg_cdr_summary_day WHERE day = '" . $value['day'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();

        }

        $sql = "INSERT INTO pkg_cdr_summary_day (day, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill) VALUES " . substr($line, 0, -1) . ";";
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        echo "perDay " . date('H:i:s') . "\n";

        if ($this->day == date('d')) {
            $filter = 't.id >= ' . $this->cdr_falide_id . ' AND starttime > "' . date('Y-m-d') . '"';
        } else {
            $filter = 't.id >= ' . $this->cdr_falide_id;
        }

        $sql = "SELECT DATE(starttime) AS day,
                count(*) as nbcall_fail
                FROM pkg_cdr_failed t WHERE $filter  GROUP BY day ORDER BY day DESC ";
        $model = Yii::app()->db->createCommand($sql)->queryAll();

        foreach ($model as $key => $value) {
            $sql = "UPDATE pkg_cdr_summary_day SET nbcall_fail =" . $value['nbcall_fail'] . " WHERE day = '" . $value['day'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        $sql = "UPDATE  pkg_cdr_summary_day SET asr = (nbcall / ( nbcall_fail + nbcall) ) * 100 ";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_day SET lucro = sessionbill - buycost";
        Yii::app()->db->createCommand($sql)->execute();

    }

    public function perDayUser()
    {

        if ($this->day == date('d')) {
            $filter = 't.id >= ' . $this->cdr_id . ' AND starttime > "' . date('Y-m-d') . '"';
        } else {
            $filter = 't.id >= ' . $this->cdr_id;
        }
        $sql = "SELECT DATE(starttime) AS day,
                id_user,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill,
                sum(agent_bill) AS agent_bill
                FROM pkg_cdr t WHERE $filter  GROUP BY day, id_user ORDER BY day DESC";
        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['day'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['day'] > date('Y-m-d')) {
                continue;
            }
            $line .= "('" . $value['day'] . "','" . $value['id_user'] . "', '" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "','" . $value['agent_bill'] . "'),";

            $sql = "DELETE FROM pkg_cdr_summary_day_user WHERE day = '" . $value['day'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();

        }

        $sql = "INSERT INTO pkg_cdr_summary_day_user (day, id_user, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill, agent_bill) VALUES " . substr($line, 0, -1) . ";";
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            echo $sql . "\n";
            print_r($e->getMessage());
        }

        echo "perDayUser " . date('H:i:s') . "\n";

        $sql = "UPDATE pkg_cdr_summary_day_user t  INNER JOIN pkg_user u ON t.id_user = u.id SET t.isAgent = IF(u.id_user > 1, 1,0);";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_day_user SET lucro = sessionbill - buycost WHERE isAgent = 0";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_day_user SET lucro = agent_bill - sessionbill WHERE isAgent = 1";
        Yii::app()->db->createCommand($sql)->execute();
    }

    public function perDayTrunk()
    {

        if ($this->day == date('d')) {
            $filter = 't.id >= ' . $this->cdr_id . ' AND starttime > "' . date('Y-m-d') . '"';
        } else {
            $filter = 't.id >= ' . $this->cdr_id;
        }

        $sql = "SELECT DATE(starttime) AS day,
                id_trunk,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill
                FROM pkg_cdr t WHERE $filter AND id_trunk IS NOT NULL GROUP BY day, id_trunk ORDER BY day DESC";

        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['day'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['day'] > date('Y-m-d')) {
                continue;
            }
            $line .= "('" . $value['day'] . "','" . $value['id_trunk'] . "', '" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "'),";

            $sql = "DELETE FROM pkg_cdr_summary_day_trunk WHERE day = '" . $value['day'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();

        }

        $sql = "INSERT INTO pkg_cdr_summary_day_trunk (day, id_trunk, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill) VALUES " . substr($line, 0, -1) . ";";
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        echo "perDayTrunk " . date('H:i:s') . "\n";

        $sql = "UPDATE  pkg_cdr_summary_day_trunk SET lucro = sessionbill - buycost";
        Yii::app()->db->createCommand($sql)->execute();
    }

    public function perDayAgent()
    {
        if ($this->day == date('d')) {
            $filter = 't.id >= ' . $this->cdr_id . ' AND starttime > "' . date('Y-m-d') . '"';
        } else {
            $filter = 't.id >= ' . $this->cdr_id;
        }

        $sql = "SELECT DATE(starttime) AS day,
                c.id_user as id_user,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill,
                sum(agent_bill) AS agent_bill
                FROM pkg_cdr t
                LEFT JOIN pkg_user c ON t.id_user = c.id
                WHERE  $filter AND c.id_user > 1  GROUP BY day, c.id_user ORDER BY day DESC";
        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['day'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['day'] > date('Y-m-d')) {
                continue;
            }
            $line .= "('" . $value['day'] . "','" . $value['id_user'] . "', '" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "','" . $value['agent_bill'] . "'),";
            $sql = "DELETE FROM pkg_cdr_summary_day_agent WHERE day = '" . $value['day'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        $sql = "INSERT INTO pkg_cdr_summary_day_agent (day, id_user, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill, agent_bill) VALUES " . substr($line, 0, -1) . ";";
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        echo "perDayAgent " . date('H:i:s') . "\n";

        $sql = "UPDATE  pkg_cdr_summary_day_agent SET lucro = sessionbill - buycost, agent_lucro = agent_bill -  sessionbill";
        Yii::app()->db->createCommand($sql)->execute();
    }

    public function perMonth()
    {

        $filter = 't.id >= ' . $this->month_cdr_id;

        echo $sql = "SELECT EXTRACT(YEAR_MONTH FROM starttime) AS month,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill
                FROM pkg_cdr t WHERE $filter GROUP BY month ORDER BY month DESC";
        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['month'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['month'] > date('Ym')) {
                continue;
            }
            $line .= "('" . $value['month'] . "', '" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "'),";
            $sql = "DELETE FROM pkg_cdr_summary_month WHERE month = '" . date("Ym") . "' ";
            Yii::app()->db->createCommand($sql)->execute();

        }

        $sql = "INSERT INTO pkg_cdr_summary_month (month, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill) VALUES " . substr($line, 0, -1) . ";";

        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        echo "perMonth " . date('H:i:s') . "\n";

        $sql = "UPDATE  pkg_cdr_summary_month SET lucro = sessionbill - buycost";
        Yii::app()->db->createCommand($sql)->execute();

    }

    public function perMonthUser()
    {

        $filter = 't.id >= ' . $this->month_cdr_id;

        $sql = "SELECT EXTRACT(YEAR_MONTH FROM starttime) AS month,
                id_user,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill,
                sum(agent_bill) AS agent_bill
                FROM pkg_cdr t WHERE  $filter GROUP BY month, id_user ORDER BY month DESC";

        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['month'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['month'] > date('Ym')) {
                continue;
            }

            $line .= "('" . $value['month'] . "','" . $value['id_user'] . "', '" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "','" . $value['agent_bill'] . "'),";

            $sql = "DELETE FROM pkg_cdr_summary_month_user WHERE month = '" . $value['month'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();

        }

        $sql = "INSERT INTO pkg_cdr_summary_month_user (month, id_user, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill,agent_bill) VALUES " . substr($line, 0, -1) . ";";

        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        echo "perMonthUser " . date('H:i:s') . "\n";

        $sql = "UPDATE pkg_cdr_summary_month_user t  INNER JOIN pkg_user u ON t.id_user = u.id SET t.isAgent = IF(u.id_user > 1, 1,0);";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_month_user SET lucro = sessionbill - buycost  WHERE isAgent = 0";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_month_user SET lucro = agent_bill - sessionbill  WHERE isAgent = 1";
        Yii::app()->db->createCommand($sql)->execute();
    }

    public function perMonthTrunk()
    {

        $filter = 't.id >= ' . $this->month_cdr_id;

        $sql = "SELECT EXTRACT(YEAR_MONTH FROM starttime) AS month,
                id_trunk,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill
                FROM pkg_cdr t WHERE $filter AND  id_trunk IS NOT NULL GROUP BY month, id_trunk ORDER BY month DESC";

        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['month'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            if ($value['month'] > date('Ym')) {
                continue;
            }
            $line .= "('" . $value['month'] . "','" . $value['id_trunk'] . "','" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "'),";
            $sql = "DELETE FROM pkg_cdr_summary_month_trunk WHERE month = '" . $value['month'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();

        }

        $sql = "INSERT INTO pkg_cdr_summary_month_trunk (month, id_trunk, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill) VALUES " . substr($line, 0, -1) . ";";
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        echo "perMonthTrunk " . date('H:i:s') . "\n";

        $sql = "UPDATE  pkg_cdr_summary_month_trunk SET lucro = sessionbill - buycost";
        Yii::app()->db->createCommand($sql)->execute();

    }

    public function perUser()
    {

        $sql = "SELECT t.id_user,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill,
                IF(c.id_user > 1, 1,0) AS isAgent,
                sum(agent_bill) AS agent_bill
                FROM pkg_cdr t
                LEFT JOIN pkg_user c ON t.id_user = c.id
                GROUP BY t.id_user";
        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['id_user'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            $line .= "('" . $value['id_user'] . "','" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "','" . $value['isAgent'] . "','" . $value['agent_bill'] . "'),";
        }

        $sql = "TRUNCATE pkg_cdr_summary_user";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "INSERT INTO pkg_cdr_summary_user (id_user, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill, isAgent, agent_bill) VALUES " . substr($line, 0, -1) . ";";

        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
            //print_r($e);
            return;
        }

        $sql = "SELECT t.id_user,
        count(*) as nbcall_fail
        FROM pkg_cdr_failed t LEFT JOIN pkg_user c ON t.id_user = c.id
        GROUP BY t.id_user";
        $model = Yii::app()->db->createCommand($sql)->queryAll();

        foreach ($model as $key => $value) {
            $sql = "UPDATE pkg_cdr_summary_user SET nbcall_fail =" . $value['nbcall_fail'] . " WHERE id_user = '" . $value['id_user'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        $sql = "UPDATE  pkg_cdr_summary_user SET isAgent = 0 WHERE isAgent <= 1";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_user SET isAgent =1 WHERE isAgent > 1";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_user SET asr = (nbcall / ( nbcall_fail + nbcall) ) * 100 ";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_user SET lucro = sessionbill - buycost  WHERE isAgent = 0";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_user SET lucro = agent_bill - sessionbill  WHERE isAgent = 1";
        Yii::app()->db->createCommand($sql)->execute();

    }

    public function perTrunk()
    {
        $sql = "SELECT t.id_trunk,
                sum(sessiontime) AS sessiontime,
                SUM(sessiontime) / COUNT(*) AS aloc_all_calls,
                count(*) as nbcall,
                sum(buycost) AS buycost,
                sum(sessionbill) AS sessionbill
                FROM pkg_cdr t
                LEFT JOIN pkg_trunk c ON t.id_trunk = c.id
                GROUP BY t.id_trunk";
        $result = Yii::app()->db->createCommand($sql)->queryAll();

        if (!isset($result[0]['id_trunk'])) {
            return;
        }

        $line = '';
        foreach ($result as $key => $value) {
            $line .= "('" . $value['id_trunk'] . "','" . $value['sessiontime'] . "','" . $value['aloc_all_calls'] . "','" . $value['nbcall'] . "','" . $value['buycost'] . "','" . $value['sessionbill'] . "'),";
        }

        $sql = "TRUNCATE pkg_cdr_summary_trunk";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "INSERT INTO pkg_cdr_summary_trunk (id_trunk, sessiontime, aloc_all_calls, nbcall, buycost, sessionbill) VALUES " . substr($line, 0, -1) . ";";
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {
            //
        }

        $sql = "SELECT t.id_trunk,
                count(*) as nbcall_fail
                FROM pkg_cdr_failed t LEFT JOIN pkg_user c ON t.id_trunk = c.id
                GROUP BY t.id_trunk";
        $model = Yii::app()->db->createCommand($sql)->queryAll();

        foreach ($model as $key => $value) {
            $sql = "UPDATE pkg_cdr_summary_trunk SET nbcall_fail =" . $value['nbcall_fail'] . " WHERE id_trunk = '" . $value['id_trunk'] . "' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        $sql = "UPDATE  pkg_cdr_summary_trunk SET asr = (nbcall / ( nbcall_fail + nbcall) ) * 100 ";
        Yii::app()->db->createCommand($sql)->execute();

        $sql = "UPDATE  pkg_cdr_summary_trunk SET lucro = sessionbill - buycost";
        Yii::app()->db->createCommand($sql)->execute();
    }

}
