<?php

class Reminder {

    /**
     * Contains all of available user logins with reminder tag
     *
     * @var array
     */
    protected $AllLogin = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $AltCfg = array();

    /**
     * Contains all of available user phones data
     *
     * @var array
     */
    protected $AllPhones = array();

    /**
     * Placeholder for UbillingSMS object
     *
     * @var object
     */
    protected $sms = '';

    /**
     * Placeholder for FundsFlow object
     *
     * @var object
     */
    protected $money = '';

    /**
     * Contains data for native templating messages
     *
     * @var array
     */
    protected $AllTemplates = array();

    const FLAGPREFIX = 'exports/REMINDER.';

    /**
     * it's a magic
     */
    public function __construct() {
        $this->loadAlter();
        $this->LoadAllTemplates();
        $this->LoadRemindLogin();
        $this->LoadPhones();
        $this->sms = new UbillingSMS();
        $this->money = new FundsFlow();
    }

    /**
     * load all logins whith cash >=0 and with set tagid to $alllogin
     * 
     * @return void
     */
    protected function LoadRemindLogin() {
        if (isset($this->AltCfg['REMINDER_TAGID'])) {
            $tagid = vf($this->AltCfg['REMINDER_TAGID'], 3);
            $query = "SELECT `login` FROM `tags` WHERE `tagid`='" . $tagid . "'";
            $tmp = simple_queryall($query);
            if (!empty($tmp)) {
                $this->AllLogin = $tmp;
            }
        }
    }

    /**
     * load all available phones + mobile
     * 
     * @return void
     */
    protected function LoadPhones() {
        $this->AllPhones = zb_UserGetAllPhoneData();
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->AltCfg = $ubillingConfig->getAlter();
    }

    /**
     * Load all users templates
     * 
     * @return void
     */
    protected function LoadAllTemplates() {
        $this->AllTemplates = zb_TemplateGetAllUserData();
    }

    /**
     * Make queue for sms send
     * 
     * @return void
     */
    public function RemindUser() {
        $LiveDays = $this->AltCfg['REMINDER_DAYS_THRESHOLD'];
        $LiveTime = $LiveDays * 24 * 60 * 60;
        $CacheTime = time() - $LiveTime;

        foreach ($this->AllLogin as $userLoginData) {
            $eachLogin = $userLoginData['login'];
            if ($this->money->getOnlineLeftCount($eachLogin, true) <= $LiveDays) {
                if (!file_exists(self::FLAGPREFIX . $eachLogin)) {
                    $number = $this->AllPhones[$eachLogin]['mobile'];
                    if (!empty($number)) {
                        $number = trim($number);
                        $number = str_replace($this->AltCfg['REMINDER_PREFIX'], '', $number);
                        $number = vf($number, 3);
                        $number = $this->AltCfg['REMINDER_PREFIX'] . $number;
                        $template = $this->AltCfg['REMINDER_TEMPLATE'];
                        if (!empty($template)) {
                            $message = zb_TemplateReplace($eachLogin, $template, $this->AllTemplates);
                            if (!empty($message)) {
                                $this->sms->sendSMS($number, $message, false);
                                file_put_contents(self::FLAGPREFIX . $eachLogin, '');
                            }
                        }
                    }
                }
            } else {
                if (file_exists(self::FLAGPREFIX . $eachLogin)) {
                    if (filemtime(self::FLAGPREFIX . $eachLogin) > $CacheTime) {
                        unlink(self::FLAGPREFIX . $eachLogin);
                    }
                }
            }
        }
    }

}