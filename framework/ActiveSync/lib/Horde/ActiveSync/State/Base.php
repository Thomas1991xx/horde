<?php
/**
 * Base class for managing everything related to state:
 *
 *     Persistence of state data
 *     Generating delta between server and PIM
 *     Caching PING related state (hearbeat interval, folder list etc...)
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
abstract class Horde_ActiveSync_State_Base
{

    /**
     * Filtertype constants
     */
    const FILTERTYPE_ALL = 0;
    const FILTERTYPE_1DAY = 1;
    const FILTERTYPE_3DAYS = 2;
    const FILTERTYPE_1WEEK = 3;
    const FILTERTYPE_2WEEKS = 4;
    const FILTERTYPE_1MONTH = 5;
    const FILTERTYPE_3MONTHS = 6;
    const FILTERTYPE_6MONTHS = 7;

    /**
     * Configuration parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Caches the current state(s) in memory
     *
     * @var array
     */
    protected $_stateCache;

    /**
     * The syncKey for the current request.
     *
     * @var string
     */
    protected $_syncKey;

    /**
     * The backend driver
     *
     * @param Horde_ActiveSync_Driver_Base
     */
    protected $_backend;

    /**
     * The collection array for the collection we are currently syncing.
     * Keys include:
     *   'class'      - The collection class Contacts, Calendar etc...
     *   'synckey'    - The current synckey
     *   'newsynckey' - The new synckey sent back to the PIM
     *   'id'         - Server folder id
     *   'filtertype' - Filter
     *   'conflict'   - Conflicts
     *   'truncation' - Truncation
     *
     *
     * @var array
     */
    protected $_collection;

    /**
     * Logger instance
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * The PIM device id. Needed for PING requests
     *
     * @var string
     */
    protected $_devId;

    /**
     * Const'r
     *
     * @param array $collection  A collection array
     * @param array $params  All configuration parameters, requirements.
     *
     * @return Horde_ActiveSync_State_Base
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Update the $oldKey syncState to $newKey.
     *
     * @param string $newKey
     *
     * @return void
     */
    public function setNewSyncKey($newKey)
    {
        $this->_syncKey = $newKey;
    }

    /**
     * Loads the initial state from storage for the specified syncKey and
     * intializes the stateMachine for use.
     *
     * @param string $key       The key for the syncState or pingState to load.
     *
     * @return array The state array
     */
    abstract public function loadState($syncKey);

    /**
     * Load/initialize the ping state for the specified device.
     *
     * @param string $devId
     */
    abstract public function initPingState($devId);

    /**
     * Load the ping state for the given device id
     *
     * @param string $devid  The device id.
     */
    abstract public function loadPingCollectionState($devid);

    /**
     * Get the list of known folders for the specified syncState
     *
     * @param string $syncKey  The syncState key
     *
     * @return array  An array of server folder ids
     */
    abstract public function getKnownFolders();

    /**
     * Save the current syncstate to storage
     *
     * @param string $syncKey
     */
    abstract public function save();

    /**
     * Update the state for a specific syncKey
     *
     * @param <type> $type
     * @param <type> $change
     * @param <type> $key
     */
    abstract public function updateState($type, $change);

    /**
     * Obtain the diff between PIM and server
     */
    abstract public function getChanges();

    /**
     * Determines if the server version of the message represented by $stat
     * conflicts with the PIM version of the message according to the current
     * state.
     *
     * @param array $stat   A message stat array
     * @param string $type  The type of change (change, delete, add)
     *
     * @return boolean
     */
    abstract public function isConflict($stat, $type);

    /**
     * Get the specified device's policy key.
     *
     * @param string $devId     The device id to get key for.
     *
     * @return integer  The policy key
     */
    abstract public function getPolicyKey($devId);

    /**
     * Set the policy key for the specified device id
     *
     * @param string $devId     The device id
     * @param integer $key      The policy key
     *
     * @return void
     */
    abstract public function setPolicyKey($devId, $key);

    /**
     * Set the backend driver
     * (should really only be called by a backend object when passing this
     * object to client code)
     *
     * @param Horde_ActiveSync_Driver_Base $backend  The backend driver
     *
     * @return void
     */
    public function setBackend(Horde_ActiveSync_Driver_Base $backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Initialize the state object
     *
     * @param array $collection  The collection array
     *
     * @return void
     */
    public function init($collection = array())
    {
        $this->_collection = $collection;
    }

    /**
     * Set the logger instance for this object.
     *
     * @param Horde_Log_Logger $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Gets the new sync key for a specified sync key. You must save the new
     * sync state under this sync key when done sync'ing by calling
     * setNewSyncKey(), then save().
     *
     * @param string $syncKey  The old syncKey
     *
     * @return string  The new synckey
     */
    static public function getNewSyncKey($syncKey)
    {
        if (empty($syncKey)) {
            return '{' . self::uuid() . '}' . '1';
        } else {
            if (preg_match('/^s{0,1}\{([a-fA-F0-9-]+)\}([0-9]+)$/', $syncKey, $matches)) {
                $n = $matches[2];
                $n++;

                return '{' . $matches[1] . '}' . $n;
            }

            // @TODO: should this thrown an exception instead of returning false?
            return false;
        }
    }

    /**
     * Generate a uid for the sync key
     *
     * @return unknown_type
     */
    static public function uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0x0fff ) | 0x4000,
                    mt_rand( 0, 0x3fff ) | 0x8000,
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ));
    }

   /**
    * Returns the timestamp of the earliest modification time to consider
    *
    * @param integer $restrict  The time period to restrict to
    *
    * @return integer
    */
    static protected function _getCutOffDate($restrict)
    {
        switch($restrict) {
        case self::FILTERTYPE_1DAY:
            $back = 60 * 60 * 24;
            break;
        case self::FILTERTYPE_3DAYS:
            $back = 60 * 60 * 24 * 3;
            break;
        case self::FILTERTYPE_1WEEK:
            $back = 60 * 60 * 24 * 7;
            break;
        case self::FILTERTYPE_2WEEKS:
            $back = 60 * 60 * 24 * 14;
            break;
        case self::FILTERTYPE_1MONTH:
            $back = 60 * 60 * 24 * 31;
            break;
        case self::FILTERTYPE_3MONTHS:
            $back = 60 * 60 * 24 * 31 * 3;
            break;
        case self::FILTERTYPE_6MONTHS:
            $back = 60 * 60 * 24 * 31 * 6;
            break;
        default:
            break;
        }

        if (isset($back))
        {
            $date = time() - $back;
            return $date;
        } else {
            return 0; // unlimited
        }
    }

}