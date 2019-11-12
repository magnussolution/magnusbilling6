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
class SmsSend
{
    public static function send($modelUser, $destination, $text, $id_phonenumber = 0)
    {
        if (!count($modelUser)) {
            return array(
                'success' => false,
                'msg'     => Yii::t('yii', 'Error : Autentication Error!'),
            );
        }

        if (!$destination || !$text) {
            return array(
                'success' => false,
                'msg'     => Yii::t('yii', 'Disallowed action'),
            );
        }

        //VERIFICA SE O CLIENTE TEM CREDITO
        if (UserCreditManager::checkGlobalCredit($modelUser->id) === false) {
            return array(
                'success' => false,
                'msg'     => Yii::t('yii', 'Error : You don t have enough credit to call you SMS!'),
            );
        }

        /*protabilidade*/
        $destination = Portabilidade::getDestination($destination, $modelUser->id_plan);

        $destination = "999" . $destination;

        $date_msn = date("Y-m-d H:i:s");

        //PEGA O PREÇO DE VENDA DO AGENT
        if ($modelUser->id_user > 1) {

            $modelRate = Rate::model()->searchAgentRate($destination, $modelUser->id_plan);

            if (!count($modelRate)) {
                return array(
                    'success' => false,
                    'msg'     => Yii::t('yii', 'Prefix not found in Agent') . ' ' . $destination,
                );
            }

            $rateInitialClientAgent = $modelRate[0]['rateinitial'];

            $modelUserAgent = User::model()->findByPk((int) $modelUser->id_user);

            $modelUser->id_plan = $modelUserAgent->id_plan;
        } else {
            $rateInitialClientAgent = 0;
        }

        $searchTariff = new SearchTariff();
        $callTrunk    = $searchTariff->find($destination, $modelUser->id_plan, $modelUser->id);

        if ($callTrunk == 0) {
            return array(
                'success' => false,
                'msg'     => Yii::t('yii', 'Prefix not found') . ' ' . $destination,
            );
        } else {
            //RETIRO O 1111
            if (substr($destination, 0, 7) == '9991111') {
                $destination = substr($destination, 10);
            } else if (substr($destination, 0, 3) == '999') {
                $destination = substr($destination, 3);
            }

            if (!isset($callTrunk[0]['link_sms'])) {
                return array(
                    'success' => false,
                    'msg'     => Yii::t('yii', 'No sms link'),
                );
            }

            $linkSms      = isset($callTrunk[0]['link_sms']) ? $callTrunk[0]['link_sms'] : null;
            $trunkPrefix  = isset($callTrunk[0]['rc_trunkprefix']) ? $callTrunk[0]['rc_trunkprefix'] : null;
            $removePrefix = isset($callTrunk[0]['rc_removeprefix']) ? $callTrunk[0]['rc_removeprefix'] : null;
            $smsRes       = isset($callTrunk[0]['sms_res']) ? $callTrunk[0]['sms_res'] : null;

            $buyRate     = isset($callTrunk[0]['buyrate']) ? $callTrunk[0]['buyrate'] : null;
            $rateInitial = isset($callTrunk[0]['rateinitial']) ? $callTrunk[0]['rateinitial'] : null;
            $id_prefix   = isset($callTrunk[0]['id_prefix']) ? $callTrunk[0]['id_prefix'] : null;

            //retiro e adiciono os prefixos do tronco
            if (strncmp($destination, $removePrefix, strlen($removePrefix)) == 0) {
                $destination = substr($destination, strlen($removePrefix));
            }

            $destination = $trunkPrefix . $destination;

            //Adiciona barras invertidas a uma string
            $text = addslashes((string) $text);
            //CODIFICA O TESTO DO SMS
            $text = urlencode($text);

            $linkSms = preg_replace("/\%number\%/", $destination, $linkSms);
            $linkSms = preg_replace("/\%text\%/", $text, $linkSms);
            if ($id_phonenumber > 0) {
                $linkSms = preg_replace("/\%id\%/", $id_phonenumber, $linkSms);
            }
            if (strlen($linkSms) < 10) {
                return array(
                    'success' => false,
                    'msg'     => Yii::t('yii', 'Your SMS is not send!') . ' ' . Yii::t('yii', 'Not have link in trunk'),
                );

            }

            if (!$res = @file_get_contents($linkSms, false)) {
                return array(
                    'success' => false,
                    'msg'     => Yii::t('yii', 'ERROR, contact us'),
                );
            }

            //DESCODIFICA O TESTO DO SMS PARA GRAVAR NO BANCO DE DADOS
            $text = urldecode($text);

            $sussess = !$smsRes == '' && !preg_match("/$smsRes/", $res) ? false : true;

            if ($sussess) {
                $terminateCauseid = 1;
                $sessionTime      = 60;
                $rateInitial      = strlen($text) > 160 ? $rateInitial * 2 : $rateInitial;
                $msg              = Yii::t('yii', 'Send');
                $success          = true;

                $modelSms            = new Sms();
                $modelSms->id_user   = $modelUser->id;
                $modelSms->prefix    = $id_prefix;
                $modelSms->telephone = $destination;
                $modelSms->sms       = $text;
                $modelSms->result    = $sussess;
                $modelSms->rate      = $rateInitial;
                $modelSms->save();

                //RETIRA CREDITO DO CLIENTE
                if ($modelUser->id_user > 1) {

                    User::model()->updateByPk($modelUser->id,
                        array(
                            'credit' => new CDbExpression('credit - ' . $rateInitialClientAgent),
                        )
                    );
                } else {
                    User::model()->updateByPk($modelUser->id,
                        array(
                            'credit' => new CDbExpression('credit - ' . $rateInitial),
                        )
                    );
                }

                //RETIRA CREDITO DO REVENDEDOR
                if ($modelUser->id_user > 1) {
                    User::model()->updateByPk($modelUser->id_user,
                        array(
                            'credit' => new CDbExpression('credit - ' . $buyRate),
                        )
                    );
                }
            } else {
                $buyRate          = 0;
                $terminateCauseid = 4;
                $rateInitial      = 0;
                $sessionTime      = 0;
                $msg              = Yii::t('yii', 'Your SMS is not send!');
                $success          = false;
            }

            $uniqueid = "$destination-" . date('His');

            $modelCall                   = new Call();
            $modelCall->uniqueid         = $uniqueid;
            $modelCall->id_user          = $modelUser->id;
            $modelCall->starttime        = $date_msn;
            $modelCall->sessiontime      = $sessionTime;
            $modelCall->calledstation    = $destination;
            $modelCall->sessionbill      = $rateInitial;
            $modelCall->id_plan          = $modelUser->id_plan;
            $modelCall->id_trunk         = $callTrunk[0]['id_trunk'];
            $modelCall->src              = $modelUser->username;
            $modelCall->buycost          = $buyRate;
            $modelCall->terminatecauseid = $terminateCauseid;
            $modelCall->id_prefix        = $id_prefix;
            $modelCall->sipiax           = 6;
            $modelCall->agent_bill       = $rateInitialClientAgent;
            $modelCall->save();
            $modelError = $modelCall->getErrors();
            if (count($modelError)) {
                $msg = $modelError;
            }

            return array(
                'success' => $success,
                'msg'     => $msg);
        }
    }
}
