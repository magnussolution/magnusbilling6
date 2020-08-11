<?php

/**
 * Url for customer register http://ip/billing/index.php/user/add .
 */
class SignupController extends Controller
{

    public function actions()
    {
        return array(
            'captcha' => array(
                'class'     => 'CCaptchaAction',
                'backColor' => 0xFFFFFF,
            ),
        );
    }
    public function actionView($id)
    {
        if (isset($_GET['loginkey']) && strlen($_GET['loginkey']) > 5 and strlen($_GET['loginkey']) < 30) {
            $modelUser = User::model()->find('loginkey = :key AND id = :key1', array(':key' => $_GET['loginkey'], ':key1' => $_GET['id']));
            if (count($modelUser) < 1) {
                $this->redirect(array('add'));
            }

            if (isset($_GET['loginkey']) && $_GET['loginkey'] == $modelUser->loginkey) {
                $modelUser->active   = 1;
                $modelUser->loginkey = '';
                $modelUser->save();
                $idUserType                                     = $modelUser->idGroup->idUserType->id;
                Yii::app()->session['isAdmin']                  = $idUserType == 1 ? true : false;
                Yii::app()->session['isAgent']                  = $idUserType == 2 ? true : false;
                Yii::app()->session['isClient']                 = $idUserType == 3 ? true : false;
                Yii::app()->session['isClientAgent']            = isset($modelUser->id_user) && $modelUser->id_user > 1 ? true : false;
                Yii::app()->session['id_plan']                  = $modelUser->id_plan;
                Yii::app()->session['credit']                   = isset($modelUser->credit) ? $modelUser->credit : 0;
                Yii::app()->session['username']                 = $modelUser->username;
                Yii::app()->session['logged']                   = true;
                Yii::app()->session['id_user']                  = $modelUser->id;
                Yii::app()->session['id_agent']                 = is_null($modelUser->id_user) ? 1 : $modelUser->id_user;
                Yii::app()->session['name_user']                = $modelUser->firstname . ' ' . $modelUser->lastname;
                Yii::app()->session['id_group']                 = $modelUser->id_group;
                Yii::app()->session['user_type']                = $idUserType;
                Yii::app()->session['language']                 = $modelUser->language;
                Yii::app()->session['currency']                 = $this->config['global']['base_currency'];
                Yii::app()->session['showGoogleCode']           = false;
                Yii::app()->session['newGoogleAuthenticator']   = false;
                Yii::app()->session['checkGoogleAuthenticator'] = false;
                Yii::app()->session['googleAuthenticatorKey']   = false;

            }

            $this->redirect('/');
        } else {

            $mail = new Mail(Mail::$TYPE_SIGNUP, $id);
            try {
                $mail->send();
            } catch (Exception $e) {
            }

            if ($this->config['global']['signup_admin_email'] == 1) {
                $mail->setTitle('NEW USER SIGNUP FROM MAGNUSBILLING SIGNUP FORM. USERNAME ');
                $mail->send($this->config['global']['admin_email']);
            }
        }
    }

    public function actionAdd()
    {
        $signup = new Signup();

        if (isset($_POST['Signup'])) {

            $result = GroupUser::model()->findAllByAttributes(array("id_user_type" => 3));

            $password = trim($_POST['Signup']['password']);

            $signup->id_group = $result[0]['id'];
            $signup->active   = 2;

            if ($this->config['global']['base_language'] == 'pt_BR') {
                $phone = $_POST['Signup']['phone'];
                if (substr($phone, 0, 2) == 55 && strlen($phone) > 11) {
                    $ddd = substr($phone, 2, 2);
                } elseif (substr($phone, 0, 1) == 0) {
                    $ddd = substr($phone, 1, 2);
                } elseif (strlen($phone) == 10 || (strlen($phone) == 11 && substr($phone, 2, 1) == 9)) {
                    $ddd = substr($phone, 0, 2);
                } else {
                    $ddd = 11;
                }
                $signup->prefix_local = '0/55,*/55' . $ddd . '/8,*/55' . $ddd . '/9';
            } else {
                $signup->prefix_local = '';
            }

            if (!isset($_POST['Signup']['username'])) {
                $signup->username = Util::getNewUsername();
            }

            $signup->callingcard_pin = Util::getNewLock_pin();
            $signup->loginkey        = trim(Util::generatePassword(20, true, true, true, false));
            $signup->credit          = $_POST['Signup']['ini_credit'];
            $signup->calllimit       = $this->config['global']['start_user_call_limit'];
            unset($_POST['Signup']['ini_credit']);

            $signup->typepaid = 0;
            $signup->language = $this->config['global']['base_language'] == 'pt_BR'
            ? 'br'
            : $this->config['global']['base_language'];

            $signup->attributes   = $_POST['Signup'];
            $signup->company_name = isset($_POST['Signup']['company_name']) ? $_POST['Signup']['company_name'] : '';

            $modelPlan = Plan::model()->findByPk((int) $_POST['Signup']['id_plan']);

            if ($modelPlan->signup != 1) {
                //error if invalid plan(tampered data)
                $signup->addError('id_plan', Yii::t('yii', 'Invalid plan used to signup'));
            } else {
                $success = $signup->save();

                //add the new user to SuperLogica
                $this->createUserinSuperLogica();

                if ($success) {
                    $modelSip              = new Sip();
                    $modelSip->id_user     = $signup->id;
                    $modelSip->accountcode = $signup->username;
                    $modelSip->name        = $signup->username;
                    $modelSip->allow       = 'g729,gsm';
                    $modelSip->host        = 'dynamic';
                    $modelSip->insecure    = 'no';
                    $modelSip->defaultuser = $signup->username;
                    $modelSip->secret      = $password;
                    $modelSip->callerid    = $signup->phone;
                    $modelSip->cid_number  = $signup->phone;
                    $modelSip->save();

                    AsteriskAccess::instance()->generateSipPeers();

                    $this->redirect(array('view', 'id' => $signup->id, 'username' => $signup->username, 'password' => $signup->password, 'id_user' => $_POST['Signup']['id_user']));
                }
            }

        }
        //if exist get id, find agent plans else get admin plans
        if (isset($_GET['id'])) {
            $filter = "AND username = :id";
            $params = array(":id" => $_GET['id']);
        } else {
            $filter = "AND t.id_user = :id";
            $params = array(":id" => 1);
        }

        $modelPlan = Plan::model()->findAll(array(
            'condition' => 'signup = 1 ' . $filter,
            'join'      => 'JOIN pkg_user ON t.id_user = pkg_user.id',
            'params'    => $params,
        ));

        if ($this->config['global']['signup_auto_pass'] > 5) {
            $pass = Util::generatePassword($this->config['global']['signup_auto_pass'], true, true, true, false);
        } else {
            $pass = 0;
        }

        //render to ADD form
        $this->render('add', array(
            'signup'       => $signup,
            'plan'         => $modelPlan,
            'autoPassword' => $pass,
            'autoUser'     => $this->config['global']['auto_generate_user_signup'],
            'language'     => $this->config['global']['base_language'],
            'termsLink'    => $this->config['global']['accept_terms_link'],
        ));
    }

    public function createUserinSuperLogica()
    {
        $modelMethodPay = Methodpay::model()->find("payment_method = 'SuperLogica' AND active = 1");
        if (count($modelMethodPay)) {

            $response = SLUserSave::saveUserSLCurl($this, $modelMethodPay->SLAppToken
                , $modelMethodPay->SLAccessToken, false);

            if (isset($response[0]->data->id_sacado_sac)) {
                $this->id_sacado_sac = $response[0]->data->id_sacado_sac;
            }

        }
        return;
    }
}
