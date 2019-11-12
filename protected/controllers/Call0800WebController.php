<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
/**
 * Acoes do modulo "Call".
 *
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
 * 19/09/2012
 */

class Call0800WebController extends Controller
{

    public function actionIndex()
    {

        Yii::app()->setLanguage($this->config['global']['base_language']);

        if (!isset($_REQUEST['number'])) {

            $this->render('index', array(
                'send' => false,
            ));

        } else {

            $destination = isset($_REQUEST['number']) ? $_REQUEST['number'] : '';
            $user        = isset($_GET['user']) ? $_GET['user'] : '';

            $model = Sip::model()->find("name = :user", array(':user' => $user));

            if (!isset($model->id)) {

                $model = Iax::model()->find("name = :user", array(':user' => $user));
                if (!isset($model->id)) {
                    $error_msg = Yii::t('yii', 'Error : User no Found!');
                    echo $error_msg;
                    exit;
                } else {
                    $type = 'IAX2';
                }
            } else {
                $type = 'SIP';
            }

            $dialstr = $type . '/' . $model->name;

            // gerar os arquivos .call
            $call = "Channel: " . $dialstr . "\n";
            $call .= "Callerid: " . $model->callerid . "\n";
            $call .= "Context: billing\n";
            $call .= "Extension: " . $user . "\n";
            $call .= "Priority: 1\n";
            $call .= "Set:IDUSER=" . $model->id_user . "\n";
            $call .= "Set:SECCALL=" . $destination . "\n";

            AsteriskAccess::generateCallFile($call);

            $this->render('index', array(
                'send' => true,
            ));

        }

    }

    public function actionCallback()
    {

        if (isset($_GET['l'])) {

            $data = explode('|', $_GET['l']);

            Yii::log(print_r($data, true), 'error');

            if (!isset($data[2])) {
                echo 'Your number is required';

            } else if (strlen($data[2]) < 4) {
                echo 'The minimum length for your number is 4';

            } else if (!isset($data[3])) {
                echo 'Destination is required';

            } else if (strlen($data[3]) < 4) {
                echo 'The minimum length for destination is 4';

            } else {

                $user = $data[0];
                $pass = $data[1];

                $modelSip = AccessManager::checkAccess($user, $pass);

                if (!is_array($modelSip) || !count($modelSip)) {
                    echo 'User or password is invalid';
                    exit;
                }

                if ($modelSip->id_user > 1) {
                    $modelUserAgent = User::model()->findByPk((int) $modelSip->id_user);

                    //VERIFICA SE O AGENT TEM CREDITO
                    if (isset($modelUserAgent->credit) && $modelUserAgent->credit <= 0) {
                        echo Yii::t('yii', 'You don t have enough credit to call');
                        exit;
                    }
                }

                if (isset($modelSip->idUser->credit) && $modelSip->idUser->credit <= 0.5) {
                    echo 'You don t have enough credit to call';
                    exit;
                }

                $yournumber  = $data[2];
                $destination = $data[3];

                if (preg_match("/->/", $destination)) {
                    $destination = explode("->", $destination);
                    $destination = preg_replace("/-|\(|\)| /", "", $destination[1]);
                    Yii::log(print_r($destination, true), 'error');
                } elseif (preg_match("/ - /", $destination)) {
                    $destination = explode(" - ", $destination);
                    $destination = preg_replace("/-|\(|\)| /", "", $destination[1]);
                    Yii::log(print_r($destination, true), 'error');
                }

                $yournumber  = Util::number_translation($modelSip->idUser->prefix_local, $yournumber);
                $destination = Util::number_translation($modelSip->idUser->prefix_local, $destination);

                /*protabilidade*/

                $SearchTariff = new SearchTariff();
                $callTrunk    = $SearchTariff->find($yournumber, $modelSip->idUser->id_plan, $modelSip->idUser->id);

                if (!is_array($callTrunk) || !count($callTrunk)) {
                    echo Yii::t('yii', 'Prefix not found to you number');
                    exit;
                }

                $destination = Portabilidade::getDestination($destination, $modelSip->idUser->id_plan);

                $callTrunkDestination = $SearchTariff->find($destination, $modelSip->idUser->id_plan, $modelSip->idUser->id);

                if (!is_array($callTrunkDestination) || count($callTrunkDestination) == 0) {
                    echo $sql;
                    echo Yii::t('yii', 'Prefix not found to destination');

                    exit;
                }

                if (substr("$yournumber", 0, 4) == 1111) {
                    $yournumber = str_replace(substr($yournumber, 0, 7), "", $yournumber);
                }

                if (substr("$destination", 0, 4) == 1111) {
                    $destination = str_replace(substr($destination, 0, 7), "", $destination);
                }

                $yournumber   = $yournumber;
                $providertech = $callTrunk[0]['rc_providertech'];
                $ipaddress    = $callTrunk[0]['rc_providerip'];
                $removeprefix = $callTrunk[0]['rc_removeprefix'];
                $prefix       = $callTrunk[0]['rc_trunkprefix'];

                if (strncmp($yournumber, $removeprefix, strlen($removeprefix)) == 0) {
                    $yournumber = substr($yournumber, strlen($removeprefix));
                }

                $dialstr = "$providertech/$ipaddress/$prefix$yournumber";

                // gerar os arquivos .call
                $call = "Channel: " . $dialstr . "\n";
                $call .= "Callerid: " . $user . "\n";
                $call .= "Context: billing\n";
                $call .= "Extension: " . $yournumber . "\n";
                $call .= "Priority: 1\n";
                $call .= "Set:CALLED=" . $yournumber . "\n";
                $call .= "Set:TARRIFID=" . $callTrunk[0]['id_rate'] . "\n";
                $call .= "Set:SELLCOST=" . $callTrunk[0]['rateinitial'] . "\n";
                $call .= "Set:BUYCOST=" . $callTrunk[0]['buyrate'] . "\n";
                $call .= "Set:CIDCALLBACK=1\n";
                $call .= "Set:IDUSER=" . $result[0]['id'] . "\n";
                $call .= "Set:IDPREFIX=" . $callTrunk[0]['id_prefix'] . "\n";
                $call .= "Set:IDTRUNK=" . $callTrunk[0]['id_trunk'] . "\n";
                $call .= "Set:IDPLAN=" . $result[0]['id_plan'] . "\n";

                $call .= "Set:SECCALL=" . $destination . "\n";
                AsteriskAccess::generateCallFile($call, 5);
                echo Yii::t('yii', 'CallBack Success');
            }

        }
    }

}

/*
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
function submitForm() {
var number = document.getElementById('number').value;
var user = document.getElementById('user').value;
if (number == '') {
alert('Numero invalido');
exit;
}
$.ajax({
type: "GET",
url: "http://ip/mbilling/index.php/callFree?user="+user+"&number="+number,
success: function(returnValue){
alert("Su telefono va llamar");
},
error: function(request,error) {
alert("error");
}
});
}
</script>
<form method='GET' >
<input name="number" type="text" class="input" id="number" size="10" style="font-family: 'Handlee', cursive" />
<input type="hidden" name="user" id='user' value="prueba">
<input name="button" type="button" value="Ll&aacute;mame" onclick="return submitForm();">
</form>

 */
