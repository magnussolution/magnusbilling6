<?php
/**
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2017 MagnusBilling. All rights reserved.
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
//not check credit and send call to any number, active or inactive
class Start0800CallBackCommand extends ConsoleCommand
{
    public function run($args)
    {
        ini_set("max_execution_time", "900");

        for (;;) {

            if (date('s') == 58) {
                exit;
            }
            sleep(1);

            $modelCallBack = CallBack::model()->findAll('status IN(1,2,4)');

            foreach ($modelCallBack as $callback) {

                echo "Callback to number " . $callback->exten . "\n";
                //

                $status = $callback->status;
                echo $status . "\n";

                //print_r($callback->getAttributes());

                $modelDiddestination = Diddestination::model()->find('id_did = :key', array(':key' => $callback->id_did));

                if (!count($modelDiddestination)) {
                    CallBack::model()->deleteByPk($callback->id);
                    continue;
                }

                $modelDid = $modelDiddestination->idDid;

                //esperar 60 segundos antes de tentar ligar para o cliente.
                if ($status == 1 && $callback->entry_time > date('Y-m-d H:i:s', strtotime('-1 minutes'))) {
                    echo "esperar 60 segundos antes de tentar ligar para o cliente. " . $callback->entry_time . ' ' . date('Y-m-d H:i:s', strtotime('-1 minutes')) . "\n";
                    continue;
                }

                //

                $voltarChamar = time() - ($modelDid->cbr_time_try * 60);
                $voltarChamar = date('Y-m-d H:i:s', $voltarChamar);

                if ($callback->status == 2 && $callback->num_attempt < $modelDid->cbr_total_try && $callback->last_attempt_time < $voltarChamar) {
                    echo " reative number because status = 2 and last_attempt_time > than " . $modelDid->cbr_time_try . "\n";
                    $status = 1;
                }

                if ($callback->status == 4) {
                    $work = $this->checkIVRSchedule($modelDid);
                    if ($work == 'open') {
                        $status                = 1;
                        $callback->status      = 1;
                        $callback->sessiontime = 0;
                        $callback->save();
                    }
                }

                if ($status != 1) {
                    continue;
                }

                $destination = $callback->exten;

                $searchTariff = new SearchTariff();
                $resfindrate  = $searchTariff->find($destination, $modelDiddestination->idUser->id_plan, $modelDiddestination->id_user);

                if (substr("$destination", 0, 4) == 1111) {
                    $destination = str_replace(substr($destination, 0, 7), "", $destination);
                }

                $credit = $modelDiddestination->idUser->typepaid == 1
                ? $modelDiddestination->idUser->credit + $modelDiddestination->idUser->creditlimit
                : $modelDiddestination->idUser->credit;

                if ($resfindrate != 0 && $credit > 0) {
                    $providertech = $resfindrate[0]['rc_providertech'];
                    $ipaddress    = $resfindrate[0]['rc_providerip'];
                    $removeprefix = $resfindrate[0]['rc_removeprefix'];
                    $prefix       = $resfindrate[0]['rc_trunkprefix'];

                    if (strncmp($destination, $removeprefix, strlen($removeprefix)) == 0) {
                        $destination = substr($destination, strlen($removeprefix));
                    }

                    $dialstr = "$providertech/$ipaddress/$prefix$destination";

                    // gerar os arquivos .call
                    $call = "Channel: " . $dialstr . "\n";
                    $call .= "Callerid: " . $callback['exten'] . "\n";
                    $call .= "Context: billing\n";
                    $call .= "Extension: " . $modelDiddestination->idDid->did . "\n";
                    $call .= "Priority: 1\n";
                    $call .= "Priority: 1\n";
                    $call .= "Set:CALLED=" . $destination . "\n";
                    $call .= "Set:TARRIFID=" . $resfindrate[0]['id_rate'] . "\n";

                    $call .= "Set:SELLCOST=" . $resfindrate[0]['rateinitial'] . "\n";
                    $call .= "Set:SELLINITBLOCK=" . $resfindrate[0]['initblock'] . "\n";
                    $call .= "Set:SELLINCREMENT=" . $resfindrate[0]['billingblock'] . "\n";

                    $call .= "Set:BUYCOST=" . $resfindrate[0]['buyrate'] . "\n";
                    $call .= "Set:BUYRATEINIT=" . $resfindrate[0]['buyrateinitblock'] . "\n";
                    $call .= "Set:BUYINCREMENT=" . $resfindrate[0]['buyrateincrement'] . "\n";

                    $call .= "Set:IDUSER=" . $modelDiddestination->id_user . "\n";
                    $call .= "Set:IDPREFIX=" . $resfindrate[0]['id_prefix'] . "\n";
                    $call .= "Set:IDTRUNK=" . $resfindrate[0]['id_trunk'] . "\n";
                    $call .= "Set:IDPLAN=" . $modelDiddestination->idUser->id_plan . "\n";
                    $call .= "Set:IDCALLBACK=" . $callback->id . "\n";
                    $call .= "Set:ISFROMCALLBACKPRO=1\n";
                    AsteriskAccess::generateCallFile($call, 1);

                    echo $call;
                    $callback->num_attempt++;
                    $callback->last_attempt_time = date('Y-m-d H:i:s');
                    $callback->status            = 2;
                    $callback->save();
                    $modelError = $callback->getErrors();
                    if (count($modelError)) {
                        print_r($modelError);
                    }

                } else {
                    $callback->status      = 0;
                    $callback->sessiontime = 0;
                    $callback->save();
                }

            }
        }
    }

    public function checkIVRSchedule($model)
    {
        $weekDay = date('D');

        switch ($weekDay) {
            case 'Sun':
                $weekDay = $model->{'TimeOfDay_sun'};
                break;
            case 'Sat':
                $weekDay = $model->{'TimeOfDay_sat'};
                break;
            default:
                $weekDay = $model->{'TimeOfDay_monFri'};
                break;
        }

        $hours   = date('H');
        $minutes = date('i');
        $now     = ($hours * 60) + $minutes;

        $intervals = preg_split("/\|/", $weekDay);

        foreach ($intervals as $key => $interval) {
            $hours = explode('-', $interval);

            $start = $hours[0];
            $end   = $hours[1];

            #convert start hour to minutes
            $hourInterval = explode(':', $start);
            $starthour    = $hourInterval[0] * 60;
            $start        = $starthour + $hourInterval[1];

            #convert end hour to minutes
            $hourInterval = explode(':', $end);
            $starthour    = $hourInterval[0] * 60;
            $end          = $starthour + $hourInterval[1];

            if ($now >= $start && $now <= $end) {
                return "open";
            }
        }
        return "closed";
    }
}
