<?php
/**
 * The IMP_Imap:: class provides common functions for interaction with
 * IMAP/POP3 servers via the Horde_Imap_Client:: library.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 *
 * @property boolean $imap  If true, this is an IMAP connection.
 * @property boolean $pop3  If true, this is a POP3 connection.
 */
class IMP_Imap implements Serializable
{
    /* Access constants. */
    const ACCESS_FOLDERS = 1;
    const ACCESS_SEARCH = 2;
    const ACCESS_FLAGS = 3;
    const ACCESS_UNSEEN = 4;
    const ACCESS_TRASH = 5;

    /* Access constants for mailboxes. */
    const ACCESS_READONLY = 100;
    const ACCESS_FILTERS = 101;
    const ACCESS_SORT = 102;

    /**
     * The Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client
     */
    public $ob = null;

    /**
     * Server configuration file.
     *
     * @var array
     */
    static protected $_config;

    /**
     * Access cache.
     *
     * @var array
     */
    protected $_access = array();

    /**
     * Have we logged into server yet?
     *
     * @var boolean
     */
    protected $_login = false;

    /**
     * Default namespace.
     *
     * @var array
     */
    protected $_nsdefault = null;

    /**
     * UIDVALIDITY check cache.
     *
     * @var array
     */
    protected $_uidvalid = array();

    /**
     */
    public function __get($key)
    {
        switch ($key) {
        case 'imap':
            return $this->ob && ($this->ob instanceof Horde_Imap_Client_Socket);

        case 'pop3':
            return $this->ob && ($this->ob instanceof Horde_Imap_Client_Socket_Pop3);
        }
    }

    /**
     * Create a new Horde_Imap_Client object.
     *
     * @param string $username  The username to authenticate with.
     * @param string $password  The password to authenticate with.
     * @param string $key       Create a new object using this server key.
     *
     * @return boolean  The object on success, false on error.
     */
    public function createImapObject($username, $password, $key)
    {
        global $prefs;

        if (!is_null($this->ob)) {
            return $this->ob;
        }

        if (($server = $this->loadServerConfig($key)) === false) {
            return false;
        }

        $protocol = isset($server['protocol'])
            ? strtolower($server['protocol'])
            : 'imap';

        $imap_config = array(
            'capability_ignore' => empty($server['capability_ignore']) ? array() : $server['capability_ignore'],
            'comparator' => empty($server['comparator']) ? false : $server['comparator'],
            'debug' => isset($server['debug']) ? $server['debug'] : null,
            'debug_literal' => !empty($server['debug_raw']),
            'encryptKey' => array(__CLASS__, 'getEncryptKey'),
            'hostspec' => isset($server['hostspec']) ? $server['hostspec'] : null,
            'id' => empty($server['id']) ? false : $server['id'],
            'lang' => empty($server['lang']) ? false : $server['lang'],
            'password' => $password,
            'port' => isset($server['port']) ? $server['port'] : null,
            'secure' => isset($server['secure']) ? $server['secure'] : false,
            'statuscache' => true,
            'timeout' => empty($server['timeout']) ? null : $server['timeout'],
            'username' => $username,
        );

        /* Initialize caching. */
        if (!empty($server['cache'])) {
            $imap_config['cache'] = $this->loadCacheConfig(is_array($server['cache']) ? $server['cache'] : array());
        }

        try {
            $ob = Horde_Imap_Client::factory(($protocol == 'imap') ? 'Socket' : 'Socket_Pop3', $imap_config);
        } catch (Horde_Imap_Client_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }

        $this->ob = $ob;

        if ($protocol == 'pop') {
            /* Check for UIDL support. */
            if (!$this->queryCapability('UIDL')) {
                Horde::logMessage('The POP3 server does not support the *REQUIRED* UIDL capability.', 'ERR');
                return false;
            }

            /* Turn some options off if we are working with POP3. */
            $prefs->setValue('save_sent_mail', false);
            $prefs->setLocked('save_sent_mail', true);
            $prefs->setLocked('sent_mail_folder', true);
            $prefs->setLocked('drafts_folder', true);
            $prefs->setLocked('trash_folder', true);
        }

        return $ob;
    }

    /**
     * Prepare the config parameters necessary to use IMAP caching.
     *
     * @param mixed $config  Either a list of cache config parameters, or a
     *                       string containing the name of the driver with
     *                       which to load the cache config from.
     *
     * @return array  The configuration array.
     */
    public function loadCacheConfig($config)
    {
        if (!($ob = $GLOBALS['injector']->getInstance('Horde_Cache'))) {
            return array();
        }

        if (is_string($config)) {
            if ((($server = $this->loadServerConfig($config)) === false) ||
                empty($server['cache'])) {
                return array();
            }
            $config = $server['cache'];
        }

        return array(
            'cacheob' => $ob,
            'lifetime' => empty($config['lifetime']) ? false : $config['lifetime'],
            'slicesize' => empty($config['slicesize']) ? false : $config['slicesize'],
        );
    }

    /**
     * Update the list of mailboxes to ignore when caching FETCH data in the
     * IMAP client object.
     */
    public function updateFetchIgnore()
    {
        if ($this->imap) {
            $special = IMP_Mailbox::getSpecialMailboxes();

            $this->ob->fetchCacheIgnore(array(
                strval($special[IMP_Mailbox::SPECIAL_SPAM]),
                strval($special[IMP_Mailbox::SPECIAL_TRASH])
            ));
        }
    }

    /**
     * Checks access rights for a server.
     *
     * @param integer $right  Access right.
     *
     * @return boolean  Does the mailbox have the access right?
     */
    public function access($right)
    {
        switch ($right) {
        case self::ACCESS_FOLDERS:
            return (!empty($GLOBALS['conf']['user']['allow_folders']) &&
                    !$this->pop3);

        case self::ACCESS_FLAGS:
        case self::ACCESS_SEARCH:
        case self::ACCESS_TRASH:
        case self::ACCESS_UNSEEN:
            return !$this->pop3;
        }

        return false;
    }

    /**
     * Checks access rights for a mailbox.
     *
     * @param IMP_Mailbox $mailbox  The mailbox to check.
     * @param integer $right        Access right.
     *
     * @return boolean  Does the mailbox have the access right?
     * @throws Horde_Exception
     */
    public function accessMailbox(IMP_Mailbox $mailbox, $right)
    {
        $mbox_key = strval($mailbox);
        $res = false;

        if (!$right) {
            return false;
        } elseif (isset($this->_access[$mbox_key][$right])) {
            return $this->_access[$mbox_key][$right];
        }

        switch ($right) {
        case self::ACCESS_FILTERS:
            $res = !$this->pop3 && !$mailbox->search;
            break;

        case self::ACCESS_READONLY:
            /* These tests work on both regular and search mailboxes. */
            try {
                $res = Horde::callHook('mbox_readonly', array($mailbox), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}

            /* This check can only be done for regular IMAP mailboxes
             * UIDNOTSTICKY not valid for POP3). */
            if (!$res && $this->imap && !$mailbox->search) {
                try {
                    $status = $this->ob->status($mbox_key, Horde_Imap_Client::STATUS_UIDNOTSTICKY);
                    $res = $status['uidnotsticky'];
                } catch (Horde_Imap_Client_Exception $e) {}
            }
            break;

        case self::ACCESS_SORT:
            /* Although possible to abstract other sorting methods, all other
             * non-sequence methods require a download of ALL messages, which
             * is too much overhead.*/
            $res = !$this->pop3;
            break;
        }

        $this->_access[$mbox_key][$right] = $res;

        return $res;
    }

    /**
     * Do a UIDVALIDITY check.
     *
     * @param IMP_Mailbox $mailbox  The mailbox to check.
     *
     * @return string  The mailbox UIDVALIDITY.
     * @throws IMP_Exception
     */
    public function checkUidvalidity(IMP_Mailbox $mailbox)
    {
        global $session;

        // POP3 does not support UIDVALIDITY.
        if ($this->pop3) {
            return;
        }

        $mbox_str = strval($mailbox);

        if (!isset($this->_uidvalid[$mbox_str])) {
            $status = $this->ob->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $val = $session->get('imp', 'uidvalid/' . $mailbox);
            $session->set('imp', 'uidvalid/' . $mailbox, $status['uidvalidity']);

            $this->_uidvalid[$mbox_str] = (!is_null($val) && ($status['uidvalidity'] != $val));
        }

        if ($this->_uidvalid[$mbox_str]) {
            throw new IMP_Exception(_("Mailbox structure on server has changed."));
        }

        return $session->get('imp', 'uidvalid/' . $mbox_str);
    }

    /**
     * Get the namespace list.
     *
     * @return array  See Horde_Imap_Client_Base#getNamespaces().
     */
    public function getNamespaceList()
    {
        try {
            return $this->ob->getNamespaces($GLOBALS['session']->get('imp', 'imap_namespace', Horde_Session::TYPE_ARRAY));
        } catch (Horde_Imap_Client_Exception $e) {
            return array();
        }
    }

    /**
     * Get namespace info for a full folder path.
     *
     * @param string $mailbox    The folder path.
     * @param boolean $personal  If true, will return empty namespace only
     *                           if it is a personal namespace.
     *
     * @return mixed  The namespace info for the folder path or null if the
     *                path doesn't exist.
     */
    public function getNamespace($mailbox = null, $personal = false)
    {
        if ($this->pop3) {
            return null;
        }

        $ns = $this->getNamespaceList();

        if ($mailbox === null) {
            reset($ns);
            $mailbox = key($ns);
        }

        foreach ($ns as $key => $val) {
            $mbox = $mailbox . $val['delimiter'];
            if (!empty($key) && (strpos($mbox, $key) === 0)) {
                return $val;
            }
        }

        return (isset($ns['']) && (!$personal || ($val['type'] == Horde_Imap_Client::NS_PERSONAL)))
            ? $ns['']
            : null;
    }

    /**
     * Get the default personal namespace.
     *
     * @return mixed  The default personal namespace info.
     */
    public function defaultNamespace()
    {
        if ($this->pop3) {
            return null;
        }

        if ($this->_login && !isset($this->_nsdefault)) {
            foreach ($this->getNamespaceList() as $val) {
                if ($val['type'] == Horde_Imap_Client::NS_PERSONAL) {
                    $this->_nsdefault = $val;
                    break;
                }
            }
        }

        return $this->_nsdefault;
    }

    /**
     * Make sure a user-entered mailbox contains namespace information.
     *
     * @param string $mbox  The user-entered mailbox string.
     *
     * @return string  The mailbox string with any necessary namespace info
     *                 added.
     */
    public function appendNamespace($mbox)
    {
        $ns_info = $this->getNamespace($mbox);
        if (is_null($ns_info)) {
            $ns_info = $this->defaultNamespace();
        }
        return $ns_info['name'] . $mbox;
    }

    /**
     * Return the Horde_Imap_Client_Utils object.
     *
     * @return Horde_Imap_Client_Utils  The utility object.
     */
    public function getUtils()
    {
        return $this->ob
            ? $this->ob->utils
            : $GLOBALS['injector']->createInstance('Horde_Imap_Client_Utils');
    }

    /**
     * All other calls to this class are routed to the underlying
     * Horde_Imap_Client_Base object.
     *
     * @param string $method  Method name.
     * @param array $params   Method Parameters.
     *
     * @return mixed  The return from the requested method.
     * @throws BadMethodCallException
     * @throws IMP_Imap_Exception
     */
    public function __call($method, $params)
    {
        if (!$this->ob || !method_exists($this->ob, $method)) {
            throw new BadMethodCallException(sprintf('%s: Invalid method call "%s".', __CLASS__, $method));
        }

        try {
            $result = call_user_func_array(array($this->ob, $method), $params);
        } catch (Horde_Imap_Client_Exception $e) {
            $error = new IMP_Imap_Exception($e);

            switch ($e->getCode()) {
            case Horde_Imap_Client_Exception::DISCONNECT:
                $error->notify(_("Unexpectedly disconnected from the mail server."));
                break;

            case Horde_Imap_Client_Exception::SERVER_READERROR:
                $error->notify(_("Error when communicating with the mail server."));
                break;

            case Horde_Imap_Client_Exception::MAILBOX_NOOPEN:
                if (strcasecmp($method, 'openMailbox') === 0) {
                    $error->notify(sprintf(_("Could not open mailbox \"%s\"."), IMP_Mailbox::get(reset($params)))->label);
                } else {
                    $error->notify(_("Could not open mailbox."));
                }
                break;

            case Horde_Imap_Client_Exception::CATENATE_TOOBIG:
                $error->notify(_("Could not save message data because it is too large."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::NOPERM'):
                $error->notify(_("You did not have adequate permissions to carry out this operation."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::INUSE'):
                $error->notify(_("There was a temporary issue when attempting this operation. Please try again later."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::CORRUPTION'):
                $error->notify(_("The mail server is reporting corrupt data in your mailbox. Details have been logged for the administrator."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::LIMIT'):
                $error->notify(_("The mail server has denied the request. Details have been logged for the administrator."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::QUOTA'):
                $error->notify(_("The operation failed because you have exceeded your quota on the mail server."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::ALREADYEXISTS'):
                $error->notify(_("The object could not be created because it already exists."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::NONEXISTENT'):
                $error->notify(_("The object could not be deleted because it does not exist."));
                break;
            }

            $error->log();

            throw $error;
        }

        /* Special handling for various methods. */
        switch ($method) {
        case 'createMailbox':
        case 'renameMailbox':
            // Mailbox is first parameter.
            unset($this->_uidvalid[$params[0]]);
            $GLOBALS['session']->remove('imp', 'uidvalid/' . $params[0]);
            break;

        case 'login':
            if (!$this->_login) {
                $this->_login = true;
                $this->updateFetchIgnore();
            }
            break;
        }

        return $result;
    }

    /* Static methods. */

    /**
     * Loads the IMP server configuration from backends.php.
     *
     * @param string $server  Returns this labeled entry only.
     *
     * @return mixed  If $server is set return this entry; else, return the
     *                entire servers array. Returns false on error.
     */
    static public function loadServerConfig($server = null)
    {
        if (isset(self::$_config)) {
            $servers = self::$_config;
        } else {
            try {
                $servers = Horde::loadConfiguration('backends.php', 'servers', 'imp');
                if (is_null($servers)) {
                    return false;
                }
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return false;
            }

            foreach (array_keys($servers) as $key) {
                if (!empty($servers[$key]['disabled'])) {
                    unset($servers[$key]);
                }
            }
            self::$_config = $servers;
        }

        if (is_null($server)) {
            return $servers;
        }

        /* Check for the existence of the server in the config file. */
        if (empty($servers[$server]) || !is_array($servers[$server])) {
            $entry = sprintf('Invalid server key "%s" from client [%s]', $server, $_SERVER['REMOTE_ADDR']);
            Horde::logMessage($entry, 'ERR');
            return false;
        }

        return $servers[$server];
    }

    /* Callback functions used in Horde_Imap_Client_Base. */

    static public function getEncryptKey()
    {
        return $GLOBALS['injector']->getInstance('Horde_Secret')->getKey('imp');
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            $this->ob,
            $this->_nsdefault,
            $this->_login
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->ob,
            $this->_nsdefault,
            $this->_login
        ) = unserialize($data);
    }

}
