<?php
/**
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2018 MagnusSolution. All rights reserved.
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

class MassiveCall
{
    public function send($agi, &$MAGNUS, &$CalcAgi)
    {

        require_once 'Tts.php';
        $uploaddir = $MAGNUS->magnusFilesDirectory . 'sounds/';

        $agi->answer();
        $now = time();

        if ($MAGNUS->dnid == 'failed' || !is_numeric($MAGNUS->dnid)) {
            $agi->verbose("Hangup becouse dnid is OutgoingSpoolFailed", 25);
            $MAGNUS->hangup($agi);
        }

        $idPhonenumber       = $agi->get_variable("PHONENUMBER_ID", true);
        $phonenumberCity     = $agi->get_variable("PHONENUMBER_CITY", true);
        $idCampaign          = $agi->get_variable("CAMPAIGN_ID", true);
        $idRate              = $agi->get_variable("RATE_ID", true);
        $MAGNUS->id_user     = $agi->get_variable("IDUSER", true);
        $MAGNUS->username    = $MAGNUS->accountcode    = $agi->get_variable("USERNAME", true);
        $MAGNUS->id_agent    = $agi->get_variable("AGENT_ID", true);
        $MAGNUS->destination = $destination = $MAGNUS->dnid;

        $sql = "SELECT *, pkg_campaign.id AS id, pkg_campaign.id_user AS id_user, pkg_campaign.description AS description FROM pkg_campaign
                            LEFT JOIN pkg_user ON pkg_campaign.id_user = pkg_user.id
                            WHERE pkg_campaign.id = $idCampaign LIMIT 1";
        $modelCampaign = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

        if (!isset($modelCampaign->id)) {
            $agi->verbose($idCampaign . ' campaing not exist');
            return;
        }
        $sql              = "SELECT * FROM pkg_phonenumber WHERE id = $idPhonenumber LIMIT 1";
        $modelPhoneNumber = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
        if (!isset($modelPhoneNumber->id)) {
            $agi->verbose($idPhonenumber . ' number not exist');
            exit;
        }

        $forward_number = $modelCampaign->forward_number;

        if ($agi->get_variable("MBILLINGRESULT", true) && preg_match('/AGI\|FORWARD/', $agi->get_variable("MBILLINGRESULT", true))) {
            //massive call execute from app_mbilling and have redirect
            $res = explode('|', $agi->get_variable("MBILLINGRESULT", true));
            $agi->verbose(print_r($res, true));
            $now                = $res[2];
            $res_dtmf['result'] = $modelCampaign->digit_authorize;
        } elseif ($agi->get_variable("MBILLINGRESULT", true) && preg_match('/AGI\|POLL/', $agi->get_variable("MBILLINGRESULT", true))) {
            //massive call execute from app_mbilling and have poll
            $res = explode('|', $agi->get_variable("MBILLINGRESULT", true));
            $agi->verbose(print_r($res, true));
            $now                = $res[2];
            $res_dtmf['result'] = $res[3];

            /*VERIFICA SE CAMPAÃ‘A TEM ENCUESTA*/
            $sql               = "SELECT * FROM pkg_campaign_poll WHERE id_campaign = $idCampaign";
            $modelCampaignPoll = $agi->query($sql)->fetchAll(PDO::FETCH_OBJ);
            $forward_number    = "";

        } else {

            /*VERIFICA SE CAMPAÃ‘A TEM ENCUESTA*/
            $sql               = "SELECT * FROM pkg_campaign_poll WHERE id_campaign = $idCampaign";
            $modelCampaignPoll = $agi->query($sql)->fetchAll(PDO::FETCH_OBJ);

            if (isset($modelCampaign->audio_2) && strlen($modelPhoneNumber->name) > 3 && (strlen($modelCampaign->audio_2) > 5) || strlen($modelCampaign->tts_audio2) > 2) {
                $agi->verbose('get phonenumber name from TTS', 10);
                $tts  = true;
                $file = $idPhonenumber . date("His");

                $audio_name = Tts::create($MAGNUS, $modelPhoneNumber->name);

            }

            /*AUDIO FOR CAMPAIN*/
            if (strlen($modelCampaign->tts_audio) > 2) {

                $file = 'campaign_' . MD5($modelCampaign->tts_audio);
                if (file_exists('/tmp/' . $file . '.wav')) {
                    $agi->verbose('Audio already exist');
                    $audio = '/tmp/' . $file;
                } else {
                    $agi->verbose('Get audio from TTS');
                    $audio = Tts::create($MAGNUS, $modelCampaign->tts_audio);
                }

            } else {
                $audio = $uploaddir . "idCampaign_" . $modelCampaign->id;
            }

            //If exist audio2 execute audio1
            if (isset($tts)) {
                $agi->stream_file($audio, '#');

            } else {
                // CHECK IF NEED AUTORIZATION FOR EXECUTE POLL OR IS EXISTE FORWARD NUMBER
                if (strlen($forward_number) > 2 || (isset($modelCampaignPoll[0]->id) && $modelCampaignPoll[0]->request_authorize == 1)) {
                    $res_dtmf = $agi->get_data($audio, 5000, 1);
                } else {
                    $agi->stream_file($audio, ' #');
                }
            }

            //execute
            if (isset($tts)) {
                $agi->stream_file($audio_name, ' #');
                if (!preg_match('/campaign/', $audio_name)) {
                    $agi->verbose('delete audio name ' . $audio_name, 10);
                    exec("rm -rf $audio_name*");
                }
            }

            if (strlen($modelCampaign->audio_2) > 5 || strlen($modelCampaign->tts_audio2) > 2) {

                /*Execute audio 2*/

                if (strlen($modelCampaign->tts_audio2) > 2) {

                    $audio = Tts::create($MAGNUS, $modelCampaign->tts_audio2);

                } else {
                    $audio = $uploaddir . "idCampaign_" . $idCampaign . "_2";
                }

                // CHECK IF NEED AUTORIZATION FOR EXECUTE POLL OR IS EXISTE FORWARD NUMBER
                if (strlen($forward_number) > 2 || (isset($modelCampaignPoll[0]) && $modelCampaignPoll[0]->request_authorize == 1)) {
                    $res_dtmf = $agi->get_data($audio, 5000, 1);
                } else {
                    $agi->stream_file($audio, ' #');
                }

            }

            if (strlen($modelCampaign->asr_options)) {
                //execute audio to ASR
                for ($i = 0; $i < 4; $i++) {
                    $agi->execute('AGI speech-recog.agi,"pt-BR",2,,NOBEEP');
                    $textASR = $agi->get_variable("utterance", true);
                    $agi->verbose('O texto que você acabou de dizer: ' . $textASR);
                    if (strlen($textASR) < 1) {
                        $text  = "Desculpe não consegui te compreender. Vamos tentar novamente?";
                        $audio = Tts::create($MAGNUS, $text);

                        $agi->stream_file($audio, ' #');

                    } elseif (preg_match('/' . $modelCampaign->asr_options . '/', $textASR)) {

                        $text  = "Você disse. " . $textASR . ". Por favor aguarde.";
                        $audio = Tts::create($MAGNUS, $text);

                        $agi->stream_file($audio, ' #');

                        $res_dtmf['result'] = 1;
                        break;
                    } else {

                        $text  = "Você realmente não quer escutar o recado? Vamos tentar novamente?";
                        $audio = Tts::create($MAGNUS, $text);

                        $agi->stream_file($audio, ' #');
                    }
                }
            }

            $agi->verbose('RESULT DTMF ' . $res_dtmf['result'], 25);

            //CHECK IF IS FORWARD EXTERNAL CALLL
            $agi->verbose("forward_number $forward_number , res_dtmf: " . $res_dtmf['result'] . ", digit_authorize: " . $modelCampaignPoll[0]->digit_authorize, 10);

        }
        //if have a forward                         if res_dtmf is equal the digit_authorize                OR press any digit and digit_authorize equal -2 (any digit)    OR  digit_authorize equal -3 (every)
        if (strlen($forward_number) > 2 && (($res_dtmf['result'] == $modelCampaign->digit_authorize) || (strlen($res_dtmf['result']) > 0 && $modelCampaign->digit_authorize == -2) || $modelCampaign->digit_authorize == -3)) {
            $agi->verbose("have Forward number $forward_number");
            $sql = "UPDATE pkg_phonenumber SET info = 'Forward DTMF 1' WHERE id = $idPhonenumber LIMIT 1";
            $agi->exec($sql);

            $chanStatus = $agi->channel_status($MAGNUS->channel);
            if ($chanStatus['result'] == 6) {

                $MAGNUS->record_call = $modelCampaign->record_call;

                $forwardOption     = explode("|", $forward_number);
                $forwardOptionType = $forwardOption[0];

                $agi->verbose(print_r($forwardOption, true), 15);

                if ($forwardOptionType == 'sip') {

                    $sql      = "SELECT name FROM pkg_sip WHERE id = $forwardOption[1] LIMIT 1";
                    $modelSip = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                    $dialstr = 'SIP/' . $modelSip->name;

                    $MAGNUS->startRecordCall($agi);

                    $myres      = $MAGNUS->run_dial($agi, $dialstr, $MAGNUS->agiconfig['dialcommand_param_sipiax_friend']);
                    $dialstatus = $agi->get_variable("DIALSTATUS");
                    $dialstatus = $dialstatus['data'];

                    if ($dialstatus == "NOANSWER") {
                        $agi->stream_file('prepaid-callfollowme', '#');
                    } elseif (($dialstatus == "BUSY" || $dialstatus == "CHANUNAVAIL") || ($dialstatus == "CONGESTION")) {
                        $agi->stream_file('prepaid-isbusy', '#');
                    }
                } elseif ($forwardOptionType == 'queue') {

                    $DidAgi                                  = new DidAgi();
                    $DidAgi->modelDestination[0]['id_queue'] = $forwardOption[1];
                    $DidAgi->modelDid->did                   = $destination;

                    $agi->set_variable("CALLERID(num)", $destination . ' ' . $modelPhoneNumber->name);
                    $agi->set_callerid($destination . ' ' . $modelPhoneNumber->name);
                    $MAGNUS->CallerID = $destination . ' ' . $modelPhoneNumber->name;

                    QueueAgi::callQueue($agi, $MAGNUS, $CalcAgi, $DidAgi, 'torpedo');
                } elseif ($forwardOptionType == 'ivr') {

                    $DidAgi                                = new DidAgi();
                    $DidAgi->modelDestination[0]['id_ivr'] = $forwardOption[1];
                    $DidAgi->modelDid->did                 = $destination;

                    $agi->set_variable("CALLERID(num)", $destination . ' ' . $modelPhoneNumber->name);
                    $agi->set_callerid($destination . ' ' . $modelPhoneNumber->name);
                    $MAGNUS->CallerID = $destination . ' ' . $modelPhoneNumber->name;

                    IvrAgi::callIvr($agi, $MAGNUS, $CalcAgi, $DidAgi, 'torpedo');
                } elseif ($forwardOptionType == 'group') {

                    $agi->verbose("Call group " . $forwardOption[1], 25);
                    $sql      = "SELECT name FROM pkg_sip WHERE `group` = $forwardOption[1] LIMIT 1";
                    $modelSip = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                    if (isset($modelSip[0]) == 0) {
                        $agi->verbose('GROUP NOT FOUND');
                        $agi->stream_file('prepaid-invalid-digits', '#');

                    } else {
                        $group = '';
                        foreach ($modelSip as $key => $value) {
                            $group .= "SIP/" . $value->name . "&";
                        }

                        $dialstr = substr($group, 0, -1);
                        $agi->verbose("DIAL $dialstr", 25);
                        $MAGNUS->run_dial($agi, $dialstr, $MAGNUS->agiconfig['dialcommand_param_sipiax_friend']);
                    }

                } elseif ($forwardOptionType == 'custom') {
                    $agi->set_variable("CALLERID(num)", $destination . ' ' . $modelPhoneNumber->name);
                    $agi->set_callerid($destination . ' ' . $modelPhoneNumber->name);
                    $MAGNUS->CallerID = $destination . ' ' . $modelPhoneNumber->name;

                    if (preg_match('/AGI/', $forwardOption[1])) {
                        $agi = explode("|", $forwardOption[1]);
                        $agi->exec_agi($agi[1] . ",$destination,$idCampaign,$idPhonenumber");
                    } else if (strtoupper($forwardOption[1]) == 'SMS') {

                        $text = $modelCampaign->description;

                        $text = addslashes((string) $text);
                        //CODIFICA O TESTO DO SMS
                        $text = urlencode($text);

                        $sql = "SELECT pkg_rate.id AS idRate, rateinitial, buyrate, pkg_prefix.id AS id_prefix, pkg_rate.id_trunk,
                            rt_trunk.trunkcode, rt_trunk.trunkprefix, rt_trunk.removeprefix, rt_trunk.providertech, rt_trunk.inuse,
                            rt_trunk.maxuse, rt_trunk.status, rt_trunk.failover_trunk, rt_trunk.link_sms, rt_trunk.sms_res
                            FROM pkg_rate
                            LEFT JOIN pkg_plan ON pkg_rate.id_plan=pkg_plan.id
                            LEFT JOIN pkg_trunk AS rt_trunk ON pkg_rate.id_trunk=rt_trunk.id
                            LEFT JOIN pkg_prefix ON pkg_rate.id_prefix=pkg_prefix.id
                            WHERE prefix = SUBSTRING(999$destination,1,length(prefix)) and pkg_plan.id= " . $modelCampaign->id_plan . "
                            ORDER BY LENGTH(prefix) DESC";
                        $modelTrunk = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                        //retiro e adiciono os prefixos do tronco
                        if (strncmp($destination, $modelTrunk->removeprefix, strlen($modelTrunk->removeprefix)) == 0) {
                            $destination = substr($destination, strlen($modelTrunk->removeprefix));
                        }
                        $destination = $modelTrunk->trunkprefix . $destination;

                        $url = $modelTrunk->link_sms;
                        $url = preg_replace("/\%number\%/", $destination, $url);
                        $url = preg_replace("/\%text\%/", $text, $url);

                        $agi->verbose($url);

                        if (!$res = @file_get_contents($url, false)) {
                            $agi->verbose("ERRO SMS -> " . $url);
                        }

                    } else {
                        $MAGNUS->run_dial($agi, $forwardOption[1]);
                    }
                }

                $agi->set_variable("CALLERID(num)", $destination . ' ' . $modelPhoneNumber->name);
                $agi->set_callerid($destination . ' ' . $modelPhoneNumber->name);
                $MAGNUS->CallerID = $destination . ' ' . $modelPhoneNumber->name;

                if ($MAGNUS->agiconfig['record_call'] == 1 || $MAGNUS->record_call == 1) {
                    $myres = $agi->execute("StopMixMonitor");
                }
            }

        } else {
            $MAGNUS->sip_account = $MAGNUS->username;
        }

        //execute poll if exist
        if (isset($modelCampaignPoll[0])) {

            foreach ($modelCampaignPoll as $poll) {

                $repeat = $poll->repeat;

                if ($dtmf_result == -1) {
                    break;
                }
                sleep(1);
                $dtmf_result == '';

                if ($poll->id == 18 && $dtmf_result > 0) {
                    continue;
                }

                if ($poll->id == 20 && $dtmf_result > 0) {
                    continue;
                }

                for ($i = 0; $i < 12; $i++) {

                    $audio = $uploaddir . "idPoll_" . $poll->id;

                    if ($poll->request_authorize == 1) {
                        $agi->verbose('Request authorize', 5);
                        //IF CUSTOMER MARK 1 EXECUTE POLL
                        if ($res_dtmf['result'] == $modelCampaignPoll[0]->digit_authorize) {
                            $agi->verbose('Authorized', 5);
                            $res_dtmf = $agi->get_data($audio, 5000, 1);
                        } else {
                            $dtmf_result = -1;
                            $agi->verbose('NOT authorized', 5);
                            break;
                        }

                    } else {
                        $res_dtmf = $agi->get_data($audio, 5000, 1);
                    }

                    //GET RESULT OF POLL
                    $dtmf_result = $res_dtmf['result'];

                    $agi->verbose("Cliente votou na opcao: $dtmf_result", 5);

                    //Hungaup call if the fisrt poll dtmf is not numeric
                    if ($i == 0 && !is_numeric($dtmf_result)) {
                        $agi->verbose('nao votou nada na 1º enquete', 5);
                        break;
                    }

                    if ($repeat > 0) {
                        for ($i = 0; $i < $repeat; $i++) {

                            if ($i > 0) {
                                $agi->stream_file('prepaid-invalid-digits', ' #');

                                $res_dtmf = $agi->get_data($audio, 5000, 1);

                                //GET RESULT OF POLL
                                $dtmf_result = $res_dtmf['result'];
                            }

                            if ($i == 2) {
                                $agi->verbose('Client press invalid option after two try');
                                $dtmf_result = 'error';
                                break;
                            }

                            if (is_numeric($dtmf_result)) {
                                $agi->verbose("dtmf_result es numerico ", 8);

                                $sql               = "SELECT option" . $dtmf_result . " as resposta_option FROM pkg_campaign_poll WHERE id = $poll->id LIMIT 1";
                                $modelCampaignPoll = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                                $agi->verbose('$i' . $i . " " . $repeat, 25);
                                if ($modelCampaignPoll->resposta_option == '' && $i >= $repeat - 1) {
                                    $agi->verbose("Client press invalid option after try $repeat, hangup call on poll " . $poll->id);
                                    $agi->stream_file('prepaid-invalid-digits', ' #');
                                    $dtmf_result = 'error';
                                    break;
                                } else if ($modelCampaignPoll->resposta_option == '') {
                                    $agi->verbose("Client press invalid option $dtmf_result on poll " . $poll->id, 8);

                                } else {
                                    $agi->verbose("Client press number: $dtmf_result", 8);
                                    break;
                                }
                            }
                        }
                    }

                    if ($modelCampaignPoll->resposta_option != 'repeat') {
                        break;
                    }

                }

                if (is_numeric($dtmf_result) && $dtmf_result >= 0) {
                    //si esta hangup en la opcion, corlgar.
                    if (preg_match('/hangup/', $poll->{'option' . $dtmf_result})) {

                        $agi->verbose('desligar chamadas', 25);

                        $newIdPoll = explode('_', $poll->{'option' . $dtmf_result});

                        //si tiene una id en el hangup, executar el audio
                        if (isset($newIdPoll[1])) {
                            $audio    = $uploaddir . "idPoll_" . $newIdPoll[1];
                            $res_dtmf = $agi->get_data($audio, 5000, 1);
                        }

                        $sql = "INSERT INTO pkg_campaign_poll_info (id_campaign_poll,resposta,number,city ) VALUES
                             (  $poll->id, '$dtmf_result', '$destination', '$phonenumberCity') ";
                        $agi->exec($sql);

                        break;

                    } elseif (preg_match('/create/', $poll->{'option' . $dtmf_result})) {

                        $sql       = "SELECT * FROM pkg_plan WHERE signup = 1 LIMIT 1";
                        $modelPlan = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                        if (isset($modelPlan->id)) {

                            $id_plan         = $modelPlan->id;
                            $credit          = $modelPlan->ini_credit;
                            $sql             = "SELECT * FROM pkg_group_user WHERE id_user_type = 3 LIMIT 1";
                            $modelGroupUser  = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
                            $id_group        = $modelGroupUser->id;
                            $password        = Util::generatePassword(8, true, true, true, false);
                            $callingcard_pin = Util::getNewLock_pin($agi);
                            $prefix_local    = $MAGNUS->config['global']['base_language'] == 'pt_BR'
                            ? '0/55,*/5511/8,*/5511/9'
                            : '';
                            $fields = "username,password,id_user,id_plan,credit,id_group,active,prefix_local,callingcard_pin,loginkey,typepaid";
                            $values = "'$destination', '$password', '1', '$id_plan', '$credit', '$id_group',
                                    '1', '$prefix_local', '$callingcard_pin', '', 0";
                            $sql = "INSERT INTO pkg_user ($fields) VALUES ($values)";

                            if ($agi->exec($sql)) {

                                $fields = "id_user,accountcode,name,allow,host,insecure,defaultuser,secret";
                                $values = "'" . $agi->lastInsertId() . "', '$destination', '$destination',
                                            'g729,gsm,g726,alaw,ulaw', 'dynamic', 'no',
                                            '$destination', '$password'";
                                $sql = "INSERT INTO pkg_user ($fields) VALUES ($values)";
                                $agi->exec($sql);
                            }

                        } else {
                            $agi->verbose('NOT HAVE PLAN ENABLE ON SIGNUP', 25);
                        }

                    } else {

                        $fields = "id_campaign_poll,resposta,number,city";
                        $values = "'$poll->id', '$dtmf_result', '$destination', '$phonenumberCity'";
                        $sql    = "INSERT INTO pkg_campaign_poll_info ($fields) VALUES ($values)";
                        $agi->exec($sql);

                        if (preg_match('/SIP|sip/', $poll->{'option' . $res_dtmf['result']})) {
                            $MAGNUS->destination = $destination;
                            $MAGNUS->startRecordCall($agi);

                            $dialstr = $poll->{'option' . $res_dtmf['result']};
                            $dialstr = preg_replace("/number/", $destination, $dialstr);
                            $agi->set_variable("CALLERID(num)", $destination . ' ' . $modelPhoneNumber->name);
                            $agi->set_callerid($destination . ' ' . $modelPhoneNumber->name);
                            $MAGNUS->CallerID = $destination . ' ' . $modelPhoneNumber->name;
                            $agi->verbose('CALL SEND TO SIP IN POLL -> ' . $dialstr, 25);

                            $myres = $MAGNUS->run_dial($agi, $dialstr, $MAGNUS->agiconfig['dialcommand_param_sipiax_friend']);

                            $MAGNUS->stopRecordCall($agi);
                        }
                    }

                } else {
                    $agi->verbose('Cliente no marco nada', 8);
                    break;
                }

            }

            $agi->stream_file('prepaid-final', ' #');
        }
        $sql       = "SELECT * FROM pkg_rate WHERE id = $idRate LIMIT 1";
        $modelRate = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

        if (!isset($modelRate->id)) {
            return;
        }

        $id_prefix = $modelRate->id_prefix;
        /*buy rate*/
        $buyrate          = $modelRate->buyrate;
        $buyrateinitblock = $modelRate->buyrateinitblock;
        $buyrateincrement = $modelRate->buyrateincrement;
        /*sell rate*/
        $rateinitial  = $modelRate->rateinitial;
        $initblock    = $modelRate->initblock;
        $billingblock = $modelRate->billingblock;

        $id_trunk = $modelRate->id_trunk;

        $duration = time() - $now;

        /* ####     CALCUL BUYRATE COST     #####*/
        $buyratecost  = $MAGNUS->calculation_price($buyrate, $duration, $buyrateinitblock, $buyrateincrement);
        $sellratecost = $MAGNUS->calculation_price($rateinitial, $duration, $initblock, $billingblock);
        $agi->verbose("[TEMPO DA LIGAÃ‡AO] " . $duration, 8);

        $MAGNUS->id_plan = $modelRate->id_plan;
        if ($duration > 1) {

            if (isset($modelCampaign->enable_max_call) && $modelCampaign->enable_max_call == 1 && $duration >= $modelCampaign->nb_callmade) {
                //desativa a campanha se o limite de chamadas foi alcançado
                //diminui 1 do total de chamadas permitidas completas , se o tempo da chamada for superior ao tempo do audio
                $status = $modelCampaign->secondusedreal < 1 ? 0 : 1;
                $sql    = "UPDATE pkg_campaign SET status = $status, secondusedreal = secondusedreal -1
                        WHERE id = $modelCampaign->id LIMIT 1";
                $agi->exec($sql);

            }
            $CalcAgi->starttime        = date("Y-m-d H:i:s", time() - $duration);
            $CalcAgi->sessiontime      = $duration;
            $CalcAgi->real_sessiontime = intval($duration);
            $MAGNUS->destination       = $destination;
            $CalcAgi->terminatecauseid = 1;
            $CalcAgi->sessionbill      = $sellratecost;
            $MAGNUS->id_trunk          = $id_trunk;
            $CalcAgi->sipiax           = 5;
            $CalcAgi->buycost          = $buyratecost;
            $CalcAgi->id_prefix        = $id_prefix;
            $CalcAgi->id_campaign      = $idCampaign;
            $MAGNUS->CallerID          = intval($destination);

            $id_call = $CalcAgi->saveCDR($agi, $MAGNUS, true);

            if (!is_null($MAGNUS->id_agent) && $MAGNUS->id_agent > 1) {

                $sql                   = "SELECT * FROM pkg_user WHERE id_user = " . $MAGNUS->id_agent . " LIMIT 1";
                $modelAgent            = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
                $MAGNUS->id_plan_agent = $modelAgent->id_plan;
                $agi->verbose($MAGNUS->id_plan_agent);
                $agi->verbose('$MAGNUS->id_agent' . $MAGNUS->id_agent . ' $id_call' . $id_call . '$destination' . $destination, 10);

                $CalcAgi->sessiontime = $duration;
                $CalcAgi->updateSystemAgent($agi, $MAGNUS, $destination, $sellratecost, $duration);
            } else {
                $sql = "UPDATE pkg_user SET credit = credit - " . $MAGNUS->round_precision(abs($sellratecost)) . "
                        WHERE id =  $MAGNUS->id_user LIMIT 1";
                $agi->exec($sql);
            }
            $sql = "UPDATE pkg_trunk SET secondusedreal = secondusedreal + $duration
                        WHERE id =  $MAGNUS->id_trunk LIMIT 1";
            $agi->exec($sql);
        }

        $sql = "UPDATE pkg_phonenumber SET status = 3 WHERE id = $idPhonenumber LIMIT 1";
        $agi->exec($sql);
        $MAGNUS->hangup($agi);
    }

}
