<?php

class StandardCallAgi
{

    public function processCall(&$MAGNUS, &$agi, &$CalcAgi)
    {
        //Play intro message
        if (strlen($MAGNUS->agiconfig['intro_prompt']) > 0) {
            $agi->stream_file($MAGNUS->agiconfig['intro_prompt'], '#');
        }

        // CALL AUTHENTICATE AND WE HAVE ENOUGH CREDIT TO GO AHEAD
        if (AuthenticateAgi::authenticateUser($agi, $MAGNUS) == 1) {
            for ($i = 0; $i < $MAGNUS->agiconfig['number_try']; $i++) {
                // CREATE A DIFFERENT UNIQUEID FOR EACH TRY
                if ($i > 0) {
                    $MAGNUS->uniqueid = $MAGNUS->uniqueid + 1000000000;
                }
                $MAGNUS->extension = $MAGNUS->dnid;

                if ($MAGNUS->agiconfig['use_dnid'] == 1 && strlen($MAGNUS->dnid) > 2 && $i == 0) {
                    $MAGNUS->destination = $MAGNUS->dnid;
                }

                if ($MAGNUS->checkNumber($agi, $CalcAgi, $i, true) == 1) {
                    // PERFORM THE CALL
                    $result_callperf = $CalcAgi->sendCall($agi, $MAGNUS->destination, $MAGNUS);

                    // INSERT CDR  & UPDATE SYSTEM
                    $CalcAgi->updateSystem($MAGNUS, $agi);

                    if (!$result_callperf) {
                        $MAGNUS->executePlayAudio("prepaid-dest-unreachable", $agi);
                        break;
                    }

                    if ($MAGNUS->agiconfig['say_balance_after_call'] == 1) {
                        $MAGNUS->sayBalance($agi, $MAGNUS->credit);
                    }
                } else {
                    $MAGNUS->hangup($agi, 1);
                    exit;
                }

                $MAGNUS->agiconfig['use_dnid'] = 0;
            } //END FOR
        }
        $MAGNUS->hangup($agi);
    }
}
