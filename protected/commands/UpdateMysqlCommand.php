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
class UpdateMysqlCommand extends ConsoleCommand
{

    public function run($args)
    {

        $version  = $this->config['global']['version'];
        $language = $this->config['global']['base_language'];

        echo $version;

        $sql = "DELETE FROM  pkg_configuration WHERE id = 258";
        $this->executeDB($sql);

        if ($version == '5.3.4') {

            $sql = "ALTER TABLE  `pkg_did` ADD  `send_to_callback_1` TINYINT( 1 ) NOT NULL DEFAULT  '0',
			ADD  `send_to_callback_2` TINYINT( 1 ) NOT NULL DEFAULT  '0',
			ADD  `send_to_callback_3` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
            $this->executeDB($sql);
            $version = '5.3.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '5.3.5') {

            $sql = "ALTER TABLE  `pkg_sip` ADD  `ringfalse` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
            $this->executeDB($sql);
            $version = '5.3.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '5.3.6') {

            $sql = "ALTER TABLE  `pkg_user`
			ADD  `state_number` VARCHAR( 40 ) DEFAULT NULL AFTER  `company_website` ,

			ADD  `disk_space` INT( 10 ) NOT NULL DEFAULT  '-1',
			ADD  `sipaccountlimit` INT( 10 ) NOT NULL DEFAULT  '-1',
			ADD  `calllimit` INT( 10 ) NOT NULL DEFAULT  '-1',
			ADD mix_monitor_format VARCHAR(5) DEFAULT 'gsm';
			ALTER TABLE  `pkg_sip` ADD  `record_call` TINYINT( 1 ) NOT NULL DEFAULT  '0';
			";
            $this->executeDB($sql);

            $sql = 'INSERT INTO pkg_templatemail VALUES

				(NULL, 1, \'services_unpaid\', \'usuario\', \'VoIP\', \'Aviso de Vencimento de serviço\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Você tem serviços com vencimento em aberto e não possiu saldo para o pagamento. Por favor entre no link $service_pending_url$ para iniciar o pagamento. </p>\r\n<br> \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'services_unpaid\', \'usuario\', \'VoIP\', \'Aviso de Vencimiento de servicio\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Usted tien servicios por vencer o vencido. Por favor entre en este link $service_pending_url$ para iniciar el pago.</p> \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'services_unpaid\', \'username\', \'VoIP\', \'Balance Due Alert for your\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>You have services pendent. Please use this link $service_pending_url$ to start the payment</p>\r\n\r\n<br> \r\n<p>Best Regards<br>\r\n \', \'en\'),

				(NULL, 1, \'services_activation\', \'usuario\', \'VoIP\', \'Ativação de serviço\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Foi ativado o serviço $service_name$ com valor de $service_price$. </p>\r\n<br>\r\n\r\n<p>Este valor sera descontado do credito de sua conta automaticamente todos os meses.</p>\r\n\r\n<br> \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'services_activation\', \'usuario\', \'VoIP\', \'Activacion de servicio\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Fue activado el servicio $service_name$ con importe $service_price$.</p>\r\n<br>\r\n\r\n<p>Este importe sera descontado del credito de su cuenta automaticamente todos los meses..</p>\r\n\r\n<br> \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'services_activation\', \'username\', \'VoIP\', \'Service activation\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>The service $service_name$ was activated. Service price: $service_price$ .</p>\r\n<br>\r\n\r\n<p>This amount will be charged of your account every month.</p>\r\n\r\n<br> \r\n<p>Best Regards<br>\r\n \', \'en\'),

				(NULL, 1, \'services_pending\', \'usuario\', \'VoIP\', \'Serviço pendente de pagamento\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Foi solicitado o serviço $service_name$ com valor de $service_price$. </p>\r\n
				<p>A ativaçao do serviço esta pendente de pagamento.</p>\r\n
				<p>Link para pagamento $service_pending_url$.</p>\r\n
				<br>\r\n\r\n<p></p>\r\n\r\n<br> \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'services_pending\', \'usuario\', \'VoIP\', \'Servicio pendente de pagao\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Fue solicitado la activacion del servicio $service_name$ con importe $service_price$.</p>\r\n<p>La activacion del servicio esta pendiente de pago.</p>\r\n
				<p>Link para el pago: $service_pending_url$.</p>\r\n<br>\r\n\r\n<p>.</p>\r\n\r\n<br> \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'services_pending\', \'username\', \'VoIP\', \'Service pending\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>The service $service_name$ was pending. Service price: $service_price$ .</p>\r\n
				<p>Please make the payment to active the service.</p>\r\n
				<p>Payment Link:  $service_pending_url$.</p>\r\n
				<br>\r\n\r\n<br> \r\n<p>Best Regards<br>\r\n \', \'en\'),

				(NULL, 1, \'services_released\', \'usuario\', \'VoIP\', \'Cancelamento de serviço\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Foi desativado o serviço $service_name$ com valor de $service_price$. </p>\r\n<br>\r\n\r\n<p></p>\r\n\r\n<br> \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'services_released\', \'usuario\', \'VoIP\', \'Baja de servicio\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Fue dado de baja el servicio $service_name$ con importe $service_price$.</p>\r\n<br>\r\n\r\n<p>.</p>\r\n\r\n<br> \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'services_released\', \'username\', \'VoIP\', \'Service canceled\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>The service $service_name$ was canceled. Service price: $service_price$ .</p>\r\n<br>\r\n\r\n<br> \r\n<p>Best Regards<br>\r\n \', \'en\'),

				(NULL, 1, \'services_paid\', \'usuario\', \'VoIP\', \'Serviço Pago\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Foi pago o serviço $service_name$ com valor de $service_price$. </p>\r\n<br>\r\n\r\n<p></p>\r\n\r\n<br> \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'services_paid\', \'usuario\', \'VoIP\', \'Servicio pago\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Fue pago el servicio $service_name$ con importe $service_price$.</p>\r\n<br>\r\n\r\n<p>.</p>\r\n\r\n<br> \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'services_paid\', \'username\', \'VoIP\', \'Service paid\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>The service $service_name$ was paid. Service price: $service_price$ .</p>\r\n<br>\r\n\r\n<br> \r\n<p>Best Regards<br>\r\n \', \'en\'),


				(NULL, 1, \'user_disk_space\', \'usuario\', \'VoIP\', \'Armazenamento em disco superado\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Sua conta VoIP número $cardnumber$ superou o limite de $disk_usage_limit$ GB.</p>\r\n<br>\r\n\r\n<p>Para manter o serviço foi deletado automaticamente os audios anteriores a $time_deleted$.</p>\r\n\r\n<br> \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'user_disk_space\', \'usuario\', \'VoIP\', \'Armazenamento en disco superado\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Su cuenta VoIP número $cardnumber$ supero el limite de $disk_usage_limit$ GB.</p>\r\n<br>\r\n\r\n<p>Para mantener el servicio fue borrado automaticamente los audios anteriores a $time_deleted$.</p>\r\n\r\n<br> \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'user_disk_space\', \'username\', \'VoIP\', \'Disk space surpassed\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>Your account $cardnumber$ surpassed the disk space limit of $disk_usage_limit$ GB.</p>\r\n<br>\r\n\r\n<p>To keep the service was deleted the records before than $time_deleted$.</p>\r\n\r\n<br> \r\n<p>Best Regards<br>\r\n \', \'en\');';
            $this->executeDB($sql);

            $sql = "
			ALTER TABLE  `pkg_campaign` ADD  `id_plan` INT( 11 ) NULL DEFAULT NULL AFTER  `id_user`;
			ALTER TABLE `pkg_campaign`  ADD CONSTRAINT `fk_pkg_plan_pkg_campaign` FOREIGN KEY (`id_plan`) REFERENCES `pkg_plan` (`id`);
			INSERT INTO pkg_configuration VALUES
				(NULL, 'Link to signup terms', 'accept_terms_link', '', 'Set a link to signup terms', 'global', '1'),
				(NULL, 'Auto gernerate user in Signup form', 'auto_generate_user_signup', '1', 'Auto generate user in Signup form', 'global', '1'),
				(NULL, 'Notificação de  Pagamento de serviços', 'service_daytopay', '5', 'Total Dias anterior ao vencimento que o MagnusBilling avisara o cliente para pagar os serviços', 'global', '1');
				";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `pkg_estados` (
					  `id` int(11) NOT NULL,
					  `nome` varchar(45) NOT NULL,
					  `sigla` varchar(2) NOT NULL,
					  PRIMARY KEY (`id`,`sigla`),
					  UNIQUE KEY `sigla_UNIQUE` (`sigla`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;

					INSERT INTO `pkg_estados` (`id`, `nome`, `sigla`) VALUES
					(1, 'Acre', 'AC'),
					(2, 'Alagoas', 'AL'),
					(3, 'Amazonas', 'AM'),
					(4, 'Amapá', 'AP'),
					(5, 'Bahia', 'BA'),
					(6, 'Ceará', 'CE'),
					(7, 'Distrito Federal', 'DF'),
					(8, 'Espírito Santo', 'ES'),
					(9, 'Goiás', 'GO'),
					(10, 'Maranhão', 'MA'),
					(11, 'Minas Gerais', 'MG'),
					(12, 'Mato Grosso do Sul', 'MS'),
					(13, 'Mato Grosso', 'MT'),
					(14, 'Pará', 'PA'),
					(15, 'Paraíba', 'PB'),
					(16, 'Pernambuco', 'PE'),
					(17, 'Piauí', 'PI'),
					(18, 'Paraná', 'PR'),
					(19, 'Rio de Janeiro', 'RJ'),
					(20, 'Rio Grande do Norte', 'RN'),
					(21, 'Rondônia', 'RO'),
					(22, 'Roraima', 'RR'),
					(23, 'Rio Grande do Sul', 'RS'),
					(24, 'Santa Catarina', 'SC'),
					(25, 'Sergipe', 'SE'),
					(26, 'São Paulo', 'SP'),
					(27, 'Tocantins', 'TO');";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Services'')', NULL, 'prefixs', NULL)";
            $this->executeDB($sql);
            $idServiceModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idServiceModule . "', 'crud', '1', '1', '1');";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Services'')', 'services', 'offer', '" . $idServiceModule . "')";
            $this->executeDB($sql);
            $idSubModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idSubModule . "', 'crud', '1', '1', '1');";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Services Use'')', 'servicesuse', 'offer', '" . $idServiceModule . "')";
            $this->executeDB($sql);
            $idSubModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idSubModule . "', 'crud', '1', '1', '1');";
            $this->executeDB($sql);

            $sql = "
				CREATE TABLE IF NOT EXISTS `pkg_services` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `name` varchar(100) NOT NULL,
					  `type` varchar(50) NOT NULL,
					  `status` tinyint(1) NOT NULL DEFAULT '1',
					  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
					  `description` text,
					  `disk_space` int(11) DEFAULT NULL,
					  `sipaccountlimit` int(11) DEFAULT NULL,
					  `calllimit` int(11) DEFAULT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

				CREATE TABLE IF NOT EXISTS `pkg_services_module` (
				  `id_services` int(11) NOT NULL,
				  `id_module` int(11) NOT NULL,
				  `action` varchar(45) NOT NULL,
				  `show_menu` tinyint(1) NOT NULL DEFAULT '1',
				  `createShortCut` tinyint(1) NOT NULL DEFAULT '0',
				  `createQuickStart` tinyint(1) NOT NULL DEFAULT '0',
				  PRIMARY KEY (`id_services`,`id_module`),
				  KEY `fk_pkg_services_module_pkg_module` (`id_module`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;


				ALTER TABLE `pkg_services_module`
				  ADD CONSTRAINT `fk_pkg_services_pkg_services_module` FOREIGN KEY (`id_services`) REFERENCES `pkg_services` (`id`),
				  ADD CONSTRAINT `fk_pkg_services_module_pkg_module` FOREIGN KEY (`id_module`) REFERENCES `pkg_module` (`id`);



				CREATE TABLE IF NOT EXISTS `pkg_services_use` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `id_user` int(11) DEFAULT NULL,
				  `id_services` int(11) NOT NULL,
				  `reservationdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `releasedate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				  `status` int(11) DEFAULT '0',
				  `month_payed` int(11) DEFAULT '0',
				  `reminded` tinyint(4) NOT NULL DEFAULT '0',
				  `id_method` int(11) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_pkg_user_pkg_services_use` (`id_user`),
				  KEY `fk_pkg_services_pkg_services_use` (`id_services`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

				ALTER TABLE `pkg_services_use`
				  ADD CONSTRAINT `fk_pkg_services_pkg_services_use` FOREIGN KEY (`id_services`) REFERENCES `pkg_services` (`id`),
				  ADD CONSTRAINT `fk_pkg_user_pkg_services_use` FOREIGN KEY (`id_user`) REFERENCES `pkg_user` (`id`);


				CREATE TABLE IF NOT EXISTS `pkg_services_plan` (
				  `id_services` int(11) NOT NULL,
				  `id_plan` int(11) NOT NULL,
				  PRIMARY KEY (`id_services`,`id_plan`),
				  KEY `fk_pkg_services_pkg_services_plan` (`id_services`),
				  KEY `fk_pkg_plan_pkg_services_plan` (`id_plan`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;


				ALTER TABLE `pkg_services_plan`
				  ADD CONSTRAINT `fk_pkg_services_pkg_services_plan` FOREIGN KEY (`id_services`) REFERENCES `pkg_services` (`id`) ON DELETE CASCADE,
				  ADD CONSTRAINT `fk_pkg_plan_pkg_services_plan` FOREIGN KEY (`id_plan`) REFERENCES `pkg_plan` (`id`) ON DELETE CASCADE;";
            $this->executeDB($sql);

            $version = '5.4.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            $this->executeDB($sql);
        }
        if ($version == '5.4.0') {
            $sql = "CREATE TABLE IF NOT EXISTS `pkg_group_user_group` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
				  	`id_group_user` int(11) NOT NULL,
				  	`id_group` int(11) NOT NULL,
				  	PRIMARY KEY (`id`),
				  	KEY `fk_pkg_pkg_group_user_pkg_group` (`id_group_user`),
				  	KEY `fk_pkg_group_pkg_pkg_group_user_group` (`id_group`)
					) 	ENGINE=InnoDB DEFAULT CHARSET=utf8;


				ALTER TABLE `pkg_group_user_group`
				  ADD CONSTRAINT `fk_pkg_pkg_group_user_pkg_group` FOREIGN KEY (`id_group_user`) REFERENCES `pkg_group_user` (`id`) ON DELETE CASCADE,
				  ADD CONSTRAINT `fk_pkg_group_pkg_pkg_group_user_group` FOREIGN KEY (`id_group`) REFERENCES `pkg_group_user` (`id`) ON DELETE CASCADE;";

            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Group to Admins'')', 'groupusergroup', 'prefixs', 12)";
            try {
                Yii::app()->db->createCommand($sql)->execute();
            } catch (Exception $e) {

            }
            $idServiceModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idServiceModule . "', 'crud', '1', '1', '1');";
            $this->executeDB($sql);

            $sql = "ALTER TABLE  `pkg_user` CHANGE  `address`  `address` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;";
            $this->executeDB($sql);
            $sql = "INSERT INTO `pkg_configuration`  VALUES (NULL, 'Start User Call Limit', 'start_user_call_limit', '-1', 'Default call limit for new user', 'global', '0');";
            $this->executeDB($sql);
            $version = '5.4.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            $this->executeDB($sql);
        }
        if ($version == '5.4.1') {
            $sql = "ALTER TABLE  `pkg_did` ADD  `cbr` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `billingtype`,
			ADD `cbr_em` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `initblock`,
			ADD `cbr_ua` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `initblock`,
			ADD  `TimeOfDay_monFri` VARCHAR( 150 ) NULL DEFAULT NULL ,
			ADD  `TimeOfDay_sat` VARCHAR( 150 ) NULL DEFAULT NULL ,
			ADD  `TimeOfDay_sun` VARCHAR(150 ) NULL DEFAULT NULL ,
			ADD  `workaudio` VARCHAR( 150 ) NULL DEFAULT NULL ,
			ADD  `noworkaudio` VARCHAR( 150 ) NULL DEFAULT NULL;
			UPDATE  `pkg_did` SET  `TimeOfDay_monFri` =  '09:00-12:00|14:00-18:00';
			UPDATE  `pkg_did` SET  `TimeOfDay_sat` =  '09:00-12:00';
			UPDATE  `pkg_did` SET  `TimeOfDay_sun` =  '00:00';
			ALTER TABLE  `pkg_callback` ADD  `id_did` INT( 11 ) NOT NULL AFTER  `id_user`;
			";
            $this->executeDB($sql);
            $version = '5.4.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            $this->executeDB($sql);
        }

        if (preg_match("/^5\./", $version)) {
            $sql = "
				ALTER TABLE  `pkg_iax` CHANGE  `nat`  `nat` VARCHAR( 25 ) NULL DEFAULT  'force_rport,comedia';
				ALTER TABLE  `pkg_sip` CHANGE  `nat`  `nat` VARCHAR( 25 ) NULL DEFAULT  'force_rport,comedia';

				ALTER TABLE  `pkg_trunk` ADD  `register_string` VARCHAR( 300 ) NOT NULL DEFAULT  '';

				CREATE TABLE IF NOT EXISTS `pkg_log_actions` (
					  `id` int(11) NOT NULL,
					  `name` varchar(20) NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1;

				INSERT INTO `pkg_log_actions` (`id`, `name`) VALUES
				(1, 'Login'),
				(2, 'Edit'),
				(3, 'Delete'),
				(4, 'New'),
				(5, 'Import'),
				(6, 'UpdateAll'),
				(7, 'Export'),
				(8, 'Logout');

				DROP TABLE pkg_log;

				CREATE TABLE IF NOT EXISTS `pkg_log` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `id_user` int(11) DEFAULT NULL,
				  `id_log_actions` int(11) DEFAULT NULL,
				  `description` mediumtext CHARACTER SET utf8 COLLATE utf8_bin,
				  `username` varchar(50) DEFAULT NULL,
				  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `ip` varchar(50) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  KEY `fk_pkg_log_actions_pkg_log` (`id_log_actions`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


				ALTER TABLE  `pkg_campaign_poll` ADD  `id_user` INT( 11 ) NOT NULL AFTER  `id`;
				UPDATE pkg_campaign_poll SET id_user = (SELECT id_user FROM pkg_campaign WHERE id = id_campaign);

				ALTER TABLE  `pkg_plan` CHANGE  `id_user`  `id_user` INT( 11 ) NULL DEFAULT NULL ;

			";
            $this->executeDB($sql);

            $sql    = "SELECT prefix FROM pkg_prefix group by prefix having count(*) >= 2";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            for ($i = 0; $i < count($result); $i++) {
                $ids = array();

                $sql          = "SELECT id FROM pkg_prefix WHERE prefix = " . $result[$i]['prefix'];
                $resultPrefix = Yii::app()->db->createCommand($sql)->queryAll();
                $firstPrefix  = $resultPrefix[0]['id'];
                unset($resultPrefix[0]);

                foreach ($resultPrefix as $key => $deletePrefix) {
                    $ids[] = $deletePrefix['id'];
                }
                $ids = implode(',', $ids);

                $sql = "UPDATE pkg_rate SET id_prefix = $firstPrefix WHERE id_prefix IN ($ids);
						UPDATE pkg_rate_agent SET id_prefix = $firstPrefix WHERE id_prefix IN ($ids);
						DELETE FROM pkg_prefix WHERE id IN ($ids)";
                $this->executeDB($sql);

            }

            $sql = "
			CREATE TABLE IF NOT EXISTS `pkg_prefix_length` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `code` int(11) NOT NULL,
			  `length` int(11) NOT NULL,
			  PRIMARY KEY (`id`),
			    UNIQUE KEY `code` (`code`)
			) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;
				ALTER TABLE  `pkg_prefix` ADD UNIQUE (`prefix`)";
            $this->executeDB($sql);

            $version = '6.0.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            $this->executeDB($sql);
        }

        if ($version == '6.0.0') {

            $sql = "ALTER TABLE  `pkg_method_pay` ADD  `min` INT( 11 ) NOT NULL DEFAULT  '10', ADD  `max` INT( 11 ) NOT NULL DEFAULT  '500';";
            $this->executeDB($sql);
            $sql = "ALTER TABLE  `pkg_trunk` ADD  `transport` VARCHAR( 3 ) NOT NULL DEFAULT  'no', ADD  `encryption` VARCHAR( 3 ) NOT NULL DEFAULT  'no', ADD  `port` VARCHAR( 5 ) NOT NULL DEFAULT  '5060';";
            $this->executeDB($sql);

            $version = '6.0.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.0.1') {

            $sql = " ALTER TABLE  `pkg_method_pay` ADD  `showFields` TEXT NULL DEFAULT NULL;";
            $this->executeDB($sql);

            $sql = "
		        INSERT INTO pkg_method_pay VALUES (NULL, '1', 'MercadoPago', 'MercadoPago', 'Brasil', '0', '0', NULL, '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', '500', 'payment_method,show_name,id_user,country,active,min,max,username,pagseguro_TOKEN');
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,boleto_convenio,boleto_inicio_nosso_numeroa,boleto_banco,boleto_agencia,boleto_conta_corrente,boleto_carteira,boleto_taxa,boleto_instrucoes,boleto_nome_emp,boleto_end_emp,boleto_cidade_emp,boleto_estado_emp,boleto_cpf_emp' WHERE payment_method = 'BoletoBancario';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,username,url' WHERE payment_method = 'CuentaDigital';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,min,max,username,url' WHERE payment_method = 'DineroMail';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,id_user,show_name,country,active,min,max,username,url' WHERE payment_method = 'Moip';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,username,pagseguro_TOKEN' WHERE payment_method = 'Pagseguro';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,username,url,fee' WHERE payment_method = 'Paypal';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,username,pagseguro_TOKEN'  WHERE payment_method = 'IcePay';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,username,url,pagseguro_TOKEN'  WHERE payment_method = 'Payulatam';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,username,pagseguro_TOKEN' WHERE payment_method = 'AuthorizeNet';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,P2P_CustomerSiteID,P2P_KeyID,P2P_Passphrase,P2P_RecipientKeyID,P2P_tax_amount' WHERE payment_method = 'PlacetoPay';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,client_id,client_secret' WHERE payment_method = 'GerenciaNet';
		        UPDATE pkg_method_pay SET showFields = 'payment_method,show_name,id_user,country,active,min,max,SLIdProduto,SLAppToken,SLAccessToken,SLvalidationtoken' WHERE payment_method = 'SuperLogica';";
            $this->executeDB($sql);
            $version = '6.0.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.0.2') {

            $sql = "ALTER TABLE  `pkg_sip` ADD  `voicemail` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
            $this->executeDB($sql);
            $version = '6.0.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.2') {

            $sql    = "SELECT * FROM pkg_method_pay WHERE payment_method = 'paghiper'";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            if (!count($result)) {
                $sql = "INSERT INTO pkg_method_pay VALUES (NULL, '1', 'paghiper', 'paghiper', 'Brasil', '0', '0', NULL, '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', '500', 'payment_method,show_name,id_user,country,active,min,max,username,pagseguro_TOKEN');";
                $this->executeDB($sql);
            }
            $version = '6.0.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.3') {

            $sql = "
        	ALTER TABLE  `pkg_iax` CHANGE `canreinvite` `canreinvite` VARCHAR(20) CHARACTER SET CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'no';
        	ALTER TABLE  `pkg_iax` CHANGE `mask` `mask` VARCHAR(95) CHARACTER SET CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT NULL;
        	ALTER TABLE  `pkg_iax` CHANGE  `musiconhold`  `musiconhold` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ;";
            $this->executeDB($sql);
            $version = '6.0.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.4') {

            $sql = "INSERT INTO pkg_method_pay VALUES (NULL, '1', 'molpay', 'MoPay', 'Global', '0', '0', NULL, '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', '500', 'payment_method,show_name,id_user,country,active,min,max,username,pagseguro_TOKEN');";
            $this->executeDB($sql);

            $version = '6.0.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.5') {
            $sql = "INSERT INTO `pkg_configuration`  VALUES
				(NULL, 'CallingCard answer call', 'callingcard_answer', '1', 'Answer call in CallingCard', 'agi-conf1', '1'),
				(NULL, 'CallingCard enable CID authentication', 'callingcard_cid_enable', '1', 'CID authentication in CallingCard', 'agi-conf1', '1'),
				(NULL, 'CallingCard number try', 'callingcard_number_try', '3', 'Number try call in CallingCard', 'agi-conf1', '1'),
				(NULL, 'CallingCard say sall rate', 'callingcard_say_rateinitial', '0', 'CallingCard say sall rate', 'agi-conf1', '1'),
				(NULL, 'CallingCard say timecall', 'callingcard_say_timetocall', '0', 'CallingCard say timecall', 'agi-conf1', '1');
			";
            $this->executeDB($sql);

            $version = '6.0.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.6') {
            $sql = "UPDATE  pkg_configuration SET config_value =  'gray-neptune' WHERE  config_key = 'template' AND config_value = 'gray-classic'";
            $this->executeDB($sql);

            $version = '6.0.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.7') {
            $sql = "ALTER TABLE  `pkg_iax`
            CHANGE  `mask`  `mask` VARCHAR( 95 ) NULL DEFAULT NULL,
            CHANGE  `musiconhold`  `musiconhold` VARCHAR( 100 ) NULL DEFAULT NULL ;";
            $this->executeDB($sql);

            $version = '6.0.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.8') {

            $sql = "ALTER TABLE `pkg_sip` ADD `forward` VARCHAR(50) NOT NULL DEFAULT '' AFTER `voicemail`;
        		";
            $this->executeDB($sql);

            $version = '6.0.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.0.9') {
            $sql = "INSERT INTO `pkg_configuration`  VALUES
				(NULL, 'reCaptchaKey sitekey', 'reCaptchaKey', '', 'Generate your sitekey in https://www.google.com/recaptcha/admin#list', 'global', '1');
			";
            $this->executeDB($sql);

            $sql = 'INSERT INTO pkg_templatemail VALUES
				(NULL, 1, \'forgetpassword\', \'usuário\', \'VoIP\', \'Recuperação de senha\', \'<p>Olá $firstname$ $lastname$, </p>\r\n<p>Você solicitou sua senha por email. </p>\r\nSua senha é: $password$<br>\r\n \r\n<p>Atenciosamente,<br>\r\n \', \'br\'),
				(NULL, 1, \'forgetpassword\', \'usuario\', \'VoIP\', \'Recuperacion de contraseña\', \'<p>Hola $firstname$ $lastname$, </p>\r\n<p>Usted solicito su contraseña por email. </p>\r\nSu contraseña es: $password$<br>\r\n \r\n<p>Saludos,<br>\r\n \', \'es\'),
				(NULL, 1, \'forgetpassword\', \'username\', \'VoIP\', \'Password recovery\', \'<p>Hello $firstname$ $lastname$, </p>\r\n<p>You request your password. </p>\r\nYour password is: $password$<br>\r\n \r\n<p>Best Regards,<br>\r\n \', \'en\')';

            $this->executeDB($sql);

            $version = '6.1.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.0') {
            $sql = "ALTER TABLE `pkg_call_online` ADD `sip_account` VARCHAR(20) NULL DEFAULT NULL AFTER `id`;";
            $this->executeDB($sql);

            $version = '6.1.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.1') {
            $sql = "ALTER TABLE `pkg_callshop` ADD `destination` VARCHAR(100) NULL DEFAULT NULL AFTER `calledstation`;
        		ALTER TABLE `pkg_callshop` DROP `id_prefix`;
        		ALTER TABLE `pkg_callshop` ADD `price_min` DECIMAL(15,5) NOT NULL DEFAULT '0.00000' AFTER `price`;";
            $this->executeDB($sql);

            $version = '6.1.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.2') {
            $sql = "ALTER TABLE `pkg_queue` ADD `ring_or_moh` VARCHAR(4) NOT NULL DEFAULT 'moh' AFTER `var_answeredCalls`;";
            $this->executeDB($sql);

            $version = '6.1.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.3') {
            $sql = "ALTER TABLE `pkg_call_online` ADD `uniqueid` VARCHAR(25) NULL DEFAULT NULL AFTER `id`;
        		ALTER TABLE `pkg_call_online` CHANGE `sip_account` `sip_account` VARCHAR(50) ;";
            $this->executeDB($sql);

            $version = '6.1.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.4') {
            $sql = "UPDATE `pkg_module` SET `text` = 't(\'Send Credit\')' WHERE module = 'transfertomobile';

            ALTER TABLE `pkg_user`
            	ADD `transfer_international` TINYINT(1) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_flexiload` TINYINT(1) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_bkash` TINYINT(1) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_dbbl_rocke` TINYINT(1) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_international_profit` INT(11) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_flexiload_profit` INT(11) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_bkash_profit` INT(11) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_dbbl_rocke_profit` INT(11) NULL DEFAULT '0' AFTER `mix_monitor_format`,
            	ADD `transfer_bdservice_rate` INT(11) NULL DEFAULT '0' AFTER `mix_monitor_format`;

            ";
            $this->executeDB($sql);

            $sql = "
            DELETE FROM pkg_configuration WHERE config_key = 'fm_transfer_to_profit';
            DELETE FROM pkg_configuration WHERE config_key = 'BDService_agent';
            ";
            $this->executeDB($sql);

            $sql = "
            INSERT IGNORE INTO pkg_configuration  VALUES
            	(NULL, 'BDService Username', 'BDService_username', '', 'BDService username', 'global', '1'),
            	(NULL, 'BDService token', 'BDService_token', '', 'BDService token', 'global', '1'),
            	(NULL, 'BDService flexiload values', 'BDService_flexiload', '10-1000', 'BDService flexiload values', 'global', '1'),
            	(NULL, 'BDService bkash values', 'BDService_bkash', '50-2500', 'BDService bkash values', 'global', '1'),
            	(NULL, 'BDService currency translation', 'BDService_cambio', '0.01', 'BDService currency translation', 'global', '1');
            ";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `pkg_BDService` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `id_user` int(11) NOT NULL,
              `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

            INSERT IGNORE INTO pkg_BDService (id) VALUES (15254);

            ";
            $this->executeDB($sql);

            $version = '6.1.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.5') {
            $sql = " INSERT IGNORE INTO pkg_configuration  VALUES
            	(NULL, 'BDService DBBL/Rocket values', 'BDService_dbbl_rocket', '10-1000', 'DBBL/Rocket flexiload values', 'global', '1');
            ";
            $this->executeDB($sql);

            $version = '6.1.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.6') {
            $sql = "
            	DELETE FROM pkg_configuration WHERE config_key = 'fm_transfer_show_selling_price';
            	RENAME TABLE pkg_BDService TO pkg_send_credit;
            	ALTER TABLE `pkg_send_credit` ADD `service` VARCHAR(50) NOT NULL AFTER `date`;
            	ALTER TABLE `pkg_send_credit` ADD `number` VARCHAR(30) NOT NULL AFTER `service`;
            	ALTER TABLE `pkg_send_credit` ADD `profit` VARCHAR(10) NULL DEFAULT '0' AFTER `number`;
            	ALTER TABLE `pkg_send_credit` ADD `earned` VARCHAR(20) NULL DEFAULT NULL AFTER `profit`;
            	ALTER TABLE `pkg_send_credit` ADD `amount` VARCHAR(10) NULL DEFAULT NULL AFTER `earned`;
            	ALTER TABLE `pkg_send_credit` ADD `count` INT(11) NULL DEFAULT NULL AFTER `amount`;
            	ALTER TABLE `pkg_send_credit` ADD `total_sale` INT(11) NULL DEFAULT NULL AFTER `amount`;

            ";
            $this->executeDB($sql);
            $sql = "ALTER TABLE `pkg_user` CHANGE `transfer_dbbl_rocke_profit` `transfer_dbbl_rocket_profit` INT(11)  NULL DEFAULT '0';
            ";
            $this->executeDB($sql);
            $sql = "
            	ALTER TABLE `pkg_user` CHANGE `transfer_dbbl_rocke` `transfer_dbbl_rocket` TINYINT(1) NOT NULL DEFAULT '0';
            	ADD `transfer_show_selling_price` TINYINT(1) NULL DEFAULT '0' AFTER `mix_monitor_format`;
            ";
            $this->executeDB($sql);

            $sql = "
            	ALTER TABLE `pkg_user`
            	ADD `transfer_show_selling_price` TINYINT(1) NULL DEFAULT '0' AFTER `mix_monitor_format`;
            ";
            $this->executeDB($sql);

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Send Credit Summary'')', 'sendcreditsummary', 'callsummary', '9')";
            $this->executeDB($sql);

            $sql = "
            	ALTER TABLE `pkg_send_credit` ADD `confirmed` TINYINT(1) NOT NULL DEFAULT '0' AFTER `count`;
            	UPDATE `pkg_send_credit` SET `confirmed` = '1'
            	";
            $this->executeDB($sql);

            $sql = " INSERT IGNORE INTO pkg_configuration  VALUES
            	(NULL, 'BDService Credit', 'BDService_credit_provider', '0', 'BDService Credit', 'global', '1');";
            $this->executeDB($sql);

            $version = '6.1.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.7') {
            $sql = " ALTER TABLE `pkg_sip` ADD `url_events` VARCHAR(150) NULL DEFAULT NULL AFTER `forward`";
            $this->executeDB($sql);

            $version = '6.1.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.8') {
            $sql = "ALTER TABLE `pkg_user` CHANGE `transfer_dbbl_rocket_profit` `transfer_dbbl_rocket_profit` INT(11) NULL DEFAULT '0';";
            $this->executeDB($sql);

            $version = '6.1.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.1.9') {
            $sql = "ALTER TABLE `pkg_campaign`
            		ADD `tts_audio` VARCHAR(200) NULL DEFAULT NULL AFTER `digit_authorize`,
            		ADD `tts_audio2` VARCHAR(200) NULL DEFAULT NULL AFTER `tts_audio`,
            		ADD `asr_audio` VARCHAR(200) NULL DEFAULT NULL AFTER `tts_audio2`,
            		ADD `asr_options` VARCHAR(200) NULL DEFAULT NULL AFTER `asr_audio`;";
            $this->executeDB($sql);

            $version = '6.2.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.0') {
            $sql = "INSERT INTO `pkg_configuration` (`id`, `config_title`, `config_key`, `config_value`, `config_description`, `config_group_title`, `status`) VALUES (NULL, 'Session timeout', 'session_timeout', '3600', 'Time in seconds to close user session', 'global', '1');";
            $this->executeDB($sql);

            $version = '6.2.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.1') {
            $sql = "ALTER TABLE `pkg_user` ADD `calllimit_error` VARCHAR(3) NOT NULL DEFAULT '503' AFTER `calllimit`;";
            $this->executeDB($sql);

            $version = '6.2.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.2') {
            $sql = "ALTER TABLE `pkg_did` ADD `calllimit` int(11) NOT NULL DEFAULT '-1';";
            $this->executeDB($sql);

            $version = '6.2.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.3') {
            $sql = "ALTER TABLE  `pkg_provider` CHANGE  `credit`  `credit` DECIMAL( 18, 5 ) NOT NULL DEFAULT  '0.00000';";
            $this->executeDB($sql);

            $version = '6.2.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.4') {
            $sql = "CREATE TABLE IF NOT EXISTS `pkg_tables_changes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `table` varchar(300) NOT NULL,
              `last_time` varchar(18) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
            $this->executeDB($sql);

            $version = '6.2.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.2.5') {

            $sql = "
            	ALTER TABLE `pkg_tables_changes` CHANGE `table` `module` VARCHAR(300) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
            	INSERT INTO `pkg_tables_changes` (`id`, `module`, `last_time`) VALUES
			(1, 'pkg_rate', '1525439132'),
			(2, 'pkg_prefix_length', '1525439132'),
			(3, 'pkg_prefix', '1525439132');";
            $this->executeDB($sql);

            $version = '6.2.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.6') {
            $sql = "
			INSERT INTO pkg_configuration VALUES
				(NULL, 'Show Play icon on CDR', 'show_playicon_cdr', '0', 'Show Play icon on CDR menu. Set to 1 for show the icon', 'global', '1')";
            $this->executeDB($sql);
            $version = '6.2.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.2.7') {
            $sql = "
			ALTER TABLE pkg_cdr CHANGE `sessionid` `sessionid` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
			ALTER TABLE pkg_cdr_failed CHANGE `sessionid` `sessionid` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;
			ALTER TABLE pkg_cdr DROP INDEX buycost;
			ALTER TABLE pkg_cdr DROP INDEX sessionbill;
			ALTER TABLE pkg_cdr DROP INDEX sessiontime;
			ALTER TABLE pkg_cdr DROP INDEX uniqueid;
			ALTER TABLE pkg_cdr DROP INDEX id_prefix;
			ALTER TABLE pkg_cdr DROP INDEX terminatecauseid;
			ALTER TABLE pkg_cdr DROP INDEX calledstation;
			ALTER TABLE pkg_cdr DROP INDEX id_trunk;
			ALTER TABLE pkg_cdr DROP INDEX id_plan;
			ALTER TABLE pkg_cdr_failed DROP INDEX id_user;
			ALTER TABLE pkg_cdr_failed DROP INDEX id_plan;
			ALTER TABLE pkg_cdr_failed DROP INDEX id_trunk;
			ALTER TABLE pkg_cdr_failed DROP INDEX calledstation;
			ALTER TABLE pkg_cdr_failed DROP INDEX terminatecauseid;
			ALTER TABLE pkg_cdr_failed DROP INDEX id_prefix;
			ALTER TABLE pkg_cdr_failed DROP INDEX uniqueid;
			";
            $this->executeDB($sql);
            $version = '6.2.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.8') {
            $sql = "ALTER TABLE `pkg_sip` ADD `block_call_reg` VARCHAR(100) NOT NULL DEFAULT '' AFTER `url_events`;

            ALTER TABLE `pkg_queue_status` ADD `agentName` VARCHAR(50) NOT NULL DEFAULT '' AFTER `oldtime`;";

            $this->executeDB($sql);
            $version = '6.2.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.2.9') {
            $sql = "ALTER TABLE `pkg_cdr` CHANGE `sessionid` `callerid` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT ''";

            $this->executeDB($sql);
            $version = '6.3.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.3.0') {
            $sql = "ALTER TABLE `pkg_sip` ADD `dial_timeout` INT(11) NOT NULL DEFAULT '60' AFTER `block_call_reg`;";

            $this->executeDB($sql);
            $version = '6.3.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.3.1') {
            $sql = "ALTER TABLE `pkg_cdr_failed` ADD `hangupcause` INT(11) NULL DEFAULT NULL AFTER `terminatecauseid`;";
            $this->executeDB($sql);
            $version = '6.3.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.3.2') {
            $sql = "ALTER TABLE `pkg_user` ADD `cpslimit` INT(11) NOT NULL DEFAULT '-1' AFTER `calllimit`;";
            $this->executeDB($sql);

            $version = '6.3.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.3.3') {
            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''callsummarypertrunk'')', 'callsummarypertrunk', 'callsummarybymonth', '9')";
            $this->executeDB($sql);
            $idServiceModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idServiceModule . "', 'r', '1', '1', '1');";
            $this->executeDB($sql);

            $version = '6.3.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.3.4') {
            $sql = " ALTER TABLE `pkg_call_online` CHANGE `duration` `duration` INT(11) NOT NULL DEFAULT '0';";
            $this->executeDB($sql);

            $version = '6.3.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.3.4') {
            $sql = "ALTER TABLE `pkg_trunk` CHANGE `directmedia` `directmedia` CHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'no';
            ALTER TABLE `pkg_sip` CHANGE `directmedia` `directmedia` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'no';";
            $this->executeDB($sql);

            $version = '6.3.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.3.5') {
            $sql = "INSERT INTO pkg_configuration VALUES (NULL, 'Show fields help', 'show_filed_help', '0', 'Show fields help', 'global', '1')";
            $this->executeDB($sql);

            $version = '6.3.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.3.6') {
            $sql = "ALTER TABLE `pkg_ivr` ADD `direct_extension` TINYINT(1) NULL DEFAULT '0' AFTER `option_10`;";
            $this->executeDB($sql);

            $version = '6.3.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.3.7') {

            $sql = "ALTER TABLE  `pkg_cdr` ADD INDEX (  `id_trunk` ) ;";
            $this->executeDB($sql);

            $sql = "ALTER TABLE  `pkg_cdr_failed` ADD INDEX (  `id_trunk` ) ;";
            $this->executeDB($sql);

            $sql = "ALTER TABLE  `pkg_cdr_failed` ADD INDEX (  `id_user` ) ;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_day (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    day varchar(10) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    nbcall_fail int(11) DEFAULT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float DEFAULT NULL,
                    asr float DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY day (day)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_day_user (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    day varchar(10) NOT NULL,
                    id_user int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    buycost float  NULL DEFAULT '0',
                    sessionbill float NULL DEFAULT '0',
                    lucro float NULL DEFAULT '0',
                    PRIMARY KEY (id),
                    KEY day (day),
                    KEY id_user (id_user)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_day_trunk (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    day varchar(10) NOT NULL,
                    id_trunk int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float NULL DEFAULT '0',
                    PRIMARY KEY (id),
                    KEY day (day),
                    KEY id_trunk (id_trunk)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_day_agent (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    day varchar(10) NOT NULL,
                    id_user int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float NULL DEFAULT '0',
                    PRIMARY KEY (id),
                    KEY day (day),
                    KEY id_user (id_user)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_month (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    month varchar(20) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float NULL DEFAULT '0',
                    PRIMARY KEY (id),
                    UNIQUE KEY month (month)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_month_user (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    month varchar(20) NOT NULL,
                    id_user int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float NULL DEFAULT '0',
                    PRIMARY KEY (id),
                    KEY month (month),
                    KEY id_user (id_user)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_month_trunk (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    month varchar(20) NOT NULL,
                    id_trunk int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float NULL DEFAULT '0',
                    PRIMARY KEY (id),
                    KEY month (month),
                    KEY id_trunk (id_trunk)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_user (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    id_user int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    nbcall_fail int(11) DEFAULT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float DEFAULT NULL,
                    asr float DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY id_user (id_user)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "CREATE TABLE IF NOT EXISTS pkg_cdr_summary_trunk (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    id_trunk int(11) NOT NULL,
                    sessiontime bigint(25) NOT NULL,
                    aloc_all_calls int(11) NOT NULL,
                    nbcall int(11) NOT NULL,
                    nbcall_fail int(11) DEFAULT NULL,
                    buycost float NOT NULL DEFAULT '0',
                    sessionbill float NOT NULL DEFAULT '0',
                    lucro float DEFAULT NULL,
                    asr float DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY id_trunk (id_trunk)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
            $this->executeDB($sql);

            $sql = "ALTER TABLE `pkg_module` ADD `priority` INT(11) NULL DEFAULT NULL AFTER `id_module`;";
            $this->executeDB($sql);

            $sql = "UPDATE `pkg_module` SET priority = id;";
            $this->executeDB($sql);

            $sql = "UPDATE `pkg_module` SET `text` = 't(\'Summary per Day\')', module = 'callsummaryperday', priority = 3 WHERE module = 'callsummary'";
            $this->executeDB($sql);

            $sql       = "SELECT id FROM pkg_module WHERE `icon_cls` LIKE 'report' AND module IS NULL AND id_module IS NULL";
            $result    = Yii::app()->db->createCommand($sql)->queryAll();
            $cdrModule = $result[0]['id'];

            //day user
            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Summary Day User'')', 'callsummarydayuser', 'callsummary',  $cdrModule, 4)";
            $this->executeDB($sql);
            $idModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idModule . "', 'r', '1', '1', '1');";
            $this->executeDB($sql);

            //day trunk
            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Summary Day Trunk'')', 'callsummarydaytrunk', 'callsummary',  $cdrModule,5)";
            $this->executeDB($sql);
            $idModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idModule . "', 'r', '1', '1', '1');";
            $this->executeDB($sql);

            //day agent
            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Summary Day Agent'')', 'callsummarydayagent', 'callsummary',  $cdrModule,6)";
            $this->executeDB($sql);
            $idModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idModule . "', 'r', '1', '1', '1');";
            $this->executeDB($sql);

            //month
            //update to callsummary to summary/month
            $sql = "UPDATE `pkg_module` SET `text` = 't(\'Summary per Month\')', priority = 7, module = 'callsummarypermonth' WHERE module = 'callsummarybymonth'";
            $this->executeDB($sql);

            //month user
            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Summary Month User'')', 'callsummarymonthuser', 'callsummarybymonth',  $cdrModule,8)";
            $this->executeDB($sql);
            $idModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idModule . "', 'r', '1', '1', '1');";
            $this->executeDB($sql);

            //month trunk
            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Summary Month Trunk'')', 'callsummarymonthtrunk', 'callsummarybymonth',  $cdrModule,9)";
            $this->executeDB($sql);
            $idModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idModule . "', 'r', '1', '1', '1');";
            $this->executeDB($sql);

            //per user
            $sql = "UPDATE `pkg_module` SET `text` = 't(\'Summary per User\')', priority = 11 WHERE module = 'callsummaryperuser'";
            $this->executeDB($sql);

            //per trunk
            $sql = "UPDATE `pkg_module` SET `text` = 't(\'Summary per Trunk\')', priority = 12 WHERE module = 'callsummarypertrunk'";
            $this->executeDB($sql);

            //per trunk
            $sql = "UPDATE `pkg_module` SET `text` = 't(\'Summary per Agent\')', priority = 13 WHERE module = 'callsummaryperagent'";
            $this->executeDB($sql);

            $sql = "UPDATE `pkg_module` SET `icon_cls` = 'campaignpollinfo' WHERE module = 'trunkreport' OR  module = 'sendcreditsummary' OR  module = 'callsummaryperuser' OR   module = 'callsummarypertrunk' OR  module = 'callsummaryperagent' ";
            $this->executeDB($sql);

            //delete old modules
            $sql = "DELETE FROM pkg_group_module WHERE id_module = (SELECT id FROM pkg_module WHERE module = 'trunkreport')";
            $this->executeDB($sql);
            $sql = "DELETE FROM pkg_module WHERE module = 'trunkreport'";
            $this->executeDB($sql);

            $sql = "DELETE FROM pkg_group_module WHERE id_module = (SELECT id FROM pkg_module WHERE module = 'callsummaryperagent')";
            $this->executeDB($sql);
            $sql = "DELETE FROM pkg_module WHERE module = 'callsummaryperagent'";
            $this->executeDB($sql);

            $sql = "UPDATE pkg_module SET priority = 1 WHERE module = 'call' AND icon_cls = 'cdr';
            UPDATE pkg_module SET priority = 2 WHERE module = 'callfailed' AND icon_cls = 'cdr'";
            $this->executeDB($sql);

            $sql = "DELETE FROM pkg_group_module WHERE id_module = (SELECT id FROM pkg_module WHERE module = 'callsummaryperday') AND id_group IN (SELECT id FROM pkg_group_user WHERE id_user_type != 1)";
            $this->executeDB($sql);

            $sql = "DELETE FROM pkg_group_module WHERE id_module = (SELECT id FROM pkg_module WHERE module = 'callsummarypermonth') AND id_group IN (SELECT id FROM pkg_group_user WHERE id_user_type != 1)";
            $this->executeDB($sql);

            $sql    = "SELECT id FROM pkg_group_user WHERE id_user_type = 3";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            foreach ($result as $key => $value) {
                $sql = "INSERT INTO pkg_group_module (id_group,id_module,action,show_menu) VALUES (" . $value['id'] . ",(SELECT id FROM pkg_module WHERE module = 'callsummaryperuser'),'r',1)";
                $this->executeDB($sql);

                $sql = "INSERT INTO pkg_group_module (id_group,id_module,action,show_menu) VALUES (" . $value['id'] . ",(SELECT id FROM pkg_module WHERE module = 'callsummarydayuser'),'r',1)";
                $this->executeDB($sql);

                $sql = "INSERT INTO pkg_group_module (id_group,id_module,action,show_menu) VALUES (" . $value['id'] . ",(SELECT id FROM pkg_module WHERE module = 'callsummarymonthuser'),'r',1)";
                $this->executeDB($sql);
            }

            $version = '6.3.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();

            exec("php /var/www/html/mbilling/cron.php SummaryTablesCdr");
            exec("echo '\n0 4 * * * php /var/www/html/mbilling/cron.php SummaryTablesCdr processCdrLast30Days\n0 8-22 * * * php /var/www/html/mbilling/cron.php SummaryTablesCdr processCdrToday\n' >> /var/spool/cron/root");
        }

        if ($version == '6.3.8') {
            $sql = "INSERT INTO pkg_configuration VALUES
				(NULL, 'Authentication IP/tech length', 'ip_tech_length', '6', 'Authentication IP/tech length 4, 5 or 6 digits', 'global', '1')";
            $this->executeDB($sql);

            $sql = "ALTER TABLE `pkg_user` ADD `techprefix` INT(6) NULL DEFAULT NULL AFTER `transfer_international`, ADD UNIQUE (`techprefix`);";
            $this->executeDB($sql);

            $sql = "UPDATE `pkg_user` SET techprefix = callingcard_pin;";
            $this->executeDB($sql);

            $version = '6.3.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.3.9') {
            $sql = "ALTER TABLE `pkg_user` DROP `techprefix`";
            $this->executeDB($sql);

            $sql = "ALTER TABLE `pkg_sip` ADD `techprefix` INT(6) NULL DEFAULT NULL , ADD UNIQUE (`techprefix`);";
            $this->executeDB($sql);

            $version = '6.4.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.0') {

            $sql = "ALTER TABLE `pkg_campaign_poll_info` ADD INDEX(`number`);";
            $this->executeDB($sql);

            $version = '6.4.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.1') {

            $sql = "ALTER TABLE  `pkg_cdr_summary_day_user` ADD  `isAgent` TINYINT( 1 ) NULL DEFAULT NULL ;
				ALTER TABLE  `pkg_cdr_summary_day_user` ADD  `agent_bill` FLOAT NOT NULL DEFAULT  '0';
				ALTER TABLE  `pkg_cdr_summary_month_user` ADD  `isAgent` TINYINT( 1 ) NULL DEFAULT NULL ;
				ALTER TABLE  `pkg_cdr_summary_month_user` ADD  `agent_bill` FLOAT NOT NULL DEFAULT  '0';
				ALTER TABLE  `pkg_cdr_summary_user` ADD  `isAgent` INT( 11 ) NULL DEFAULT NULL ;
				ALTER TABLE  `pkg_cdr_summary_user` ADD  `agent_bill` FLOAT NOT NULL DEFAULT  '0';
				";
            $this->executeDB($sql);

            $version = '6.4.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.2') {

            $sql = "UPDATE pkg_cdr_summary_day_user LEFT JOIN  pkg_user ON pkg_user.id = pkg_cdr_summary_day_user.id_user
					SET isAgent = 1 WHERE pkg_user.id_user > 1 AND isAgent IS NULL;
					UPDATE pkg_cdr_summary_day_user LEFT JOIN  pkg_user ON pkg_user.id = pkg_cdr_summary_day_user.id_user
					SET isAgent = 0 WHERE pkg_user.id_user < 2 AND isAgent IS NULL;
					UPDATE pkg_cdr_summary_month_user LEFT JOIN  pkg_user ON pkg_user.id = pkg_cdr_summary_month_user.id_user
					SET isAgent = 1 WHERE pkg_user.id_user > 1 AND isAgent IS NULL;
					UPDATE pkg_cdr_summary_month_user LEFT JOIN  pkg_user ON pkg_user.id = pkg_cdr_summary_month_user.id_user
					SET isAgent = 0 WHERE pkg_user.id_user < 2 AND isAgent IS NULL;
					UPDATE pkg_cdr_summary_user LEFT JOIN  pkg_user ON pkg_user.id = pkg_cdr_summary_user.id_user
					SET isAgent = 1 WHERE pkg_user.id_user > 1 AND isAgent IS NULL;
					UPDATE pkg_cdr_summary_user LEFT JOIN  pkg_user ON pkg_user.id = pkg_cdr_summary_user.id_user
					SET isAgent = 0 WHERE pkg_user.id_user < 2 AND isAgent IS NULL;
				";
            $this->executeDB($sql);

            $version = '6.4.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.3') {

            $sql = "ALTER TABLE `pkg_voucher` CHANGE `prefix_local` `prefix_local` VARCHAR(50) NULL DEFAULT NULL;";
            $this->executeDB($sql);

            $version = '6.4.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.4.4') {

            $sql = "ALTER TABLE `pkg_trunk` ADD `sendrpid` VARCHAR(10) NOT NULL DEFAULT 'no' AFTER `port`;";
            $this->executeDB($sql);

            $version = '6.4.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.4.5') {

            $sql = "ALTER TABLE  `pkg_firewall` ADD  `jail` VARCHAR( 100 ) NULL DEFAULT NULL ;";
            $this->executeDB($sql);

            $version = '6.4.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.4.6') {

            $sql = "ALTER TABLE `pkg_sip` ADD `alias` VARCHAR(10) NULL DEFAULT NULL AFTER `accountcode`;";
            $this->executeDB($sql);

            $version = '6.4.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.7') {

            $sql = "INSERT INTO pkg_configuration VALUES
				(NULL, 'External URL to download records', 'external_record_link', '', 'External URL to download records. Only used to download only one audio. Leave blank to no find audio in external link. URL EX: http://IP/record.php?username=%user%&audio=%number%.%uniqueid%.%audio_exten%', 'global', '1')";
            $this->executeDB($sql);

            $version = '6.4.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.8') {

            $sql = "ALTER TABLE `pkg_plan` ADD `tariff_limit` INT(11) NOT NULL DEFAULT '3' AFTER `play_audio`;";
            $this->executeDB($sql);

            $version = '6.4.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.4.9') {

            $sql    = "SELECT * FROM pkg_method_pay WHERE payment_method = 'sagepay'";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            if (!count($result)) {
                $sql = "INSERT INTO pkg_method_pay VALUES (NULL, '1', 'Sagepay', 'sagepay', 'Global', '0', '0', NULL, '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', '500', 'payment_method,show_name,id_user,country,active,min,max,P2P_KeyID,client_id');";
                $this->executeDB($sql);
            }
            $version = '6.5.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.0') {

            $sql = "ALTER TABLE `pkg_cdr_failed` ADD `callerid` VARCHAR(40) NULL DEFAULT NULL AFTER `src`;";
            $this->executeDB($sql);

            $version = '6.5.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.1') {

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''Call Archive'')', 'callarchive', 'prefixs', 9,15)";
            $this->executeDB($sql);
            $idServiceModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idServiceModule . "', 'crud', '1', '1', '1');
            		ALTER TABLE `pkg_cdr_archive` DROP `sessionid`;
            ";
            $this->executeDB($sql);

            exec("echo '\n40 5 * * * php /var/www/html/mbilling/cron.php callarchive' >> /var/spool/cron/root");

            $version = '6.5.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.2') {

            $sql = "ALTER TABLE `pkg_did` ADD `cbr_total_try` INT(11) NOT NULL DEFAULT '3' AFTER `cbr_em`, ADD `cbr_time_try` INT(11) NOT NULL DEFAULT '30' AFTER `cbr_total_try`;";
            $this->executeDB($sql);

            $sql = "ALTER TABLE `pkg_callback` ADD `sessiontime` INT(11) NOT NULL DEFAULT '0' ;";
            $this->executeDB($sql);

            $version = '6.5.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.5.3') {

            $sql = "ALTER TABLE  `pkg_sip` ADD  `trace` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
            $this->executeDB($sql);

            $version = '6.5.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.4') {

            $sql = "
			INSERT INTO pkg_configuration VALUES
				(NULL, 'Campaign call limit to users', 'campaign_user_limit', '1', 'Campaign call limit to users', 'global', '1')";
            $this->executeDB($sql);

            $version = '6.5.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.5') {

            $sql = " DELETE FROM pkg_configuration WHERE config_key = 'summary_per_agent_day';
            	DELETE FROM pkg_configuration WHERE config_key = 'summary_per_user_days';
            	DELETE FROM pkg_configuration WHERE config_key = 'cache';
            	DELETE FROM pkg_configuration WHERE config_key = 'play_audio';
            	DELETE FROM pkg_configuration WHERE config_key = 'purchase_amount';
            	UPDATE pkg_configuration SET status = 0 WHERE config_key LIKE 'intra-inter%';
            	UPDATE pkg_configuration SET status = 0 WHERE config_key LIKE 'log';
            	UPDATE pkg_configuration SET status = 0 WHERE config_key LIKE 'asterisk_version';

            	;";
            $this->executeDB($sql);

            $sql    = "SELECT * FROM pkg_configuration WHERE config_key = 'BDService_username'";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            if ($result[0]['config_value'] == '') {
                $sql = "UPDATE pkg_configuration SET status = 0 WHERE config_key LIKE 'BDService%';";
                $this->executeDB($sql);
            }

            $sql    = "SELECT * FROM pkg_configuration WHERE config_key = 'fm_transfer_to_username'";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            if ($result[0]['config_value'] == '') {
                $sql = "UPDATE pkg_configuration SET status = 0 WHERE config_key LIKE 'fm_transfer_%';";
                $this->executeDB($sql);
            }

            $sql = " ALTER TABLE  `pkg_servers` ADD  `sip_port` INT( 7 ) NOT NULL DEFAULT  '5060' AFTER  `port` ;";
            $this->executeDB($sql);

            $version = '6.5.6';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.6') {

            //2019-05-10
            $sql = "ALTER TABLE `pkg_services` ADD `return_credit` BOOLEAN NOT NULL DEFAULT TRUE ;";
            $this->executeDB($sql);

            $version = '6.5.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.7') {

            //2019-05-28
            $sql = "
			INSERT INTO pkg_configuration VALUES
				(NULL, 'Enable CallingCard', 'enable_callingcard', '1', 'Enable CallingCard', 'global', '1')";
            $this->executeDB($sql);

            $version = '6.5.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.8') {

            $sql = "INSERT INTO pkg_method_pay VALUES (NULL, '1', 'Stripe', 'Stripe', 'Global', '0', '0', NULL, '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', '500', 'payment_method,show_name,id_user,country,active,min,max,client_id,client_secret');";
            $this->executeDB($sql);

            $version = '6.5.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.5.9') {

            $sql = "INSERT INTO pkg_method_pay VALUES (NULL, '1', 'Elavon', 'Elavon', 'Global', '0', '0', NULL, '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', '500', 'payment_method,show_name,id_user,country,active,min,max,username,client_id,client_secret');";
            $this->executeDB($sql);

            $version = '6.6.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.6.0') {

            $sql = " ALTER TABLE `pkg_sip` CHANGE `group` `sip_group` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;";
            $this->executeDB($sql);

            $version = '6.6.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        //2019-07-18
        if ($version == '6.6.1') {

            $sql = "
			INSERT INTO pkg_configuration VALUES
				(NULL, 'Send email to admin when user signup from form', 'signup_admin_email', '1', 'Send email to administrator email when creation new account from signup page\n 0 - Disable \n1 - Enable', 'global', '1')";
            $this->executeDB($sql);

            $sql = "CREATE TABLE `pkg_cryptocurrency` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`id_user` int(11) NOT NULL,
				`status` TINYINT(1) NOT NULL DEFAULT '0',
				`currency` varchar(50) NOT NULL,
				`amountCrypto` decimal(15,8) NOT NULL DEFAULT '0.00000000',
				`amount` decimal(15,8) NOT NULL DEFAULT '0.00000000',
				`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `fk_pkg_user_pkg_cryptocurrency` (`id_user`),
				CONSTRAINT `fk_pkg_user_pkg_cryptocurrency` FOREIGN KEY (`id_user`) REFERENCES `pkg_user` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $this->executeDB($sql);

            $sql = "INSERT INTO `pkg_method_pay` (`id`, `id_user`, `payment_method`, `show_name`, `country`, `active`, `active_agent`, `obs`, `url`, `username`, `pagseguro_TOKEN`, `fee`, `boleto_convenio`, `boleto_banco`, `boleto_agencia`, `boleto_conta_corrente`, `boleto_inicio_nosso_numeroa`, `boleto_carteira`, `boleto_taxa`, `boleto_instrucoes`, `boleto_nome_emp`, `boleto_end_emp`, `boleto_cidade_emp`, `boleto_estado_emp`, `boleto_cpf_emp`, `P2P_CustomerSiteID`, `P2P_KeyID`, `P2P_Passphrase`, `P2P_RecipientKeyID`, `P2P_tax_amount`, `client_id`, `client_secret`, `SLAppToken`, `SLAccessToken`, `SLSecret`, `SLIdProduto`, `SLvalidationtoken`, `min`, `max`, `showFields`) VALUES
				(NULL, 1, 'cryptocurrency', 'BITCOIN', 'Global', 1, 0, NULL, '', '', '', 0, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', '', '', NULL, NULL, NULL, NULL, NULL, 10, 500, 'payment_method,show_name,id_user,country,active,min,max,username,client_id,client_secret');
			";
            $this->executeDB($sql);

            $version = '6.6.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        //2019-07-24
        if ($version == '6.6.2') {

            $sql = "
            	TRUNCATE pkg_call_chart;
            	ALTER TABLE `pkg_call_chart` ADD UNIQUE(`date`);";
            $this->executeDB($sql);

            $version = '6.6.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        if ($version == '6.6.3') {

            $sql = "ALTER TABLE pkg_servers ADD `name` VARCHAR(100) NOT NULL AFTER `id`;
            	UPDATE pkg_servers SET name = host;";
            $this->executeDB($sql);

            $version = '6.6.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.6.4') {

            $sql = "
				CREATE TABLE IF NOT EXISTS `pkg_cdr_summary_ids` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`day` date NOT NULL,
					cdr_id int(11) NOT NULL,
					cdr_falide_id int(11) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `day` (`day`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;";
            $this->executeDB($sql);
            $version = '6.6.5';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.6.5') {

            $sql = "UPDATE pkg_configuration SET `config_value` = '1' WHERE config_key = 'username_generate'";
            $this->executeDB($sql);

            $sql    = "SELECT config_value FROM pkg_configuration WHERE config_key = 'generate_length'";
            $result = Yii::app()->db->createCommand($sql)->queryAll();

            if ($result[0]['config_value'] == '0') {
                $sql = "UPDATE pkg_configuration SET `config_value` = '5' WHERE config_key = 'generate_length'";
                $this->executeDB($sql);
            }

            $version = '6.6.7';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        if ($version == '6.6.7') {

            $sql    = "SELECT id FROM pkg_group_user WHERE name = 'Agent' AND id_user_type = '2'";
            $result = Yii::app()->db->createCommand($sql)->queryAll();
            if (isset($result[0]['id']) && $result[0]['id'] == '2') {
                $sql = "UPDATE pkg_group_module SET `show_menu` = '0' WHERE id_group = '2' AND id_module = '2'";
                $this->executeDB($sql);
            }

            $version = '6.6.8';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        //2019-08-08
        if ($version == '6.6.8') {

            $sql = "
			INSERT INTO pkg_configuration VALUES
				(NULL, 'Show Signup button on login page', 'show_signup_button', '0', 'Show Signup button on login page\n 0 - Disable \n1 - Enable', 'global', '1')";
            $this->executeDB($sql);

            $sql = "
			INSERT INTO pkg_configuration VALUES
				(NULL, 'reCaptchaKey secretkey', 'reCaptchaSecret', '', 'Generate your secredt key in https://www.google.com/recaptcha/admin#list', 'global', '1')";
            $this->executeDB($sql);

            $version = '6.6.9';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        //2019-08-09
        if ($version == '6.6.9') {

            $sql = "INSERT INTO pkg_module VALUES (NULL, 't(''API'')', 'api', 'offercdr', 12, 98)";
            $this->executeDB($sql);
            $idServiceModule = Yii::app()->db->lastInsertID;

            $sql = "INSERT INTO pkg_group_module VALUES ((SELECT id FROM pkg_group_user WHERE id_user_type = 1 LIMIT 1), '" . $idServiceModule . "', 'crud', '1', '1', '1');";
            $this->executeDB($sql);

            $sql = "
			    CREATE TABLE IF NOT EXISTS `pkg_api` (
			    `id` int(11) NOT NULL AUTO_INCREMENT,
			    `id_user` int(11) NOT NULL,
			    `status` tinyint(11) NOT NULL DEFAULT '1',
			    `api_key` varchar(150) NOT NULL,
			    `api_secret` varchar(150) NOT NULL,
			    `api_restriction_ips` varchar(150) DEFAULT NULL,
			    `action` varchar(7) NOT NULL,
			    PRIMARY KEY (`id`)
			    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
            $this->executeDB($sql);
            $version = '6.7.0';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

        //2019-08-09
        if ($version == '6.7.0') {
            $sql = "
            ALTER TABLE `pkg_did` ADD `callerid` VARCHAR(50) NOT NULL DEFAULT '' AFTER `did`;
            ALTER TABLE `pkg_trunk` ADD `sip_config` TEXT NULL DEFAULT NULL ;
            ";
            $this->executeDB($sql);

            $version = '6.7.1';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        //2019-08-22
        if ($version == '6.7.1') {
            $sql = "ALTER TABLE `pkg_queue` ADD `max_wait_time` INT(11) NULL DEFAULT NULL ;
		        ALTER TABLE `pkg_queue` ADD `max_wait_time_action` VARCHAR(50) NULL DEFAULT NULL ;";
            $this->executeDB($sql);

            $version = '6.7.2';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }
        //2019-08-30
        if ($version == '6.7.2') {
            $sql = "ALTER TABLE `pkg_campaign` ADD `auto_reprocess` INT(11) NULL DEFAULT 0 ;";
            $this->executeDB($sql);

            $version = '6.7.3';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();

            exec("echo '\n*/2 * * * * php /var/www/html/mbilling/cron.php PhoneBooksReprocess\n' >> /var/spool/cron/root");
        }

        //2019-09-04
        if ($version == '6.7.3') {
            $sql = "ALTER TABLE  `pkg_cdr_summary_day_agent` ADD  `agent_bill` FLOAT NOT NULL DEFAULT  '0';
    				ALTER TABLE  `pkg_cdr_summary_day_agent` ADD  `agent_lucro` FLOAT NOT NULL DEFAULT  '0';";
            $this->executeDB($sql);

            $version = '6.7.4';
            $sql     = "UPDATE pkg_configuration SET config_value = '" . $version . "' WHERE config_key = 'version' ";
            Yii::app()->db->createCommand($sql)->execute();
        }

    }

    public function executeDB($sql)
    {
        try {
            Yii::app()->db->createCommand($sql)->execute();
        } catch (Exception $e) {

        }
    }

}
