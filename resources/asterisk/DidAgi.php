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

class DidAgi
{
    public $voip_call;
    public $did;
    public $sell_price;
    public $modelDestination;
    public $modelDid;
    public $startCall;
    public $id_prefix = 0;

    public function checkIfIsDidCall(&$agi, &$MAGNUS, &$CalcAgi)
    {
        $this->startCall = time();

        //check if did call
        $mydnid = substr($MAGNUS->dnid, 0, 1) == '0' ? substr($MAGNUS->dnid, 1) : $MAGNUS->dnid;
        $agi->verbose('Check If Is Did ' . $mydnid, 10);
        $sql            = "SELECT * FROM pkg_did WHERE did = '$mydnid' AND activated = 1 LIMIT 1";
        $this->modelDid = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
        if (isset($this->modelDid->id)) {
            $agi->verbose("Is a DID call", 5);
            $sql                    = "SELECT * FROM pkg_did_destination WHERE activated = 1 AND id_did = '" . $this->modelDid->id . "' ORDER BY priority";
            $this->modelDestination = $agi->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if (count($this->modelDestination)) {

                $this->did = $this->modelDid->did;

                $agi->verbose("Did have destination", 15);

                $sql               = "SELECT * FROM pkg_user WHERE id = " . $this->modelDid->id_user . " LIMIT 1";
                $MAGNUS->modelUser = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                if ($this->id_prefix == 0) {

                    $sql = "SELECT id FROM pkg_prefix WHERE prefix = SUBSTRING('" . $this->did . "',1,length(prefix))
                                    ORDER BY LENGTH(prefix) DESC";
                    $modelPrefix = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
                    if (!isset($modelPrefix->id)) {
                        $agi->verbose('Not found prefix to DID ' . $this->did);
                    }
                    $CalcAgi->id_prefix = $modelPrefix->id;

                    $sql              = "SELECT * FROM pkg_trunk WHERE trunkcode = '" . $MAGNUS->sip_account . "' LIMIT 1";
                    $modelTrunk       = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
                    $MAGNUS->id_trunk = isset($modelTrunk->id) ? $modelTrunk->id : null;
                } else {
                    $CalcAgi->id_prefix = $this->id_prefix;
                }

                if ($this->modelDid->calllimit > 0) {
                    $agi->verbose('Check DID channels');
                    $calls = AsteriskAccess::getCallsPerDid($this->modelDid->did);
                    $agi->verbose('Did ' . $this->modelDid->did . ' have ' . $calls . ' Calls');
                    if ($calls > $this->modelDid->calllimit) {

                        if ($MAGNUS->modelUser->calllimit_error == 403) {
                            $agi->execute((busy), busy);
                        } else {
                            $agi->execute((congestion), Congestion);
                        }

                        $MAGNUS->hangup($agi);
                        exit;
                    }
                }
                $this->checkDidDestinationType($agi, $MAGNUS, $CalcAgi);
            } else {
                $agi->verbose("Is a DID call But not have destination Hangup Call");
                $MAGNUS->hangup($agi);
            }
        }
    }
    public function checkDidDestinationType(&$agi, &$MAGNUS, &$CalcAgi)
    {

        $this->didCallCost($agi, $MAGNUS);

        $this->did = $this->modelDid->did;
        $agi->verbose('DID ' . $this->did, 5);

        //if DID option charge of was = 0 only allow call from existent callerid
        if ($this->modelDid->charge_of == 0) {

            $sql           = "SELECT * FROM pkg_callerid WHERE cid = '$MAGNUS->CallerID'  AND activated = 1 LIMIT 1";
            $modelCallerId = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
            if (isset($modelCallerId->id)) {
                $agi->verbose('found callerid, new user = ' . $MAGNUS->modelUser->username . ' ' . $this->sell_price);
                $CalcAgi->did_charge_of_id_user     = $modelCallerId->id_user;
                $CalcAgi->did_charge_of_answer_time = time();
                $CalcAgi->didAgi                    = $this->modelDid;
                $this->modelDid->selling_rate_1     = $this->sell_price;

            } else {
                $agi->verbose('NOT found callerid, = ' . $MAGNUS->CallerID . ' to did ' . $this->did . ' and was selected charge_of to callerID');
                $MAGNUS->hangup($agi);
            }
        }

        //check if is a call betewen 2 sipcounts.
        if (strlen($MAGNUS->accountcode) > 0) {
            $sql      = "SELECT * FROM pkg_sip WHERE name = '$this->did' LIMIT 1";
            $modelSip = $agi->query($sql)->fetch(PDO::FETCH_OBJ);
        }

        if (!isset($modelSip->id)) {

            if (strlen($this->modelDid->callerid)) {
                $agi->execute("SET", "CALLERID(name)=" . $this->modelDid->callerid . "");
            }

            $MAGNUS->record_call = $MAGNUS->modelUser->record_call;
            $MAGNUS->accountcode = $MAGNUS->username = $MAGNUS->modelUser->username;

            $this->voip_call = $this->modelDestination[0]['voip_call'];
            $this->checkBlockCallerID($agi, $MAGNUS);

            $agi->verbose('voip_call ' . $this->voip_call, 5);

            if ($this->modelDid->cbr == 1 && !$agi->get_variable("ISFROMCALLBACKPRO", true)) {
                if (!$agi->get_variable("SECCALL", true)) {
                    $agi->verbose('RECEIVED 0800 CALLBACPRO', 5);
                    CallbackAgi::advanced0800CallBack($agi, $MAGNUS, $this, $CalcAgi);
                    return;
                }
            }

            switch ($this->voip_call) {
                case 2:
                    $MAGNUS->mode = 'ivr';
                    IvrAgi::callIvr($agi, $MAGNUS, $CalcAgi, $this);
                    break;
                case 3:
                    //callingcard
                    $MAGNUS->mode = 'standard';
                    $agi->answer();
                    sleep(1);
                    $MAGNUS->callingcardConnection = $this->modelDid->connection_sell;

                    $MAGNUS->agiconfig['use_dnid']        = 0;
                    $MAGNUS->agiconfig['answer']          = $MAGNUS->agiconfig['callingcard_answer'];
                    $MAGNUS->agiconfig['cid_enable']      = $MAGNUS->agiconfig['callingcard_cid_enable'];
                    $MAGNUS->agiconfig['number_try']      = $MAGNUS->agiconfig['callingcard_number_try'];
                    $MAGNUS->agiconfig['say_rateinitial'] = $MAGNUS->agiconfig['callingcard_say_rateinitial'];
                    $MAGNUS->agiconfig['say_timetocall']  = $MAGNUS->agiconfig['callingcard_say_timetocall'];
                    $MAGNUS->accountcode                  = null;
                    $MAGNUS->CallerID                     = is_numeric($MAGNUS->CallerID) ? $MAGNUS->CallerID : $agi->request['agi_calleridname'];
                    $agi->verbose('CallerID ' . $MAGNUS->CallerID);
                    break;
                case 4:
                    $MAGNUS->mode = 'portalDeVoz';
                    $agi->verbose('PortalDeVozAgi');
                    PortalDeVozAgi::send($agi, $MAGNUS, $CalcAgi, $this);
                    break;
                case 5:
                    $agi->verbose('RECEIVED ANY CALLBACK', 5);
                    CallbackAgi::callbackCID($agi, $MAGNUS, $CalcAgi, $this);
                    break;
                case 6:
                    if (!$agi->get_variable("SECCALL", true)) {
                        $agi->verbose('RECEIVED 0800 CALLBACK', 5);
                        CallbackAgi::callback0800($agi, $MAGNUS, $CalcAgi, $this);
                    }
                    break;
                case 7:
                    $MAGNUS->mode = 'queue';
                    QueueAgi::callQueue($agi, $MAGNUS, $CalcAgi, $this);
                    break;
                default:
                    $agi->verbose('Mode = did', 5);
                    $MAGNUS->mode = 'did';
                    $this->call_did($agi, $MAGNUS, $CalcAgi);
                    break;
            }

            if ($agi->get_variable("ISFROMCALLBACKPRO", true)) {

                $sessiontime         = time() - $this->startCall;
                $sell                = $agi->get_variable("SELLCOST", true);
                $sellinitblock       = $agi->get_variable("SELLINITBLOCK", true);
                $sellincrement       = $agi->get_variable("SELLINCREMENT", true);
                $buy                 = $agi->get_variable("BUYCOST", true);
                $buyinitblock        = $agi->get_variable("BUYRATEINIT", true);
                $buyincrement        = $agi->get_variable("BUYINCREMENT", true);
                $MAGNUS->id_user     = $agi->get_variable("IDUSER", true);
                $MAGNUS->id_plan     = $agi->get_variable("IDPLAN", true);
                $MAGNUS->sip_account = $MAGNUS->destination;
                $MAGNUS->destination = $MAGNUS->CallerID;

                $sell_price                = $MAGNUS->roudRatePrice($sessiontime, $sell, $sellinitblock, $sellincrement);
                $buy_price                 = $MAGNUS->roudRatePrice($sessiontime, $buy, $buyinitblock, $buyincrement);
                $MAGNUS->id_trunk          = $agi->get_variable("IDTRUNK", true);
                $CalcAgi->starttime        = date("Y-m-d H:i:s", $this->startCall);
                $CalcAgi->sessiontime      = $sessiontime;
                $CalcAgi->terminatecauseid = 1;
                $CalcAgi->sessionbill      = $sell_price;
                $CalcAgi->sipiax           = 4;
                $CalcAgi->buycost          = $buy_price;
                $CalcAgi->id_prefix        = $agi->get_variable("IDPREFIX", true);
                $CalcAgi->saveCDR($agi, $MAGNUS);

                $sql = "UPDATE pkg_callback SET status = 3, last_attempt_time = '" . date('Y-m-d H:i:s') . "', sessiontime = $sessiontime
                        WHERE id = " . $agi->get_variable("IDCALLBACK", true);
                $agi->exec($sql);

                $sql = "UPDATE pkg_user SET credit = credit - $sell_price WHERE id = " . $MAGNUS->id_user;
                $agi->exec($sql);

                $MAGNUS->hangup($agi);
            }

        }
    }

    public function call_did(&$agi, &$MAGNUS, &$CalcAgi, $destinationIvr = false)
    {

        //sip call, group, custom or PSTN destination
        if ($MAGNUS->agiconfig['answer_call'] == 1) {
            $agi->verbose("ANSWER CALL", 6);
            $agi->answer();
        }

        $CalcAgi->init();
        $MAGNUS->init();

        $agi->verbose("DID CALL - CallerID=" . $MAGNUS->CallerID . " -> DID=" . $this->did, 6);

        $res = 0;

        $MAGNUS->agiconfig['say_timetocall'] = 0;

        //altera o destino do did caso ele venha de uma IVR
        $this->modelDestination[0]['destination'] = $destinationIvr ? $destinationIvr : $this->modelDestination[0]['destination'];

        $callcount = 0;

        foreach ($this->modelDestination as $inst_listdestination) {

            $agi->verbose(print_r($inst_listdestination, true), 10);

            $callcount++;

            $MAGNUS->agiconfig['cid_enable'] = 0;
            $MAGNUS->accountcode             = $MAGNUS->username             = $MAGNUS->modelUser->username;
            $MAGNUS->id_plan                 = $MAGNUS->modelUser->id_plan;

            $msg = "[Magnus] DID call friend: FOLLOWME=$callcount (username:" . $MAGNUS->username . "
                    | destination type:" . $this->voip_call . "| id_plan:" . $MAGNUS->id_plan . ")";
            $agi->verbose($msg, 10);

            if (AuthenticateAgi::authenticateUser($agi, $MAGNUS) != 1) {
                $msg = "DID AUTHENTICATION ERROR";
            } else {

                $MAGNUS->record_call = $MAGNUS->modelUser->record_call;

                $agi->verbose("DID call friend: IS LOCAL !!!", 1);

                /* IF SIP CALL*/
                if ($inst_listdestination['voip_call'] == 1) {

                    $sql              = "SELECT * FROM pkg_sip WHERE id = " . $inst_listdestination['id_sip'] . " LIMIT 1";
                    $MAGNUS->modelSip = $modelSip = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                    if (isset($modelSip->id)) {
                        $MAGNUS->voicemail   = isset($modelSip->voicemail) ? $modelSip->voicemail : false;
                        $MAGNUS->destination = $modelSip->name;
                    } else {
                        $agi->stream_file('prepaid-dest-unreachable', '#');
                        continue;
                    }
                    $MAGNUS->sip_account = $MAGNUS->modelSip->name;
                    $agi->verbose('Call to user ' . $modelSip->name, 1);

                    $MAGNUS->extension = $MAGNUS->destination = $MAGNUS->dnid = $modelSip->name;

                    $dialResult = SipCallAgi::processCall($MAGNUS, $agi, $CalcAgi, 'fromDID');

                    $dialstatus   = $dialResult['dialstatus'];
                    $answeredtime = $dialResult['answeredtime'];

                    $agi->verbose($inst_listdestination['destination'] . " Friend -> followme=$callcount : ANSWEREDTIME=" . $answeredtime . "-DIALSTATUS=" . $dialstatus, 1);

                    if ($this->parseDialStatus($agi, $dialstatus, $answeredtime) != true) {
                        $answeredtime = 0;
                        continue;
                    }
                }
                /* Call to group*/
                else if ($inst_listdestination['voip_call'] == 8) {

                    $agi->verbose("Call group $group ", 6);
                    $sql      = "SELECT * FROM pkg_sip WHERE sip_group = '" . $inst_listdestination['destination'] . "'";
                    $modelSip = $agi->query($sql)->fetchAll(PDO::FETCH_OBJ);
                    $agi->verbose("Call group $group ", 6);
                    if (!isset($modelSip[0]->id)) {
                        $answeredtime = 0;
                        continue;
                    }

                    $group = '';

                    foreach ($modelSip as $key => $value) {

                        $group .= "SIP/" . $value->name . "&";
                    }

                    $dialstr = substr($group, 0, -1) . $dialparams;

                    $MAGNUS->startRecordCall($agi, $this->did);

                    $agi->verbose("DIAL $dialstr", 6);
                    $myres = $MAGNUS->run_dial($agi, $dialstr, $MAGNUS->agiconfig['dialcommand_param_call_2did']);

                    $sipaccount          = $agi->get_variable("DIALEDPEERNUMBER");
                    $MAGNUS->sip_account = $sipaccount['data'];
                    $answeredtime        = $agi->get_variable("ANSWEREDTIME");
                    $answeredtime        = $answeredtime['data'];
                    $dialstatus          = $agi->get_variable("DIALSTATUS");
                    $dialstatus          = $dialstatus['data'];

                    $MAGNUS->stopRecordCall($agi);

                    if ($this->parseDialStatus($agi, $dialstatus, $answeredtime) != true) {
                        $answeredtime = 0;
                        continue;
                    }

                }
                /* Call to custom dial*/
                else if ($inst_listdestination['voip_call'] == 9) {
                    //SMS@O numero %callerid% acabou de  ligar.
                    if (strtoupper(substr($inst_listdestination['destination'], 0, 3)) == 'SMS') {
                        //url format ->  SMS|Text|trunkname
                        $sms = explode('|', $inst_listdestination['destination']);

                        $destination = $MAGNUS->modelUser->mobile;
                        $trunk       = $sms[2];
                        $text        = $sms[1];
                        $text        = preg_replace("/\%callerid\%/", $MAGNUS->CallerID, $text);

                        if (file_exists('/var/lib/asterisk/sounds/' . $this->did . '.gsm')) {
                            $agi->verbose('execute earlymedia');
                            $agi->verbose('earl ok');
                            $agi->execute('Ringing');
                            $agi->execute("Progress");
                            $agi->execute('Wait', '1');
                            $agi->execute('Playback', $this->did . ",noanswer");
                        }

                        $text = addslashes((string) $text);
                        //CODIFICA O TESTO DO SMS
                        $text = urlencode($text);

                        $sql        = "SELECT link_sms, removeprefix, trunkprefix FROM pkg_trunk WHERE trunkcode = '$trunk' LIMIT 1";
                        $modelTrunk = $agi->query($sql)->fetch(PDO::FETCH_OBJ);

                        //retiro e adiciono os prefixos do tronco
                        if (strncmp($destination, $modelTrunk->removeprefix, strlen($modelTrunk->removeprefix)) == 0) {
                            $destination = substr($destination, strlen($modelTrunk->removeprefix));
                        }
                        $destination = $modelTrunk->trunkprefix . $destination;
                        $agi->verbose($destination);
                        $url = $modelTrunk->link_sms;
                        $url = preg_replace("/\%number\%/", $destination, $url);
                        $url = preg_replace("/\%text\%/", $text, $url);

                        $agi->verbose($url);
                        file_get_contents($url);

                        $answeredtime = 60;
                        $dialstatus   = 'ANSWER';
                        $agi->execute('Congestion', '5');
                        break;
                    } else {
                        $agi->verbose("Ccall group $group ", 6);
                        $dialstr = $inst_listdestination['destination'];

                        $MAGNUS->startRecordCall($agi, $this->did);

                        if (preg_match('/PUSH/', $dialstr)) {

                            if (file_exists(dirname(__FILE__) . '/push/Push.php')) {
                                include dirname(__FILE__) . '/push/Push.php';

                                $tmp = explode('PUSH/', $dialstr);
                                foreach ($tmp as $key => $value) {

                                    $agi->verbose($value);
                                    if (preg_match('/^SIP|LOCAL|AGI/', strtoupper($value))) {
                                        continue;
                                    }

                                    $dialString = preg_split('/\,|\&/', $value);
                                    $agi->verbose("there are PUSH on DID custom dial " . $dialString[0]);
                                    Push::send($agi, $dialString[0], $MAGNUS->CallerID, 0);
                                }
                                sleep(5);
                            }
                            $dialstr = preg_replace('/PUSH/', 'SIP', $dialstr);
                        }

                        $agi->verbose("DIAL $dialstr", 6);
                        $myres = $MAGNUS->run_dial($agi, $dialstr, $MAGNUS->agiconfig['dialcommand_param_call_2did']);
                        $MAGNUS->stopRecordCall($agi);

                        $answeredtime = $agi->get_variable("ANSWEREDTIME");
                        $answeredtime = $answeredtime['data'];
                        $dialstatus   = $agi->get_variable("DIALSTATUS");
                        $dialstatus   = $dialstatus['data'];

                        if ($this->parseDialStatus($agi, $dialstatus, $answeredtime) != true) {
                            $answeredtime = 0;
                            continue;
                        }
                    }

                } else {
                    /* CHECK IF DESTINATION IS SET*/
                    if (strlen($inst_listdestination['destination']) == 0) {
                        continue;
                    }

                    $MAGNUS->agiconfig['use_dnid']       = 1;
                    $MAGNUS->agiconfig['say_timetocall'] = 0;

                    //if is a PSTN call can destination format is number@callerID, set the CallID.
                    if (preg_match("/@/", $inst_listdestination['destination'])) {
                        $destinationCallerID                 = explode('@', $inst_listdestination['destination']);
                        $inst_listdestination['destination'] = $destinationCallerID[0];
                        $agi->set_callerid($destinationCallerID[1]);
                    }

                    $MAGNUS->extension = $MAGNUS->dnid = $MAGNUS->destination = $inst_listdestination['destination'];

                    if ($MAGNUS->checkNumber($agi, $CalcAgi, 0) == true) {

                        /* PERFORM THE CALL*/
                        $result_callperf = $CalcAgi->sendCall($agi, $MAGNUS->destination, $MAGNUS);
                        if (!$result_callperf) {
                            $prompt = "prepaid-callfollowme";
                            $agi->verbose($prompt, 10);
                            $agi->stream_file($prompt, '#');
                            continue;
                        }

                        $dialstatus   = $CalcAgi->dialstatus;
                        $answeredtime = $CalcAgi->answeredtime;

                        if (($CalcAgi->dialstatus == "NOANSWER") || ($CalcAgi->dialstatus == "BUSY") || ($CalcAgi->dialstatus == "CHANUNAVAIL") || ($CalcAgi->dialstatus == "CONGESTION")) {
                            continue;
                        }

                        if ($CalcAgi->dialstatus == "CANCEL") {
                            break;
                        }

                        /* INSERT CDR  & UPDATE SYSTEM*/
                        $CalcAgi->updateSystem($MAGNUS, $agi, 1, 1);

                        $sql = "UPDATE pkg_did_destination SET secondusedreal = secondusedreal + $CalcAgi->answeredtime
                                WHERE id = " . $this->modelDestination[0]['id'] . " LIMIT 1";
                        $agi->exec($sql);

                        /* THEN STATUS IS ANSWER*/
                        break;
                    }
                }
            }
            /* END IF AUTHENTICATE*/
        }

        $answeredtime = $MAGNUS->executeVoiceMail($agi, $dialstatus, $answeredtime);

        $agi->verbose('DID answeredtime =' . $answeredtime, 25);
        if ($answeredtime > 0) {
            $this->call_did_billing($agi, $MAGNUS, $CalcAgi, $answeredtime, $dialstatus);
            return 1;
        }
    }
    public function checkBlockCallerID(&$agi, &$MAGNUS)
    {
        $agi->verbose("try blocked", 5);
        $block_expression_1 = $this->modelDid->block_expression_1;
        $block_expression_2 = $this->modelDid->block_expression_2;
        $block_expression_3 = $this->modelDid->block_expression_3;

        $send_to_callback_1 = $this->modelDid->send_to_callback_1;
        $send_to_callback_2 = $this->modelDid->send_to_callback_2;
        $send_to_callback_3 = $this->modelDid->send_to_callback_3;

        $expression_1 = $this->modelDid->expression_1;
        $expression_2 = $this->modelDid->expression_2;
        $expression_3 = $this->modelDid->expression_3;

        if ($block_expression_1 == 1 || $send_to_callback_1) {
            $agi->verbose("try blocked number match with expression 1, " . $MAGNUS->CallerID . ' ' . $expression_2, 10);
            if (strlen($expression_1) > 1 && preg_match('/' . $expression_1 . '/', $MAGNUS->CallerID)) {

                if ($block_expression_1 == 1) {
                    $agi->verbose("Call blocked becouse this number match with expression 1, " . $MAGNUS->CallerID . ' FROM did ' . $this->did, 10);
                    $MAGNUS->hangup($agi);
                } elseif ($send_to_callback_1 == 1) {
                    $agi->verbose('Send to Callback expression 1', 10);
                    $this->voip_call = 6;
                }
            }
        }

        if ($block_expression_2 == 1 || $send_to_callback_2) {
            $agi->verbose("try blocked number match with expression 2, " . $MAGNUS->CallerID . ' ' . $expression_2, 1);
            if (strlen($expression_2) > 1 && preg_match('/' . $expression_2 . '/', $MAGNUS->CallerID)) {
                if ($block_expression_2 == 1) {
                    $agi->verbose("Call blocked becouse this number match with expression 2, " . $MAGNUS->CallerID . ' FROM did ' . $this->did, 10);
                    $MAGNUS->hangup($agi);
                } elseif ($send_to_callback_2 == 1) {
                    $agi->verbose('Send to Callback expression 2', 10);
                    $this->voip_call = 6;
                }
            }
        }

        if ($block_expression_3 == 1 || $send_to_callback_3) {
            $agi->verbose("try blocked number match with expression 3, " . $MAGNUS->CallerID . ' ' . $expression_3, 10);
            if (strlen($expression_3) > 0 && (preg_match('/' . $expression_3 . '/', $MAGNUS->CallerID) || $expression_3 == '*') &&
                strlen($expression_1) > 1 && !preg_match('/' . $expression_1 . '/', $MAGNUS->CallerID) &&
                strlen($expression_2) > 1 && !preg_match('/' . $expression_2 . '/', $MAGNUS->CallerID)
            ) {

                if ($block_expression_3 == 1) {
                    $agi->verbose("Call blocked becouse this number match with expression 3, " . $MAGNUS->CallerID . ' FROM did ' . $this->did, 10);
                    $MAGNUS->hangup($agi);
                } elseif ($send_to_callback_3 == 1) {
                    $agi->verbose('Send to Callback expression 3', 10);
                    $this->voip_call = 6;
                }
            }
        }
    }

    public function parseDialStatus(&$agi, $dialstatus, $answeredtime)
    {
        $agi->verbose('parseDialStatus', 25);
        if ($dialstatus == "BUSY") {
            if ($this->play_audio == 1) {
                $agi->stream_file('prepaid-isbusy', '#');
            } else {
                $agi->execute((busy), busy);
            }
            return false;
        } elseif ($dialstatus == "NOANSWER") {
            if ($this->play_audio == 1) {
                $agi->stream_file('prepaid-callfollowme', '#');
            }
            return false;
        } elseif ($dialstatus == "CANCEL") {
            return true;
        } elseif ($dialstatus == "ANSWER") {
            $agi->verbose("[Magnus] DID call friend: dialstatus : $dialstatus, answered time is " . $answeredtime . " ", 10);
            return true;
        } elseif (($dialstatus == "CHANUNAVAIL") || ($dialstatus == "CONGESTION")) {
            return false;
        } else {
            if ($this->play_audio == 1) {
                $agi->stream_file('prepaid-callfollowme', '#');
            }
            return false;
        }
    }

    public function didCallCost(&$agi, &$MAGNUS)
    {
        $agi->verbose('didCallCost', 10);
        if (file_exists(dirname(__FILE__) . '/didCallCost.php')) {
            include dirname(__FILE__) . '/didCallCost.php';
            return;
        }

        //brazil mobile - ^[4,5,6][1-9][7-9].{7}$|^[1,2,3,7,8,9][1-9]9.{8}$
        //brazil fixed - ^[1-9][0-9][1-5].
        $agi->verbose(print_r($this->modelDestination[0], true), 25);
        if (strlen($this->modelDid->expression_1) > 0 && preg_match('/' . $this->modelDid->expression_1 . '/', $MAGNUS->CallerID) || $this->modelDid->expression_1 == '*') {
            $agi->verbose("CallerID Match regular expression 1 " . $MAGNUS->CallerID, 10);
            $selling_rate = $this->modelDid->selling_rate_1;

        } elseif (strlen($this->modelDid->expression_2) > 0 && preg_match('/' . $this->modelDid->expression_2 . '/', $MAGNUS->CallerID) || $this->modelDid->expression_2 == '*') {
            $agi->verbose("CallerID Match regular expression 2 " . $MAGNUS->CallerID, 10);
            $selling_rate = $this->modelDid->selling_rate_2;
        } elseif (strlen($this->modelDid->expression_3) > 0 && preg_match('/' . $this->modelDid->expression_3 . '/', $MAGNUS->CallerID) || $this->modelDid->expression_3 == '*') {
            $agi->verbose("CallerID Match regular expression 3 " . $MAGNUS->CallerID, 10);
            $selling_rate = $this->modelDid->selling_rate_3;
        } else {
            $selling_rate = 0;
        }

        if ($this->modelDid->connection_sell == 0 && $selling_rate == 0) {
            $this->sell_price = 0;
        } else {
            $this->sell_price = $selling_rate;
        }

        $credit = $MAGNUS->modelUser->typepaid == 1
        ? $MAGNUS->modelUser->credit + $MAGNUS->modelUser->creditlimit
        : $MAGNUS->modelUser->credit;

        if ($MAGNUS->modelUser->active != 1) {
            $agi->verbose("HANGUP BECAUSE USER IS NOT ACTIVE " . $username, 10);
            $MAGNUS->hangup($agi);
        } else if ($this->sell_price > 0 && $credit <= 0) {
            $agi->verbose(" USER NO CREDIT FOR CALL " . $username, 10);
            $MAGNUS->hangup($agi);
        }
    }

    public function billDidCall(&$agi, &$MAGNUS, $answeredtime)
    {
        $agi->verbose('billDidCall, sell_price=' . $this->sell_price, 10);

        $this->sell_price = $MAGNUS->roudRatePrice($answeredtime, $this->sell_price, $this->modelDid->initblock, $this->modelDid->increment);

        $this->sell_price = $this->sell_price + $this->modelDid->connection_sell;

        if ($answeredtime < $this->modelDid->minimal_time_charge) {
            $this->sell_price = 0;
        }

        $agi->verbose(' answeredtime = ' . $answeredtime . ' sell_price = ' . $this->sell_price . ' connection_sell = ' . $this->modelDid->connection_sell, 10);
    }

    public function call_did_billing(&$agi, &$MAGNUS, &$CalcAgi, $answeredtime, $dialstatus)
    {
        if ($answeredtime > 0) {
            $terminatecauseid = 1;
        } else if (strlen($MAGNUS->dialstatus_rev_list[$dialstatus]) > 0) {
            $terminatecauseid = $MAGNUS->dialstatus_rev_list[$dialstatus];
        } else {
            $terminatecauseid = 0;
        }

        /*recondeo call*/
        if ($MAGNUS->config["global"]['bloc_time_call'] == 1 && $this->sell_price > 0) {
            $initblock    = $this->modelDid->initblock > 0 ? $this->modelDid->initblock : 1;
            $billingblock = $this->modelDid->increment > 0 ? $this->modelDid->increment : 1;

            if ($answeredtime > $initblock) {
                $restominutos   = $answeredtime % $billingblock;
                $calculaminutos = ($answeredtime - $restominutos) / $billingblock;
                if ($restominutos > '0') {
                    $calculaminutos++;
                }

                $answeredtime = $calculaminutos * $billingblock;

            } elseif ($answeredtime < '1') {
                $sessiontime = 0;
            } else {
                $answeredtime = $initblock;
            }

        }

        $this->billDidCall($agi, $MAGNUS, $answeredtime);

        if ($this->sell_price > 0) {
            $sql = "UPDATE pkg_user SET credit = credit - " . $MAGNUS->round_precision(abs($this->sell_price)) . "
                 WHERE  id = " . $MAGNUS->modelUser->id . " LIMIT 1";
            $agi->exec($sql);
        }

        $CalcAgi->starttime        = date("Y-m-d H:i:s", time() - $answeredtime);
        $CalcAgi->sessiontime      = $answeredtime;
        $CalcAgi->real_sessiontime = intval($answeredtime);
        $MAGNUS->destination       = $this->did;
        $CalcAgi->terminatecauseid = $terminatecauseid;
        $CalcAgi->sessionbill      = $this->sell_price;
        $MAGNUS->id_trunk          = $MAGNUS->id_trunk > 0 ? $MAGNUS->id_trunk : null;
        $CalcAgi->sipiax           = 3;
        $CalcAgi->buycost          = 0;
        $CalcAgi->saveCDR($agi, $MAGNUS);

        $sql = "UPDATE pkg_did_destination SET secondusedreal = secondusedreal + $answeredtime
                    WHERE  id = " . $this->modelDestination[0]['id'] . " LIMIT 1;
                UPDATE pkg_did SET secondusedreal = secondusedreal + $answeredtime
                    WHERE  id = " . $this->modelDid->id . " LIMIT 1";
        $agi->exec($sql);

        return;
    }
}
