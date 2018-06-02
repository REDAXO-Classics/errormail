
<?php
/**
 * errormail Addon.
 *
 * @author Friends of REDAXO
 *
 */

if (!$this->hasConfig('errormail_intval')) {
        $this->setConfig('errormail_intval', 0);
    }

if (!rex::isBackend() && $this->getConfig('errormail_intval') != 0) {
    rex_extension::register('RESPONSE_SHUTDOWN', function(rex_extension_point $ep)
    {
        $logFile    = rex_path::coreData('system.log');
        $sendTime   = $this->getConfig('last_log_file_send_time', 0);
        $fileTime   = filemtime($logFile);
        $timediff   = '';
        $fatalerror = false;
        $logevent = false;
        $timediff   = time() - $sendTime;
        if ($timediff > 900 && filesize($logFile) > 0 && $file = new rex_log_file($logFile)) {
            //Start - generate mailbody
            $mailBody  = '<h2>REDAXO errormail</h2>';
            $mailBody .= '<br>To disable these messages go to system settings of <strong>'. rex::getServerName() .'</strong><br><hr>';    
            $mailBody .= '<style> .errorbg {background: #F6C4AF; } .eventbg {background: #E1E1E1; } td, th {padding: 5px;} table {width: 100%; border: 1px solid #ccc; } th {background: #b00; color: #fff;} td { border: 0; border-bottom: 1px solid #b00;} </style> ';
            $mailBody .= '<table>';
            $mailBody .= '    <thead>';
            $mailBody .= '        <tr>';
            $mailBody .= '            <th>' . rex_i18n::msg('syslog_timestamp') . '</th>';
            $mailBody .= '            <th>' . rex_i18n::msg('syslog_type') . '</th>';
            $mailBody .= '            <th>' . rex_i18n::msg('syslog_message') . '</th>';
            $mailBody .= '            <th>' . rex_i18n::msg('syslog_file') . '</th>';
            $mailBody .= '            <th>' . rex_i18n::msg('syslog_line') . '</th>';
            $mailBody .= '        </tr>';
            $mailBody .= '    </thead>';
            $mailBody .= '    <tbody>';
            foreach (new LimitIterator($file, 0, 30) as $entry) {
                /* @var rex_log_entry $entry */
                $data  = $entry->getData();
                $style = '';
                $logtypes = array('error', 'exception');


                foreach($logtypes as $type) {
                    if(stripos($data[0], $type) !== false) {
                        $logevent = true;
                        $style      = ' class="errorbg"';
                        break;
                    }
                }

                if ($data[0]=='logevent') {
                    $style      = ' class="eventbg"';
                    $logevent = true;
                }
                $mailBody .= '        <tr' . $style . '>';
                $mailBody .= '            <td>' . $entry->getTimestamp('%d.%m.%Y %H:%M:%S') . '</td>';
                $mailBody .= '            <td>' . $data[0] . '</td>';
                $mailBody .= '            <td>' . substr(rex_escape($data[1]), 0, 128) . '</td>';
                $mailBody .= '            <td>' . (isset($data[2]) ? $data[2] : '') . '</td>';
                $mailBody .= '            <td>' . (isset($data[3]) ? $data[3] : '') . '</td>';
                $mailBody .= '        </tr>';
            }
            // check if logevent occured then send mail
            if ($logevent == true) {
                $mailBody .= '    </tbody>';
                $mailBody .= '</table>';
                //End - generate mailbody
                //Start  send mail
                $mail          = new rex_mailer();
                $mail->Subject = rex::getServerName() . ' | system.log';
                $mail->Body    = $mailBody;
                $mail->AltBody = strip_tags($mailBody);
                $mail->setFrom(rex::getErrorEmail(), 'REDAXO Errormail');
                $mail->addAddress(rex::getErrorEmail());
                $this->setConfig('last_log_file_send_time', $fileTime);
                if ($mail->Send()) {
                    // mail has been sent
                }
            }
            // close logger, to free remaining file-handles to syslog
            rex_logger::close();
            //End  send mail
        }
    });
}


    if (rex_be_controller::getCurrentPagePart(1) == 'system') {
        rex_system_setting::register(new rex_system_setting_errormail_intval('errormail_intval'));

    }
